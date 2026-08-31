<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize existing tickets: pending orders tickets active -> reserved';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE tickets SET status = 'reserved' FROM orders WHERE tickets.order_id = orders.id AND orders.status = 'pending' AND tickets.status = 'active'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE tickets SET status = 'active' FROM orders WHERE tickets.order_id = orders.id AND orders.status = 'pending' AND tickets.status = 'reserved'");
    }
}
