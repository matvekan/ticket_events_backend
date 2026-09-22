<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Payment;

use App\Application\Service\Payment\StripeWebhookProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/webhooks/stripe', name: 'stripe_webhook', methods: ['POST'])]
final class StripeWebhookController
{
    public function __construct(
        private readonly StripeWebhookProcessor $processor,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->processor->process(
            $request->getContent(),
            (string) $request->headers->get('stripe-signature', '')
        );

        return new Response('Webhook handled', Response::HTTP_OK);
    }
}
