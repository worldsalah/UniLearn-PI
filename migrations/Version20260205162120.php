<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205162120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chapter (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, course_id INT NOT NULL, INDEX IDX_F981B52E591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lesson (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, duration VARCHAR(255) NOT NULL, chapter_id INT NOT NULL, INDEX IDX_F87474F3579F4768 (chapter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chapter ADD CONSTRAINT FK_F981B52E591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id)');
        $this->addSql('ALTER TABLE course ADD thumbnail_url VARCHAR(255) DEFAULT NULL, DROP description, DROP created_at, DROP teacher_id, DROP image_path, DROP discount_price, DROP featured, DROP total_lectures, DROP updated_at, DROP tags, DROP reviewer_message, DROP license_checkbox, CHANGE level level VARCHAR(255) NOT NULL, CHANGE short_description short_description LONGTEXT NOT NULL, CHANGE price price DOUBLE PRECISION NOT NULL, CHANGE language language VARCHAR(255) DEFAULT NULL, CHANGE duration duration DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chapter DROP FOREIGN KEY FK_F981B52E591CC992');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3579F4768');
        $this->addSql('DROP TABLE chapter');
        $this->addSql('DROP TABLE lesson');
        $this->addSql('ALTER TABLE course ADD description LONGTEXT NOT NULL, ADD created_at DATETIME NOT NULL, ADD teacher_id INT NOT NULL, ADD discount_price DOUBLE PRECISION DEFAULT NULL, ADD featured TINYINT NOT NULL, ADD total_lectures INT DEFAULT NULL, ADD updated_at DATETIME NOT NULL, ADD tags VARCHAR(255) DEFAULT NULL, ADD reviewer_message LONGTEXT DEFAULT NULL, ADD license_checkbox TINYINT NOT NULL, CHANGE short_description short_description VARCHAR(255) DEFAULT NULL, CHANGE level level VARCHAR(50) NOT NULL, CHANGE price price DOUBLE PRECISION DEFAULT NULL, CHANGE language language JSON DEFAULT NULL, CHANGE duration duration INT DEFAULT NULL, CHANGE thumbnail_url image_path VARCHAR(255) DEFAULT NULL');
    }
}
