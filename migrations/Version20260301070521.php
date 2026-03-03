<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301070521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher_profile CHANGE rating_avg rating_avg NUMERIC(3, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE user ADD face_encoding LONGTEXT DEFAULT NULL, ADD face_enabled TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher_profile CHANGE rating_avg rating_avg NUMERIC(3, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE user DROP face_encoding, DROP face_enabled');
    }
}
