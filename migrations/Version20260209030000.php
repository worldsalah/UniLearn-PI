<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create roles table';
    }

    public function up(Schema $schema): void
    {
        // Create roles table
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        
        // Insert default roles
        $this->addSql("INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'instructor'), (3, 'student'), (4, 'user')");
    }

    public function down(Schema $schema): void
    {
        // Drop roles table
        $this->addSql("DROP TABLE roles");
    }
}
