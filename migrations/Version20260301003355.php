<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301003355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE availability (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', teacher_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', day_of_week SMALLINT NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_3FB7A2BF41807E1D (teacher_id), UNIQUE INDEX unique_availability (teacher_id, day_of_week, start_time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bundle (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', student_id INT NOT NULL, teacher_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(20) NOT NULL, sessions_total INT NOT NULL, sessions_used INT DEFAULT 0 NOT NULL, price NUMERIC(10, 2) NOT NULL, expires_at DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT \'active\' NOT NULL, purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_A57B32FDCB944F1A (student_id), INDEX IDX_A57B32FD41807E1D (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bundle_usage (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', bundle_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', booking_id INT NOT NULL, used_at DATETIME NOT NULL, INDEX IDX_4BB3EBDBF1FAD9D3 (bundle_id), UNIQUE INDEX UNIQ_4BB3EBDB3301C60 (booking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', tutoring_session_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', student_id INT NOT NULL, teacher_id INT NOT NULL, rating SMALLINT NOT NULL, comment LONGTEXT DEFAULT NULL, is_public TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_794381C68B567663 (tutoring_session_id), INDEX IDX_794381C6CB944F1A (student_id), INDEX IDX_794381C641807E1D (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE teacher_profile (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id INT NOT NULL, subjects JSON NOT NULL COMMENT \'(DC2Type:json)\', hourly_rate NUMERIC(10, 2) NOT NULL, bio LONGTEXT DEFAULT NULL, education VARCHAR(255) DEFAULT NULL, experience_years INT DEFAULT 0 NOT NULL, rating_avg NUMERIC(3, 2) DEFAULT \'0\' NOT NULL, review_count INT DEFAULT 0 NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_4C95274EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE time_slot (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', availability_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, status VARCHAR(20) DEFAULT \'available\' NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_1B3294A61778466 (availability_id), UNIQUE INDEX unique_slot (availability_id, date, start_time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tutoring_session (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', booking_id INT NOT NULL, started_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, actual_duration INT DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'scheduled\' NOT NULL, notes LONGTEXT DEFAULT NULL, recording_url VARCHAR(500) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_DD9D67613301C60 (booking_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BF41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher_profile (id)');
        $this->addSql('ALTER TABLE bundle ADD CONSTRAINT FK_A57B32FDCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bundle ADD CONSTRAINT FK_A57B32FD41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher_profile (id)');
        $this->addSql('ALTER TABLE bundle_usage ADD CONSTRAINT FK_4BB3EBDBF1FAD9D3 FOREIGN KEY (bundle_id) REFERENCES bundle (id)');
        $this->addSql('ALTER TABLE bundle_usage ADD CONSTRAINT FK_4BB3EBDB3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C68B567663 FOREIGN KEY (tutoring_session_id) REFERENCES tutoring_session (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C641807E1D FOREIGN KEY (teacher_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE teacher_profile ADD CONSTRAINT FK_4C95274EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE time_slot ADD CONSTRAINT FK_1B3294A61778466 FOREIGN KEY (availability_id) REFERENCES availability (id)');
        $this->addSql('ALTER TABLE tutoring_session ADD CONSTRAINT FK_DD9D67613301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE availability DROP FOREIGN KEY FK_3FB7A2BF41807E1D');
        $this->addSql('ALTER TABLE bundle DROP FOREIGN KEY FK_A57B32FDCB944F1A');
        $this->addSql('ALTER TABLE bundle DROP FOREIGN KEY FK_A57B32FD41807E1D');
        $this->addSql('ALTER TABLE bundle_usage DROP FOREIGN KEY FK_4BB3EBDBF1FAD9D3');
        $this->addSql('ALTER TABLE bundle_usage DROP FOREIGN KEY FK_4BB3EBDB3301C60');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C68B567663');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6CB944F1A');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C641807E1D');
        $this->addSql('ALTER TABLE teacher_profile DROP FOREIGN KEY FK_4C95274EA76ED395');
        $this->addSql('ALTER TABLE time_slot DROP FOREIGN KEY FK_1B3294A61778466');
        $this->addSql('ALTER TABLE tutoring_session DROP FOREIGN KEY FK_DD9D67613301C60');
        $this->addSql('DROP TABLE availability');
        $this->addSql('DROP TABLE bundle');
        $this->addSql('DROP TABLE bundle_usage');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE teacher_profile');
        $this->addSql('DROP TABLE time_slot');
        $this->addSql('DROP TABLE tutoring_session');
    }
}
