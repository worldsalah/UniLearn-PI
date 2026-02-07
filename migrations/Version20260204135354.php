<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204135354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course ADD short_description VARCHAR(255) DEFAULT NULL, ADD category VARCHAR(255) NOT NULL, ADD video_url VARCHAR(255) DEFAULT NULL, ADD image_path VARCHAR(255) DEFAULT NULL, ADD price DOUBLE PRECISION DEFAULT NULL, ADD discount_price DOUBLE PRECISION DEFAULT NULL, ADD featured TINYINT NOT NULL, ADD language VARCHAR(255) DEFAULT NULL, ADD duration INT DEFAULT NULL, ADD total_lectures INT DEFAULT NULL, ADD updated_at DATETIME NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE level level VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course DROP short_description, DROP category, DROP video_url, DROP image_path, DROP price, DROP discount_price, DROP featured, DROP language, DROP duration, DROP total_lectures, DROP updated_at, CHANGE description description VARCHAR(255) NOT NULL, CHANGE level level VARCHAR(255) NOT NULL');
    }
}
