<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default categories and fix course foreign key';
    }

    public function up(Schema $schema): void
    {
        // Insert default categories if they don't exist
        $this->addSql("INSERT IGNORE INTO category (name, description, icon, color, created_at) VALUES 
            ('Programming', 'Programming and software development courses', 'bi-code-slash', '#007bff', NOW()),
            ('Design', 'Graphic design, UI/UX and creative courses', 'bi-palette', '#e83e8c', NOW()),
            ('Business', 'Business, marketing and entrepreneurship', 'bi-briefcase', '#28a745', NOW()),
            ('Marketing', 'Digital marketing and sales courses', 'bi-megaphone', '#fd7e14', NOW()),
            ('Data Science', 'Data analysis, machine learning and AI', 'bi-graph-up', '#6f42c1', NOW()),
            ('Languages', 'Foreign language learning courses', 'bi-translate', '#20c997', NOW()),
            ('Other', 'Miscellaneous courses', 'bi-three-dots', '#6c757d', NOW())");

        // Update existing courses to have a default category if they don't have one
        $this->addSql('UPDATE course SET category_id = 1 WHERE category_id IS NULL');

        // Add foreign key constraint only if it doesn't exist
        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        }

        // Add index only if it doesn't exist
        if (!$courseTable->hasIndex('IDX_169E6FB912469DE2')) {
            $this->addSql('CREATE INDEX IDX_169E6FB912469DE2 ON course (category_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify to your needs
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY IF EXISTS FK_169E6FB912469DE2');
        $this->addSql('DROP INDEX IF EXISTS IDX_169E6FB912469DE2 ON course');
    }
}
