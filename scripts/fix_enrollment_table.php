<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Adding missing columns to enrollment table ===\n\n";

    // Check if placement_test_taken column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment LIKE 'placement_test_taken'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        echo "Adding 'placement_test_taken' column...\n";
        $pdo->exec("ALTER TABLE enrollment ADD COLUMN placement_test_taken TINYINT(1) NOT NULL DEFAULT 0");
        echo "✓ Column 'placement_test_taken' added successfully!\n";
    } else {
        echo "✓ Column 'placement_test_taken' already exists\n";
    }

    // Check for other potential missing columns
    $columnsToCheck = [
        'starting_lesson_id' => 'INT NULL',
        'placement_test_result_id' => 'INT NULL'
    ];

    foreach ($columnsToCheck as $column => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM enrollment LIKE '$column'");
        if (!$stmt->fetch()) {
            echo "Adding '$column' column...\n";
            $pdo->exec("ALTER TABLE enrollment ADD COLUMN $column $definition");
            echo "✓ Column '$column' added successfully!\n";
        } else {
            echo "✓ Column '$column' already exists\n";
        }
    }

    echo "\n=== Enrollment table structure updated ===\n";

    // Show current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nCurrent columns in enrollment table:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
