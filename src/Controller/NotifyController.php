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
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Chatter;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsTransport;
use Symfony\Component\Notifier\Message\ChatMessage;



class NotifyController implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    private ChatterInterface $chatter;

    public function __construct(ChatterInterface $chatter)
    {
        $this->chatter = $chatter;
    }

    public function __invoke(Request $request)
    {
        $orderId = $request->query->get('ORDERID');
        $chatMessage = (new ChatMessage('test'))->transport('microsoftteams');
        $this->chatter->send($chatMessage);

        /** @var Order $order */
        $order = $this->container->get('sylius.repository.order')->findOneBy(['number' => $orderId]);
        if (null === $order) {
            return new Response(sprintf('Order not %s found.', $orderId), 400);
        }

        if ($request->query->get('EXECCODE') === Api::EXECCODE_SUCCESSFUL && $order->getPaymentState() === Payment::STATE_CANCELLED && $order->getState() === Order::STATE_CANCELLED) {
            $logger = $this->container->get('logger');
            $logger->error('[Dalenys] Order paided and cancelled.', [
                'orderId' => $orderId,
                'transactionId' => $request->query->get('TRANSACTIONID'),
            ]);

            $chatMessage = (new ChatMessage(sprintf('Order %s paided and cancelled.', $orderId)))->transport('microsoftteams');
            $this->chatter->send($chatMessage);

            return new Response(sprintf('Order %s paided and cancelled.', $orderId), 400);
        }

        $payments = $this->container->get('sylius.repository.payment')->findBy(['order' => $order], ['createdAt' => 'DESC']);
        if (count($payments) === 0) {
            $chatMessage = (new ChatMessage(sprintf('Payment for order %s not found.', $orderId)))->transport('microsoftteams');
            $this->chatter->send($chatMessage);

            return new Response(sprintf('Payment for order %s not found.', $orderId), 400);
        }

        return new Response('OK', 200);
    }
}
