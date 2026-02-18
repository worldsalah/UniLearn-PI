<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quiz module tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Create tables only if they don't exist
        if (!$schema->hasTable('quiz')) {
            $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, course_id INT DEFAULT NULL, INDEX IDX_A412FA92591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
        
        if (!$schema->hasTable('question')) {
            $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(255) NOT NULL, option_a VARCHAR(255) NOT NULL, option_b VARCHAR(255) NOT NULL, option_c VARCHAR(255) NOT NULL, option_d VARCHAR(255) NOT NULL, correct_option VARCHAR(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
        
        if (!$schema->hasTable('quiz_result')) {
            $this->addSql('CREATE TABLE quiz_result (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, max_score INT NOT NULL, taken_at DATETIME NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_FE2E314AA76ED395 (user_id), INDEX IDX_FE2E314A853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }
        
        // Add foreign keys only if tables exist and constraints don't exist
        if ($schema->hasTable('quiz') && $schema->hasTable('course')) {
            $quizTable = $schema->getTable('quiz');
            if (!$quizTable->hasForeignKey('FK_A412FA92591CC992')) {
                $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
            }
        }
        
        if ($schema->hasTable('question') && $schema->hasTable('quiz')) {
            $questionTable = $schema->getTable('question');
            if (!$questionTable->hasForeignKey('FK_B6F7494E853CD175')) {
                $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
            }
        }
        
        if ($schema->hasTable('quiz_result')) {
            $quizResultTable = $schema->getTable('quiz_result');
            if (!$quizResultTable->hasForeignKey('FK_FE2E314AA76ED395')) {
                $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            }
            if (!$quizResultTable->hasForeignKey('FK_FE2E314A853CD175')) {
                $this->addSql('ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92591CC992');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314AA76ED395');
        $this->addSql('ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314A853CD175');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz_result');
    }
}
