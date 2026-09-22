<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\ScanTicketCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Exception\EntityNotFoundException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\TicketCode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ScanTicketHandler implements CommandHandlerInterface
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ScanTicketCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $ticket = $this->tickets->findByCode(new TicketCode($command->ticketCode));
            if (!$ticket) {
                throw new EntityNotFoundException('Ticket not found.');
            }

            $ticket->scan();
            $this->tickets->save($ticket);
        });
    }
}
