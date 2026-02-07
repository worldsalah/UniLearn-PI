<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207180320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, job_id INT NOT NULL, INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED9BE04EA9 (job_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trust_score (id INT AUTO_INCREMENT NOT NULL, overall_score DOUBLE PRECISION NOT NULL, behavior_score DOUBLE PRECISION NOT NULL, content_score DOUBLE PRECISION NOT NULL, pricing_score DOUBLE PRECISION NOT NULL, reputation_score DOUBLE PRECISION NOT NULL, score_breakdown JSON NOT NULL, historical_trend JSON NOT NULL, last_updated DATETIME NOT NULL, risk_level VARCHAR(20) NOT NULL, seller_id INT NOT NULL, UNIQUE INDEX UNIQ_5E6F5B878DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE validation_result (id INT AUTO_INCREMENT NOT NULL, validation_type VARCHAR(50) NOT NULL, overall_score DOUBLE PRECISION NOT NULL, risk_level VARCHAR(20) NOT NULL, component_scores JSON NOT NULL, findings JSON NOT NULL, improvement_suggestions JSON NOT NULL, created_at DATETIME NOT NULL, passed TINYINT NOT NULL, seller_id INT NOT NULL, product_id INT DEFAULT NULL, INDEX IDX_973C93C88DE820D9 (seller_id), INDEX IDX_973C93C84584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE trust_score ADD CONSTRAINT FK_5E6F5B878DE820D9 FOREIGN KEY (seller_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE validation_result ADD CONSTRAINT FK_973C93C88DE820D9 FOREIGN KEY (seller_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE validation_result ADD CONSTRAINT FK_973C93C84584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9BE04EA9');
        $this->addSql('ALTER TABLE trust_score DROP FOREIGN KEY FK_5E6F5B878DE820D9');
        $this->addSql('ALTER TABLE validation_result DROP FOREIGN KEY FK_973C93C88DE820D9');
        $this->addSql('ALTER TABLE validation_result DROP FOREIGN KEY FK_973C93C84584665A');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE trust_score');
        $this->addSql('DROP TABLE validation_result');
    }
}
