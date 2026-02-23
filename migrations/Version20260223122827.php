<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223122827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8545BDF5');
        $this->addSql('CREATE TABLE checkout (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, state VARCHAR(100) NOT NULL, zip_code VARCHAR(20) NOT NULL, country VARCHAR(100) NOT NULL, card_number VARCHAR(50) NOT NULL, expiry_date VARCHAR(10) NOT NULL, cvv VARCHAR(5) NOT NULL, cardholder_name VARCHAR(100) NOT NULL, payment_method VARCHAR(20) DEFAULT \'credit_card\' NOT NULL, agree_terms TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) DEFAULT \'pending\' NOT NULL, total_amount DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, subject VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) DEFAULT \'pending\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) DEFAULT \'active\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quiz_settings (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, points INT NOT NULL, time_limit INT DEFAULT NULL, passing_score INT NOT NULL, max_attempts INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_33815980853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quiz_settings ADD CONSTRAINT FK_33815980853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FCDF80196');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FCB944F1A');
        $this->addSql('ALTER TABLE quiz_attempt_answer DROP FOREIGN KEY FK_9453B9FCB191BE6B');
        $this->addSql('ALTER TABLE quiz_attempt_answer DROP FOREIGN KEY FK_9453B9FCF24C5BEC');
        $this->addSql('ALTER TABLE quiz_attempt_answer DROP FOREIGN KEY FK_9453B9FC1E27F6BF');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33A76ED395');
        $this->addSql('ALTER TABLE trust_score DROP FOREIGN KEY FK_5E6F5B878DE820D9');
        $this->addSql('ALTER TABLE validation_result DROP FOREIGN KEY FK_973C93C88DE820D9');
        $this->addSql('ALTER TABLE validation_result DROP FOREIGN KEY FK_973C93C84584665A');
        $this->addSql('DROP TABLE lesson_progress');
        $this->addSql('DROP TABLE quiz_attempt_answer');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE trust_score');
        $this->addSql('DROP TABLE validation_result');
        $this->addSql('ALTER TABLE booking ADD created_at DATETIME DEFAULT NULL, ADD status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE course ADD requirements LONGTEXT DEFAULT NULL, ADD learning_outcomes LONGTEXT DEFAULT NULL, ADD target_audience LONGTEXT DEFAULT NULL, ADD status VARCHAR(20) DEFAULT \'inactive\' NOT NULL, ADD image_status VARCHAR(20) DEFAULT \'pending\' NOT NULL, ADD video_status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('DROP INDEX UNIQ_AFF874975E237E06 ON course_category');
        $this->addSql('DROP INDEX UNIQ_AFF87497989D9B62 ON course_category');
        $this->addSql('ALTER TABLE course_category ADD course_id INT NOT NULL, ADD category_id INT NOT NULL, ADD created_at DATETIME NOT NULL, DROP name, DROP slug');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF8749712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_AFF87497591CC992 ON course_category (course_id)');
        $this->addSql('CREATE INDEX IDX_AFF8749712469DE2 ON course_category (category_id)');
        $this->addSql('ALTER TABLE course_lesson ADD sort_order INT NOT NULL, ADD created_at DATETIME NOT NULL, DROP title, DROP content, CHANGE position lesson_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_lesson ADD CONSTRAINT FK_564CB5BECDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('CREATE INDEX IDX_564CB5BECDF80196 ON course_lesson (lesson_id)');
        $this->addSql('ALTER TABLE course_review DROP FOREIGN KEY FK_D77B408BCB944F1A');
        $this->addSql('DROP INDEX IDX_D77B408BCB944F1A ON course_review');
        $this->addSql('ALTER TABLE course_review CHANGE student_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_review ADD CONSTRAINT FK_D77B408BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D77B408BA76ED395 ON course_review (user_id)');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1CB944F1A');
        $this->addSql('DROP INDEX IDX_DBDCD7E1CB944F1A ON enrollment');
        $this->addSql('ALTER TABLE enrollment ADD completed_at DATETIME DEFAULT NULL, ADD status VARCHAR(20) DEFAULT \'active\' NOT NULL, ADD progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, DROP is_completed, DROP progress_percent, CHANGE student_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1A76ED395 ON enrollment (user_id)');
        $this->addSql('ALTER TABLE job ADD location VARCHAR(255) DEFAULT NULL, ADD type VARCHAR(50) DEFAULT NULL, ADD experience_level VARCHAR(50) DEFAULT NULL, ADD duration VARCHAR(100) DEFAULT NULL, ADD requirements LONGTEXT DEFAULT NULL, ADD skills VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson ADD type VARCHAR(50) NOT NULL, ADD content LONGTEXT DEFAULT NULL, ADD attachment_url VARCHAR(255) DEFAULT NULL, ADD is_preview TINYINT(1) NOT NULL, ADD status VARCHAR(20) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD sort_order INT NOT NULL');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8545BDF5');
        $this->addSql('ALTER TABLE product ADD category_id INT NOT NULL, DROP category');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('DROP INDEX UNIQ_A412FA92989D9B62 ON quiz');
        $this->addSql('ALTER TABLE quiz DROP slug, DROP time_limit_minutes, DROP is_published');
        $this->addSql('ALTER TABLE quiz_answer ADD quiz_result_id INT NOT NULL, ADD answer LONGTEXT NOT NULL, ADD created_at DATETIME NOT NULL, DROP content, CHANGE is_correct is_correct TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE quiz_answer ADD CONSTRAINT FK_3799BA7C1C7C7A5 FOREIGN KEY (quiz_result_id) REFERENCES quiz_result (id)');
        $this->addSql('CREATE INDEX IDX_3799BA7C1C7C7A5 ON quiz_answer (quiz_result_id)');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6CB944F1A');
        $this->addSql('DROP INDEX IDX_AB6AFC6CB944F1A ON quiz_attempt');
        $this->addSql('ALTER TABLE quiz_attempt ADD total_questions INT NOT NULL, ADD correct_answers INT NOT NULL, ADD status VARCHAR(20) DEFAULT \'in_progress\' NOT NULL, DROP max_score, CHANGE score score DOUBLE PRECISION NOT NULL, CHANGE student_id user_id INT NOT NULL, CHANGE finished_at completed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AB6AFC6A76ED395 ON quiz_attempt (user_id)');
        $this->addSql('ALTER TABLE quiz_question ADD explanation LONGTEXT DEFAULT NULL, ADD options JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD points INT NOT NULL, ADD created_at DATETIME NOT NULL, CHANGE content question LONGTEXT NOT NULL, CHANGE position correct_answer INT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE session CHANGE start_date start_date DATETIME NOT NULL, CHANGE end_date end_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user ADD role_id INT DEFAULT NULL, ADD full_name VARCHAR(100) NOT NULL, DROP name, DROP role, CHANGE email email VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649D60322AC ON user (role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lesson_progress (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, lesson_id INT NOT NULL, completed_at DATETIME NOT NULL, INDEX IDX_6A46B85FCB944F1A (student_id), INDEX IDX_6A46B85FCDF80196 (lesson_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE quiz_attempt_answer (id INT AUTO_INCREMENT NOT NULL, attempt_id INT NOT NULL, question_id INT NOT NULL, selected_answer_id INT DEFAULT NULL, INDEX IDX_9453B9FC1E27F6BF (question_id), INDEX IDX_9453B9FCF24C5BEC (selected_answer_id), INDEX IDX_9453B9FCB191BE6B (attempt_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, full_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bio LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, skills JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', rating DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_B723AF33A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE trust_score (id INT AUTO_INCREMENT NOT NULL, seller_id INT NOT NULL, overall_score DOUBLE PRECISION NOT NULL, behavior_score DOUBLE PRECISION NOT NULL, content_score DOUBLE PRECISION NOT NULL, pricing_score DOUBLE PRECISION NOT NULL, reputation_score DOUBLE PRECISION NOT NULL, score_breakdown JSON NOT NULL COMMENT \'(DC2Type:json)\', historical_trend JSON NOT NULL COMMENT \'(DC2Type:json)\', last_updated DATETIME NOT NULL, risk_level VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX UNIQ_5E6F5B878DE820D9 (seller_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE validation_result (id INT AUTO_INCREMENT NOT NULL, seller_id INT NOT NULL, product_id INT DEFAULT NULL, validation_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, overall_score DOUBLE PRECISION NOT NULL, risk_level VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, component_scores JSON NOT NULL COMMENT \'(DC2Type:json)\', findings JSON NOT NULL COMMENT \'(DC2Type:json)\', improvement_suggestions JSON NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, passed TINYINT(1) NOT NULL, INDEX IDX_973C93C84584665A (product_id), INDEX IDX_973C93C88DE820D9 (seller_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85FCDF80196 FOREIGN KEY (lesson_id) REFERENCES course_lesson (id)');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85FCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_attempt_answer ADD CONSTRAINT FK_9453B9FCB191BE6B FOREIGN KEY (attempt_id) REFERENCES quiz_attempt (id)');
        $this->addSql('ALTER TABLE quiz_attempt_answer ADD CONSTRAINT FK_9453B9FCF24C5BEC FOREIGN KEY (selected_answer_id) REFERENCES quiz_answer (id)');
        $this->addSql('ALTER TABLE quiz_attempt_answer ADD CONSTRAINT FK_9453B9FC1E27F6BF FOREIGN KEY (question_id) REFERENCES quiz_question (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trust_score ADD CONSTRAINT FK_5E6F5B878DE820D9 FOREIGN KEY (seller_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE validation_result ADD CONSTRAINT FK_973C93C88DE820D9 FOREIGN KEY (seller_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE validation_result ADD CONSTRAINT FK_973C93C84584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE quiz_settings DROP FOREIGN KEY FK_33815980853CD175');
        $this->addSql('DROP TABLE checkout');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE quiz_settings');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE613FECDF');
        $this->addSql('ALTER TABLE booking DROP created_at, DROP status');
        $this->addSql('ALTER TABLE course DROP requirements, DROP learning_outcomes, DROP target_audience, DROP status, DROP image_status, DROP video_status');
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF87497591CC992');
        $this->addSql('ALTER TABLE course_category DROP FOREIGN KEY FK_AFF8749712469DE2');
        $this->addSql('DROP INDEX IDX_AFF87497591CC992 ON course_category');
        $this->addSql('DROP INDEX IDX_AFF8749712469DE2 ON course_category');
        $this->addSql('ALTER TABLE course_category ADD name VARCHAR(120) NOT NULL, ADD slug VARCHAR(140) NOT NULL, DROP course_id, DROP category_id, DROP created_at');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFF874975E237E06 ON course_category (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFF87497989D9B62 ON course_category (slug)');
        $this->addSql('ALTER TABLE course_lesson DROP FOREIGN KEY FK_564CB5BECDF80196');
        $this->addSql('DROP INDEX IDX_564CB5BECDF80196 ON course_lesson');
        $this->addSql('ALTER TABLE course_lesson ADD title VARCHAR(255) NOT NULL, ADD content LONGTEXT DEFAULT NULL, ADD position INT NOT NULL, DROP lesson_id, DROP sort_order, DROP created_at');
        $this->addSql('ALTER TABLE course_review DROP FOREIGN KEY FK_D77B408BA76ED395');
        $this->addSql('DROP INDEX IDX_D77B408BA76ED395 ON course_review');
        $this->addSql('ALTER TABLE course_review CHANGE user_id student_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_review ADD CONSTRAINT FK_D77B408BCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D77B408BCB944F1A ON course_review (student_id)');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1A76ED395');
        $this->addSql('DROP INDEX IDX_DBDCD7E1A76ED395 ON enrollment');
        $this->addSql('ALTER TABLE enrollment ADD is_completed TINYINT(1) DEFAULT 0 NOT NULL, ADD progress_percent INT DEFAULT NULL, DROP completed_at, DROP status, DROP progress, CHANGE user_id student_id INT NOT NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1CB944F1A ON enrollment (student_id)');
        $this->addSql('ALTER TABLE job DROP location, DROP type, DROP experience_level, DROP duration, DROP requirements, DROP skills');
        $this->addSql('ALTER TABLE lesson DROP type, DROP content, DROP attachment_url, DROP is_preview, DROP status, DROP description, DROP sort_order');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8545BDF5');
        $this->addSql('DROP INDEX IDX_D34A04AD12469DE2 ON product');
        $this->addSql('ALTER TABLE product ADD category VARCHAR(255) NOT NULL, DROP category_id');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz ADD slug VARCHAR(255) NOT NULL, ADD time_limit_minutes INT DEFAULT 60 NOT NULL, ADD is_published TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A412FA92989D9B62 ON quiz (slug)');
        $this->addSql('ALTER TABLE quiz_answer DROP FOREIGN KEY FK_3799BA7C1C7C7A5');
        $this->addSql('DROP INDEX IDX_3799BA7C1C7C7A5 ON quiz_answer');
        $this->addSql('ALTER TABLE quiz_answer ADD content VARCHAR(255) NOT NULL, DROP quiz_result_id, DROP answer, DROP created_at, CHANGE is_correct is_correct TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6A76ED395');
        $this->addSql('DROP INDEX IDX_AB6AFC6A76ED395 ON quiz_attempt');
        $this->addSql('ALTER TABLE quiz_attempt ADD student_id INT NOT NULL, ADD max_score INT DEFAULT NULL, DROP user_id, DROP total_questions, DROP correct_answers, DROP status, CHANGE score score INT DEFAULT NULL, CHANGE completed_at finished_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AB6AFC6CB944F1A ON quiz_attempt (student_id)');
        $this->addSql('ALTER TABLE quiz_question ADD position INT NOT NULL, DROP explanation, DROP options, DROP correct_answer, DROP points, DROP created_at, CHANGE question content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314AA76ED395');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314A853CD175');
        $this->addSql('ALTER TABLE session CHANGE start_date start_date DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('DROP INDEX IDX_8D93D649D60322AC ON user');
        $this->addSql('ALTER TABLE user ADD name VARCHAR(255) NOT NULL, ADD role VARCHAR(255) NOT NULL, DROP role_id, DROP full_name, CHANGE email email VARCHAR(255) NOT NULL');
    }
}
