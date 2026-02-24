<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223164248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quiz_statistics (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, student_id INT NOT NULL, score INT NOT NULL, total_questions INT NOT NULL, correct_answers INT NOT NULL, completed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', average_time_per_question DOUBLE PRECISION NOT NULL, question_results JSON NOT NULL COMMENT \'(DC2Type:json)\', difficulty_level INT NOT NULL, INDEX IDX_ABBCAC1E853CD175 (quiz_id), INDEX IDX_ABBCAC1ECB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quiz_statistics ADD CONSTRAINT FK_ABBCAC1E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz_statistics ADD CONSTRAINT FK_ABBCAC1ECB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE placement_test DROP FOREIGN KEY FK_DFF7FF0423EDC87');
        $this->addSql('ALTER TABLE placement_test_question DROP FOREIGN KEY FK_A121F169D1B5F7FE');
        $this->addSql('ALTER TABLE placement_test_result DROP FOREIGN KEY FK_7EF18CCDCB944F1A');
        $this->addSql('ALTER TABLE placement_test_result DROP FOREIGN KEY FK_7EF18CCDD1B5F7FE');
        $this->addSql('DROP TABLE placement_test');
        $this->addSql('DROP TABLE placement_test_question');
        $this->addSql('DROP TABLE placement_test_result');
        $this->addSql('DROP TABLE subject');
        $this->addSql('ALTER TABLE user DROP first_login');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE placement_test (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, question_count INT NOT NULL, duration_minutes INT NOT NULL, passing_score INT DEFAULT 0 NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, level_mapping JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DFF7FF0423EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE placement_test_question (id INT AUTO_INCREMENT NOT NULL, placement_test_id INT NOT NULL, question LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, options JSON NOT NULL COMMENT \'(DC2Type:json)\', correct_answer VARCHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, difficulty INT NOT NULL, explanation LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, tags JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A121F169D1B5F7FE (placement_test_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE placement_test_result (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, placement_test_id INT NOT NULL, score INT NOT NULL, percentage INT NOT NULL, assigned_level VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, correct_answers INT NOT NULL, total_questions INT NOT NULL, answers JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', time_spent_minutes INT DEFAULT 0 NOT NULL, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7EF18CCDCB944F1A (student_id), INDEX IDX_7EF18CCDD1B5F7FE (placement_test_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE subject (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, code VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, icon VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, color VARCHAR(7) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, sort_order INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FBCE3E7A77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE placement_test ADD CONSTRAINT FK_DFF7FF0423EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE placement_test_question ADD CONSTRAINT FK_A121F169D1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_test (id)');
        $this->addSql('ALTER TABLE placement_test_result ADD CONSTRAINT FK_7EF18CCDCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE placement_test_result ADD CONSTRAINT FK_7EF18CCDD1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_test (id)');
        $this->addSql('ALTER TABLE quiz_statistics DROP FOREIGN KEY FK_ABBCAC1E853CD175');
        $this->addSql('ALTER TABLE quiz_statistics DROP FOREIGN KEY FK_ABBCAC1ECB944F1A');
        $this->addSql('DROP TABLE quiz_statistics');
        $this->addSql('ALTER TABLE user ADD first_login TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
