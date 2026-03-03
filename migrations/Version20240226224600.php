<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240226224600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create lesson_completion table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('lesson_completion');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->addColumn('lesson_id', 'integer', ['notnull' => true]);
        $table->addColumn('course_id', 'integer', ['notnull' => true]);
        $table->addColumn('completed_at', 'datetime_immutable', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'idx_lesson_completion_user_id');
        $table->addIndex(['lesson_id'], 'idx_lesson_completion_lesson_id');
        $table->addIndex(['course_id'], 'idx_lesson_completion_course_id');
        $table->addUniqueIndex(['user_id', 'lesson_id'], 'uniq_lesson_completion_user_lesson');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('lesson_completion');
    }
}
