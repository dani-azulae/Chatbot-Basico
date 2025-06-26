<?php
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../../vendor/autoload.php';


class DocumentController {
    private $documentModel;
    
    public function __construct() {
        $this->documentModel = new Document();
    }
    
    public function getAll() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        $documents = $this->documentModel->getByUserId($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'documents' => $documents
        ]);
    }
    
    public function getById($id) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        $document = $this->documentModel->getById($id);
        
        if (!$document) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }
        
        // Check if the document belongs to the user
        if ($document['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'document' => $document
        ]);
    }
    
    public function upload() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Check if file is uploaded
        if (!isset($_FILES['document']) || $_FILES['document']['error'] != 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }
        
        // Get form data
        $title = $_POST['title'] ?? 'Untitled Document';
        
        // Create directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/' . $_SESSION['user_id'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . $_FILES['document']['name'];
        $filePath = $uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file']);
            return;
        }
        
        // Extract text content if it's a PDF or text file
        $content = '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($extension === 'txt') {
            $content = file_get_contents($filePath);
        } elseif ($extension === 'pdf' && extension_loaded('pdfparser')) {
            // This requires the PDF parser extension to be installed
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $content = $pdf->getText();
        }
        
        // Save document record
        $relativePath = 'uploads/' . $_SESSION['user_id'] . '/' . $filename;
        $docId = $this->documentModel->saveDocument($title, $relativePath, $content, $_SESSION['user_id']);
        
        if ($docId) {
            echo json_encode([
                'success' => true,
                'document_id' => $docId,
                'title' => $title,
                'path' => $relativePath
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save document record']);
        }
    }
    
    public function update($id) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get document
        $document = $this->documentModel->getById($id);
        
        if (!$document) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }
        
        // Check if the document belongs to the user
        if ($document['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Get data
        $data = json_decode(file_get_contents('php://input'), true);
        $updateData = [];
        
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        
        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }
        
        // If there's nothing to update
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid update data provided']);
            return;
        }
        
        // Update document
        $success = $this->documentModel->update($id, $updateData);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update document']);
        }
    }
    
    public function delete($id) {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get document
        $document = $this->documentModel->getById($id);
        
        if (!$document) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }
        
        // Check if the document belongs to the user
        if ($document['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        // Delete the file if it exists
        $filePath = __DIR__ . '/../../' . $document['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete document from database
        $success = $this->documentModel->delete($id);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete document']);
        }
    }
    
    public function search() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            return;
        }
        
        $documents = $this->documentModel->search($query);
        
        // Filter results to only show user's documents
        $userDocuments = array_filter($documents, function($doc) {
            return $doc['user_id'] == $_SESSION['user_id'];
        });
        
        echo json_encode([
            'success' => true,
            'documents' => array_values($userDocuments) // Reset array keys
        ]);
    }
}
?>
