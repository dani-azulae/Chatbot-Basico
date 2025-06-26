<?php
require_once __DIR__ . '/BaseModel.php';

class ChatMessage extends BaseModel {
    protected $table = 'chat_messages';
    
    public function __construct() {
        parent::__construct();
    }
    
    // Create a user message
    public function createUserMessage($userId, $message, $sessionId) {
        $data = [
            'user_id' => $userId,
            'message' => $message,
            'is_bot' => 0,
            'session_id' => $sessionId
        ];
        
        return $this->create($data);
    }
    
    // Create a bot message
    public function createBotMessage($message, $sessionId, $userId = null) {
        $data = [
            'user_id' => $userId,
            'message' => $message,
            'is_bot' => 1,
            'session_id' => $sessionId
        ];
        
        return $this->create($data);
    }
    
    // Get messages by session ID
    public function getBySessionId($sessionId, $limit = 50) {
        $sql = "SELECT * FROM {$this->table} WHERE session_id = :session_id ORDER BY created_at ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    // Get recent messages for a user
    public function getRecentByUser($userId, $limit = 20) {
        $sql = "SELECT m.*, s.title as session_title 
                FROM {$this->table} m
                JOIN chat_sessions s ON m.session_id = s.id 
                WHERE m.user_id = :user_id 
                ORDER BY m.created_at DESC 
                LIMIT :limit";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>
