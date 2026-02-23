<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209193839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable('chapter');
        if (!$table->hasColumn('sort_order')) {
            $this->addSql('ALTER TABLE chapter ADD sort_order INT NOT NULL');
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
        $this->addSql('ALTER TABLE chapter DROP sort_order');

        $courseTable = $schema->getTable('course');
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        }
    }
}
