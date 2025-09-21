<?php
// Тестируем API endpoint для генерации заклинаний
$url = 'http://localhost/dnd/public/api/generate-spells.php';
$data = [
    'level' => 1,
    'class' => 'wizard',
    'count' => 2
];

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error: Could not connect to API\n";
} else {
    echo "API Response:\n";
    echo $result . "\n";
    
    $response = json_decode($result, true);
    if ($response && $response['success']) {
        echo "Success! Generated " . count($response['spells']) . " spells\n";
        foreach ($response['spells'] as $spell) {
            echo "- " . $spell['name'] . " (Level " . $spell['level'] . ")\n";
        }
    } else {
        echo "API Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
}
?>
