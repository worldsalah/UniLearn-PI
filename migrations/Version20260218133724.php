<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218133724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS course_audit_log');
        $this->addSql('DROP TABLE IF EXISTS course_version');

        // Only alter tables that exist
        if ($schema->hasTable('application')) {
            $this->addSql('ALTER TABLE application CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('category')) {
            $this->addSql('ALTER TABLE category CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('checkout')) {
            $this->addSql('ALTER TABLE checkout CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('contact')) {
            $this->addSql('ALTER TABLE contact CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        // Handle course table - add created_at if missing, then modify it
        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE course ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        } else {
            $this->addSql('ALTER TABLE course CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        // Drop columns from course table if they exist
        if ($courseTable->hasColumn('submitted_at')) {
            $this->addSql('ALTER TABLE course DROP submitted_at');
        }
        if ($courseTable->hasColumn('reviewed_at')) {
            $this->addSql('ALTER TABLE course DROP reviewed_at');
        }
        if ($courseTable->hasColumn('published_at')) {
            $this->addSql('ALTER TABLE course DROP published_at');
        }
        if ($courseTable->hasColumn('archived_at')) {
            $this->addSql('ALTER TABLE course DROP archived_at');
        }
        if ($courseTable->hasColumn('rejection_reason')) {
            $this->addSql('ALTER TABLE course DROP rejection_reason');
        }
        if ($courseTable->hasColumn('version_number')) {
            $this->addSql('ALTER TABLE course DROP version_number');
        }
        if ($courseTable->hasColumn('is_locked')) {
            $this->addSql('ALTER TABLE course DROP is_locked');
        }
        if ($courseTable->hasColumn('last_modified_by')) {
            $this->addSql('ALTER TABLE course DROP last_modified_by');
        }
        if ($schema->hasTable('favorite')) {
            $this->addSql('ALTER TABLE favorite CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('job')) {
            $this->addSql('ALTER TABLE job CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE deleted_at deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('notification')) {
            $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('order')) {
            $this->addSql('ALTER TABLE `order` CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('product')) {
            $this->addSql('ALTER TABLE product CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE deleted_at deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('question')) {
            $this->addSql('ALTER TABLE question CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        // Handle quiz table - add columns if missing, then modify them
        if ($schema->hasTable('quiz')) {
            $quizTable = $schema->getTable('quiz');
            if (!$quizTable->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE quiz ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            } else {
                $this->addSql('ALTER TABLE quiz CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            }

            if (!$quizTable->hasColumn('updated_at')) {
                $this->addSql('ALTER TABLE quiz ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            } else {
                $this->addSql('ALTER TABLE quiz CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            }
        }
        if ($schema->hasTable('quiz_result')) {
            $this->addSql('ALTER TABLE quiz_result CHANGE taken_at taken_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('quiz_settings')) {
            $this->addSql('ALTER TABLE quiz_settings CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if ($schema->hasTable('messenger_messages')) {
            $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_audit_log (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, changed_by INT NOT NULL, from_status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, to_status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, reason TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ip_address VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, user_agent VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_course_audit_log_course (course_id), INDEX idx_course_audit_log_changed_by (changed_by), INDEX idx_course_audit_log_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE course_version (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, version_number INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, short_description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, requirements TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, learning_outcomes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, target_audience TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, price DOUBLE PRECISION DEFAULT NULL, thumbnail_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, video_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, curriculum_snapshot JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_by INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, publish_status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, version_notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_course_version_course (course_id), INDEX idx_course_version_created_by (created_by), UNIQUE INDEX uniq_course_version (course_id, version_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE application CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE checkout CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE contact CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE course ADD submitted_at DATETIME DEFAULT NULL, ADD reviewed_at DATETIME DEFAULT NULL, ADD published_at DATETIME DEFAULT NULL, ADD archived_at DATETIME DEFAULT NULL, ADD rejection_reason TEXT DEFAULT NULL, ADD version_number INT DEFAULT 1, ADD is_locked TINYINT(1) DEFAULT 0, ADD last_modified_by INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE favorite CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE job CHANGE created_at created_at DATETIME NOT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE notification CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `order` CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE question CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz_result CHANGE taken_at taken_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE quiz_settings CHANGE created_at created_at DATETIME NOT NULL');
    }
}
