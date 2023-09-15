<?php

namespace Onatera\PayumDalenysPlugin\Payum\Action;

use Onatera\PayumDalenysPlugin\Payum\Api;
use Onatera\PayumDalenysPlugin\Payum\Request\ReturnRequest;
use Onatera\PayumDalenysPlugin\Payum\RequestStackAwareInterface;
use Onatera\PayumDalenysPlugin\Payum\RequestStackAwareTrait;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\SensitiveValue;
use Payum\Core\Security\TokenFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, RequestStackAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;
    use RequestStackAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());
        $model['3DSECUREPREFERENCE'] = 'frictionless';

        if (null !== $model['EXECCODE']) {
            if (Api::EXECCODE_3DSECURE_IDENTIFICATION_REQUIRED === $model['EXECCODE']) {
                $this->gateway->execute(new ReturnRequest($request->getModel()));
            }
            return;
        }

        if (false == $model['CLIENTUSERAGENT']) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $model['CLIENTUSERAGENT'] = $httpRequest->userAgent;
        }
        if (false == $model['CLIENTIP']) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $model['CLIENTIP'] = $httpRequest->clientIp;
        }

        $extradata = [];
        if ($model['EXTRADATA']) {
            $extradata = json_decode($model['EXTRADATA'], true);
        }
        if (empty($extradata)) {
            $extradata = [];
        }
        if (!array_key_exists('notify_token', $extradata)) {
            $token = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            )->getHash();

            $extradata['notify_token'] = $token;
        }
        if (!array_key_exists('capture_timestamp', $extradata)) {
            $extradata['capture_timestamp'] = time();
        }
        $model['EXTRADATA'] = json_encode($extradata);

        $cardFields = array('CARDCODE', 'CARDCVV', 'CARDVALIDITYDATE', 'CARDFULLNAME');
        if (empty($model['HFTOKEN']) && false == $model->validateNotEmpty($cardFields, false) && false == $model['ALIAS']) {
            try {
                $obtainCreditCard = new ObtainCreditCard($request->getToken());
                $obtainCreditCard->setModel($request->getFirstModel());
                $obtainCreditCard->setModel($request->getModel());
                $this->gateway->execute($obtainCreditCard);
                $card = $obtainCreditCard->obtain();

                if ($card->getToken()) {
                    $model['ALIAS'] = $card->getToken();
                } else {
                    $model['CARDVALIDITYDATE'] = SensitiveValue::ensureSensitive($card->getExpireAt()->format('m-y'));
                    $model['CARDCODE'] = SensitiveValue::ensureSensitive($card->getNumber());
                    $model['CARDFULLNAME'] = SensitiveValue::ensureSensitive($card->getHolder());
                    $model['CARDCVV'] = SensitiveValue::ensureSensitive($card->getSecurityCode());
                }
            } catch (RequestNotSupportedException $e) {
                throw new LogicException('Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.');
            }
        }

        //instruction must have an alias set (e.g oneclick payment) or credit card info.
        if (empty($model['HFTOKEN'])) {
            if (false == ($model['ALIAS'] || $model->validateNotEmpty($cardFields, false))) {
                throw new LogicException('Either credit card fields or its alias has to be set.');
            }
        }

        $result = $this->api->payment($model->toUnsafeArray());

        $model->replace((array) $result);

        if (Api::EXECCODE_3DSECURE_IDENTIFICATION_REQUIRED === $model['EXECCODE']) {
            $this->requestStack->getSession()->set('payum_token', $request->getToken()->getHash());

            throw new HttpResponse(base64_decode($model['REDIRECTHTML']), 302);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
