<?php

// Database connection parameters
$host = 'localhost';
$dbname = 'unilearn_pi';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Course Status Analysis ===\n\n";

    // Check course statuses
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM course GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Courses by status:\n";
    foreach ($statuses as $row) {
        $status = $row['status'] ?? 'NULL';
        echo "  - Status: '$status' => {$row['count']} courses\n";
    }

    // Total courses
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "\nTotal courses in database: $total\n";

    // Check if any courses have 'live' status
    $stmt = $pdo->query("SELECT COUNT(*) as live_count FROM course WHERE status = 'live'");
    $liveCount = $stmt->fetch(PDO::FETCH_ASSOC)['live_count'];
    echo "Courses with 'live' status: $liveCount\n";

    // Show sample courses
    echo "\n=== Sample courses (first 5) ===\n";
    $stmt = $pdo->query("SELECT id, title, status, created_at FROM course LIMIT 5");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($courses as $course) {
        $status = $course['status'] ?? 'NULL';
        echo "  ID: {$course['id']}, Title: '{$course['title']}', Status: '$status'\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
