<?php

// Database connection parameters - adjust these to match your .env configuration
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "Creating roles table...\n";
        $pdo->exec("CREATE TABLE roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(255) NULL,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Roles table created successfully!\n";
    }

    // Insert default roles
    $roles = [
        [1, 'admin', 'Administrator with full access'],
        [2, 'instructor', 'Course instructor who can create and manage courses'],
        [3, 'student', 'Student who can enroll and take courses'],
        [4, 'user', 'Basic user role']
    ];

    $inserted = 0;
    $skipped = 0;

    foreach ($roles as $role) {
        // Check if role already exists
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ? OR name = ?");
        $stmt->execute([$role[0], $role[1]]);
        
        if ($stmt->fetch()) {
            echo "Role '{$role[1]}' (ID: {$role[0]}) already exists - skipping\n";
            $skipped++;
        } else {
            $stmt = $pdo->prepare("INSERT INTO roles (id, name, description) VALUES (?, ?, ?)");
            $stmt->execute($role);
            echo "Inserted role '{$role[1]}' (ID: {$role[0]})\n";
            $inserted++;
        }
    }

    echo "\n=== Summary ===\n";
    echo "Inserted: $inserted roles\n";
    echo "Skipped: $skipped roles\n";
    echo "\nCurrent roles in database:\n";
    
    $stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY id");
    $allRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allRoles as $role) {
        echo "  - ID: {$role['id']}, Name: {$role['name']}, Description: {$role['description']}\n";
    }

    echo "\nRoles setup complete!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
