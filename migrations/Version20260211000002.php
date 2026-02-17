<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Safe migration for Course Lifecycle System
 */
final class Version20260211000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add course lifecycle columns safely';
    }

    public function up(Schema $schema): void
    {
        // Check if course table exists and add columns one by one
        $this->addSql('
            ALTER TABLE course 
            ADD COLUMN IF NOT EXISTS submitted_at DATETIME DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS reviewed_at DATETIME DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS published_at DATETIME DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS rejection_reason TEXT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS version_number INT DEFAULT 1,
            ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS last_modified_by INT DEFAULT NULL
        ');

        // Update existing course statuses to new format
        $this->addSql("UPDATE course SET status = 'draft' WHERE status = 'inactive' OR status IS NULL");
        $this->addSql("UPDATE course SET status = 'published' WHERE status = 'live'");
        $this->addSql("UPDATE course SET status = 'rejected' WHERE status = 'unaccept'");
        $this->addSql("UPDATE course SET status = 'soft_deleted' WHERE status = 'deleted'");

        // Create audit log table if it doesn't exist
        $this->addSql('
            CREATE TABLE IF NOT EXISTS course_audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                changed_by INT NOT NULL,
                from_status VARCHAR(20) NOT NULL,
                to_status VARCHAR(20) NOT NULL,
                reason TEXT DEFAULT NULL,
                metadata JSON DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                INDEX idx_course_audit_log_course (course_id),
                INDEX idx_course_audit_log_changed_by (changed_by),
                INDEX idx_course_audit_log_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // Create version table if it doesn't exist
        $this->addSql('
            CREATE TABLE IF NOT EXISTS course_version (
                id INT AUTO_INCREMENT PRIMARY KEY,
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
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                publish_status VARCHAR(20) DEFAULT NULL,
                version_notes TEXT DEFAULT NULL,
                INDEX idx_course_version_course (course_id),
                INDEX idx_course_version_created_by (created_by),
                UNIQUE KEY uniq_course_version (course_id, version_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // Add foreign key constraints if they don't exist
        $this->addSql('
            ALTER TABLE course 
            ADD CONSTRAINT IF NOT EXISTS fk_course_last_modified_by 
            FOREIGN KEY (last_modified_by) REFERENCES user (id) ON DELETE SET NULL
        ');

        $this->addSql('
            ALTER TABLE course_audit_log 
            ADD CONSTRAINT IF NOT EXISTS fk_course_audit_log_course 
            FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE course_audit_log 
            ADD CONSTRAINT IF NOT EXISTS fk_course_audit_log_changed_by 
            FOREIGN KEY (changed_by) REFERENCES user (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE course_version 
            ADD CONSTRAINT IF NOT EXISTS fk_course_version_course 
            FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE course_version 
            ADD CONSTRAINT IF NOT EXISTS fk_course_version_created_by 
            FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign keys first
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY IF EXISTS fk_course_last_modified_by');
        $this->addSql('ALTER TABLE course_audit_log DROP FOREIGN KEY IF EXISTS fk_course_audit_log_course');
        $this->addSql('ALTER TABLE course_audit_log DROP FOREIGN KEY IF EXISTS fk_course_audit_log_changed_by');
        $this->addSql('ALTER TABLE course_version DROP FOREIGN KEY IF EXISTS fk_course_version_course');
        $this->addSql('ALTER TABLE course_version DROP FOREIGN KEY IF EXISTS fk_course_version_created_by');

        // Drop new tables
        $this->addSql('DROP TABLE IF EXISTS course_audit_log');
        $this->addSql('DROP TABLE IF EXISTS course_version');

        // Remove new columns from course table
        $this->addSql('
            ALTER TABLE course 
            DROP COLUMN IF EXISTS submitted_at,
            DROP COLUMN IF EXISTS reviewed_at,
            DROP COLUMN IF EXISTS published_at,
            DROP COLUMN IF EXISTS archived_at,
            DROP COLUMN IF EXISTS rejection_reason,
            DROP COLUMN IF EXISTS version_number,
            DROP COLUMN IF EXISTS is_locked,
            DROP COLUMN IF EXISTS last_modified_by
        ');

        // Revert status changes
        $this->addSql("UPDATE course SET status = 'inactive' WHERE status = 'draft'");
        $this->addSql("UPDATE course SET status = 'live' WHERE status = 'published'");
        $this->addSql("UPDATE course SET status = 'unaccept' WHERE status = 'rejected'");
        $this->addSql("UPDATE course SET status = 'deleted' WHERE status = 'soft_deleted'");
    }
}
