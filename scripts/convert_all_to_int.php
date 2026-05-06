<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Converting all UUID columns to INT ===\n\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Tables and their UUID columns to convert
    $tables = [
        'teacher_profile' => 'id',
        'availability' => 'id',
        'bundle' => 'id',
        'bundle_usage' => 'id',
        'review' => 'id',
        'time_slot' => 'id',
        'tutoring_session' => 'id'
    ];

    foreach ($tables as $table => $column) {
        try {
            // Check if column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($stmt->fetch()) {
                $pdo->exec("ALTER TABLE $table MODIFY $column INT AUTO_INCREMENT");
                echo "✓ Changed $table.$column to INT AUTO_INCREMENT\n";
            }
        } catch (PDOException $e) {
            echo "✗ Error on $table.$column: " . $e->getMessage() . "\n";
        }
    }

    // Also convert foreign key columns
    $fkColumns = [
        'availability' => 'teacher_id',
        'booking' => 'teacher_id',
        'session' => 'teacher_profile_id',
        'review' => 'teacher_id',
        'tutoring_session' => 'teacher_id',
        'time_slot' => 'teacher_id',
        'bundle' => 'teacher_id'
    ];

    foreach ($fkColumns as $table => $column) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            if ($stmt->fetch()) {
                $pdo->exec("ALTER TABLE $table MODIFY $column INT");
                echo "✓ Changed $table.$column to INT\n";
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n=== Done! ===\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
