<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207152926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE instructor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, bio VARCHAR(500) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, verified TINYINT NOT NULL, rating NUMERIC(2, 1) NOT NULL, total_students INT NOT NULL, total_courses INT NOT NULL, instructor VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_31FC43DDE7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE quiz ADD instructor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA928C4FC193 FOREIGN KEY (instructor_id) REFERENCES instructor (id)');
        $this->addSql('CREATE INDEX IDX_A412FA928C4FC193 ON quiz (instructor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE instructor');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA928C4FC193');
        $this->addSql('DROP INDEX IDX_A412FA928C4FC193 ON quiz');
        $this->addSql('ALTER TABLE quiz DROP instructor_id');
    }
}
