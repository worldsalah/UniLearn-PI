<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224041613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE learning_roadmap (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, learning_goal VARCHAR(255) NOT NULL, skill_level VARCHAR(50) NOT NULL, time_commitment VARCHAR(50) DEFAULT NULL, learning_styles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', roadmap_content JSON NOT NULL COMMENT \'(DC2Type:json)\', generated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A7645600A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE learning_roadmap ADD CONSTRAINT FK_A7645600A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz DROP duration, DROP opening_date, DROP closing_date');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE learning_roadmap DROP FOREIGN KEY FK_A7645600A76ED395');
        $this->addSql('DROP TABLE learning_roadmap');
        $this->addSql('ALTER TABLE quiz ADD duration INT DEFAULT NULL, ADD opening_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD closing_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
