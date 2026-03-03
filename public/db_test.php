<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing MySQL connection...\n";

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=unilearn_dbs',
        'root',
        '',
        [PDO::ATTR_TIMEOUT => 10, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Connection: OK\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM category');
    echo "Categories: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM course');
    echo "Courses: " . $stmt->fetchColumn() . "\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
