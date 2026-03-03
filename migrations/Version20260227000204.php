<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227000204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Certificate table already exists, just add missing foreign key if needed
        $this->addSql('ALTER TABLE enrollment CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL, CHANGE user_agent user_agent LONGTEXT DEFAULT NULL');
        // Skip lesson_completion index changes as they may already exist
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4AA76ED395');
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4A1C7C7A5');
        $this->addSql('ALTER TABLE certificate DROP FOREIGN KEY FK_219CDA4A591CC992');
        $this->addSql('DROP TABLE certificate');
        $this->addSql('ALTER TABLE enrollment CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL COMMENT \'Student IP address at time of enrollment\', CHANGE user_agent user_agent TEXT DEFAULT NULL COMMENT \'Student browser user agent at time of enrollment\'');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFA76ED395');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFA76ED395');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
        $this->addSql('CREATE UNIQUE INDEX uniq_lesson_completion_user_lesson ON lesson_completion (user_id, lesson_id)');
        $this->addSql('DROP INDEX idx_35df7eafcdf80196 ON lesson_completion');
        $this->addSql('CREATE INDEX idx_lesson_completion_lesson_id ON lesson_completion (lesson_id)');
        $this->addSql('DROP INDEX idx_35df7eaf591cc992 ON lesson_completion');
        $this->addSql('CREATE INDEX idx_lesson_completion_course_id ON lesson_completion (course_id)');
        $this->addSql('DROP INDEX idx_35df7eafa76ed395 ON lesson_completion');
        $this->addSql('CREATE INDEX idx_lesson_completion_user_id ON lesson_completion (user_id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAF591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8545BDF5');
    }
}
