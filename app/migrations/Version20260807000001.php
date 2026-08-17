<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payments table for the mock payment gateway flow.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payments (id UUID NOT NULL, order_id UUID NOT NULL, amount INT NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, failed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B328D9F6D38 ON payments (order_id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B328D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B328D9F6D38');
        $this->addSql('DROP TABLE payments');
    }
}
