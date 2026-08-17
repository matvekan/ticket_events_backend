<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260705144430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ALTER title TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE events ALTER description TYPE VARCHAR');
        $this->addSql('ALTER TABLE tickets ALTER code TYPE VARCHAR(12)');
        $this->addSql('ALTER TABLE venues ALTER city TYPE VARCHAR(100)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ALTER title TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE events ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE tickets ALTER code TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE venues ALTER city TYPE VARCHAR(255)');
    }
}
