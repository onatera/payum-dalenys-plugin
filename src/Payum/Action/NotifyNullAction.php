<?php
namespace Onatera\PayumDalenysPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;

class NotifyNullAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private const MIN_DELAY_NOTIFICATION = 60; //seconds

    /**
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (empty($httpRequest->query['EXTRADATA'])) {
            throw new HttpResponse('The notification is invalid. Code 201', 400);
        }

        $extraDataJson = $httpRequest->query['EXTRADATA'];
        if (false == $extraData = json_decode($extraDataJson, true)) {
            throw new HttpResponse('The notification is invalid. Code 202', 400);
        }

        if (empty($extraData['notify_token'])) {
            throw new HttpResponse('The notification is invalid. Code 203', 400);
        }

        if (empty($extraData['capture_timestamp'])) {
            throw new HttpResponse('The notification is invalid. Code 204', 400);
        }

        if (time() - $extraData['capture_timestamp'] < self::MIN_DELAY_NOTIFICATION) {
            throw new HttpResponse('The notification is invalid. Code 205', 400);
        }

        $this->gateway->execute($getToken = new GetToken($extraData['notify_token']));
        $this->gateway->execute(new Notify($getToken->getToken()));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            null === $request->getModel()
        ;
    }
}
