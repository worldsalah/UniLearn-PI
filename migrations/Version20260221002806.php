<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221002806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_category (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, category_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_AFF87497591CC992 (course_id), INDEX IDX_AFF8749712469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_lesson (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, lesson_id INT NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_564CB5BE591CC992 (course_id), INDEX IDX_564CB5BECDF80196 (lesson_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_review (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_D77B408B591CC992 (course_id), INDEX IDX_D77B408BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE enrollment (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, user_id INT NOT NULL, enrolled_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, INDEX IDX_DBDCD7E1591CC992 (course_id), INDEX IDX_DBDCD7E1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_answer (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, quiz_result_id INT NOT NULL, answer LONGTEXT NOT NULL, is_correct TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_3799BA7C1E27F6BF (question_id), INDEX IDX_3799BA7C1C7C7A5 (quiz_result_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_attempt (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, user_id INT NOT NULL, score DOUBLE PRECISION NOT NULL, total_questions INT NOT NULL, correct_answers INT NOT NULL, started_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT \'in_progress\' NOT NULL, INDEX IDX_AB6AFC6853CD175 (quiz_id), INDEX IDX_AB6AFC6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_question (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, question LONGTEXT NOT NULL, explanation LONGTEXT DEFAULT NULL, options JSON NOT NULL COMMENT \'(DC2Type:json)\', correct_answer INT NOT NULL, points INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_6033B00B853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF8749712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE course_lesson ADD CONSTRAINT FK_564CB5BE591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_lesson ADD CONSTRAINT FK_564CB5BECDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE course_review ADD CONSTRAINT FK_D77B408B591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_review ADD CONSTRAINT FK_D77B408BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_answer ADD CONSTRAINT FK_3799BA7C1E27F6BF FOREIGN KEY (question_id) REFERENCES quiz_question (id)');
        $this->addSql('ALTER TABLE quiz_answer ADD CONSTRAINT FK_3799BA7C1C7C7A5 FOREIGN KEY (quiz_result_id) REFERENCES quiz_result (id)');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_question ADD CONSTRAINT FK_6033B00B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF87497591CC992');
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF8749712469DE2');
        $this->addSql('ALTER TABLE course_lesson DROP FOREIGN KEY FK_564CB5BE591CC992');
        $this->addSql('ALTER TABLE course_lesson DROP FOREIGN KEY FK_564CB5BECDF80196');
        $this->addSql('ALTER TABLE course_review DROP FOREIGN KEY FK_D77B408B591CC992');
        $this->addSql('ALTER TABLE course_review DROP FOREIGN KEY FK_D77B408BA76ED395');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1591CC992');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1A76ED395');
        $this->addSql('ALTER TABLE quiz_answer DROP FOREIGN KEY FK_3799BA7C1E27F6BF');
        $this->addSql('ALTER TABLE quiz_answer DROP FOREIGN KEY FK_3799BA7C1C7C7A5');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6853CD175');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6A76ED395');
        $this->addSql('ALTER TABLE quiz_question DROP FOREIGN KEY FK_6033B00B853CD175');
        $this->addSql('DROP TABLE course_category');
        $this->addSql('DROP TABLE course_lesson');
        $this->addSql('DROP TABLE course_review');
        $this->addSql('DROP TABLE enrollment');
        $this->addSql('DROP TABLE quiz_answer');
        $this->addSql('DROP TABLE quiz_attempt');
        $this->addSql('DROP TABLE quiz_question');
    }
}
