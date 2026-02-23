<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210015700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $bookingTable = $schema->getTable('booking');

        // Add columns only if they don't exist
        if (!$bookingTable->hasColumn('preferred_date')) {
            $this->addSql('ALTER TABLE booking ADD preferred_date DATE DEFAULT NULL');
        }
        if (!$bookingTable->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE booking ADD user_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_E00CEDDEA76ED395 ON booking (user_id)');
        }

        // Add progress columns to course table if they don't exist
        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasColumn('image_progress')) {
            $this->addSql('ALTER TABLE course ADD image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
        if (!$courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course ADD video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('DROP INDEX IDX_E00CEDDEA76ED395 ON booking');
        $this->addSql('ALTER TABLE booking DROP preferred_date, DROP user_id');
        // Drop progress columns from course table if they exist
        $courseTable = $schema->getTable('course');
        if ($courseTable->hasColumn('image_progress')) {
            $this->addSql('ALTER TABLE course DROP image_progress');
        }
        if ($courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course DROP video_progress');
        }
    }
}
