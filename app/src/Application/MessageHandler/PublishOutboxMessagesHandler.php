<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\PublishOutboxMessages;
use App\Domain\Entity\OutboxMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

#[AsMessageHandler]
final class PublishOutboxMessagesHandler
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransportInterface $eventsTransport,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PublishOutboxMessages $message): void
    {
        $rows = $this->findPending(self::BATCH_SIZE);

        foreach ($rows as $row) {
            try {
                $this->eventsTransport->send(new Envelope(
                    unserialize(base64_decode($row->body()), ['allowed_classes' => true]),
                    [new BusNameStamp('event.bus')],
                ));

                $row->markSent();
            } catch (\Throwable $exception) {
                $row->markFailed();
                $this->logger->error('Failed to publish outbox message {id}: {error}', [
                    'id' => (string) $row->id(),
                    'class' => $row->messageClass(),
                    'error' => $exception->getMessage(),
                ]);
            }

            $this->entityManager->flush();
        }
    }

    /** @return OutboxMessage[] */
    private function findPending(int $limit): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(OutboxMessage::class, 'm')
            ->where('m.sentAt IS NULL')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}