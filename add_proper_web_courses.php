<?php

// Database connection
$host = 'localhost';
$dbname = 'unilearn_dbs';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding proper web development courses...\n";
    
    // Add proper web development courses
    $webCoursesToAdd = [
        [
            'title' => 'Web Development Fundamentals',
            'short_description' => 'Learn the fundamentals of web development with HTML, CSS, and JavaScript from scratch',
            'level' => 'Beginner',
            'price' => 79.99,
            'category_id' => 1
        ],
        [
            'title' => 'HTML & CSS Complete Guide',
            'short_description' => 'Master HTML5 and CSS3 for modern web development and responsive design',
            'level' => 'Beginner',
            'price' => 89.99,
            'category_id' => 1
        ],
        [
            'title' => 'JavaScript Programming Basics',
            'short_description' => 'Learn JavaScript programming fundamentals for web development',
            'level' => 'Beginner',
            'price' => 99.99,
            'category_id' => 1
        ],
        [
            'title' => 'Web Development Bootcamp',
            'short_description' => 'Complete web development bootcamp from zero to professional developer',
            'level' => 'Beginner',
            'price' => 149.99,
            'category_id' => 1
        ],
        [
            'title' => 'Frontend Web Development',
            'short_description' => 'Master frontend web development with modern tools and frameworks',
            'level' => 'Intermediate',
            'price' => 119.99,
            'category_id' => 1
        ],
        [
            'title' => 'Full Stack Web Development',
            'short_description' => 'Become a full stack web developer with frontend and backend skills',
            'level' => 'Intermediate',
            'price' => 159.99,
            'category_id' => 1
        ],
        [
            'title' => 'Modern Web Development',
            'short_description' => 'Learn modern web development with latest technologies and best practices',
            'level' => 'Advanced',
            'price' => 179.99,
            'category_id' => 1
        ]
    ];
    
    $sql = "INSERT INTO course (title, short_description, level, price, category_id, thumbnail_url, video_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    $addedCount = 0;
    foreach ($webCoursesToAdd as $course) {
        try {
            $stmt->execute([
                $course['title'],
                $course['short_description'],
                $course['level'],
                $course['price'],
                $course['category_id'],
                '/assets/images/courses/web-dev.jpg',
                '/assets/videos/web-dev-intro.mp4'
            ]);
            $addedCount++;
            echo "✓ Added: {$course['title']}\n";
        } catch (PDOException $e) {
            echo "✗ Error adding {$course['title']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nAdded {$addedCount} new web development courses.\n";
    
    // Show all web development courses
    echo "\nAll web development courses in database:\n";
    $stmt = $pdo->query("SELECT id, title, short_description, level FROM course WHERE title LIKE '%web%' OR title LIKE '%html%' OR title LIKE '%css%' OR title LIKE '%javascript%' OR title LIKE '%react%' OR title LIKE '%vue%' OR title LIKE '%angular%' ORDER BY level, title");
    $webCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($webCourses as $course) {
        echo "- {$course['title']} ({$course['level']})\n";
        echo "  {$course['short_description']}\n\n";
    }
    
    echo "✓ Database is now ready with proper web development courses!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
