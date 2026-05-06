<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing columns to course_test_result table
 */
final class Version20260323093200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing time_taken column to course_test_result table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_result ADD time_taken DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_result DROP time_taken');
    }
}
