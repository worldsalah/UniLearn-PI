<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

function generateUuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Fixing invalid UUID values (consolidating duplicates) ===\n\n";

    // Start transaction
    $pdo->beginTransaction();

    // Step 1: Find all invalid teacher_profile IDs
    $stmt = $pdo->query("SELECT id, user_id FROM teacher_profile 
                         WHERE id NOT REGEXP '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$'");
    $invalidProfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($invalidProfiles)) {
        echo "No invalid UUIDs found in teacher_profile table.\n";
        $pdo->rollBack();
        exit(0);
    }

    echo "Found " . count($invalidProfiles) . " invalid profiles\n";

    // Group by user_id to find duplicates
    $userProfiles = [];
    foreach ($invalidProfiles as $profile) {
        $userId = $profile['user_id'];
        if (!isset($userProfiles[$userId])) {
            $userProfiles[$userId] = [];
        }
        $userProfiles[$userId][] = $profile['id'];
    }

    // Step 2: For each user, check if they already have a valid UUID profile
    $profilesToDelete = [];
    $idMapping = []; // old invalid ID -> new valid ID (or existing valid ID)

    foreach ($userProfiles as $userId => $invalidIds) {
        echo "\nUser $userId has " . count($invalidIds) . " invalid profiles: " . implode(', ', $invalidIds) . "\n";
        
        // Check if user already has a valid UUID profile
        $stmt = $pdo->prepare("SELECT id FROM teacher_profile 
                              WHERE user_id = ? 
                              AND id REGEXP '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$'");
        $stmt->execute([$userId]);
        $validProfile = $stmt->fetch(PDO::FETCH_COLUMN);
        
        if ($validProfile) {
            echo "  User already has valid profile: $validProfile\n";
            // Map all invalid IDs to the existing valid one
            foreach ($invalidIds as $invalidId) {
                $idMapping[$invalidId] = $validProfile;
                $profilesToDelete[] = $invalidId;
            }
        } else {
            // Keep the first invalid profile, convert it to valid UUID
            $keepId = array_shift($invalidIds);
            $newUuid = generateUuid();
            $idMapping[$keepId] = $newUuid;
            
            echo "  Converting $keepId to $newUuid\n";
            
            // Create new profile with valid UUID
            $stmt = $pdo->prepare("SELECT * FROM teacher_profile WHERE id = ?");
            $stmt->execute([$keepId]);
            $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("INSERT INTO teacher_profile 
                (id, user_id, subjects, hourly_rate, bio, education, experience_years, rating_avg, review_count, is_verified, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $newUuid,
                $profileData['user_id'],
                $profileData['subjects'],
                $profileData['hourly_rate'],
                $profileData['bio'],
                $profileData['education'],
                $profileData['experience_years'],
                $profileData['rating_avg'],
                $profileData['review_count'],
                $profileData['is_verified'],
                $profileData['created_at'],
                $profileData['updated_at']
            ]);
            
            // Delete the old invalid profile
            $profilesToDelete[] = $keepId;
            
            // Mark remaining invalid IDs for this user to map to the new valid one
            foreach ($invalidIds as $invalidId) {
                $idMapping[$invalidId] = $newUuid;
                $profilesToDelete[] = $invalidId;
            }
        }
    }

    // Step 3: Update all referencing tables
    echo "\n=== Updating references ===\n";
    
    $tables = [
        'availability' => 'teacher_profile_id',
        'booking' => 'teacher_profile_id',
        'review' => 'teacher_id',
        'tutoring_session' => 'teacher_id',
        'time_slot' => 'teacher_id'
    ];
    
    foreach ($tables as $table => $column) {
        try {
            foreach ($idMapping as $oldId => $newId) {
                $stmt = $pdo->prepare("UPDATE $table SET $column = ? WHERE $column = ?");
                $stmt->execute([$newId, $oldId]);
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    echo "Updated $affected rows in $table.$column: $oldId -> $newId\n";
                }
            }
        } catch (PDOException $e) {
            // Table or column might not exist
        }
    }

    // Step 4: Delete old invalid profiles
    echo "\n=== Deleting invalid profiles ===\n";
    foreach ($profilesToDelete as $oldId) {
        $stmt = $pdo->prepare("DELETE FROM teacher_profile WHERE id = ?");
        $stmt->execute([$oldId]);
        echo "Deleted: $oldId\n";
    }

    // Commit transaction
    $pdo->commit();
    echo "\n=== Fix completed! ===\n";
    echo "Consolidated " . count($invalidProfiles) . " invalid profiles.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
