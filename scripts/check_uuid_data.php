<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking for UUID-like values in all tables ===\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Get all columns
        $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns as $column) {
            // Check for UUID-like patterns in string columns
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table WHERE $column LIKE '%-%-%-%-%' OR $column LIKE '%-____-____-____-____'");
                $count = $stmt->fetchColumn();
                if ($count > 0) {
                    echo "WARNING: $table.$column has $count rows with UUID-like values\n";
                    
                    // Show sample
                    $stmt = $pdo->query("SELECT id, $column FROM $table WHERE $column LIKE '%-%-%-%-%' LIMIT 3");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "  Sample: id={$row['id']}, $column={$row[$column]}\n";
                    }
                }
            } catch (PDOException $e) {
                // Skip text columns that can't be queried this way
            }
        }
    }
    
    echo "\n=== Done ===\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
