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
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\OpenUriAction;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Section\Field\Image;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Chatter;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsTransport;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\ActionCard;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\HttpPostAction;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\Input\TextInput;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Action\Input\DateInput;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsOptions;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Section\Section;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\Section\Field\Fact;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class NotifyController implements ContainerAwareInterface
{
    private const CHATTER = 'dalenys_microsoft_teams_chatter';
    use ContainerAwareTrait;

    public function __construct(private ChatterInterface $chatter, private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function __invoke(Request $request)
    {
        $orderNumber = $request->query->get('ORDERID');

        /** @var Order $order */
        $order = $this->container->get('sylius.repository.order')->findOneBy(['number' => $orderNumber]);
        if (null === $order) {
            return new Response(sprintf('Order not %s found.', $orderNumber), 400);
        }

        if ($request->query->get('EXECCODE') === Api::EXECCODE_SUCCESSFUL
            && $order->getPaymentState() === Payment::STATE_CANCELLED
            && $order->getState() === Order::STATE_CANCELLED
        ) {
            $logger = $this->container->get('logger');
            $logger->error('[Dalenys] Order paid and canceled.', [
                'orderId' => $orderNumber,
                'transactionId' => $request->query->get('TRANSACTIONID'),
            ]);

            $this->sendAlertChatMessage($order->getId(), $orderNumber, $request->query->get('TRANSACTIONID'));

            return new Response(sprintf('Order %s paid and canceled.', $orderNumber), 200);
        }

        $payments = $this->container->get('sylius.repository.payment')->findBy(['order' => $order],
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
