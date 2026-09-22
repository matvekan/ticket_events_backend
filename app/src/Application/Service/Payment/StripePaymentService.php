<?php

declare(strict_types=1);

namespace App\Application\Service\Payment;

use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct(
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        string $stripeSecretKey,
        #[Autowire(env: 'FRONTEND_URL')]
        private string $frontendUrl,
    ) {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    public function createCheckoutSession(string $orderId, string $paymentId, int $amount): Session
    {
        $baseUrl = rtrim($this->frontendUrl, '/');

        return $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'byn',
                    'product_data' => [
                        'name' => 'Event Tickets (Order #'.substr($orderId, 0, 8).')',
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'success_url' => \sprintf('%s/orders/%s?payment=success', $baseUrl, $orderId),
            'cancel_url' => \sprintf('%s/orders/%s?payment=failed', $baseUrl, $orderId),
            'client_reference_id' => $paymentId,
        ]);
    }
}
