<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional coordinates to venues.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE venues ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE venues ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE venues DROP longitude');
        $this->addSql('ALTER TABLE venues DROP latitude');
    }
}