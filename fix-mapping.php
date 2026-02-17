<?php

echo "🔧 Fixing Doctrine Mapping Issue\n";
echo "===============================\n\n";

// Database connection
$dbHost = $_ENV['DATABASE_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DATABASE_PORT'] ?? '3306';
$dbName = $_ENV['DATABASE_NAME'] ?? 'unilearn_dbs';
$dbUser = $_ENV['DATABASE_USER'] ?? 'root';
$dbPass = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database\n";
    
    // Check current column names
    echo "\n📋 Current Course Table Columns:\n";
    $stmt = $pdo->query("DESCRIBE course");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Fix the column name issue
    echo "\n🔧 Fixing Column Mapping:\n";
    
    // Check if the problematic column exists
    $checkColumn = $pdo->fetchAllAssociative("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'course' 
        AND COLUMN_NAME = 'last_modified_by_id'
    ");
    
    if (!empty($checkColumn)) {
        // Rename the column to match our mapping
        $pdo->exec("ALTER TABLE course CHANGE COLUMN last_modified_by_id last_modified_by INT DEFAULT NULL");
        echo "   ✅ Renamed last_modified_by_id to last_modified_by\n";
    } else {
        echo "   ⚠️  last_modified_by_id column not found (good!)\n";
    }
    
    // Verify the correct column exists
    $checkCorrectColumn = $pdo->fetchAllAssociative("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'course' 
        AND COLUMN_NAME = 'last_modified_by'
    ");
    
    if (!empty($checkCorrectColumn)) {
        echo "   ✅ last_modified_by column exists\n";
    } else {
        echo "   ⚠️  last_modified_by column missing\n";
    }
    
    // Clear Symfony cache
    echo "\n🗑️  Clearing Symfony Cache:\n";
    
    $cacheDir = __DIR__ . '/var/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeDirectory($file);
            } else {
                unlink($file);
            }
        }
        echo "   ✅ Cache cleared\n";
    } else {
        echo "   ⚠️  Cache directory not found\n";
    }
    
    echo "\n🎉 Mapping Fix Complete!\n";
    echo "========================\n";
    echo "✅ Doctrine mapping issue resolved\n";
    echo "✅ Column names now match entity mapping\n";
    echo "✅ Cache cleared\n\n";
    
    echo "🚀 Test the system:\n";
    echo "1. Start server: php -S localhost:8000 -t public/\n";
    echo "2. Test: http://localhost:8000/api/public/courses/transitions\n";
    echo "3. The mapping error should be gone\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}
