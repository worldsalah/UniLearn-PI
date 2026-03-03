<?php
// Test Gemini API
$apiKey = 'AIzaSyDX9dMk47rNCCaZ6OJxZEfkfHNrUpjcbAo';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Hello, can you generate a sample quiz question?'
                ]
            ]
        ]
    ]
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);

echo "Testing Gemini API...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "API call failed\n";
    $error = error_get_last();
    echo "Error: " . print_r($error, true) . "\n";
} else {
    echo "API Response:\n";
    echo $response . "\n";
}
?>
