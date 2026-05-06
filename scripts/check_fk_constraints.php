<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Checking foreign key constraints ===\n\n";

    // Find all FK constraints referencing teacher_profile
    $stmt = $pdo->query("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = '$dbname' 
                        AND REFERENCED_TABLE_NAME = 'teacher_profile'");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($constraints) . " foreign key constraints:\n";
    foreach ($constraints as $c) {
        echo "  - {$c['TABLE_NAME']}.{$c['COLUMN_NAME']} -> teacher_profile.id (FK: {$c['CONSTRAINT_NAME']})\n";
    }

    // Find all FK constraints where teacher_profile references other tables
    $stmt = $pdo->query("SELECT TABLE_NAME, CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = '$dbname' 
                        AND TABLE_NAME = 'teacher_profile'
                        AND REFERENCED_TABLE_NAME IS NOT NULL");
    $teacherFkConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nTeacher profile has " . count($teacherFkConstraints) . " outgoing FKs:\n";
    foreach ($teacherFkConstraints as $c) {
        echo "  - FK: {$c['CONSTRAINT_NAME']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
