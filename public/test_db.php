<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=unilearn_dbs', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection: OK\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM category');
    echo "Categories: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM course');
    echo "Courses: " . $stmt->fetchColumn() . "\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
