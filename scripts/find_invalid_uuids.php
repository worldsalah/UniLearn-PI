<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Searching for invalid UUID values ===\n\n";

    // Search for teacher-001 in teacher_profile table
    $stmt = $pdo->query("SELECT * FROM teacher_profile WHERE id = 'teacher-001' OR user_id LIKE '%teacher%'");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($results) {
        echo "Found in teacher_profile:\n";
        print_r($results);
    }

    // Check all tables that might have UUID columns
    $tables = ['teacher_profile', 'availability', 'bundle', 'bundle_usage', 'review', 'tutoring_session', 'time_slot'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table WHERE Type LIKE '%uuid%' OR Type LIKE '%char%'");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                $colName = $col['Field'];
                // Search for values that look like 'teacher-xxx'
                $stmt2 = $pdo->query("SELECT * FROM $table WHERE $colName LIKE 'teacher-%' LIMIT 5");
                $badValues = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                if ($badValues) {
                    echo "\n=== Found invalid values in $table.$colName ===\n";
                    print_r($badValues);
                }
            }
        } catch (PDOException $e) {
            // Table might not exist
        }
    }

    // Check user table for teacher-related IDs
    $stmt = $pdo->query("SELECT id, full_name, email FROM user WHERE id LIKE '%teacher%' OR full_name LIKE '%teacher%'");
    $teacherUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($teacherUsers) {
        echo "\n=== Users with 'teacher' in ID or name ===\n";
        print_r($teacherUsers);
    }

    echo "\n=== Fix script ready ===\n";
    echo "To fix: Update invalid UUID values to proper UUIDs or change column type\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
