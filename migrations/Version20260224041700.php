<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224041700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create roles table and insert default roles';
    }

    public function up(Schema $schema): void
    {
        // Create roles table
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6A5E237E06 ON roles (name)');
        
        // Insert default roles
        $this->addSql("INSERT INTO roles (name) VALUES ('USER'), ('ADMIN'), ('INSTRUCTOR'), ('STUDENT')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE roles');
    }
}
