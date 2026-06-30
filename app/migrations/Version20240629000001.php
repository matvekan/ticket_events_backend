<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240629000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_seats (
            id UUID NOT NULL,
            event_id UUID NOT NULL,
            seat_id UUID NOT NULL,
            price_amount INT NOT NULL,
            price_currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_FEF9E67C71F7E88B ON event_seats (event_id)');
        $this->addSql('CREATE INDEX IDX_FEF9E67CC1DAFE35 ON event_seats (seat_id)');
        $this->addSql('ALTER TABLE event_seats ADD CONSTRAINT FK_FEF9E67C71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_seats ADD CONSTRAINT FK_FEF9E67CC1DAFE35 FOREIGN KEY (seat_id) REFERENCES seats (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE events (
            id UUID NOT NULL,
            venue_id UUID NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_5387574A40A73EBA ON events (venue_id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A40A73EBA FOREIGN KEY (venue_id) REFERENCES venues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE orders (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            total_amount INT NOT NULL,
            total_currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA76ED395 ON orders (user_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE seats (
            id UUID NOT NULL,
            venue_id UUID NOT NULL,
            row VARCHAR(10) NOT NULL,
            number INT NOT NULL,
            sector VARCHAR(50) DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_BFE2575040A73EBA ON seats (venue_id)');
        $this->addSql('ALTER TABLE seats ADD CONSTRAINT FK_BFE2575040A73EBA FOREIGN KEY (venue_id) REFERENCES venues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE tickets (
            id UUID NOT NULL,
            order_id UUID NOT NULL,
            event_seat_id UUID NOT NULL,
            code VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF477153098 ON tickets (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF4448D579A ON tickets (event_seat_id)');
        $this->addSql('CREATE INDEX IDX_54469DF48D9F6D38 ON tickets (order_id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF48D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4448D579A FOREIGN KEY (event_seat_id) REFERENCES event_seats (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE users (
            id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE venues (
            id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE event_seats');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE seats');
        $this->addSql('DROP TABLE venues');
        $this->addSql('DROP TABLE users');
    }
}
