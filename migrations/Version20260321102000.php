<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns (icon, color, description, created_at) to category table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD icon VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD color VARCHAR(7) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category DROP description');
        $this->addSql('ALTER TABLE category DROP icon');
        $this->addSql('ALTER TABLE category DROP color');
        $this->addSql('ALTER TABLE category DROP created_at');
    }
}
