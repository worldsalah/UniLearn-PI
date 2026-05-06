<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking all tables for non-int id columns ===\n\n";
    
    $tables = ['teacher_profile', 'availability', 'bundle', 'bundle_usage', 'review', 'time_slot', 'tutoring_session', 'enrollment', 'course', 'user', 'booking', 'session'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table WHERE Field = 'id'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($col) {
                echo "$table.id: Type={$col['Type']}, Key={$col['Key']}\n";
            }
        } catch (PDOException $e) {
            echo "$table: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Checking for varchar columns that might be UUID ===\n";
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' 
                         AND DATA_TYPE IN ('varchar', 'char', 'text')
                         AND COLUMN_NAME LIKE '%id'
                         ORDER BY TABLE_NAME, COLUMN_NAME");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['TABLE_NAME']}.{$row['COLUMN_NAME']}: {$row['COLUMN_TYPE']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
