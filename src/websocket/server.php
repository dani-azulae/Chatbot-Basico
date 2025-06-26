<?php
// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../models/ChatMessage.php';
require __DIR__ . '/../models/ChatSession.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use GuzzleHttp\Client;

class ChatServer implements MessageComponentInterface
{
    protected $clients;
    private $connectionMap; // Maps connection to user ID
    private $httpClient;
    private $chatMessageModel;
    private $chatSessionModel;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->connectionMap = [];
        $this->httpClient = new Client();
        $this->chatMessageModel = new ChatMessage();
        $this->chatSessionModel = new ChatSession();
        echo "Chat Server started\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'auth':
                // Store user ID with connection
                $this->connectionMap[$from->resourceId] = [
                    'user_id' => $data['user_id'],
                    'session_id' => $data['session_id'] ?? null
                ];

                // Send confirmation
                $from->send(json_encode([
                    'type' => 'auth_success',
                    'message' => 'Authentication successful'
                ]));
                break;

            case 'message':
                if (!isset($this->connectionMap[$from->resourceId])) {
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Not authenticated'
                    ]));
                    return;
                }

                $userId = $this->connectionMap[$from->resourceId]['user_id'];
                $sessionId = $data['session_id'] ?? $this->connectionMap[$from->resourceId]['session_id'];
                $message = $data['message'];

                // Store user message
                $this->chatMessageModel->createUserMessage($userId, $message, $sessionId);

                // Update session timestamp
                $this->chatSessionModel->updateTimestamp($sessionId);

                // Forward message to all clients (for real-time updates in multiple tabs)
                foreach ($this->clients as $client) {
                    if (
                        isset($this->connectionMap[$client->resourceId]) &&
                        $this->connectionMap[$client->resourceId]['session_id'] == $sessionId
                    ) {
                        $client->send(json_encode([
                            'type' => 'user_message',
                            'user_id' => $userId,
                            'message' => $message,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]));
                    }
                }

                // Send to N8N
                try {

                    //BORRAR
                    echo "Sending request to N8N at {$_ENV['N8N_ENDPOINT']}...\n";
                    echo "Payload: " . json_encode([
                        'message' => $message,
                        'user_id' => $userId,
                        'session_id' => $sessionId
                    ]) . "\n";


                    $response = $this->httpClient->post($_ENV['N8N_ENDPOINT'], [
                        'json' => [
                            'message' => $message,
                            'user_id' => $userId,
                            'session_id' => $sessionId
                        ]
                    ]);

                    echo 'PETAAAA';
                    
                    $aiResponse = json_decode($response->getBody(), true);
                    $aiMessage = $aiResponse['message'] ?? 'Sorry, I could not process your request.';

                    // Store AI response
                    $this->chatMessageModel->createBotMessage($aiMessage, $sessionId, $userId);

                    // Forward AI message to clients
                    foreach ($this->clients as $client) {
                        if (
                            isset($this->connectionMap[$client->resourceId]) &&
                            $this->connectionMap[$client->resourceId]['session_id'] == $sessionId
                        ) {
                            $client->send(json_encode([
                                'type' => 'ai_message',
                                'message' => $aiMessage,
                                'timestamp' => date('Y-m-d H:i:s')
                            ]));
                        }
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Error connecting to AI service'
                    ]));
                }
                break;

            case 'stream_message':
                // For streaming responses from N8N
                // This would require N8N to send streaming updates back
                if (!isset($data['stream_chunk'])) break;

                if (!isset($this->connectionMap[$from->resourceId])) {
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Not authenticated'
                    ]));
                    return;
                }

                $sessionId = $data['session_id'] ?? $this->connectionMap[$from->resourceId]['session_id'];

                // Forward stream chunk to appropriate clients
                foreach ($this->clients as $client) {
                    if (
                        isset($this->connectionMap[$client->resourceId]) &&
                        $this->connectionMap[$client->resourceId]['session_id'] == $sessionId
                    ) {
                        $client->send(json_encode([
                            'type' => 'stream_chunk',
                            'chunk' => $data['stream_chunk'],
                            'is_final' => $data['is_final'] ?? false
                        ]));
                    }
                }
                break;

            case 'switch_session':
                if (!isset($this->connectionMap[$from->resourceId])) {
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Not authenticated'
                    ]));
                    return;
                }

                // Update session ID
                $this->connectionMap[$from->resourceId]['session_id'] = $data['session_id'];

                $from->send(json_encode([
                    'type' => 'session_switched',
                    'session_id' => $data['session_id']
                ]));
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove connection from any stores
        $this->clients->detach($conn);
        unset($this->connectionMap[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Get WebSocket port from environment
$port = $_ENV['WS_PORT'] ?? 8080;

// Run the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $port
);

echo "WebSocket server running on port $port\n";
$server->run();
