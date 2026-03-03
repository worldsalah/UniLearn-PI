<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227035812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add difficulty field to CourseTestQuestion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_question ADD difficulty VARCHAR(20) DEFAULT \'medium\' NOT NULL');
        $this->addSql('ALTER TABLE course_test_question CHANGE correct_answer correct_answer VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_test_question DROP difficulty');
        $this->addSql('ALTER TABLE course_test_question CHANGE correct_answer correct_answer VARCHAR(255) DEFAULT NULL');
    }
}
