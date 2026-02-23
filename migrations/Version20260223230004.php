<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add foreign key constraint to learning_roadmap table (SQL version)
 */
final class Version20260223230004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraint to learning_roadmap table (SQL)';
    }

    public function up(Schema $schema): void
    {
        // Add foreign key using raw SQL
        $this->addSql('ALTER TABLE learning_roadmap ADD CONSTRAINT fk_learning_roadmap_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key using raw SQL
        $this->addSql('ALTER TABLE learning_roadmap DROP FOREIGN KEY fk_learning_roadmap_user');
    }
}
