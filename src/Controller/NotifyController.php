<?php

namespace Onatera\PayumDalenysPlugin\Controller;

use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotifyController extends PayumController
{
    public function __invoke(Request $request)
    {
        $orderId = $request->request->get('ORDERID');

        /** @var Order $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->findOneBy(['number' => $orderId]);
        if (null === $order) {
            throw new NotFoundHttpException('');
        }

        $gateway = $this->getPayum()->getGateway('dalenys');
        $gateway->execute(new Notify($order->getLastPayment()->getDetails()));

        return new Response('', 204);
    }
}
