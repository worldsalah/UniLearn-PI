<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing created_at column to course_test_result table
 */
final class Version20260323093300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing created_at column to course_test_result table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_result ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_result DROP created_at');
    }
}
