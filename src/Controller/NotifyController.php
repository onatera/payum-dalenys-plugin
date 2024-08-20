<?php

namespace Onatera\PayumDalenysPlugin\Controller;

use Onatera\PayumDalenysPlugin\Payum\Api;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\OpenUriAction;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\ActionCard;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsOptions;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Section\Section;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Section\Field\Fact;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class NotifyController
{
    private const CHATTER = 'dalenys_microsoft_teams_chatter';

    public function __construct(
        private ChatterInterface $chatter,
        private UrlGeneratorInterface $urlGenerator,
        private OrderRepositoryInterface $orderRepository,
        private LoggerInterface $logger,
        private PaymentRepositoryInterface $paymentRepository,
    )
    {
    }

    public function __invoke(Request $request)
    {
        $orderNumber = $request->query->get('ORDERID');

        /** @var Order $order */
        $order = $this->orderRepository->findOneBy(['number' => $orderNumber]);
        if (null === $order) {
            return new Response(sprintf('Order not %s found.', $orderNumber), 400);
        }

        if ($request->query->get('EXECCODE') === Api::EXECCODE_SUCCESSFUL
            && $request->query->get('OPERATIONTYPE') === Api::OPERATION_PAYMENT
            && $order->getPaymentState() === Payment::STATE_CANCELLED
            && $order->getState() === Order::STATE_CANCELLED
        ) {

            $this->logger->error('[Dalenys] Order paid and canceled.', [
                'orderId' => $orderNumber,
                'transactionId' => $request->query->get('TRANSACTIONID'),
            ]);

            $this->sendAlertChatMessage($order->getId(), $orderNumber, $request->query->get('TRANSACTIONID'));

            return new Response('OK', 200);
        }

        $payments = $this->paymentRepository->findBy(['order' => $order],
            ['createdAt' => 'DESC']);
        if (count($payments) === 0) {
            return new Response(sprintf('Payment for order %s not found.', $orderNumber), 400);
        }

        return new Response('OK', 200);
    }

    private function sendAlertChatMessage(int $orderId, string $orderNumber, string $transactionId): void
    {
        $chatMessage = new ChatMessage('');
        $chatMessage->transport(self::CHATTER);

        $backendUrl = new OpenUriAction();
        $backendUrl->name('Show order in Sylius');
        $backendUrl->target($this->urlGenerator->generate('sylius_admin_order_show', ['id' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL));

        $microsoftTeamsOptions = (new MicrosoftTeamsOptions())
            ->title('⚠️ [Dalenys] Order paid and canceled.')
            ->text(' ')
            ->section(
                (new Section())
                    ->fact(
                        (new Fact())
                            ->name('Order ID')
                            ->value($orderId)
                    )
                    ->fact(
                        (new Fact())
                            ->name('Order Number')
                            ->value($orderNumber)
                    )
                    ->fact(
                        (new Fact())
                            ->name('Transaction ID')
                            ->value($transactionId)
                    )
            )
            ->action(
                (new ActionCard())
                    ->name('Show order in Sylius')
                    ->action($backendUrl)
            );

        $chatMessage->options($microsoftTeamsOptions);
        $this->chatter->send($chatMessage);
    }
}
