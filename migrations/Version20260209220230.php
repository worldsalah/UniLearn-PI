<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209220230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Create tables only if they don't exist
        if (!$schema->hasTable('booking')) {
            $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, user_email VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, session_id INT NOT NULL, INDEX IDX_E00CEDDE613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
        if (!$schema->hasTable('session')) {
            $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, level VARCHAR(255) NOT NULL, date DATETIME NOT NULL, duration INT NOT NULL, session_description VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
        
        // Add foreign key only if both tables exist and constraint doesn't exist
        if ($schema->hasTable('booking') && $schema->hasTable('session')) {
            $bookingTable = $schema->getTable('booking');
            if (!$bookingTable->hasForeignKey('FK_E00CEDDE613FECDF')) {
                $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
            }
        }
        
        $courseTable = $schema->getTable('course');
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE613FECDF');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE session');
        
        $courseTable = $schema->getTable('course');
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        }
    }
}
