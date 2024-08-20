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
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Refund;
use Payum\Core\Security\SensitiveValue;
use Symfony\Component\HttpFoundation\Session\Session;

class RefundAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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

        $details = new ArrayObject($request->getModel());

        $result = $this->api->refund((array) $details);
        
        $details->replace((array) $result);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
