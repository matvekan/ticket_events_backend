<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Payment;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/webhooks/stripe', name: 'stripe_webhook', methods: ['POST'])]
final class StripeWebhookController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly PaymentRepositoryInterface $payments,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        $endpointSecret = $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if ($endpointSecret === '') {
            $this->logger->error('Stripe webhook secret is missing in .env!');

            return new Response('Config error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe: Invalid payload');

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe: Invalid signature. Check your STRIPE_WEBHOOK_SECRET.');

            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $paymentIdStr = $session->client_reference_id;

            if ($paymentIdStr) {
                try {
                    $payment = $this->payments->findById(new PaymentId($paymentIdStr));

                    if ($payment) {
                        $this->commandBus->dispatch(new ConfirmPaymentCommand(
                            orderId: $payment->orderId()->toString()
                        ));
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Error while handle webhook: ' . $e->getMessage());
                }
            }
        }

        return new Response('Webhook handled', Response::HTTP_OK);
    }
}
