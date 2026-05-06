<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Simple fix: Change UUID columns to VARCHAR ===\n\n";

    // Start transaction
    $pdo->beginTransaction();

    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Get all tables referencing teacher_profile
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = '$dbname' 
                        AND REFERENCED_TABLE_NAME = 'teacher_profile'");
    $referencingTables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($referencingTables) . " referencing tables\n";

    // Step 1: Drop all FK constraints
    echo "\n=== Dropping foreign keys ===\n";
    foreach ($referencingTables as $ref) {
        try {
            $pdo->exec("ALTER TABLE {$ref['TABLE_NAME']} DROP FOREIGN KEY {$ref['CONSTRAINT_NAME']}");
            echo "Dropped FK {$ref['CONSTRAINT_NAME']} from {$ref['TABLE_NAME']}\n";
        } catch (PDOException $e) {
            echo "Could not drop FK: " . $e->getMessage() . "\n";
        }
    }

    // Also drop FK from teacher_profile itself
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = '$dbname' 
                        AND TABLE_NAME = 'teacher_profile'
                        AND REFERENCED_TABLE_NAME = 'user'");
    $teacherFk = $stmt->fetch(PDO::FETCH_COLUMN);
    if ($teacherFk) {
        try {
            $pdo->exec("ALTER TABLE teacher_profile DROP FOREIGN KEY $teacherFk");
            echo "Dropped FK $teacherFk from teacher_profile\n";
        } catch (PDOException $e) {
            echo "Could not drop FK from teacher_profile: " . $e->getMessage() . "\n";
        }
    }

    // Step 2: Change referencing columns from UUID to VARCHAR(36)
    echo "\n=== Changing column types ===\n";
    foreach ($referencingTables as $ref) {
        try {
            $pdo->exec("ALTER TABLE {$ref['TABLE_NAME']} MODIFY {$ref['COLUMN_NAME']} VARCHAR(36)");
            echo "Changed {$ref['TABLE_NAME']}.{$ref['COLUMN_NAME']} to VARCHAR(36)\n";
        } catch (PDOException $e) {
            echo "Error changing {$ref['TABLE_NAME']}: " . $e->getMessage() . "\n";
        }
    }

    // Step 3: Change teacher_profile.id to VARCHAR(36) with auto-increment-like behavior
    // Actually, let's just make it VARCHAR(36) - not auto-increment since UUIDs aren't
    try {
        $pdo->exec("ALTER TABLE teacher_profile MODIFY id VARCHAR(36) NOT NULL");
        echo "Changed teacher_profile.id to VARCHAR(36)\n";
    } catch (PDOException $e) {
        echo "Error changing teacher_profile.id: " . $e->getMessage() . "\n";
    }

    // Step 4: Recreate FKs (but without strict UUID checking)
    echo "\n=== Recreating foreign keys ===\n";
    foreach ($referencingTables as $ref) {
        try {
            $pdo->exec("ALTER TABLE {$ref['TABLE_NAME']} 
                        ADD CONSTRAINT {$ref['CONSTRAINT_NAME']} 
                        FOREIGN KEY ({$ref['COLUMN_NAME']}) 
                        REFERENCES teacher_profile(id)");
            echo "Recreated FK {$ref['CONSTRAINT_NAME']}\n";
        } catch (PDOException $e) {
            echo "Could not recreate FK {$ref['CONSTRAINT_NAME']}: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Commit
    $pdo->commit();
    echo "\n=== Done! ===\n";
    echo "Columns changed to VARCHAR(36) to accept any format including 'teacher-001'\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
