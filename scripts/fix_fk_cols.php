<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Fixing remaining varchar columns ===\n\n";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $columns = [
        'availability' => 'teacher_profile_id',
        'booking' => 'teacher_profile_id',
        'time_slot' => 'teacher_profile_id',
        'tutoring_session' => 'teacher_profile_id'
    ];

    foreach ($columns as $table => $column) {
        try {
            $pdo->exec("ALTER TABLE $table MODIFY $column INT");
            echo "✓ Changed $table.$column to INT\n";
        } catch (PDOException $e) {
            echo "✗ Error on $table.$column: " . $e->getMessage() . "\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n=== Done! ===\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
