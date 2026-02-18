<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207172503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(100) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // Insert default categories
        $this->addSql("INSERT INTO category (name, description, icon, color, created_at) VALUES 
            ('Programming', 'Programming and software development courses', 'bi-code-slash', '#007bff', NOW()),
            ('Design', 'Graphic design, UI/UX and creative courses', 'bi-palette', '#e83e8c', NOW()),
            ('Business', 'Business, marketing and entrepreneurship', 'bi-briefcase', '#28a745', NOW()),
            ('Marketing', 'Digital marketing and sales courses', 'bi-megaphone', '#fd7e14', NOW()),
            ('Data Science', 'Data analysis, machine learning and AI', 'bi-graph-up', '#6f42c1', NOW()),
            ('Languages', 'Foreign language learning courses', 'bi-translate', '#20c997', NOW()),
            ('Other', 'Miscellaneous courses', 'bi-three-dots', '#6c757d', NOW())");

        // Make category_id nullable temporarily to avoid foreign key constraint issues
        $this->addSql('ALTER TABLE course ADD category_id INT DEFAULT NULL');
        $this->addSql('UPDATE course SET category_id = 1 WHERE category IS NOT NULL');
        $this->addSql('ALTER TABLE course DROP category');
        $this->addSql('ALTER TABLE course CHANGE category_id category_id INT NOT NULL');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB912469DE2 ON course (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB912469DE2');
        $this->addSql('DROP INDEX IDX_169E6FB912469DE2 ON course');
        $this->addSql('ALTER TABLE course ADD category VARCHAR(255) NOT NULL, DROP category_id, CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }
}
