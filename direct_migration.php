<?php

$host = '127.0.0.1';
$dbname = 'unilearn_dbs';
$username = 'root';
$password = '';

try {
    // Create connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    echo "Connected to database successfully\n";
    
    // Add ip_address column
    $sql1 = "ALTER TABLE enrollment ADD COLUMN ip_address VARCHAR(45) NULL COMMENT 'Student IP address at time of enrollment'";
    if (!$conn->query($sql1)) {
        echo "Error adding ip_address column: " . $conn->errorInfo()[2] . "\n";
    } else {
        echo "ip_address column added successfully\n";
    }
    
    // Add user_agent column
    $sql2 = "ALTER TABLE enrollment ADD COLUMN user_agent TEXT NULL COMMENT 'Student browser user agent at time of enrollment'";
    if (!$conn->query($sql2)) {
        echo "Error adding user_agent column: " . $conn->errorInfo()[2] . "\n";
    } else {
        echo "user_agent column added successfully\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
