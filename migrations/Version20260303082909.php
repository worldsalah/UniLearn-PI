<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303082909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE availability DROP FOREIGN KEY FK_3FB7A2BF41807E1D');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BF41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bundle_usage DROP FOREIGN KEY FK_4BB3EBDBF1FAD9D3');
        $this->addSql('ALTER TABLE bundle_usage ADD CONSTRAINT FK_4BB3EBDBF1FAD9D3 FOREIGN KEY (bundle_id) REFERENCES bundle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chapter DROP FOREIGN KEY FK_F981B52E591CC992');
        $this->addSql('ALTER TABLE chapter ADD CONSTRAINT FK_F981B52E591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_test DROP FOREIGN KEY FK_10DDA30A591CC992');
        $this->addSql('ALTER TABLE course_test ADD CONSTRAINT FK_10DDA30A591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_test_question DROP FOREIGN KEY FK_E01906C1169E01B5');
        $this->addSql('ALTER TABLE course_test_question ADD CONSTRAINT FK_E01906C1169E01B5 FOREIGN KEY (course_test_id) REFERENCES course_test (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz CHANGE course_id course_id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE time_slot DROP FOREIGN KEY FK_1B3294A61778466');
        $this->addSql('ALTER TABLE time_slot ADD CONSTRAINT FK_1B3294A61778466 FOREIGN KEY (availability_id) REFERENCES availability (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE income income NUMERIC(12, 2) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE availability DROP FOREIGN KEY FK_3FB7A2BF41807E1D');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BF41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher_profile (id)');
        $this->addSql('ALTER TABLE bundle_usage DROP FOREIGN KEY FK_4BB3EBDBF1FAD9D3');
        $this->addSql('ALTER TABLE bundle_usage ADD CONSTRAINT FK_4BB3EBDBF1FAD9D3 FOREIGN KEY (bundle_id) REFERENCES bundle (id)');
        $this->addSql('ALTER TABLE chapter DROP FOREIGN KEY FK_F981B52E591CC992');
        $this->addSql('ALTER TABLE chapter ADD CONSTRAINT FK_F981B52E591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_test DROP FOREIGN KEY FK_10DDA30A591CC992');
        $this->addSql('ALTER TABLE course_test ADD CONSTRAINT FK_10DDA30A591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_test_question DROP FOREIGN KEY FK_E01906C1169E01B5');
        $this->addSql('ALTER TABLE course_test_question ADD CONSTRAINT FK_E01906C1169E01B5 FOREIGN KEY (course_test_id) REFERENCES course_test (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92591CC992');
        $this->addSql('ALTER TABLE quiz CHANGE course_id course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE time_slot DROP FOREIGN KEY FK_1B3294A61778466');
        $this->addSql('ALTER TABLE time_slot ADD CONSTRAINT FK_1B3294A61778466 FOREIGN KEY (availability_id) REFERENCES availability (id)');
        $this->addSql('ALTER TABLE user CHANGE income income NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL');
    }
}
