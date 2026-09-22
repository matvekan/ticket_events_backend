<?php

declare(strict_types=1);

namespace App\Application\Service\Payment;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class StripeWebhookProcessor
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PaymentRepositoryInterface $payments,
        private LoggerInterface $logger,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private string $webhookSecret,
    ) {
    }

    public function process(string $payload, string $signature): void
    {
        if ($this->webhookSecret === '') {
            $this->logger->error('Stripe webhook secret is missing in .env!');
            throw new BadRequestHttpException('Configuration error.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe: Invalid payload');
            throw new BadRequestHttpException('Invalid payload.');
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe: Invalid signature. Check your STRIPE_WEBHOOK_SECRET.');
            throw new BadRequestHttpException('Invalid signature.');
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            /** @phpstan-ignore property.notFound */
            $paymentIdStr = $session->client_reference_id;

            if ($paymentIdStr) {
                try {
                    $payment = $this->payments->findById(new PaymentId($paymentIdStr));

                    if ($payment !== null) {
                        $this->commandBus->dispatch(new ConfirmPaymentCommand(
                            orderId: $payment->orderId()->toString()
                        ));
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Error while handling webhook: '.$e->getMessage());
                }
            }
        }
    }
}
