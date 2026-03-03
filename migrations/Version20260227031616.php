<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227031616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add realistic lesson fields for better course content';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to lesson table
        $table = $schema->getTable('lesson');
        
        if (!$table->hasColumn('video_url')) {
            $table->addColumn('video_url', 'string', ['length' => 255, 'notnull' => false]);
        }
        
        if (!$table->hasColumn('difficulty')) {
            $table->addColumn('difficulty', 'string', ['length' => 20, 'notnull' => false]);
        }
        
        if (!$table->hasColumn('learning_objectives')) {
            $table->addColumn('learning_objectives', 'text', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('prerequisites')) {
            $table->addColumn('prerequisites', 'text', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('materials')) {
            $table->addColumn('materials', 'json', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('estimated_time')) {
            $table->addColumn('estimated_time', 'integer', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('transcript')) {
            $table->addColumn('transcript', 'text', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('thumbnail_url')) {
            $table->addColumn('thumbnail_url', 'string', ['length' => 255, 'notnull' => false]);
        }
        
        if (!$table->hasColumn('resources')) {
            $table->addColumn('resources', 'text', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('assessment')) {
            $table->addColumn('assessment', 'text', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('is_completed')) {
            $table->addColumn('is_completed', 'boolean', ['default' => false]);
        }
        
        if (!$table->hasColumn('views')) {
            $table->addColumn('views', 'integer', ['notnull' => false]);
        }
        
        if (!$table->hasColumn('published_at')) {
            $table->addColumn('published_at', 'datetime', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        // Remove the new columns
        $table = $schema->getTable('lesson');
        
        if ($table->hasColumn('video_url')) {
            $table->dropColumn('video_url');
        }
        
        if ($table->hasColumn('difficulty')) {
            $table->dropColumn('difficulty');
        }
        
        if ($table->hasColumn('learning_objectives')) {
            $table->dropColumn('learning_objectives');
        }
        
        if ($table->hasColumn('prerequisites')) {
            $table->dropColumn('prerequisites');
        }
        
        if ($table->hasColumn('materials')) {
            $table->dropColumn('materials');
        }
        
        if ($table->hasColumn('estimated_time')) {
            $table->dropColumn('estimated_time');
        }
        
        if ($table->hasColumn('transcript')) {
            $table->dropColumn('transcript');
        }
        
        if ($table->hasColumn('thumbnail_url')) {
            $table->dropColumn('thumbnail_url');
        }
        
        if ($table->hasColumn('resources')) {
            $table->dropColumn('resources');
        }
        
        if ($table->hasColumn('assessment')) {
            $table->dropColumn('assessment');
        }
        
        if ($table->hasColumn('is_completed')) {
            $table->dropColumn('is_completed');
        }
        
        if ($table->hasColumn('views')) {
            $table->dropColumn('views');
        }
        
        if ($table->hasColumn('published_at')) {
            $table->dropColumn('published_at');
        }
    }
}
