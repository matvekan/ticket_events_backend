<?php

namespace App;

use App\Application\Message\ExpirePendingOrders;
use App\Application\Message\PublishOutboxMessages;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $outboxRelayIntervalSeconds,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run
            ->add(RecurringMessage::every('1 minute', new ExpirePendingOrders()))
            ->add(RecurringMessage::every(
                sprintf('%d seconds', $this->outboxRelayIntervalSeconds),
                new PublishOutboxMessages(),
            ))
        ;
    }
}
