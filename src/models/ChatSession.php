<?php
require_once __DIR__ . '/BaseModel.php';

class ChatSession extends BaseModel {
    protected $table = 'chat_sessions';
    
    public function __construct() {
        parent::__construct();
    }
    
    // Create a new chat session
    public function createSession($userId, $title = 'New Chat') {
        $data = [
            'user_id' => $userId,
            'title' => $title
        ];
        
        return $this->create($data);
    }
    
    // Get sessions by user ID
    public function getByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY updated_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    // Update session timestamp
    public function updateTimestamp($sessionId) {
        $sql = "UPDATE {$this->table} SET updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $sessionId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>
