<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ticket now snapshots the price at purchase time instead of reading it
 * from the event seat. Adds the snapshot columns to tickets.
 */
final class Version20260825000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add price snapshot columns (price_amount, price_currency) to tickets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tickets ADD COLUMN price_amount INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE tickets ADD COLUMN price_currency VARCHAR(3) NOT NULL DEFAULT \'BYN\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tickets DROP COLUMN price_amount');
        $this->addSql('ALTER TABLE tickets DROP COLUMN price_currency');
    }
}
