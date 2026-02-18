<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207172905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $courseTable = $schema->getTable('course');
        
        if ($courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course DROP FOREIGN KEY `FK_169E6FB912469DE2`');
        }
        
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
        }
        
        if ($courseTable->hasIndex('fk_169e6fb912469de2')) {
            $this->addSql('DROP INDEX fk_169e6fb912469de2 ON course');
        }
        
        if (!$courseTable->hasIndex('IDX_169E6FB912469DE2')) {
            $this->addSql('CREATE INDEX IDX_169E6FB912469DE2 ON course (category_id)');
        }
        
        if (!$courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course ADD CONSTRAINT `FK_169E6FB912469DE2` FOREIGN KEY (category_id) REFERENCES category (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $courseTable = $schema->getTable('course');
        
        if ($courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB912469DE2');
        }
        
        // Only modify columns if they exist
        if ($courseTable->hasColumn('image_progress') && $courseTable->hasColumn('video_progress')) {
            $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        }
        
        if ($courseTable->hasIndex('idx_169e6fb912469de2')) {
            $this->addSql('DROP INDEX idx_169e6fb912469de2 ON course');
        }
        
        if (!$courseTable->hasIndex('FK_169E6FB912469DE2')) {
            $this->addSql('CREATE INDEX FK_169E6FB912469DE2 ON course (category_id)');
        }
        
        if (!$courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        }
    }
}
