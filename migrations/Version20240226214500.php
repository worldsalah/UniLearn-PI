<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240226214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add IP address and user agent tracking to enrollment table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('enrollment');
        
        // Add ip_address column
        if (!$table->hasColumn('ip_address')) {
            $table->addColumn('ip_address', 'string', [
                'length' => 45,
                'notnull' => true,
                'comment' => 'Student IP address at time of enrollment'
            ]);
        }
        
        // Add user_agent column
        if (!$table->hasColumn('user_agent')) {
            $table->addColumn('user_agent', 'text', [
                'notnull' => true,
                'comment' => 'Student browser user agent at time of enrollment'
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('enrollment');
        
        // Remove ip_address column
        if ($table->hasColumn('ip_address')) {
            $table->dropColumn('ip_address');
        }
        
        // Remove user_agent column
        if ($table->hasColumn('user_agent')) {
            $table->dropColumn('user_agent');
        }
    }
}
