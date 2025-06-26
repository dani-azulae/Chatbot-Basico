<?php
// Set content type to JSON for all API responses
header('Content-Type: application/json');

// Get the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Define API routes
$routes = [
    // User routes
    'POST:/api/users/register' => ['UserController', 'register'],
    'POST:/api/users/login' => ['UserController', 'login'],
    'POST:/api/users/logout' => ['UserController', 'logout'],
    'GET:/api/users/profile' => ['UserController', 'getProfile'],
    'PUT:/api/users/profile' => ['UserController', 'updateProfile'],
    'GET:/api/users' => ['UserController', 'getAllUsers'],
    
    // Document routes
    'GET:/api/documents' => ['DocumentController', 'getAll'],
    'GET:/api/documents/search' => ['DocumentController', 'search'],
    'POST:/api/documents' => ['DocumentController', 'upload'],
    'GET:/api/documents/{id}' => ['DocumentController', 'getById'],
    'PUT:/api/documents/{id}' => ['DocumentController', 'update'],
    'DELETE:/api/documents/{id}' => ['DocumentController', 'delete'],
    
    // Chat routes
    'GET:/api/chats' => ['ChatController', 'getSessions'],
    'POST:/api/chats' => ['ChatController', 'createSession'],
    'GET:/api/chats/{id}/messages' => ['ChatController', 'getSessionMessages'],
    'PUT:/api/chats/{id}' => ['ChatController', 'updateSessionTitle'],
    'DELETE:/api/chats/{id}' => ['ChatController', 'deleteSession']
];

// Route the request
$route = "$method:$uri";
$routeMatched = false;

// Check for routes with parameters
foreach ($routes as $pattern => $handler) {    // Replace {id} placeholder with regex
    $regexPattern = preg_replace('/\{(\w+)\}/', '([^\/]+)', $pattern);
    
    // Escapar caracteres especiales de regex, excepto los paréntesis que necesitamos para capturar
    $regexPattern = str_replace('/', '\/', $regexPattern);
    
    // Check if pattern matches the route
    if (preg_match('#^' . $regexPattern . '$#', $route, $matches)) {        // Extract parameters
        preg_match_all('/\{(\w+)\}/', $pattern, $paramNames);
        array_shift($matches); // Remove the full match
        
        // Create parameter array
        $params = [];
        foreach ($matches as $index => $value) {
            if (isset($paramNames[1][$index])) {
                $params[] = $value; // Pasar los valores como argumentos posicionales, no asociativos
            } else {
                $params[] = $value;
            }
        }
        
        // Load controller
        require_once __DIR__ . "/controllers/{$handler[0]}.php";
        $controller = new $handler[0]();
        
        // Call method with parameters
        call_user_func_array([$controller, $handler[1]], $params);
        
        $routeMatched = true;
        break;
    }
}

// If no route matched
if (!$routeMatched) {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}
?>
