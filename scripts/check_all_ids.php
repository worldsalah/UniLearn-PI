<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== All varchar columns with 'id' in name ===\n\n";
    
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' 
                         AND COLUMN_NAME LIKE '%_id'
                         AND COLUMN_TYPE LIKE 'varchar%'
                         ORDER BY TABLE_NAME, COLUMN_NAME");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['TABLE_NAME']}.{$row['COLUMN_NAME']}: {$row['COLUMN_TYPE']}\n";
    }

    echo "\n=== All tables with their id column types ===\n\n";
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_TYPE, COLUMN_KEY 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' 
                         AND COLUMN_NAME = 'id'
                         ORDER BY TABLE_NAME");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['TABLE_NAME']}.id: {$row['COLUMN_TYPE']} ({$row['COLUMN_KEY']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
