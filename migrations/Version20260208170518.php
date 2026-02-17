<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208170518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_slot');
        $this->addSql('DROP TABLE certificate');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE job ADD slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F8989D9B62 ON job (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, student_id INT NOT NULL, slot_id INT NOT NULL, INDEX IDX_E00CEDDECB944F1A (student_id), INDEX IDX_E00CEDDE59E5119C (slot_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE booking_slot (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, is_available TINYINT DEFAULT 1 NOT NULL, freelancer_id INT NOT NULL, UNIQUE INDEX uniq_slot_freelancer_start (freelancer_id, start_at), INDEX IDX_B49F02298545BDF5 (freelancer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE certificate (id INT AUTO_INCREMENT NOT NULL, issued_at DATETIME NOT NULL, verification_code VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, pdf_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, student_id INT NOT NULL, course_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, INDEX IDX_219CDA4A853CD175 (quiz_id), UNIQUE INDEX UNIQ_219CDA4AE821C39F (verification_code), INDEX IDX_219CDA4ACB944F1A (student_id), INDEX IDX_219CDA4A591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('DROP INDEX UNIQ_FBD8E0F8989D9B62 ON job');
        $this->addSql('ALTER TABLE job DROP slug');
    }
}
