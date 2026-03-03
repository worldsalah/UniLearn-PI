<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301061459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE meeting_feedback (id INT AUTO_INCREMENT NOT NULL, booking_id INT NOT NULL, user_id INT NOT NULL, satisfaction_rating INT NOT NULL, call_quality_rating INT NOT NULL, learning_style_rating INT NOT NULL, comments LONGTEXT DEFAULT NULL, user_role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_41B8698D3301C60 (booking_id), INDEX IDX_41B8698DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE meeting_feedback ADD CONSTRAINT FK_41B8698D3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE meeting_feedback ADD CONSTRAINT FK_41B8698DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE teacher_profile CHANGE rating_avg rating_avg NUMERIC(3, 2) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meeting_feedback DROP FOREIGN KEY FK_41B8698D3301C60');
        $this->addSql('ALTER TABLE meeting_feedback DROP FOREIGN KEY FK_41B8698DA76ED395');
        $this->addSql('DROP TABLE meeting_feedback');
        $this->addSql('ALTER TABLE teacher_profile CHANGE rating_avg rating_avg NUMERIC(3, 2) DEFAULT \'0.00\' NOT NULL');
    }
}
