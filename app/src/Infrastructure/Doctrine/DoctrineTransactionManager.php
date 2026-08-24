<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $fn): mixed
    {
        return $this->entityManager->wrapInTransaction(function () use ($fn): mixed {
            $result = $fn();

            // Automatically persist domain events to Outbox within same DB transaction
            // Handlers return array of domain events from $order->releaseEvents() etc.
            if (is_array($result) && $result !== [] && is_object(reset($result))) {
                foreach ($result as $event) {
                    $outbox = new OutboxMessage(
                        $event::class,
                        base64_encode(serialize($event)),
                    );
                    $this->entityManager->persist($outbox);
                }
            }

            $this->entityManager->flush();

            return $result;
        });
    }
}
