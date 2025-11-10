<?php
// Simulate an AJAX request to the admin messages endpoint
echo "Attempting to fetch admin messages via HTTP...\n\n";

$url = 'http://localhost:3000/web2final/LavaLust/admin/messages';

// Create stream context with headers
$options = array(
    'http' => array(
        'method' => 'GET',
        'header' => array(
            'X-Requested-With: XMLHttpRequest',
            'Cookie: LLSession=test'
        )
    )
);

$context = stream_context_create($options);

try {
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        echo "Failed to fetch the URL\n";
        echo "Make sure:\n";
        echo "  1. The web server is running\n";
        echo "  2. You're logged in as admin (session established)\n";
        echo "  3. URL is correct: " . $url . "\n";
    } else {
        echo "Response received:\n";
        echo $response . "\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
