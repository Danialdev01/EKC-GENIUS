<?php

function callAI($prompt, $model, $apiKey) {
    $url = 'https://openrouter.ai/api/v1/chat/completions';

    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
        'X-Title: EKC-Genius'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'API request failed with code ' . $httpCode,
            'response' => $response
        ];
    }

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'content' => $result['choices'][0]['message']['content']
        ];
    }

    return [
        'success' => false,
        'error' => 'No content in response',
        'response' => $result
    ];
}