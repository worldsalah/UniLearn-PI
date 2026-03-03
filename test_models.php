<?php
// List available Gemini models
$apiKey = 'AIzaSyDX9dMk47rNCCaZ6OJxZEfkfHNrUpjcbAo';
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

echo "Getting available models...\n";
$response = file_get_contents($url, false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json',
        'ignore_errors' => true
    ]
]));

if ($response === false) {
    echo "API call failed\n";
} else {
    echo "Available Models:\n";
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        foreach ($data['models'] as $model) {
            if (strpos($model['name'], 'generateContent') !== false) {
                echo "- " . $model['name'] . "\n";
            }
        }
    }
}
?>
