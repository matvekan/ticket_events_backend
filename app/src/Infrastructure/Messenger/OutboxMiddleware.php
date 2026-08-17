<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Domain\Entity\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Defers outgoing messages to the transactional outbox instead of publishing
 * them to the transport immediately. Rows are persisted within the same DB
 * transaction as the domain changes and published after commit.
 */
final class OutboxMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($envelope->all(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }
        $message = $envelope->getMessage();

        $this->entityManager->persist(new OutboxMessage(
            get_class($message),
            base64_encode(serialize($message)),
        ));

        if (!$this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->flush();
        }

        return $envelope;
    }
}