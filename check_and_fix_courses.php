<?php

// Database connection
$host = 'localhost';
$dbname = 'unilearn_dbs';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking current courses in database:\n";
    
    // Get all courses
    $stmt = $pdo->query("SELECT id, title, short_description, level, category_id FROM course ORDER BY title");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($courses) . " courses:\n";
    foreach ($courses as $course) {
        echo "- ID: {$course['id']}, Title: '{$course['title']}', Level: {$course['level']}, Category: {$course['category_id']}\n";
    }
    
    // Check for test/invalid courses
    $invalidCourses = [];
    foreach ($courses as $course) {
        $title = trim($course['title']);
        $description = trim($course['short_description']);
        
        // Check for invalid/test courses
        if (empty($title) || strlen($title) < 3 || 
            in_array($title, ['malekkk', 'malek', 'testttt', 'tebourbi', 'fffffffffff', 'web developement']) ||
            strpos($title, 'llllll') !== false ||
            strlen($description) < 10) {
            $invalidCourses[] = $course['id'];
        }
    }
    
    if (!empty($invalidCourses)) {
        echo "\nFound " . count($invalidCourses) . " invalid/test courses to remove: " . implode(', ', $invalidCourses) . "\n";
        
        // Remove invalid courses
        $placeholders = implode(',', array_fill(0, count($invalidCourses), '?'));
        $stmt = $pdo->prepare("DELETE FROM course WHERE id IN ($placeholders)");
        $stmt->execute($invalidCourses);
        
        echo "Removed invalid courses successfully.\n";
    } else {
        echo "\nNo invalid courses found.\n";
    }
    
    // Add proper web development courses if needed
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM course WHERE title LIKE '%web%' OR title LIKE '%html%' OR title LIKE '%css%' OR title LIKE '%javascript%'");
    $webCourses = $stmt->fetch();
    
    echo "\nCurrent web development courses: " . $webCourses['count'] . "\n";
    
    if ($webCourses['count'] < 3) {
        echo "Adding proper web development courses...\n";
        
        $webCoursesToAdd = [
            [
                'title' => 'Web Development Fundamentals',
                'short_description' => 'Learn the fundamentals of web development with HTML, CSS, and JavaScript',
                'level' => 'Beginner',
                'price' => 79.99,
                'category_id' => 1
            ],
            [
                'title' => 'HTML & CSS Complete Guide',
                'short_description' => 'Master HTML5 and CSS3 for modern web development',
                'level' => 'Beginner',
                'price' => 89.99,
                'category_id' => 1
            ],
            [
                'title' => 'JavaScript Programming',
                'short_description' => 'Learn JavaScript programming from basics to advanced concepts',
                'level' => 'Beginner',
                'price' => 99.99,
                'category_id' => 1
            ],
            [
                'title' => 'React.js Development',
                'short_description' => 'Build modern web applications with React.js',
                'level' => 'Intermediate',
                'price' => 119.99,
                'category_id' => 1
            ],
            [
                'title' => 'Web Development Bootcamp',
                'short_description' => 'Complete web development bootcamp from zero to hero',
                'level' => 'Beginner',
                'price' => 149.99,
                'category_id' => 1
            ]
        ];
        
        $sql = "INSERT INTO course (title, short_description, level, price, category_id, thumbnail_url, video_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        
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
                echo "Added: {$course['title']}\n";
            } catch (PDOException $e) {
                echo "Error adding {$course['title']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Show final course list
    echo "\nFinal course list:\n";
    $stmt = $pdo->query("SELECT id, title, short_description, level, category_id FROM course ORDER BY title");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($courses as $course) {
        echo "- {$course['title']} ({$course['level']}) - {$course['short_description']}\n";
    }
    
    echo "\n✓ Database is now ready for AI roadmap testing!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
