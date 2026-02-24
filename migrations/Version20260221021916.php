<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221021916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE placement_test (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, question_count INT NOT NULL, duration_minutes INT NOT NULL, passing_score INT DEFAULT 0 NOT NULL, status VARCHAR(20) NOT NULL, level_mapping JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DFF7FF0423EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE placement_test_question (id INT AUTO_INCREMENT NOT NULL, placement_test_id INT NOT NULL, question LONGTEXT NOT NULL, options JSON NOT NULL COMMENT \'(DC2Type:json)\', correct_answer VARCHAR(1) NOT NULL, difficulty INT NOT NULL, explanation LONGTEXT DEFAULT NULL, tags JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A121F169D1B5F7FE (placement_test_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE placement_test_result (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, placement_test_id INT NOT NULL, score INT NOT NULL, percentage INT NOT NULL, assigned_level VARCHAR(20) NOT NULL, correct_answers INT NOT NULL, total_questions INT NOT NULL, answers JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', time_spent_minutes INT DEFAULT 0 NOT NULL, status VARCHAR(20) NOT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7EF18CCDCB944F1A (student_id), INDEX IDX_7EF18CCDD1B5F7FE (placement_test_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subject (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, icon VARCHAR(50) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FBCE3E7A77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE placement_test ADD CONSTRAINT FK_DFF7FF0423EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE placement_test_question ADD CONSTRAINT FK_A121F169D1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_test (id)');
        $this->addSql('ALTER TABLE placement_test_result ADD CONSTRAINT FK_7EF18CCDCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE placement_test_result ADD CONSTRAINT FK_7EF18CCDD1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_test (id)');
        $this->addSql('ALTER TABLE student_subject_level DROP FOREIGN KEY FK_DEE5F555CB944F1A');
        $this->addSql('ALTER TABLE student_subject_level DROP FOREIGN KEY FK_DEE5F55523EDC87');
        $this->addSql('DROP TABLE student_subject_level');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA9223EDC87');
        $this->addSql('DROP INDEX IDX_A412FA9223EDC87 ON quiz');
        $this->addSql('ALTER TABLE quiz DROP subject_id, DROP is_placement_test');
        $this->addSql('ALTER TABLE user DROP first_login');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE student_subject_level (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, subject_id INT NOT NULL, score INT NOT NULL, level VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DEE5F555CB944F1A (student_id), INDEX IDX_DEE5F55523EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE student_subject_level ADD CONSTRAINT FK_DEE5F555CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE student_subject_level ADD CONSTRAINT FK_DEE5F55523EDC87 FOREIGN KEY (subject_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE placement_test DROP FOREIGN KEY FK_DFF7FF0423EDC87');
        $this->addSql('ALTER TABLE placement_test_question DROP FOREIGN KEY FK_A121F169D1B5F7FE');
        $this->addSql('ALTER TABLE placement_test_result DROP FOREIGN KEY FK_7EF18CCDCB944F1A');
        $this->addSql('ALTER TABLE placement_test_result DROP FOREIGN KEY FK_7EF18CCDD1B5F7FE');
        $this->addSql('DROP TABLE placement_test');
        $this->addSql('DROP TABLE placement_test_question');
        $this->addSql('DROP TABLE placement_test_result');
        $this->addSql('DROP TABLE subject');
        $this->addSql('ALTER TABLE quiz ADD subject_id INT DEFAULT NULL, ADD is_placement_test TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA9223EDC87 FOREIGN KEY (subject_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_A412FA9223EDC87 ON quiz (subject_id)');
        $this->addSql('ALTER TABLE user ADD first_login TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
