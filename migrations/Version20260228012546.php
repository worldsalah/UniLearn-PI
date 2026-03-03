<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228012546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAF591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAF591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
    }
}
