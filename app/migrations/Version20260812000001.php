<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chat rooms and chat messages tables for the support chat.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_rooms (id UUID NOT NULL, user_id UUID NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHAT_ROOMS_USER ON chat_rooms (user_id)');
        $this->addSql('ALTER TABLE chat_rooms ADD CONSTRAINT FK_CHAT_ROOMS_USER FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE chat_messages (id UUID NOT NULL, room_id UUID NOT NULL, sender_id UUID NOT NULL, text VARCHAR(2000) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CHAT_MESSAGES_ROOM_CREATED ON chat_messages (room_id, created_at)');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_CHAT_MESSAGES_ROOM FOREIGN KEY (room_id) REFERENCES chat_rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_messages ADD CONSTRAINT FK_CHAT_MESSAGES_SENDER FOREIGN KEY (sender_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_messages DROP CONSTRAINT FK_CHAT_MESSAGES_SENDER');
        $this->addSql('ALTER TABLE chat_messages DROP CONSTRAINT FK_CHAT_MESSAGES_ROOM');
        $this->addSql('DROP TABLE chat_messages');
        $this->addSql('ALTER TABLE chat_rooms DROP CONSTRAINT FK_CHAT_ROOMS_USER');
        $this->addSql('DROP TABLE chat_rooms');
    }
}