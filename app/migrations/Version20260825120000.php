<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A seat can be sold multiple times over its lifetime (reserve -> cancel ->
 * reserve again). Ticket history must be preserved per seat, so the 1:1
 * uniqueness on tickets.event_seat_id is dropped. The "one ACTIVE ticket
 * per seat" invariant is enforced by the domain: EventSeat::reserve()
 * requires status=free and handlers take a pessimistic lock on seats.
 */
final class Version20260825120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop tickets.event_seat_id uniqueness: seats can have ticket history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_54469DF4448D579A');
        $this->addSql('CREATE INDEX IDX_54469DF4448D579A ON tickets (event_seat_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_54469DF4448D579A');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF4448D579A ON tickets (event_seat_id)');
    }
}
