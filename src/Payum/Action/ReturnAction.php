<?php

namespace Onatera\PayumDalenysPlugin\Payum\Action;

use Onatera\PayumDalenysPlugin\Payum\Api;
use Onatera\PayumDalenysPlugin\Payum\Request\ReturnRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\Payment;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\HttpFoundation\Session\Session;

class ReturnAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

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

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (false == $this->api->verifyHash($httpRequest->query)) {
            throw new HttpResponse('The notification is invalid. Code 1', 400);
        }

        if ($details['AMOUNT'] != $httpRequest->query['AMOUNT']) {
            throw new HttpResponse('The notification is invalid. Code 2', 400);
        }

        $details->replace($httpRequest->query);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ReturnRequest &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
