<?php
// Test with v1 endpoint and gemini-1.5-flash
$apiKey = 'AIzaSyBNaovrckRKsWZKRu-idWPO-GB0hPojGvc';
$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Generate a sample multiple choice question about web development'
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

echo "Testing v1 endpoint with gemini-1.5-flash...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "API call failed\n";
    $error = error_get_last();
    echo "Error: " . print_r($error, true) . "\n";
} else {
    echo "API Response Status: SUCCESS\n";
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'])) {
        echo "✅ SUCCESS: Got response from Gemini!\n";
        echo "Generated Question: " . $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "❌ ERROR: " . json_encode($responseData) . "\n";
    }
}
?>
