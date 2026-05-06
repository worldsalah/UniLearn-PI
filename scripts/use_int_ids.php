<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Converting to simple integer IDs ===\n\n";

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Drop all FK constraints first
    $tables = ['availability', 'booking', 'session', 'teacher_profile'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                WHERE TABLE_SCHEMA = '$dbname' 
                                AND TABLE_NAME = '$table'
                                AND CONSTRAINT_NAME != 'PRIMARY'");
            $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($fks as $fk) {
                $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $fk");
                echo "Dropped FK: $fk from $table\n";
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }

    // Change teacher_profile.id to INT AUTO_INCREMENT
    $pdo->exec("ALTER TABLE teacher_profile MODIFY id INT AUTO_INCREMENT");
    echo "Changed teacher_profile.id to INT AUTO_INCREMENT\n";

    // Change all referencing columns to INT
    $refColumns = [
        'availability' => 'teacher_id',
        'booking' => 'teacher_id', 
        'session' => 'teacher_profile_id',
        'review' => 'teacher_id',
        'tutoring_session' => 'teacher_id',
        'time_slot' => 'teacher_id'
    ];

    foreach ($refColumns as $table => $column) {
        try {
            $pdo->exec("ALTER TABLE $table MODIFY $column INT");
            echo "Changed $table.$column to INT\n";
        } catch (PDOException $e) {
            echo "Note: Could not change $table.$column: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n=== Done! Using simple integer IDs now ===\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
