<?php
require_once __DIR__ . '/BaseModel.php';

class Document extends BaseModel {
    protected $table = 'documents';
    
    public function __construct() {
        parent::__construct();
    }
    
    // Get documents by user ID
    public function getByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        
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
    
    // Save document
    public function saveDocument($title, $filePath, $content, $userId) {
        $data = [
            'title' => $title,
            'file_path' => $filePath,
            'content' => $content,
            'user_id' => $userId
        ];
        
        return $this->create($data);
    }
    
    // Search for documents by title or content
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} WHERE title LIKE :query OR content LIKE :query";
        $searchParam = "%$query%";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':query', $searchParam);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>
