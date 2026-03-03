<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301050100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $sessionTable = $schema->getTable('session');

        if (!$sessionTable->hasColumn('available_from')) {
            $this->addSql('ALTER TABLE session ADD available_from TIME DEFAULT NULL');
        }

        if (!$sessionTable->hasColumn('available_to')) {
            $this->addSql('ALTER TABLE session ADD available_to TIME DEFAULT NULL');
        }

        if (!$sessionTable->hasColumn('hourly_price')) {
            $this->addSql('ALTER TABLE session ADD hourly_price NUMERIC(10, 2) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sessionTable = $schema->getTable('session');

        if ($sessionTable->hasColumn('available_from')) {
            $this->addSql('ALTER TABLE session DROP available_from');
        }

        if ($sessionTable->hasColumn('available_to')) {
            $this->addSql('ALTER TABLE session DROP available_to');
        }

        if ($sessionTable->hasColumn('hourly_price')) {
            $this->addSql('ALTER TABLE session DROP hourly_price');
        }
    }
}
