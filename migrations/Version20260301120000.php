<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add start_time, duration_minutes, and total_price columns to booking table';
    }

    public function up(Schema $schema): void
    {
        $bookingTable = $schema->getTable('booking');

        if (!$bookingTable->hasColumn('start_time')) {
            $this->addSql('ALTER TABLE booking ADD start_time TIME DEFAULT NULL');
        }

        if (!$bookingTable->hasColumn('duration_minutes')) {
            $this->addSql('ALTER TABLE booking ADD duration_minutes INT DEFAULT NULL');
        }

        if (!$bookingTable->hasColumn('total_price')) {
            $this->addSql('ALTER TABLE booking ADD total_price NUMERIC(10, 2) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $bookingTable = $schema->getTable('booking');

        if ($bookingTable->hasColumn('start_time')) {
            $this->addSql('ALTER TABLE booking DROP start_time');
        }

        if ($bookingTable->hasColumn('duration_minutes')) {
            $this->addSql('ALTER TABLE booking DROP duration_minutes');
        }

        if ($bookingTable->hasColumn('total_price')) {
            $this->addSql('ALTER TABLE booking DROP total_price');
        }
    }
}
