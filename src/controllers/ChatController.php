<?php
require_once __DIR__ . '/../models/ChatMessage.php';
require_once __DIR__ . '/../models/ChatSession.php';

class ChatController {
    private $chatMessageModel;
    private $chatSessionModel;
    
    public function __construct() {
        $this->chatMessageModel = new ChatMessage();
        $this->chatSessionModel = new ChatSession();
    }
    
    public function createSession() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        $title = $data['title'] ?? 'New Chat';
        
        // Create session
        $sessionId = $this->chatSessionModel->createSession($_SESSION['user_id'], $title);
        
        if ($sessionId) {
            echo json_encode([
                'success' => true,
                'session_id' => $sessionId,
                'title' => $title
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create chat session']);
        }
    }
    
    public function getSessions() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        $sessions = $this->chatSessionModel->getByUserId($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'sessions' => $sessions
        ]);
    }
    
    public function getSessionMessages($sessionId) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get session
        $session = $this->chatSessionModel->getById($sessionId);
        
        if (!$session) {
            http_response_code(404);
            echo json_encode(['error' => 'Session not found']);
            return;
        }
        
        // Check if the session belongs to the user
        if ($session['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Get messages
        $messages = $this->chatMessageModel->getBySessionId($sessionId);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    public function updateSessionTitle($sessionId) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get session
        $session = $this->chatSessionModel->getById($sessionId);
        
        if (!$session) {
            http_response_code(404);
            echo json_encode(['error' => 'Session not found']);
            return;
        }
        
        // Check if the session belongs to the user
        if ($session['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Get data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || empty($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        // Update session
        $success = $this->chatSessionModel->update($sessionId, ['title' => $data['title']]);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update session title']);
        }
    }
    
    public function deleteSession($sessionId) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get session
        $session = $this->chatSessionModel->getById($sessionId);
        
        if (!$session) {
            http_response_code(404);
            echo json_encode(['error' => 'Session not found']);
            return;
        }
        
        // Check if the session belongs to the user
        if ($session['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Delete session (this will also delete associated messages due to ON DELETE CASCADE)
        $success = $this->chatSessionModel->delete($sessionId);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete session']);
        }
    }
}
?>
