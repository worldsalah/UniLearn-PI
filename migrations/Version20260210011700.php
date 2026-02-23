<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210011700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make phone_number column nullable in booking table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking MODIFY COLUMN phone_number VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking MODIFY COLUMN phone_number VARCHAR(255) NOT NULL');
    }
}
