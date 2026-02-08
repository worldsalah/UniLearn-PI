<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208205022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL, DROP first_name, DROP last_name, CHANGE full_name full_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE username username VARCHAR(180) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD first_name VARCHAR(50) DEFAULT NULL, ADD last_name VARCHAR(50) DEFAULT NULL, DROP roles, CHANGE email email VARCHAR(100) NOT NULL, CHANGE username username VARCHAR(50) DEFAULT NULL, CHANGE full_name full_name VARCHAR(100) NOT NULL');
    }
}
