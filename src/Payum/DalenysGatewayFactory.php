<?php

namespace Onatera\PayumDalenysPlugin\Payum;

use Onatera\PayumDalenysPlugin\Payum\Action\CaptureAction;
use Onatera\PayumDalenysPlugin\Payum\Action\ConvertPaymentAction;
use Onatera\PayumDalenysPlugin\Payum\Action\NotifyAction;
use Onatera\PayumDalenysPlugin\Payum\Action\NotifyNullAction;
use Onatera\PayumDalenysPlugin\Payum\Action\RefundAction;
use Onatera\PayumDalenysPlugin\Payum\Action\ReturnAction;
use Onatera\PayumDalenysPlugin\Payum\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class DalenysGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults(array(
            'payum.factory_name' => 'dalenys',
            'payum.factory_title' => 'Dalenys',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.return' => new ReturnAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.notify_null' => new NotifyNullAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ));

        $config['payum.default_options'] = array(
            'sandbox' => true,
        );
        $config->defaults($config['payum.default_options']);

        $config['payum.api'] = function (ArrayObject $config) {
            $config->validateNotEmpty($config['payum.required_options']);

            return new Api(
                array(
                    'identifier' => $config['identifier'],
                    'password' => $config['password'],
                    'sandbox' => $config['sandbox'],
                ),
                $config['payum.http_client'],
                $config['httplug.message_factory']
            );
        };
    }
}
