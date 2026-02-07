<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "SALAH");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to MySQL\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS unilearn_marketplace");
    echo "Database unilearn_marketplace created or already exists\n";
    $stmt = $pdo->query("SHOW DATABASES");
    while ($row = $stmt->fetch()) {
        echo $row[0] . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
