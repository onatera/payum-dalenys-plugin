<?php

namespace Onatera\PayumDalenysPlugin\Payum\Action;

use Onatera\PayumDalenysPlugin\Payum\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {

        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());

        if (null === $model['EXECCODE']) {
            $request->markNew();

            return;
        }

        if (Api::EXECCODE_SUCCESSFUL === $model['EXECCODE']) {
            $request->markCaptured();

            return;
        }

        if (Api::EXECCODE_TIME_OUT  === $model['EXECCODE']) {
            $request->markUnknown();

            return;
        }

        $request->markFailed();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
