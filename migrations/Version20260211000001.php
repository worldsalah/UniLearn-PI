<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Advanced Course Lifecycle Management System
 */
final class Version20260211000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add advanced course lifecycle management with audit logging and version control';
    }

    public function up(Schema $schema): void
    {
        // Update course table with enhanced status field
        $this->addSql('ALTER TABLE course CHANGE status status VARCHAR(20) NOT NULL DEFAULT \'draft\'');
        $this->addSql('ALTER TABLE course ADD submitted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD reviewed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD published_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD archived_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD rejection_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD version_number INT DEFAULT 1');
        $this->addSql('ALTER TABLE course ADD is_locked BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE course ADD last_modified_by INT DEFAULT NULL');

        // Create course_audit_log table
        $this->addSql('
            CREATE TABLE course_audit_log (
                id INT AUTO_INCREMENT NOT NULL,
                course_id INT NOT NULL,
                changed_by INT NOT NULL,
                from_status VARCHAR(20) NOT NULL,
                to_status VARCHAR(20) NOT NULL,
                reason TEXT DEFAULT NULL,
                metadata JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                INDEX IDX_COURSE_AUDIT_LOG_COURSE (course_id),
                INDEX IDX_COURSE_AUDIT_LOG_CHANGED_BY (changed_by),
                INDEX IDX_COURSE_AUDIT_LOG_CREATED_AT (created_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Create course_version table
        $this->addSql('
            CREATE TABLE course_version (
                id INT AUTO_INCREMENT NOT NULL,
                course_id INT NOT NULL,
                version_number INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                short_description TEXT NOT NULL,
                requirements TEXT DEFAULT NULL,
                learning_outcomes TEXT DEFAULT NULL,
                target_audience TEXT DEFAULT NULL,
                price DOUBLE PRECISION DEFAULT NULL,
                thumbnail_url VARCHAR(255) DEFAULT NULL,
                video_url VARCHAR(255) DEFAULT NULL,
                curriculum_snapshot JSON DEFAULT NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL,
                publish_status VARCHAR(20) DEFAULT NULL,
                version_notes TEXT DEFAULT NULL,
                INDEX IDX_COURSE_VERSION_COURSE (course_id),
                INDEX IDX_COURSE_VERSION_CREATED_BY (created_by),
                UNIQUE INDEX uniq_course_version (course_id, version_number),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_COURSE_LAST_MODIFIED_BY FOREIGN KEY (last_modified_by) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course_audit_log ADD CONSTRAINT FK_COURSE_AUDIT_LOG_COURSE FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_audit_log ADD CONSTRAINT FK_COURSE_AUDIT_LOG_CHANGED_BY FOREIGN KEY (changed_by) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_version ADD CONSTRAINT FK_COURSE_VERSION_COURSE FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_version ADD CONSTRAINT FK_COURSE_VERSION_CREATED_BY FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE CASCADE');

        // Update existing courses to have proper status
        $this->addSql("UPDATE course SET status = 'draft' WHERE status = 'inactive'");
        $this->addSql("UPDATE course SET status = 'published' WHERE status = 'live'");
        $this->addSql("UPDATE course SET status = 'rejected' WHERE status = 'unaccept'");
        $this->addSql("UPDATE course SET status = 'soft_deleted' WHERE status = 'deleted'");
    }

    public function down(Schema $schema): void
    {
        // Drop foreign keys
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_COURSE_LAST_MODIFIED_BY');
        $this->addSql('ALTER TABLE course_audit_log DROP FOREIGN KEY FK_COURSE_AUDIT_LOG_COURSE');
        $this->addSql('ALTER TABLE course_audit_log DROP FOREIGN KEY FK_COURSE_AUDIT_LOG_CHANGED_BY');
        $this->addSql('ALTER TABLE course_version DROP FOREIGN KEY FK_COURSE_VERSION_COURSE');
        $this->addSql('ALTER TABLE course_version DROP FOREIGN KEY FK_COURSE_VERSION_CREATED_BY');

        // Drop new tables
        $this->addSql('DROP TABLE course_audit_log');
        $this->addSql('DROP TABLE course_version');

        // Remove new columns from course table
        $this->addSql('ALTER TABLE course DROP submitted_at');
        $this->addSql('ALTER TABLE course DROP reviewed_at');
        $this->addSql('ALTER TABLE course DROP published_at');
        $this->addSql('ALTER TABLE course DROP archived_at');
        $this->addSql('ALTER TABLE course DROP rejection_reason');
        $this->addSql('ALTER TABLE course DROP version_number');
        $this->addSql('ALTER TABLE course DROP is_locked');
        $this->addSql('ALTER TABLE course DROP last_modified_by');

        // Revert status changes
        $this->addSql("UPDATE course SET status = 'inactive' WHERE status = 'draft'");
        $this->addSql("UPDATE course SET status = 'live' WHERE status = 'published'");
        $this->addSql("UPDATE course SET status = 'unaccept' WHERE status = 'rejected'");
        $this->addSql("UPDATE course SET status = 'deleted' WHERE status = 'soft_deleted'");
    }
}
