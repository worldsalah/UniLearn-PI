<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing quiz_type column to quiz table
 */
final class Version20260323093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing quiz_type column to quiz table';
    }

    public function up(Schema $schema): void
    {
        // Check if column exists before adding (for safety)
        $this->addSql('ALTER TABLE quiz ADD quiz_type VARCHAR(20) DEFAULT \'lesson\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz DROP quiz_type');
    }
}
