<?php
// Test the updated service
$apiKey = 'AIzaSyBNaovrckRKsWZKRu-idWPO-GB0hPojGvc';
$url = 'https://generativelanguage.googleapis.com/v1beta/generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Generate a sample multiple choice question about PHP programming'
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
        'timeout' => 30
    ]
];

$context = stream_context_create($options);

echo "Testing updated service configuration...\n";
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ FAILED - No response\n";
    $error = error_get_last();
    echo "Error: " . print_r($error, true) . "\n";
} else {
    echo "Raw Response: " . $response . "\n\n";
    $responseData = json_decode($response, true);
    
    if (isset($responseData['error'])) {
        echo "❌ API Error: " . $responseData['error']['message'] . "\n";
    } elseif (isset($responseData['candidates'])) {
        echo "✅ SUCCESS - Working endpoint found!\n";
        echo "Response: " . $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "❌ UNKNOWN RESPONSE FORMAT\n";
        echo "Full Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
}
?>
