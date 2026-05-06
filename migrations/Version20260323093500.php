<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing taken_at and created_at columns to quiz_result table
 */
final class Version20260323093500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing taken_at and created_at columns to quiz_result table';
    }

    public function up(Schema $schema): void
    {
        // Add columns as nullable first
        $this->addSql('ALTER TABLE quiz_result ADD taken_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE quiz_result ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        
        // Update existing rows with default values
        $this->addSql('UPDATE quiz_result SET taken_at = NOW() WHERE taken_at IS NULL');
        $this->addSql('UPDATE quiz_result SET created_at = NOW() WHERE created_at IS NULL');
        
        // Make columns NOT NULL
        $this->addSql('ALTER TABLE quiz_result MODIFY taken_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE quiz_result MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_result DROP taken_at');
        $this->addSql('ALTER TABLE quiz_result DROP created_at');
    }
}
