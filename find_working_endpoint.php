<?php
// Test different Gemini API endpoints to find working one
$apiKey = 'AIzaSyBNaovrckRKsWZKRu-idWPO-GB0hPojGvc';

$endpoints = [
    'v1_gemini_pro' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . $apiKey,
    'v1beta_gemini_pro' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey,
    'v1_gemini_1_5_flash' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $apiKey,
    'v1beta_gemini_1_5_flash' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey,
    'v1beta_gemini_1_5_flash_latest' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey,
];

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

foreach ($endpoints as $name => $url) {
    echo "\n=== Testing $name ===\n";
    echo "URL: $url\n";
    
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
            break;
        } else {
            echo "❌ ERROR - " . json_encode($responseData) . "\n";
        }
    }
}
?>
