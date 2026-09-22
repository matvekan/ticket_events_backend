<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\PublishOutboxMessages;
use App\Application\Transaction\TransactionManagerInterface;
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
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(PublishOutboxMessages $message): void
    {
        $this->transactionManager->transactional(function (): void {
            $rows = $this->outboxMessages->findPending(self::BATCH_SIZE);

            foreach ($rows as $row) {
                if ($row->isSent()) {
                    continue;
                }

                try {
                    $decoded = base64_decode($row->body(), true);
                    assert(is_string($decoded));
                    $data = unserialize($decoded, ['allowed_classes' => true]);
                    assert(is_object($data));
                    $this->eventsTransport->send(new Envelope(
                        $data,
                        [new BusNameStamp('event.bus')],
                    ));

                    $row->markSent($this->clock->now());
                } catch (\Throwable $exception) {
                    $row->markFailed();
                    $this->logger->error('Failed to publish outbox message {id}: {error}', [
                        'id' => $row->id(),
                        'class' => $row->messageClass(),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });
    }
}
