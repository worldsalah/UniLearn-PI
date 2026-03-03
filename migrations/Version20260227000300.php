<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create certificate table';
    }

    public function up(Schema $schema): void
    {
        // Certificate table already exists - skipping creation
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4AA76ED395');
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4A1C7C7A5');
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4A591CC992');
        $this->addSql('DROP TABLE certificate');
    }
}
