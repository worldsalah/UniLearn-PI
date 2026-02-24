<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223184803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(100) NOT NULL, icon VARCHAR(255) DEFAULT NULL, color VARCHAR(7) NOT NULL, category VARCHAR(20) NOT NULL, points_required INT NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, badge_order INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_FEF0481D77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_badge (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, badge_id INT NOT NULL, earned_at DATETIME NOT NULL, earned_reason LONGTEXT DEFAULT NULL, progress INT DEFAULT 1 NOT NULL, INDEX IDX_1C32B345A76ED395 (user_id), INDEX IDX_1C32B345F7A2C2FC (badge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, code VARCHAR(50) NOT NULL, min_xp INT NOT NULL, max_xp INT NOT NULL, color VARCHAR(7) NOT NULL, icon VARCHAR(100) DEFAULT NULL, level_order INT NOT NULL, UNIQUE INDEX UNIQ_7828374B77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_points (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, current_level_id INT DEFAULT NULL, total_points INT DEFAULT 0 NOT NULL, current_level_points INT DEFAULT 0 NOT NULL, last_updated DATETIME NOT NULL, rank_position INT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_42E89514A76ED395 (user_id), INDEX IDX_42E89514C2C9318D (current_level_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE user_points ADD CONSTRAINT FK_42E89514A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_points ADD CONSTRAINT FK_42E89514C2C9318D FOREIGN KEY (current_level_id) REFERENCES user_level (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345A76ED395');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345F7A2C2FC');
        $this->addSql('ALTER TABLE user_points DROP FOREIGN KEY FK_42E89514A76ED395');
        $this->addSql('ALTER TABLE user_points DROP FOREIGN KEY FK_42E89514C2C9318D');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE user_badge');
        $this->addSql('DROP TABLE user_level');
        $this->addSql('DROP TABLE user_points');
    }
}
