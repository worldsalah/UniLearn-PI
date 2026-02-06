<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206144525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(30) NOT NULL, note CLOB DEFAULT NULL, created_at DATETIME NOT NULL, student_id INTEGER NOT NULL, slot_id INTEGER NOT NULL, CONSTRAINT FK_E00CEDDECB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E00CEDDE59E5119C FOREIGN KEY (slot_id) REFERENCES booking_slot (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E00CEDDECB944F1A ON booking (student_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDE59E5119C ON booking (slot_id)');
        $this->addSql('CREATE TABLE booking_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, is_available BOOLEAN DEFAULT 1 NOT NULL, freelancer_id INTEGER NOT NULL, CONSTRAINT FK_B49F02298545BDF5 FOREIGN KEY (freelancer_id) REFERENCES student (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B49F02298545BDF5 ON booking_slot (freelancer_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_slot_freelancer_start ON booking_slot (freelancer_id, start_at)');
        $this->addSql('CREATE TABLE certificate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, issued_at DATETIME NOT NULL, verification_code VARCHAR(64) NOT NULL, pdf_file VARCHAR(255) DEFAULT NULL, student_id INTEGER NOT NULL, course_id INTEGER DEFAULT NULL, quiz_id INTEGER DEFAULT NULL, CONSTRAINT FK_219CDA4ACB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_219CDA4A591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_219CDA4A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_219CDA4AE821C39F ON certificate (verification_code)');
        $this->addSql('CREATE INDEX IDX_219CDA4ACB944F1A ON certificate (student_id)');
        $this->addSql('CREATE INDEX IDX_219CDA4A591CC992 ON certificate (course_id)');
        $this->addSql('CREATE INDEX IDX_219CDA4A853CD175 ON certificate (quiz_id)');
        $this->addSql('CREATE TABLE course (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, price DOUBLE PRECISION DEFAULT NULL, is_published BOOLEAN DEFAULT 0 NOT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, slug VARCHAR(255) NOT NULL, category_id INTEGER NOT NULL, instructor_id INTEGER NOT NULL, CONSTRAINT FK_169E6FB912469DE2 FOREIGN KEY (category_id) REFERENCES course_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_169E6FB98C4FC193 FOREIGN KEY (instructor_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB9989D9B62 ON course (slug)');
        $this->addSql('CREATE INDEX IDX_169E6FB912469DE2 ON course (category_id)');
        $this->addSql('CREATE INDEX IDX_169E6FB98C4FC193 ON course (instructor_id)');
        $this->addSql('CREATE TABLE course_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFF874975E237E06 ON course_category (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFF87497989D9B62 ON course_category (slug)');
        $this->addSql('CREATE TABLE course_lesson (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content CLOB DEFAULT NULL, position INTEGER NOT NULL, course_id INTEGER NOT NULL, CONSTRAINT FK_564CB5BE591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_564CB5BE591CC992 ON course_lesson (course_id)');
        $this->addSql('CREATE TABLE course_review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, comment CLOB DEFAULT NULL, created_at DATETIME NOT NULL, student_id INTEGER NOT NULL, course_id INTEGER NOT NULL, CONSTRAINT FK_D77B408BCB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D77B408B591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D77B408BCB944F1A ON course_review (student_id)');
        $this->addSql('CREATE INDEX IDX_D77B408B591CC992 ON course_review (course_id)');
        $this->addSql('CREATE TABLE enrollment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, enrolled_at DATETIME NOT NULL, is_completed BOOLEAN DEFAULT 0 NOT NULL, progress_percent INTEGER DEFAULT NULL, student_id INTEGER NOT NULL, course_id INTEGER NOT NULL, CONSTRAINT FK_DBDCD7E1CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DBDCD7E1591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1CB944F1A ON enrollment (student_id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1591CC992 ON enrollment (course_id)');
        $this->addSql('CREATE TABLE job (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, budget DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_FBD8E0F819EB6921 FOREIGN KEY (client_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_FBD8E0F819EB6921 ON job (client_id)');
        $this->addSql('CREATE TABLE lesson_progress (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, completed_at DATETIME NOT NULL, student_id INTEGER NOT NULL, lesson_id INTEGER NOT NULL, CONSTRAINT FK_6A46B85FCB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6A46B85FCDF80196 FOREIGN KEY (lesson_id) REFERENCES course_lesson (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6A46B85FCB944F1A ON lesson_progress (student_id)');
        $this->addSql('CREATE INDEX IDX_6A46B85FCDF80196 ON lesson_progress (lesson_id)');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, rating INTEGER DEFAULT NULL, review CLOB DEFAULT NULL, created_at DATETIME NOT NULL, product_id INTEGER NOT NULL, buyer_id INTEGER NOT NULL, CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F52993984584665A ON "order" (product_id)');
        $this->addSql('CREATE INDEX IDX_F52993986C755722 ON "order" (buyer_id)');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, price DOUBLE PRECISION NOT NULL, category VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, slug VARCHAR(255) NOT NULL, freelancer_id INTEGER NOT NULL, CONSTRAINT FK_D34A04AD8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES student (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');
        $this->addSql('CREATE INDEX IDX_D34A04AD8545BDF5 ON product (freelancer_id)');
        $this->addSql('CREATE TABLE quiz (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, time_limit_minutes INTEGER DEFAULT 60 NOT NULL, is_published BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, course_id INTEGER DEFAULT NULL, CONSTRAINT FK_A412FA92591CC992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A412FA92989D9B62 ON quiz (slug)');
        $this->addSql('CREATE INDEX IDX_A412FA92591CC992 ON quiz (course_id)');
        $this->addSql('CREATE TABLE quiz_answer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content VARCHAR(255) NOT NULL, is_correct BOOLEAN DEFAULT 0 NOT NULL, question_id INTEGER NOT NULL, CONSTRAINT FK_3799BA7C1E27F6BF FOREIGN KEY (question_id) REFERENCES quiz_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3799BA7C1E27F6BF ON quiz_answer (question_id)');
        $this->addSql('CREATE TABLE quiz_attempt (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, score INTEGER DEFAULT NULL, max_score INTEGER DEFAULT NULL, quiz_id INTEGER NOT NULL, student_id INTEGER NOT NULL, CONSTRAINT FK_AB6AFC6853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB6AFC6CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AB6AFC6853CD175 ON quiz_attempt (quiz_id)');
        $this->addSql('CREATE INDEX IDX_AB6AFC6CB944F1A ON quiz_attempt (student_id)');
        $this->addSql('CREATE TABLE quiz_attempt_answer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, attempt_id INTEGER NOT NULL, question_id INTEGER NOT NULL, selected_answer_id INTEGER DEFAULT NULL, CONSTRAINT FK_9453B9FCB191BE6B FOREIGN KEY (attempt_id) REFERENCES quiz_attempt (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9453B9FC1E27F6BF FOREIGN KEY (question_id) REFERENCES quiz_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9453B9FCF24C5BEC FOREIGN KEY (selected_answer_id) REFERENCES quiz_answer (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9453B9FCB191BE6B ON quiz_attempt_answer (attempt_id)');
        $this->addSql('CREATE INDEX IDX_9453B9FC1E27F6BF ON quiz_attempt_answer (question_id)');
        $this->addSql('CREATE INDEX IDX_9453B9FCF24C5BEC ON quiz_attempt_answer (selected_answer_id)');
        $this->addSql('CREATE TABLE quiz_question (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content CLOB NOT NULL, position INTEGER NOT NULL, quiz_id INTEGER NOT NULL, CONSTRAINT FK_6033B00B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6033B00B853CD175 ON quiz_question (quiz_id)');
        $this->addSql('CREATE TABLE student (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, bio CLOB DEFAULT NULL, skills CLOB DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B723AF33A76ED395 ON student (user_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_slot');
        $this->addSql('DROP TABLE certificate');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_category');
        $this->addSql('DROP TABLE course_lesson');
        $this->addSql('DROP TABLE course_review');
        $this->addSql('DROP TABLE enrollment');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE lesson_progress');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE quiz_answer');
        $this->addSql('DROP TABLE quiz_attempt');
        $this->addSql('DROP TABLE quiz_attempt_answer');
        $this->addSql('DROP TABLE quiz_question');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
