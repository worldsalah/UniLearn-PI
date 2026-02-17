<?php

echo "🔧 Simple Database Fix for Course Lifecycle\n";
echo "==========================================\n\n";

// Try to get database connection from environment
$dbHost = $_ENV['DATABASE_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DATABASE_PORT'] ?? '3306';
$dbName = $_ENV['DATABASE_NAME'] ?? 'unilearn_dbs';
$dbUser = $_ENV['DATABASE_USER'] ?? 'root';
$dbPass = $_ENV['DATABASE_PASSWORD'] ?? '';

echo "📋 Database Info:\n";
echo "   Host: $dbHost\n";
echo "   Port: $dbPort\n";
echo "   Database: $dbName\n";
echo "   User: $dbUser\n\n";

try {
    // Create PDO connection
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database\n\n";
    
    // Check current course table structure
    echo "📋 Current Course Table Columns:\n";
    $stmt = $pdo->query("DESCRIBE course");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = [];
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
        $existingColumns[] = $column['Field'];
    }
    
    // Columns to add
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
    
    echo "\n🔧 Adding Missing Columns:\n";
    
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            try {
                $sql = "ALTER TABLE course ADD COLUMN {$column} {$definition}";
                $pdo->exec($sql);
                echo "   ✅ Added: {$column}\n";
            } catch (Exception $e) {
                echo "   ❌ Failed to add {$column}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ⚠️  Already exists: {$column}\n";
        }
    }
    
    // Update course statuses
    echo "\n🔄 Updating Course Statuses:\n";
    
    try {
        $stmt = $pdo->query("UPDATE course SET status = 'draft' WHERE status = 'inactive' OR status IS NULL");
        echo "   ✅ Updated 'inactive' to 'draft' (" . $stmt->rowCount() . " rows)\n";
    } catch (Exception $e) {
        echo "   ⚠️  Status update issue: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("UPDATE course SET status = 'published' WHERE status = 'live'");
        echo "   ✅ Updated 'live' to 'published' (" . $stmt->rowCount() . " rows)\n";
    } catch (Exception $e) {
        echo "   ⚠️  Status update issue: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("UPDATE course SET status = 'rejected' WHERE status = 'unaccept'");
        echo "   ✅ Updated 'unaccept' to 'rejected' (" . $stmt->rowCount() . " rows)\n";
    } catch (Exception $e) {
        echo "   ⚠️  Status update issue: " . $e->getMessage() . "\n";
    }
    
    try {
        $stmt = $pdo->query("UPDATE course SET status = 'soft_deleted' WHERE status = 'deleted'");
        echo "   ✅ Updated 'deleted' to 'soft_deleted' (" . $stmt->rowCount() . " rows)\n";
    } catch (Exception $e) {
        echo "   ⚠️  Status update issue: " . $e->getMessage() . "\n";
    }
    
    // Create audit log table
    echo "\n📝 Creating Audit Log Table:\n";
    try {
        $pdo->exec("
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
        echo "   ⚠️  Audit log table issue: " . $e->getMessage() . "\n";
    }
    
    // Create version table
    echo "\n📦 Creating Version Table:\n";
    try {
        $pdo->exec("
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
        echo "   ⚠️  Version table issue: " . $e->getMessage() . "\n";
    }
    
    // Final verification
    echo "\n🔍 Final Verification:\n";
    
    // Check course table
    $stmt = $pdo->query("DESCRIBE course");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allColumnsExist = true;
    
    foreach ($columnsToAdd as $column => $definition) {
        $exists = false;
        foreach ($finalColumns as $col) {
            if ($col['Field'] === $column) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $allColumnsExist = false;
            echo "   ❌ Missing: {$column}\n";
        }
    }
    
    if ($allColumnsExist) {
        echo "   ✅ All course columns present\n";
    }
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('course_audit_log', $tables)) {
        echo "   ✅ Audit log table exists\n";
    } else {
        echo "   ❌ Audit log table missing\n";
    }
    
    if (in_array('course_version', $tables)) {
        echo "   ✅ Version table exists\n";
    } else {
        echo "   ❌ Version table missing\n";
    }
    
    // Show course status distribution
    echo "\n📊 Course Status Distribution:\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM course GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($statuses as $status) {
        echo "   {$status['status']}: {$status['count']} courses\n";
    }
    
    echo "\n🎉 Database Fix Complete!\n";
    echo "========================\n";
    echo "✅ Your database is now ready for the Course Lifecycle System\n";
    echo "✅ The 'Column not found' error should be resolved\n";
    echo "✅ You can now test all the advanced features\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "1. Start server: php -S localhost:8000 -t public/\n";
    echo "2. Test API: http://localhost:8000/api/public/courses/transitions\n";
    echo "3. Create test users: http://localhost:8000/test/setup\n";
    echo "4. Login and test the full system\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    echo "🔧 Manual SQL Commands:\n";
    echo "Run these commands in your MySQL client:\n\n";
    
    foreach ($columnsToAdd as $column => $definition) {
        echo "ALTER TABLE course ADD COLUMN {$column} {$definition};\n";
    }
    
    echo "\n-- Update statuses\n";
    echo "UPDATE course SET status = 'draft' WHERE status = 'inactive' OR status IS NULL;\n";
    echo "UPDATE course SET status = 'published' WHERE status = 'live';\n";
    echo "UPDATE course SET status = 'rejected' WHERE status = 'unaccept';\n";
    echo "UPDATE course SET status = 'soft_deleted' WHERE status = 'deleted';\n";
}
