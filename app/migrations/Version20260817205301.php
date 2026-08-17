<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817205301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_messages ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE chat_rooms ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('CREATE UNIQUE INDEX uniq_event_seat ON event_seats (event_id, seat_id)');
        $this->addSql('ALTER TABLE payments ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE payments ALTER paid_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE payments ALTER failed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('CREATE UNIQUE INDEX uniq_venue_row_number ON seats (venue_id, row, number)');
        $this->addSql('ALTER TABLE users ALTER reset_password_token_expires_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_messages ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE chat_rooms ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('DROP INDEX uniq_event_seat');
        $this->addSql('ALTER TABLE payments ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE payments ALTER paid_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE payments ALTER failed_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('DROP INDEX uniq_venue_row_number');
        $this->addSql('ALTER TABLE users ALTER reset_password_token_expires_at TYPE TIMESTAMP(0) WITH TIME ZONE');
    }
}
