<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210023032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT 0 NOT NULL');

        $sessionTable = $schema->getTable('session');

        // Add columns only if they don't exist
        if (!$sessionTable->hasColumn('instructor_id')) {
            $this->addSql('ALTER TABLE session ADD instructor_id INT DEFAULT NULL');
        }
        if (!$sessionTable->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE session ADD category_id INT DEFAULT NULL');
        }

        // Add foreign keys and indexes only if they don't exist
        if (!$sessionTable->hasForeignKey('FK_D044D5D48C4FC193')) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D48C4FC193 FOREIGN KEY (instructor_id) REFERENCES user (id)');
        }
        if (!$sessionTable->hasForeignKey('FK_D044D5D412469DE2')) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        }

        // Add indexes only if they don't exist
        if (!$sessionTable->hasIndex('IDX_D044D5D48C4FC193')) {
            $this->addSql('CREATE INDEX IDX_D044D5D48C4FC193 ON session (instructor_id)');
        }
        if (!$sessionTable->hasIndex('IDX_D044D5D412469DE2')) {
            $this->addSql('CREATE INDEX IDX_D044D5D412469DE2 ON session (category_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course CHANGE image_progress image_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE video_progress video_progress DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D48C4FC193');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D412469DE2');
        $this->addSql('DROP INDEX IDX_D044D5D48C4FC193 ON session');
        $this->addSql('DROP INDEX IDX_D044D5D412469DE2 ON session');
        $this->addSql('ALTER TABLE session DROP instructor_id, DROP category_id');
    }
}
