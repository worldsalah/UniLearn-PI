<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add missing progress columns to course table
 */
final class Version20260218140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_progress and video_progress columns to course table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('course');
        
        // Add image_progress column if it doesn't exist
        if (!$table->hasColumn('image_progress')) {
            $table->addColumn('image_progress', 'float', [
                'default' => 0,
                'notnull' => true,
            ]);
        }
        
        // Add video_progress column if it doesn't exist
        if (!$table->hasColumn('video_progress')) {
            $table->addColumn('video_progress', 'float', [
                'default' => 0,
                'notnull' => true,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('course');
        
        // Remove columns if they exist
        if ($table->hasColumn('image_progress')) {
            $table->dropColumn('image_progress');
        }
        
        if ($table->hasColumn('video_progress')) {
            $table->dropColumn('video_progress');
        }
    }
}
