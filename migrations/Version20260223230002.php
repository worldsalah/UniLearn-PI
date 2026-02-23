<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add learning_roadmap table
 */
final class Version20260223230002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add learning_roadmap table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('learning_roadmap');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->addColumn('learning_goal', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('skill_level', 'string', ['length' => 50, 'notnull' => true]);
        $table->addColumn('time_commitment', 'string', ['length' => 50, 'notnull' => false]);
        $table->addColumn('learning_styles', 'json', ['notnull' => false]);
        $table->addColumn('roadmap_content', 'json', ['notnull' => true]);
        $table->addColumn('generated_at', 'datetime', ['notnull' => true]);
        $table->addColumn('is_active', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'idx_learning_roadmap_user');
        $table->addIndex(['is_active'], 'idx_learning_roadmap_active');
        $table->addIndex(['created_at'], 'idx_learning_roadmap_created');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('learning_roadmap');
    }
}
