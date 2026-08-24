<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824171910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_messages (text VARCHAR(2000) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, room_id VARCHAR(36) NOT NULL, sender_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_EF20C9A654177093 ON chat_messages (room_id)');
        $this->addSql('CREATE INDEX IDX_EF20C9A6F624B39D ON chat_messages (sender_id)');
        $this->addSql('CREATE INDEX idx_chat_messages_room_created ON chat_messages (room_id, created_at)');
        $this->addSql('CREATE TABLE chat_rooms (created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_chat_rooms_user ON chat_rooms (user_id)');
        $this->addSql('CREATE TABLE event_seats (status VARCHAR(20) NOT NULL, id VARCHAR(36) NOT NULL, price_amount INT NOT NULL, price_currency VARCHAR(3) NOT NULL, event_id VARCHAR(36) NOT NULL, seat_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FEF9E67C71F7E88B ON event_seats (event_id)');
        $this->addSql('CREATE INDEX IDX_FEF9E67CC1DAFE35 ON event_seats (seat_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_event_seat ON event_seats (event_id, seat_id)');
        $this->addSql('CREATE TABLE events (title VARCHAR(100) NOT NULL, description VARCHAR NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, venue_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5387574A40A73EBA ON events (venue_id)');
        $this->addSql('CREATE TABLE messenger_outbox (message_class VARCHAR(255) NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, attempts INT NOT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_outbox_pending ON messenger_outbox (sent_at, created_at)');
        $this->addSql('CREATE TABLE orders (user_id VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, total_amount INT NOT NULL, total_currency VARCHAR(3) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE payments (amount INT NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, failed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_65D29B328D9F6D38 ON payments (order_id)');
        $this->addSql('CREATE TABLE seats (row VARCHAR(10) NOT NULL, number INT NOT NULL, sector VARCHAR(50) DEFAULT NULL, type VARCHAR(20) NOT NULL, id VARCHAR(36) NOT NULL, venue_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_BFE2575040A73EBA ON seats (venue_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_venue_row_number ON seats (venue_id, row, number)');
        $this->addSql('CREATE TABLE tickets (code VARCHAR(12) NOT NULL, status VARCHAR(20) NOT NULL, id VARCHAR(36) NOT NULL, event_seat_id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF477153098 ON tickets (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF4448D579A ON tickets (event_seat_id)');
        $this->addSql('CREATE INDEX IDX_54469DF48D9F6D38 ON tickets (order_id)');
        $this->addSql('CREATE TABLE users (name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, reset_password_token_hash VARCHAR(64) DEFAULT NULL, reset_password_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email ON users (email)');
        $this->addSql('CREATE TABLE venues (name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_EF20C9A654177093 FOREIGN KEY (room_id) REFERENCES chat_rooms (id)');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_EF20C9A6F624B39D FOREIGN KEY (sender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE chat_rooms ADD CONSTRAINT FK_7DDCF70DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE event_seats ADD CONSTRAINT FK_FEF9E67C71F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('ALTER TABLE event_seats ADD CONSTRAINT FK_FEF9E67CC1DAFE35 FOREIGN KEY (seat_id) REFERENCES seats (id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A40A73EBA FOREIGN KEY (venue_id) REFERENCES venues (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B328D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE seats ADD CONSTRAINT FK_BFE2575040A73EBA FOREIGN KEY (venue_id) REFERENCES venues (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4448D579A FOREIGN KEY (event_seat_id) REFERENCES event_seats (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_messages DROP CONSTRAINT FK_EF20C9A654177093');
        $this->addSql('ALTER TABLE chat_messages DROP CONSTRAINT FK_EF20C9A6F624B39D');
        $this->addSql('ALTER TABLE chat_rooms DROP CONSTRAINT FK_7DDCF70DA76ED395');
        $this->addSql('ALTER TABLE event_seats DROP CONSTRAINT FK_FEF9E67C71F7E88B');
        $this->addSql('ALTER TABLE event_seats DROP CONSTRAINT FK_FEF9E67CC1DAFE35');
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_5387574A40A73EBA');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_65D29B328D9F6D38');
        $this->addSql('ALTER TABLE seats DROP CONSTRAINT FK_BFE2575040A73EBA');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF4448D579A');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF48D9F6D38');
        $this->addSql('DROP TABLE chat_messages');
        $this->addSql('DROP TABLE chat_rooms');
        $this->addSql('DROP TABLE event_seats');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE messenger_outbox');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE seats');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE venues');
    }
}
