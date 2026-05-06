<?php

$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== All tables ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
        echo "$row\n";
    }

    echo "\n=== Dropping teacher_profile_new if exists ===\n";
    try {
        $pdo->exec("DROP TABLE IF EXISTS teacher_profile_new");
        echo "Dropped teacher_profile_new\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
