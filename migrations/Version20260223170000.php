<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cascade delete to quiz_statistics foreign key constraint';
    }

    public function up(Schema $schema): void
    {
        // Drop existing foreign key constraint
        $this->addSql('ALTER TABLE quiz_statistics DROP FOREIGN KEY FK_ABBCAC1E853CD175');
        
        // Add foreign key constraint with CASCADE DELETE
        $this->addSql('ALTER TABLE quiz_statistics ADD CONSTRAINT FK_ABBCAC1E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop the cascade delete constraint
        $this->addSql('ALTER TABLE quiz_statistics DROP FOREIGN KEY FK_ABBCAC1E853CD175');
        
        // Add back the original constraint without cascade
        $this->addSql('ALTER TABLE quiz_statistics ADD CONSTRAINT FK_ABBCAC1E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }
}
