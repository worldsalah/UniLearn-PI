<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing course_id column to lesson_completion table
 */
final class Version20260323093100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing course_id column to lesson_completion table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson_completion ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_LESSON_COMPLETION_COURSE FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_LESSON_COMPLETION_COURSE ON lesson_completion (course_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_LESSON_COMPLETION_COURSE');
        $this->addSql('DROP INDEX IDX_LESSON_COMPLETION_COURSE ON lesson_completion');
        $this->addSql('ALTER TABLE lesson_completion DROP course_id');
    }
}
