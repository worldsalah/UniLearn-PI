<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Finding all teacher_profile_id columns ===\n\n";
    
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' 
                         AND COLUMN_NAME LIKE '%teacher%'
                         ORDER BY TABLE_NAME");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['TABLE_NAME']}.{$row['COLUMN_NAME']}\n";
        
        // Get column type
        $stmt2 = $pdo->query("SHOW COLUMNS FROM {$row['TABLE_NAME']} LIKE '{$row['COLUMN_NAME']}'");
        $col = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "  Type: {$col['Type']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
