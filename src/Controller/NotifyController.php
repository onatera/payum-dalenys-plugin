<?php

namespace Onatera\PayumDalenysPlugin\Controller;

use Onatera\PayumDalenysPlugin\Payum\Api;
use Sylius\Component\Core\Model\Payment;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotifyController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __invoke(Request $request)
    {
        $orderId = $request->query->get('ORDERID');

        /** @var Order $order */
        $order = $this->container->get('sylius.repository.order')->findOneBy(['number' => $orderId]);
        if (null === $order) {
            return new Response(sprintf('Order not %s found.', $orderId), 400);
        }

        if ($request->query->get('EXECCODE') === Api::EXECCODE_SUCCESSFUL && ($order->getPaymentState() === Payment::STATE_CANCELLED || $order->getState() === Order::STATE_CANCELLED)) {
            $logger = $this->container->get('logger');
            $logger->critical('[Dalenys] Order paided and cancelled.', [
                'orderId' => $orderId,
                'transactionId' => $request->query->get('TRANSACTIONID'),
            ]);

            return new Response(sprintf('Order %s paided and cancelled.', $orderId), 400);
        }

        $payments = $this->container->get('sylius.repository.payment')->findBy(['order' => $order], ['createdAt' => 'DESC']);
        if (count($payments) === 0) {
            return new Response(sprintf('Payment for order %s not found.', $orderId), 400);
        }

        return new Response('OK', 200);
    }
}
