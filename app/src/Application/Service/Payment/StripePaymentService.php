<?php

declare(strict_types=1);

namespace App\Application\Service\Payment;

use Stripe\Checkout\Session;
use Stripe\StripeClient;

final readonly class StripePaymentService
{
    public function __construct(
        private StripeClient $stripe,

        private string $frontendUrl,
    ) {
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
