<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

function generateUuid(): string {
    // Generate a UUID v4
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Fixing invalid UUID values ===\n\n";

    // Start transaction
    $pdo->beginTransaction();

    // Step 1: Find all invalid teacher_profile_id values in availability table
    // Valid UUID format: 8-4-4-4-12 hex digits (e.g., 550e8400-e29b-41d4-a716-446655440000)
    $stmt = $pdo->query("SELECT DISTINCT teacher_profile_id FROM availability 
                         WHERE teacher_profile_id IS NOT NULL 
                         AND teacher_profile_id != ''
                         AND teacher_profile_id NOT REGEXP '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$'");
    $invalidIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($invalidIds)) {
        echo "No invalid UUIDs found in availability table.\n";
        $pdo->rollBack();
        exit(0);
    }

    echo "Found invalid teacher_profile_ids: " . implode(', ', $invalidIds) . "\n\n";

    // Step 2: For each invalid ID, create a new teacher_profile with valid UUID
    $idMapping = [];
    foreach ($invalidIds as $oldId) {
        if (empty($oldId)) continue;
        
        // Get the old teacher_profile data
        $stmt = $pdo->prepare("SELECT * FROM teacher_profile WHERE id = ?");
        $stmt->execute([$oldId]);
        $oldProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldProfile) {
            echo "Profile '$oldId' not found in teacher_profile - may be orphaned\n";
            continue;
        }
        
        $newId = generateUuid();
        $idMapping[$oldId] = $newId;
        
        echo "Creating new profile for '$oldId' -> '$newId'\n";
        
        // Insert new teacher_profile with valid UUID
        $stmt = $pdo->prepare("INSERT INTO teacher_profile 
            (id, user_id, subjects, hourly_rate, bio, education, experience_years, rating_avg, review_count, is_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $newId,
            $oldProfile['user_id'],
            $oldProfile['subjects'],
            $oldProfile['hourly_rate'],
            $oldProfile['bio'],
            $oldProfile['education'],
            $oldProfile['experience_years'],
            $oldProfile['rating_avg'],
            $oldProfile['review_count'],
            $oldProfile['is_verified'],
            $oldProfile['created_at'],
            $oldProfile['updated_at']
        ]);
        echo "  Created new profile: $newId\n";
    }

    // Step 4: Update availability table with new UUIDs
    foreach ($idMapping as $oldId => $newId) {
        $stmt = $pdo->prepare("UPDATE availability SET teacher_profile_id = ? WHERE teacher_profile_id = ?");
        $stmt->execute([$newId, $oldId]);
        $affected = $stmt->rowCount();
        echo "Updated $affected rows in availability table: $oldId -> $newId\n";
    }

    // Step 5: Check other tables that might have teacher_profile_id
    $tablesToCheck = ['booking', 'review', 'tutoring_session'];
    foreach ($tablesToCheck as $table) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '%teacher%'");
            $hasTeacherColumn = $stmt->fetch();
            
            if ($hasTeacherColumn) {
                foreach ($idMapping as $oldId => $newId) {
                    $stmt = $pdo->prepare("UPDATE $table SET teacher_profile_id = ? WHERE teacher_profile_id = ?");
                    $stmt->execute([$newId, $oldId]);
                    $affected = $stmt->rowCount();
                    if ($affected > 0) {
                        echo "Updated $affected rows in $table table: $oldId -> $newId\n";
                    }
                }
            }
        } catch (PDOException $e) {
            // Table might not exist or column doesn't exist
        }
    }

    // Step 5: Delete old teacher_profile records with invalid IDs
    foreach ($idMapping as $oldId => $newId) {
        $stmt = $pdo->prepare("DELETE FROM teacher_profile WHERE id = ?");
        $stmt->execute([$oldId]);
        echo "Deleted old profile: $oldId\n";
    }

    // Commit transaction
    $pdo->commit();
    echo "\n=== Fix completed successfully! ===\n";
    echo "Created " . count($idMapping) . " new teacher profiles with valid UUIDs.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
