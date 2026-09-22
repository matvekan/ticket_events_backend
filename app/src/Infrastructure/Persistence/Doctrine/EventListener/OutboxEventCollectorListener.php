<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\EventListener;

use App\Domain\Entity\OutboxMessage;
use App\Domain\Shared\AggregateRootInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final readonly class OutboxEventCollectorListener
{
    public function __construct(
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $entities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityDeletions()
        );

        foreach ($entities as $entity) {
            if (!$entity instanceof AggregateRootInterface) {
                continue;
            }

            foreach ($entity->releaseEvents() as $domainEvent) {
                $outboxMessage = new OutboxMessage(
                    $domainEvent::class,
                    base64_encode(serialize($domainEvent)),
                    $this->clock,
                    $this->ids,
                );

                $entityManager->persist($outboxMessage);

                $unitOfWork->computeChangeSet(
                    $entityManager->getClassMetadata(OutboxMessage::class),
                    $outboxMessage
                );
            }
        }
    }
}
