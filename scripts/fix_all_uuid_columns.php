<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Comprehensive UUID to VARCHAR fix ===\n\n";

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Step 1: Find all tables with teacher_profile_id or teacher_id columns
    $tables = ['availability', 'booking', 'review', 'tutoring_session', 'time_slot', 'session'];
    
    foreach ($tables as $table) {
        echo "\n--- Processing $table ---\n";
        
        // Check for teacher_profile_id
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'teacher_profile_id'");
            if ($stmt->fetch()) {
                // Drop FK if exists
                try {
                    $stmt2 = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                         WHERE TABLE_SCHEMA = '$dbname' 
                                         AND TABLE_NAME = '$table' 
                                         AND COLUMN_NAME = 'teacher_profile_id'
                                         AND REFERENCED_TABLE_NAME IS NOT NULL");
                    $fk = $stmt2->fetch(PDO::FETCH_COLUMN);
                    if ($fk) {
                        $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $fk");
                        echo "Dropped FK from $table.teacher_profile_id\n";
                    }
                } catch (PDOException $e) {
                    // Ignore
                }
                
                // Change column type
                $pdo->exec("ALTER TABLE $table MODIFY teacher_profile_id VARCHAR(36)");
                echo "Changed $table.teacher_profile_id to VARCHAR(36)\n";
            }
        } catch (PDOException $e) {
            echo "Table $table might not exist\n";
        }
        
        // Check for teacher_id
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'teacher_id'");
            if ($stmt->fetch()) {
                // Drop FK if exists
                try {
                    $stmt2 = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                         WHERE TABLE_SCHEMA = '$dbname' 
                                         AND TABLE_NAME = '$table' 
                                         AND COLUMN_NAME = 'teacher_id'
                                         AND REFERENCED_TABLE_NAME = 'teacher_profile'");
                    $fk = $stmt2->fetch(PDO::FETCH_COLUMN);
                    if ($fk) {
                        $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $fk");
                        echo "Dropped FK from $table.teacher_id\n";
                    }
                } catch (PDOException $e) {
                    // Ignore
                }
                
                // Change column type
                $pdo->exec("ALTER TABLE $table MODIFY teacher_id VARCHAR(36)");
                echo "Changed $table.teacher_id to VARCHAR(36)\n";
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }
    
    // Step 2: Fix teacher_profile table itself
    echo "\n--- Processing teacher_profile ---\n";
    
    // Drop its FK to user table
    try {
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = '$dbname' 
                            AND TABLE_NAME = 'teacher_profile'
                            AND REFERENCED_TABLE_NAME = 'user'");
        $fk = $stmt->fetch(PDO::FETCH_COLUMN);
        if ($fk) {
            $pdo->exec("ALTER TABLE teacher_profile DROP FOREIGN KEY $fk");
            echo "Dropped FK from teacher_profile to user\n";
        }
    } catch (PDOException $e) {
        // Ignore
    }
    
    // Change id column to VARCHAR
    $pdo->exec("ALTER TABLE teacher_profile MODIFY id VARCHAR(36) NOT NULL");
    echo "Changed teacher_profile.id to VARCHAR(36)\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== Done! All UUID columns converted to VARCHAR(36) ===\n";
    echo "The 'teacher-001' style IDs will now work.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
