<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227203321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE certificate CHANGE generated_at generated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_downloaded is_downloaded TINYINT(1) NOT NULL, CHANGE last_downloaded_at last_downloaded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE download_count download_count INT NOT NULL');
        $this->addSql('ALTER TABLE course_test ADD test_type VARCHAR(20) DEFAULT \'completion\' NOT NULL');
        $this->addSql('ALTER TABLE course_test_question CHANGE difficulty difficulty VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE enrollment ADD starting_lesson_id INT DEFAULT NULL, ADD placement_test_result_id INT DEFAULT NULL, ADD placement_test_taken TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1CB7D0048 FOREIGN KEY (starting_lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1A2BAD590 FOREIGN KEY (placement_test_result_id) REFERENCES course_test_result (id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1CB7D0048 ON enrollment (starting_lesson_id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1A2BAD590 ON enrollment (placement_test_result_id)');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFA76ED395');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
        $this->addSql('DROP INDEX idx_lesson_completion_user_id ON lesson_completion');
        $this->addSql('CREATE INDEX IDX_35DF7EAFA76ED395 ON lesson_completion (user_id)');
        $this->addSql('DROP INDEX idx_lesson_completion_lesson_id ON lesson_completion');
        $this->addSql('CREATE INDEX IDX_35DF7EAFCDF80196 ON lesson_completion (lesson_id)');
        $this->addSql('DROP INDEX idx_lesson_completion_course_id ON lesson_completion');
        $this->addSql('CREATE INDEX IDX_35DF7EAF591CC992 ON lesson_completion (course_id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAFCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE lesson_completion ADD CONSTRAINT FK_35DF7EAF591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8545BDF5 FOREIGN KEY (freelancer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user DROP google_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE certificate CHANGE generated_at generated_at DATETIME NOT NULL, CHANGE is_downloaded is_downloaded TINYINT(1) DEFAULT 0 NOT NULL, CHANGE last_downloaded_at last_downloaded_at DATETIME DEFAULT NULL, CHANGE download_count download_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE course_test DROP test_type');
        $this->addSql('ALTER TABLE course_test_question CHANGE difficulty difficulty VARCHAR(20) DEFAULT \'medium\' NOT NULL');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1CB7D0048');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E1A2BAD590');
        $this->addSql('DROP INDEX IDX_DBDCD7E1CB7D0048 ON enrollment');
        $this->addSql('DROP INDEX IDX_DBDCD7E1A2BAD590 ON enrollment');
        $this->addSql('ALTER TABLE enrollment DROP starting_lesson_id, DROP placement_test_result_id, DROP placement_test_taken');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFA76ED395');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAFCDF80196');
        $this->addSql('ALTER TABLE lesson_completion DROP FOREIGN KEY FK_35DF7EAF591CC992');
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
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(255) DEFAULT NULL');
    }
}
