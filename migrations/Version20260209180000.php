<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add agree_terms column to user table';
    }

    public function up(Schema $schema): void
    {
        // Check if column exists before adding
        $table = $schema->getTable('user');
        if (!$table->hasColumn('agree_terms')) {
            $this->addSql('ALTER TABLE user ADD agree_terms TINYINT(1) DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP agree_terms');
    }
}
