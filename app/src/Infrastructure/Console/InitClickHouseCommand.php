<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use ClickHouseDB\Client as ClickHouseClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clickhouse:init',
    description: 'Creates analytics tables in ClickHouse (idempotent).',
)]
final class InitClickHouseCommand extends Command
{
    private const TABLE_SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS {database}.{table} (
    order_id String,
    user_id String,
    amount Int64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp
SQL;

    private const TABLES = [
        'seat_reservations',
        'order_payments',
        'order_cancellations',
        'order_refunds',
    ];

    public function __construct(
        private readonly ClickHouseClient $clickhouse,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $this->clickhouse->settings()->getDatabase();

        foreach (self::TABLES as $table) {
            $sql = str_replace(
                ['{database}', '{table}'],
                [$database, $table],
                self::TABLE_SCHEMA,
            );

            $this->clickhouse->write($sql);
            $output->writeln(sprintf('Table %s.%s is ready.', $database, $table));
        }

        return Command::SUCCESS;
    }
}
