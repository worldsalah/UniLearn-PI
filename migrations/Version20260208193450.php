<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208193450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD first_name VARCHAR(50) DEFAULT NULL, ADD last_name VARCHAR(50) DEFAULT NULL, ADD username VARCHAR(50) DEFAULT NULL, ADD phone VARCHAR(20) DEFAULT NULL, ADD location VARCHAR(100) DEFAULT NULL, ADD about_me LONGTEXT DEFAULT NULL, ADD education JSON DEFAULT NULL, ADD profile_picture VARCHAR(255) DEFAULT NULL, ADD facebook_username VARCHAR(100) DEFAULT NULL, ADD twitter_username VARCHAR(100) DEFAULT NULL, ADD instagram_username VARCHAR(100) DEFAULT NULL, ADD youtube_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677 ON users');
        $this->addSql('ALTER TABLE users DROP first_name, DROP last_name, DROP username, DROP phone, DROP location, DROP about_me, DROP education, DROP profile_picture, DROP facebook_username, DROP twitter_username, DROP instagram_username, DROP youtube_url');
    }
}
