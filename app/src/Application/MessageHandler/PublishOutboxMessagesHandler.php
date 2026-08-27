<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\PublishOutboxMessages;
use App\Domain\Repository\OutboxMessageRepositoryInterface;
use App\Domain\Shared\ClockInterface;
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
        private readonly OutboxMessageRepositoryInterface $outboxMessages,
        private readonly TransportInterface $eventsTransport,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PublishOutboxMessages $message): void
    {
        $rows = $this->outboxMessages->findPending(self::BATCH_SIZE);

        foreach ($rows as $row) {
            try {
                $this->eventsTransport->send(new Envelope(
                    unserialize(base64_decode($row->body()), ['allowed_classes' => true]),
                    [new BusNameStamp('event.bus')],
                ));

                $row->markSent($this->clock->now());
            } catch (\Throwable $exception) {
                $row->markFailed();
                $this->logger->error('Failed to publish outbox message {id}: {error}', [
                    'id' => (string) $row->id(),
                    'class' => $row->messageClass(),
                    'error' => $exception->getMessage(),
                ]);
            }

            $this->outboxMessages->flush();
        }
    }
}
