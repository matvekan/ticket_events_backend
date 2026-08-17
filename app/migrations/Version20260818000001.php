<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create messenger_outbox table for transactional outbox pattern.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE messenger_outbox (id UUID NOT NULL, message_class VARCHAR(255) NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, attempts INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_outbox_pending ON messenger_outbox (sent_at, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_outbox');
    }
}