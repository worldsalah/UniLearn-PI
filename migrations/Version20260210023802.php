<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210023802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP updated_at');
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }
}
