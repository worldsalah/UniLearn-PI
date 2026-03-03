<?php
/**
 * Performance Test - Course Recommendation System
 * Tests a heavy feature that can be optimized significantly
 * Measures: Execution Time + Memory Usage
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     UNILEARN - PERFORMANCE TEST (Course Recommendations)     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Memory tracking helper
function formatBytes(int $bytes): string {
    return round($bytes / 1024 / 1024, 2) . ' MB';
}

// Time tracking
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

echo "════════════════════════════════════════════════════════════════\n";
echo "BASELINE TEST (Before Optimization)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// ============================================================================
// FEATURE: Course Recommendation Engine (Heavy Processing)
// This simulates a real recommendation system that:
// 1. Loads all courses from "database" (simulated with array)
// 2. Loads user preferences
// 3. Calculates similarity scores for each course
// 4. Sorts and returns top recommendations
// ============================================================================

// Simulate database with 10000 courses (large e-learning platform)
$courses = [];
for ($i = 1; $i <= 10000; $i++) {
    $courses[] = [
        'id' => $i,
        'title' => "Course $i: " . generateRandomTitle(),
        'category' => ['Web Dev', 'Mobile', 'AI', 'Data Science', 'Design', 'Business'][rand(0, 5)],
        'level' => ['beginner', 'intermediate', 'advanced'][rand(0, 2)],
        'price' => rand(10, 200),
        'rating' => rand(30, 50) / 10,
        'enrollments' => rand(100, 5000),
        'duration_hours' => rand(5, 100),
        'tags' => generateRandomTags(rand(3, 8)),
        'instructor_id' => rand(1, 50),
        'created_at' => date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')),
    ];
}

// Simulate user preferences
$userPreferences = [
    'interests' => ['Web Dev', 'AI', 'Data Science'],
    'level' => 'intermediate',
    'max_price' => 100,
    'min_rating' => 3.5,
    'preferred_duration' => [10, 50], // min-max hours
];

echo "Test Data:\n";
echo "  - Courses loaded: " . count($courses) . "\n";
echo "  - User interests: " . implode(', ', $userPreferences['interests']) . "\n\n";

// ============================================================================
// BASELINE ALGORITHM (Inefficient - Multiple loops, no caching)
// ============================================================================

echo "─── Running BASELINE Algorithm (Inefficient) ───\n\n";

$baselineStart = microtime(true);
$baselineMemoryBefore = memory_get_usage(true);

$recommendations = [];

// STEP 1: Filter by category (inefficient - loops through all courses multiple times)
$filteredByCategory = [];
foreach ($courses as $course) {
    foreach ($userPreferences['interests'] as $interest) {
        if (strpos($course['category'], $interest) !== false) {
            $filteredByCategory[] = $course;
            break;
        }
    }
}

// STEP 2: Filter by level (another full loop)
$filteredByLevel = [];
foreach ($filteredByCategory as $course) {
    if ($course['level'] === $userPreferences['level']) {
        $filteredByLevel[] = $course;
    }
}

// STEP 3: Filter by price (another loop)
$filteredByPrice = [];
foreach ($filteredByLevel as $course) {
    if ($course['price'] <= $userPreferences['max_price']) {
        $filteredByPrice[] = $course;
    }
}

// STEP 4: Filter by rating (another loop)
$filteredByRating = [];
foreach ($filteredByPrice as $course) {
    if ($course['rating'] >= $userPreferences['min_rating']) {
        $filteredByRating[] = $course;
    }
}

// STEP 5: Calculate similarity score for each course (expensive operation)
$scored = [];
foreach ($filteredByRating as $course) {
    $score = 0;
    
    // Category match score (with nested loop)
    foreach ($userPreferences['interests'] as $interest) {
        if (strpos($course['category'], $interest) !== false) {
            $score += 30;
        }
    }
    
    // Tag matching (nested loops - very inefficient)
    foreach ($course['tags'] as $tag) {
        foreach ($userPreferences['interests'] as $interest) {
            if (stripos($tag, $interest) !== false) {
                $score += 10;
            }
        }
    }
    
    // Rating score
    $score += ($course['rating'] / 5) * 20;
    
    // Popularity score
    $score += min($course['enrollments'] / 100, 10);
    
    // Duration preference score
    if ($course['duration_hours'] >= $userPreferences['preferred_duration'][0] && 
        $course['duration_hours'] <= $userPreferences['preferred_duration'][1]) {
        $score += 15;
    }
    
    // Price score (cheaper = higher score)
    $score += max(0, ($userPreferences['max_price'] - $course['price']) / 10);
    
    $course['score'] = $score;
    $scored[] = $course;
}

// STEP 6: Sort by score (bubble sort - intentionally inefficient)
$sorted = $scored;
$n = count($sorted);
for ($i = 0; $i < $n - 1; $i++) {
    for ($j = 0; $j < $n - $i - 1; $j++) {
        if ($sorted[$j]['score'] < $sorted[$j + 1]['score']) {
            $temp = $sorted[$j];
            $sorted[$j] = $sorted[$j + 1];
            $sorted[$j + 1] = $temp;
        }
    }
}

// STEP 7: Get top 10 recommendations
$baselineRecommendations = array_slice($sorted, 0, 10);

$baselineEnd = microtime(true);
$baselineMemoryAfter = memory_get_usage(true);

$baselineTime = ($baselineEnd - $baselineStart) * 1000;
$baselineMemory = $baselineMemoryAfter - $baselineMemoryBefore;

echo "Results:\n";
echo "  - Filtered courses: " . count($filteredByRating) . "\n";
echo "  - Final recommendations: " . count($baselineRecommendations) . "\n\n";

echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│ BASELINE METRICS                                               │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
printf("│ Execution Time:  %10.2f ms                              │\n", $baselineTime);
printf("│ Memory Used:     %10s                               │\n", formatBytes($baselineMemory));
printf("│ Peak Memory:     %10s                               │\n", formatBytes(memory_get_peak_usage(true)));
echo "└────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// OPTIMIZED ALGORITHM (Single pass, early filtering, efficient sorting)
// ============================================================================

echo "════════════════════════════════════════════════════════════════\n";
echo "OPTIMIZED TEST (After Optimization)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "─── Running OPTIMIZED Algorithm ───\n\n";

// Reset memory
gc_collect_cycles();

$optimizedStart = microtime(true);
$optimizedMemoryBefore = memory_get_usage(true);

// Pre-compute user interest lookup (O(1) lookup instead of O(n))
$interestLookup = array_flip($userPreferences['interests']);

// Single pass through courses with early filtering
$scored = [];
foreach ($courses as $course) {
    // Early exit: Check all filters in one pass
    if ($course['price'] > $userPreferences['max_price']) continue;
    if ($course['rating'] < $userPreferences['min_rating']) continue;
    if ($course['level'] !== $userPreferences['level']) continue;
    
    // Category check with O(1) lookup
    if (!isset($interestLookup[$course['category']])) continue;
    
    // Calculate score in single operation
    $score = 30; // Category matched
    
    // Tag matching with early exit
    $tagCount = 0;
    foreach ($course['tags'] as $tag) {
        foreach ($userPreferences['interests'] as $interest) {
            if (stripos($tag, $interest) !== false) {
                $score += 10;
                $tagCount++;
                if ($tagCount >= 3) break 2; // Cap tag bonus
            }
        }
    }
    
    // Combined score calculation
    $score += ($course['rating'] / 5) * 20;
    $score += min($course['enrollments'] / 100, 10);
    
    if ($course['duration_hours'] >= $userPreferences['preferred_duration'][0] && 
        $course['duration_hours'] <= $userPreferences['preferred_duration'][1]) {
        $score += 15;
    }
    
    $score += max(0, ($userPreferences['max_price'] - $course['price']) / 10);
    
    $scored[] = ['course' => $course, 'score' => $score];
}

// Use PHP's built-in quicksort (much faster than bubble sort)
usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

// Extract top 10
$optimizedRecommendations = array_slice($scored, 0, 10);

$optimizedEnd = microtime(true);
$optimizedMemoryAfter = memory_get_usage(true);

$optimizedTime = ($optimizedEnd - $optimizedStart) * 1000;
$optimizedMemory = $optimizedMemoryAfter - $optimizedMemoryBefore;

echo "Results:\n";
echo "  - Filtered courses: " . count($scored) . "\n";
echo "  - Final recommendations: " . count($optimizedRecommendations) . "\n\n";

echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│ OPTIMIZED METRICS                                              │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
printf("│ Execution Time:  %10.2f ms                              │\n", $optimizedTime);
printf("│ Memory Used:     %10s                               │\n", formatBytes($optimizedMemory));
printf("│ Peak Memory:     %10s                               │\n", formatBytes(memory_get_peak_usage(true)));
echo "└────────────────────────────────────────────────────────────────┘\n\n";

// ============================================================================
// COMPARISON SUMMARY
// ============================================================================

echo "════════════════════════════════════════════════════════════════\n";
echo "COMPARISON SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$timeImprovement = $baselineTime > 0 ? (($baselineTime - $optimizedTime) / $baselineTime) * 100 : 0;
$memoryImprovement = $baselineMemory > 0 ? (($baselineMemory - $optimizedMemory) / $baselineMemory) * 100 : 0;
$speedupFactor = $optimizedTime > 0 ? $baselineTime / $optimizedTime : 1;

echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│                    BEFORE vs AFTER                             │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
printf("│ %-20s %12s %12s %10s    │\n", "Metric", "Baseline", "Optimized", "Improvement");
echo "├────────────────────────────────────────────────────────────────┤\n";
printf("│ %-20s %10.2fms %10.2fms %9.1f%%    │\n", "Execution Time", $baselineTime, $optimizedTime, $timeImprovement);
printf("│ %-20s %10s %10s %9.1f%%    │\n", "Memory Usage", formatBytes($baselineMemory), formatBytes($optimizedMemory), $memoryImprovement);
printf("│ %-20s %10.1fx faster                         │\n", "Speedup", $speedupFactor);
echo "└────────────────────────────────────────────────────────────────┘\n\n";

echo "OPTIMIZATIONS APPLIED:\n";
echo "  ✓ Single-pass filtering (vs 5 separate loops)\n";
echo "  ✓ O(1) hash lookup for category matching\n";
echo "  ✓ Early exit conditions\n";
echo "  ✓ Built-in quicksort O(n log n) vs bubble sort O(n²)\n";
echo "  ✓ Reduced array copying\n";
echo "  ✓ Capped tag bonus calculation\n\n";

// Helper functions
function generateRandomTitle(): string {
    $words = ['Complete', 'Master', 'Professional', 'Advanced', 'Essential', 'Practical'];
    $topics = ['Development', 'Programming', 'Engineering', 'Architecture', 'Design', 'Analytics'];
    return $words[array_rand($words)] . ' ' . $topics[array_rand($topics)];
}

function generateRandomTags(int $count): array {
    $allTags = ['javascript', 'python', 'react', 'node', 'api', 'database', 'frontend', 
                'backend', 'mobile', 'web', 'ai', 'ml', 'data', 'cloud', 'docker',
                'testing', 'security', 'performance', 'ui', 'ux'];
    shuffle($allTags);
    return array_slice($allTags, 0, $count);
}

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

echo "════════════════════════════════════════════════════════════════\n";
printf("Total Test Duration: %.2f ms\n", ($endTime - $startTime) * 1000);
printf("Total Memory Peak: %s\n", formatBytes(memory_get_peak_usage(true)));
echo "════════════════════════════════════════════════════════════════\n";
