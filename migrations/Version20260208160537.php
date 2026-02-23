<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208160537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Create tables only if they don't exist
        if (!$schema->hasTable('application')) {
            $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, cover_letter LONGTEXT NOT NULL, proposed_budget NUMERIC(10, 2) NOT NULL, timeline VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, job_id INT NOT NULL, freelancer_id INT NOT NULL, INDEX IDX_A45BDDC1BE04EA9 (job_id), INDEX IDX_A45BDDC18545BDF5 (freelancer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!$schema->hasTable('favorite')) {
            $this->addSql('CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, job_id INT NOT NULL, INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED9BE04EA9 (job_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!$schema->hasTable('job')) {
            $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, budget DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, client_id INT NOT NULL, INDEX IDX_FBD8E0F819EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!$schema->hasTable('order')) {
            $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, rating INT DEFAULT NULL, review LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, buyer_id INT NOT NULL, INDEX IDX_F52993984584665A (product_id), INDEX IDX_F52993986C755722 (buyer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!$schema->hasTable('product')) {
            $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, category VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, slug VARCHAR(255) NOT NULL, freelancer_id INT NOT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04AD8545BDF5 (freelancer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        // Add foreign keys only if tables exist and constraints don't exist
        if ($schema->hasTable('application') && $schema->hasTable('job')) {
            $appTable = $schema->getTable('application');
            if (!$appTable->hasForeignKey('FK_A45BDDC1BE04EA9')) {
                $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
            }
        }

        if ($schema->hasTable('application') && $schema->hasTable('user')) {
            $appTable = $schema->getTable('application');
            if (!$appTable->hasForeignKey('FK_A45BDDC18545BDF5')) {
                $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC18545BDF5 FOREIGN KEY (freelancer_id) REFERENCES user (id)');
            }
        }

        if ($schema->hasTable('favorite')) {
            $favTable = $schema->getTable('favorite');
            if (!$favTable->hasForeignKey('FK_68C58ED9A76ED395')) {
                $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            }
            if (!$favTable->hasForeignKey('FK_68C58ED9BE04EA9')) {
                $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
            }
        }

        if ($schema->hasTable('job')) {
            $jobTable = $schema->getTable('job');
            if (!$jobTable->hasForeignKey('FK_FBD8E0F819EB6921')) {
                $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F819EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
            }
        }

        if ($schema->hasTable('order')) {
            $orderTable = $schema->getTable('order');
            if (!$orderTable->hasForeignKey('FK_F52993984584665A')) {
                $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
            }
            if (!$orderTable->hasForeignKey('FK_F52993986C755722')) {
                $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES user (id)');
            }
        }

        if ($schema->hasTable('product')) {
            $productTable = $schema->getTable('product');
            if (!$productTable->hasForeignKey('FK_D34A04AD8545BDF5')) {
                $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES user (id)');
            }
        }

        // Drop tables only if they exist
        if ($schema->hasTable('booking')) {
            $this->addSql('DROP TABLE booking');
        }
        if ($schema->hasTable('booking_slot')) {
            $this->addSql('DROP TABLE booking_slot');
        }
        if ($schema->hasTable('certificate')) {
            $this->addSql('DROP TABLE certificate');
        }

        // Only modify columns if they exist
        $courseTable = $schema->getTable('course');
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, student_id INT NOT NULL, slot_id INT NOT NULL, INDEX IDX_E00CEDDE59E5119C (slot_id), INDEX IDX_E00CEDDECB944F1A (student_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE booking_slot (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, is_available TINYINT DEFAULT 1 NOT NULL, freelancer_id INT NOT NULL, INDEX IDX_B49F02298545BDF5 (freelancer_id), UNIQUE INDEX uniq_slot_freelancer_start (freelancer_id, start_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE certificate (id INT AUTO_INCREMENT NOT NULL, issued_at DATETIME NOT NULL, verification_code VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, pdf_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, student_id INT NOT NULL, course_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, INDEX IDX_219CDA4ACB944F1A (student_id), INDEX IDX_219CDA4A591CC992 (course_id), INDEX IDX_219CDA4A853CD175 (quiz_id), UNIQUE INDEX UNIQ_219CDA4AE821C39F (verification_code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1BE04EA9');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC18545BDF5');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9BE04EA9');
        $this->addSql('ALTER TABLE job DROP FOREIGN KEY FK_FBD8E0F819EB6921');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993986C755722');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8545BDF5');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }
}
