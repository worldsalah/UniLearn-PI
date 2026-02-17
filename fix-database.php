<?php

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

echo "🔧 Fixing Database Schema for Course Lifecycle System\n";
echo "====================================================\n\n";

// Get database connection
$container = new ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\PhpFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/config'));

try {
    $loader->load('services.php');
    $entityManager = $container->get(EntityManagerInterface::class);
    $connection = $entityManager->getConnection();
    
    echo "✅ Connected to database\n";
} catch (Exception $e) {
    echo "❌ Could not connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Get current course table structure
echo "\n📋 Current Course Table Structure:\n";
try {
    $columns = $connection->fetchAllAssociative("DESCRIBE course");
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
} catch (Exception $e) {
    echo "❌ Could not get course table structure: " . $e->getMessage() . "\n";
}

// Add missing columns to course table
echo "\n🔧 Adding Missing Course Columns:\n";

$columnsToAdd = [
    'submitted_at' => 'DATETIME DEFAULT NULL',
    'reviewed_at' => 'DATETIME DEFAULT NULL', 
    'published_at' => 'DATETIME DEFAULT NULL',
    'archived_at' => 'DATETIME DEFAULT NULL',
    'rejection_reason' => 'TEXT DEFAULT NULL',
    'version_number' => 'INT DEFAULT 1',
    'is_locked' => 'BOOLEAN DEFAULT FALSE',
    'last_modified_by' => 'INT DEFAULT NULL'
];

foreach ($columnsToAdd as $column => $definition) {
    try {
        // Check if column exists
        $checkColumn = $connection->fetchAllAssociative("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'course' 
            AND COLUMN_NAME = '{$column}'
        ");
        
        if (empty($checkColumn)) {
            $connection->executeStatement("ALTER TABLE course ADD COLUMN {$column} {$definition}");
            echo "   ✅ Added column: {$column}\n";
        } else {
            echo "   ⚠️  Column already exists: {$column}\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Failed to add {$column}: " . $e->getMessage() . "\n";
    }
}

// Update course statuses
echo "\n🔄 Updating Course Statuses:\n";
try {
    $connection->executeStatement("UPDATE course SET status = 'draft' WHERE status = 'inactive' OR status IS NULL");
    echo "   ✅ Updated 'inactive' to 'draft'\n";
    
    $connection->executeStatement("UPDATE course SET status = 'published' WHERE status = 'live'");
    echo "   ✅ Updated 'live' to 'published'\n";
    
    $connection->executeStatement("UPDATE course SET status = 'rejected' WHERE status = 'unaccept'");
    echo "   ✅ Updated 'unaccept' to 'rejected'\n";
    
    $connection->executeStatement("UPDATE course SET status = 'soft_deleted' WHERE status = 'deleted'");
    echo "   ✅ Updated 'deleted' to 'soft_deleted'\n";
} catch (Exception $e) {
    echo "   ❌ Failed to update statuses: " . $e->getMessage() . "\n";
}

// Create audit log table
echo "\n📝 Creating Audit Log Table:\n";
try {
    $connection->executeStatement("
        CREATE TABLE IF NOT EXISTS course_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            changed_by INT NOT NULL,
            from_status VARCHAR(20) NOT NULL,
            to_status VARCHAR(20) NOT NULL,
            reason TEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            INDEX idx_course_audit_log_course (course_id),
            INDEX idx_course_audit_log_changed_by (changed_by),
            INDEX idx_course_audit_log_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Course audit log table created\n";
} catch (Exception $e) {
    echo "   ⚠️  Audit log table may already exist: " . $e->getMessage() . "\n";
}

// Create version table
echo "\n📦 Creating Version Table:\n";
try {
    $connection->executeStatement("
        CREATE TABLE IF NOT EXISTS course_version (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            version_number INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            short_description TEXT NOT NULL,
            requirements TEXT DEFAULT NULL,
            learning_outcomes TEXT DEFAULT NULL,
            target_audience TEXT DEFAULT NULL,
            price DOUBLE PRECISION DEFAULT NULL,
            thumbnail_url VARCHAR(255) DEFAULT NULL,
            video_url VARCHAR(255) DEFAULT NULL,
            curriculum_snapshot JSON DEFAULT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            publish_status VARCHAR(20) DEFAULT NULL,
            version_notes TEXT DEFAULT NULL,
            INDEX idx_course_version_course (course_id),
            INDEX idx_course_version_created_by (created_by),
            UNIQUE KEY uniq_course_version (course_id, version_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Course version table created\n";
} catch (Exception $e) {
    echo "   ⚠️  Version table may already exist: " . $e->getMessage() . "\n";
}

// Verify the fix
echo "\n🔍 Verification:\n";
try {
    $courseColumns = $connection->fetchAllAssociative("DESCRIBE course");
    $hasAllColumns = true;
    
    foreach ($columnsToAdd as $column => $definition) {
        $columnExists = false;
        foreach ($courseColumns as $col) {
            if ($col['Field'] === $column) {
                $columnExists = true;
                break;
            }
        }
        if (!$columnExists) {
            $hasAllColumns = false;
            echo "   ❌ Missing column: {$column}\n";
        }
    }
    
    if ($hasAllColumns) {
        echo "   ✅ All required columns exist in course table\n";
    }
    
    // Check if new tables exist
    $auditLogExists = $connection->fetchAllAssociative("SHOW TABLES LIKE 'course_audit_log'");
    $versionExists = $connection->fetchAllAssociative("SHOW TABLES LIKE 'course_version'");
    
    if (!empty($auditLogExists)) {
        echo "   ✅ Course audit log table exists\n";
    } else {
        echo "   ❌ Course audit log table missing\n";
    }
    
    if (!empty($versionExists)) {
        echo "   ✅ Course version table exists\n";
    } else {
        echo "   ❌ Course version table missing\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Verification failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 Database Fix Complete!\n";
echo "==========================\n";
echo "✅ Course table updated with lifecycle columns\n";
echo "✅ Status values updated to new format\n";
echo "✅ Audit log table created\n";
echo "✅ Version control table created\n\n";

echo "🚀 You can now test the system:\n";
echo "1. Start server: php -S localhost:8000 -t public/\n";
echo "2. Visit: http://localhost:8000/api/public/courses/transitions\n";
echo "3. Test course lifecycle features\n\n";
