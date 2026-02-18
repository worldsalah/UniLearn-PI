<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210011701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add start_date and end_date fields to session table for date range support';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking CHANGE session_id session_id INT DEFAULT NULL');
        
        $courseTable = $schema->getTable('course');
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
        
        // Add columns only if they don't exist
        $sessionTable = $schema->getTable('session');
        if (!$sessionTable->hasColumn('start_date')) {
            $this->addSql('ALTER TABLE session ADD start_date DATETIME DEFAULT NULL');
        }
        if (!$sessionTable->hasColumn('end_date')) {
            $this->addSql('ALTER TABLE session ADD end_date DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking CHANGE session_id session_id INT NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE session DROP start_date, DROP end_date');
    }
}
