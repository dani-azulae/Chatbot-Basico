<?php
// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Check if this is an API request
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    require_once __DIR__ . '/../src/api.php';
    exit;
}

// For all other requests, serve the SPA frontend
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat IA con N8N</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <div id="app"></div>
    
    <!-- Compiled and bundled JS -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
