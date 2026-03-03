<?php
// Test Gemini API with correct model
$apiKey = 'AIzaSyDX9dMk47rNCCaZ6OJxZEfkfHNrUpjcbAo';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Hello, generate a simple quiz question about programming'
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

echo "Testing Gemini API with gemini-pro...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "API call failed\n";
    $error = error_get_last();
    echo "Error: " . print_r($error, true) . "\n";
} else {
    echo "API Response:\n";
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'])) {
        echo "SUCCESS: Got response from Gemini!\n";
        echo "Generated text: " . $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "ERROR: " . json_encode($responseData) . "\n";
    }
}
?>
