<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Converting teacher_profile table to use integer IDs ===\n\n";

    // Start transaction
    $pdo->beginTransaction();

    // Step 1: Check current table structure
    $stmt = $pdo->query("SHOW COLUMNS FROM teacher_profile");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Current teacher_profile columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }

    // Step 2: Drop foreign key constraints on related tables
    echo "\n=== Dropping foreign key constraints ===\n";
    
    $fkTables = ['availability', 'booking', 'review', 'tutoring_session', 'time_slot'];
    foreach ($fkTables as $table) {
        try {
            // Find FK constraint name
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                WHERE TABLE_SCHEMA = '$dbname' 
                                AND TABLE_NAME = '$table' 
                                AND REFERENCED_TABLE_NAME = 'teacher_profile'");
            $fk = $stmt->fetch(PDO::FETCH_COLUMN);
            if ($fk) {
                $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $fk");
                echo "Dropped FK $fk from $table\n";
            }
        } catch (PDOException $e) {
            echo "Note: Could not drop FK from $table (may not exist): " . $e->getMessage() . "\n";
        }
    }

    // Step 3: Backup and recreate teacher_profile with integer ID
    echo "\n=== Recreating teacher_profile table ===\n";
    
    // Backup existing data
    $stmt = $pdo->query("SELECT * FROM teacher_profile");
    $existingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Backing up " . count($existingData) . " records\n";
    
    // Create new table with integer ID
    $pdo->exec("CREATE TABLE teacher_profile_new (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subjects JSON,
        hourly_rate DECIMAL(10,2),
        bio TEXT,
        education VARCHAR(255),
        experience_years INT DEFAULT 0,
        rating_avg DECIMAL(3,2) DEFAULT 0.00,
        review_count INT DEFAULT 0,
        is_verified TINYINT(1) DEFAULT 0,
        created_at DATETIME,
        updated_at DATETIME,
        UNIQUE KEY unique_user_id (user_id)
    ) ENGINE=InnoDB");
    echo "Created new table with integer ID\n";
    
    // Migrate data (convert invalid UUIDs to new auto-increment IDs)
    $idMapping = []; // old ID -> new ID
    foreach ($existingData as $row) {
        $stmt = $pdo->prepare("INSERT INTO teacher_profile_new 
            (user_id, subjects, hourly_rate, bio, education, experience_years, rating_avg, review_count, is_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $row['user_id'],
            $row['subjects'],
            $row['hourly_rate'],
            $row['bio'],
            $row['education'],
            $row['experience_years'],
            $row['rating_avg'],
            $row['review_count'],
            $row['is_verified'],
            $row['created_at'],
            $row['updated_at']
        ]);
        $newId = $pdo->lastInsertId();
        $idMapping[$row['id']] = $newId;
        echo "Migrated record {$row['id']} -> $newId\n";
    }
    
    // Step 4: Update referencing tables with new IDs
    echo "\n=== Updating referencing tables ===\n";
    foreach ($idMapping as $oldId => $newId) {
        // Update each referencing table
        foreach ($fkTables as $table) {
            try {
                // Check if column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'teacher_profile_id'");
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE $table SET teacher_profile_id = ? WHERE teacher_profile_id = ?");
                    $stmt->execute([$newId, $oldId]);
                    $affected = $stmt->rowCount();
                    if ($affected > 0) {
                        echo "Updated $table: $oldId -> $newId ($affected rows)\n";
                    }
                }
                
                // Also check for teacher_id column
                $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'teacher_id'");
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE $table SET teacher_id = ? WHERE teacher_id = ?");
                    $stmt->execute([$newId, $oldId]);
                    $affected = $stmt->rowCount();
                    if ($affected > 0) {
                        echo "Updated $table.teacher_id: $oldId -> $newId ($affected rows)\n";
                    }
                }
            } catch (PDOException $e) {
                // Column might not exist
            }
        }
    }
    
    // Step 5: Drop old table and rename new one
    $pdo->exec("DROP TABLE teacher_profile");
    $pdo->exec("RENAME TABLE teacher_profile_new TO teacher_profile");
    echo "\nReplaced teacher_profile table\n";
    
    // Step 6: Recreate foreign keys
    echo "\n=== Recreating foreign keys ===\n";
    try {
        $pdo->exec("ALTER TABLE teacher_profile ADD CONSTRAINT fk_teacher_profile_user 
                    FOREIGN KEY (user_id) REFERENCES user(id)");
        echo "Created FK: teacher_profile.user_id -> user.id\n";
    } catch (PDOException $e) {
        echo "Warning: Could not create FK: " . $e->getMessage() . "\n";
    }
    
    foreach ($fkTables as $table) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'teacher_profile_id'");
            if ($stmt->fetch()) {
                $pdo->exec("ALTER TABLE $table ADD CONSTRAINT fk_{$table}_teacher_profile 
                            FOREIGN KEY (teacher_profile_id) REFERENCES teacher_profile(id)");
                echo "Created FK: $table.teacher_profile_id -> teacher_profile.id\n";
            }
        } catch (PDOException $e) {
            echo "Warning: Could not create FK for $table: " . $e->getMessage() . "\n";
        }
    }

    // Commit
    $pdo->commit();
    echo "\n=== Conversion completed successfully! ===\n";
    echo "Migrated " . count($existingData) . " teacher profiles to integer IDs.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
