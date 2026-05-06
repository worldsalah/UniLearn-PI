<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321102200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing icon and color columns to category table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category ADD icon VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD color VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category DROP icon');
        $this->addSql('ALTER TABLE category DROP color');
    }
}
