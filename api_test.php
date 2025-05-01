<?php
// Simple Gemini API test
$api_key = 'AIzaSyDFnDzuI_XtqBfiT4e43xfOaDuqDikjDnk';
$endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash-lite:generateContent';

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Create a simple budget for an NSF grant with 3 items. Format as JSON with budget_items array.'
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.2,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 8192
    ]
];

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $endpoint . '?key=' . $api_key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Execute cURL session and get the response
$response = curl_exec($ch);

// Get HTTP code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "HTTP Response Code: " . $httpCode . "\n\n";
    echo "Response:\n" . $response;
}

curl_close($ch);
?>