<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209190359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $courseTable = $schema->getTable('course');
        
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
        
        // Check if columns exist before adding them
        $table = $schema->getTable('user');
        if (!$table->hasColumn('bio')) {
            $this->addSql('ALTER TABLE user ADD bio LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('phone')) {
            $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL');
        }
        if (!$table->hasColumn('location')) {
            $this->addSql('ALTER TABLE user ADD location VARCHAR(100) DEFAULT NULL');
        }
        if (!$table->hasColumn('website')) {
            $this->addSql('ALTER TABLE user ADD website VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('profile_image')) {
            $this->addSql('ALTER TABLE user ADD profile_image VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $courseTable = $schema->getTable('course');
        
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        }
        
        $this->addSql('ALTER TABLE user DROP bio, DROP phone, DROP location, DROP website, DROP profile_image');
    }
}
