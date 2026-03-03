<?php
// Test the correct Generative AI API endpoint
$apiKey = 'AIzaSyBNaovrckRKsWZKRu-idWPO-GB0hPojGvc';
$url = 'https://generativelanguage.googleapis.com/v1beta/generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Hello, generate a simple test response'
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
        'ignore_errors' => true,
        'timeout' => 10
    ]
];

$context = stream_context_create($options);

echo "Testing Generative AI API...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ FAILED\n";
    $error = error_get_last();
    echo "Error: " . print_r($error, true) . "\n";
} else {
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'])) {
        echo "✅ SUCCESS - Working endpoint found!\n";
        echo "Response: " . $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "❌ ERROR - " . json_encode($responseData) . "\n";
    }
}
?>
