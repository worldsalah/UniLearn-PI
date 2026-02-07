<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207184310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, cover_letter LONGTEXT NOT NULL, proposed_budget NUMERIC(10, 2) NOT NULL, timeline VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, job_id INT NOT NULL, freelancer_id INT NOT NULL, INDEX IDX_A45BDDC1BE04EA9 (job_id), INDEX IDX_A45BDDC18545BDF5 (freelancer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC18545BDF5 FOREIGN KEY (freelancer_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1BE04EA9');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC18545BDF5');
        $this->addSql('DROP TABLE application');
    }
}
