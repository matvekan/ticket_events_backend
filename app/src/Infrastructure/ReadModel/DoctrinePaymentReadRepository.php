<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\PaymentDto;
use App\Application\Port\PaymentReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrinePaymentReadRepository implements PaymentReadRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByOrderId(string $orderId, ?string $userId): ?PaymentDto
    {
        $sql = <<<'SQL'
            SELECT p.id, p.status, p.amount
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.order_id = :orderId
        SQL;

        $params = ['orderId' => $orderId];

        if ($userId !== null) {
            $sql .= ' AND o.user_id = :userId';
            $params['userId'] = $userId;
        }

        $row = $this->connection->fetchAssociative($sql . ' LIMIT 1', $params);

        if ($row === false) {
            return null;
        }

        return new PaymentDto(
            id: (string) $row['id'],
            status: (string) $row['status'],
            amount: (int) $row['amount'],
        );
    }
}
