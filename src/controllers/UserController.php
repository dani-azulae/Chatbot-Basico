<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function register() {
        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        // Check if username or email already exists
        if ($this->userModel->findByUsername($data['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        
        if ($this->userModel->findByEmail($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
        
        // Create user
        $userId = $this->userModel->createUser(
            $data['username'],
            $data['password'],
            $data['email']
        );
        
        if ($userId) {
            // Start session
            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $data['username'];
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'user_id' => $userId,
                'username' => $data['username']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
        }
    }
    
    public function login() {
        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($data['username']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing username or password']);
            return;
        }
        
        // Authenticate
        $user = $this->userModel->authenticate($data['username'], $data['password']);
        
        if ($user) {
            // Start session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid username or password']);
        }
    }
    
    public function logout() {
        session_start();
        session_destroy();
        
        echo json_encode(['success' => true]);
    }
    
    public function getProfile() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get user profile
        $user = $this->userModel->getById($_SESSION['user_id']);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'created_at' => $user['created_at']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }
    
    public function updateProfile() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        $updateData = [];
        
        // Validate email if provided
        if (isset($data['email'])) {
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already in use']);
                return;
            }
            
            $updateData['email'] = $data['email'];
        }
        
        // Update password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // If there's nothing to update
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid update data provided']);
            return;
        }
        
        // Update user
        $success = $this->userModel->update($_SESSION['user_id'], $updateData);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
        }
    }
    
    public function getAllUsers() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }
        
        $users = $this->userModel->getAll();
        
        if ($users) {
            // Remove password from response
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get users']);
        }
    }
}
?>
