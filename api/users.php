<?php

require_once __DIR__ . '/bootstrap.php';

use App\Database;
use App\User;

// Set response header
header('Content-Type: application/json');

try {
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = str_replace('/api', '', $path);

    // Initialize database and user service
    $database = new Database();
    $database->connect();
    $userService = new User($database);

    // Route handling
    if ($path === '/users' && $method === 'GET') {
        // Get all users with optional sorting and pagination
        $sortBy = $_GET['sortBy'] ?? 'id';
        $sortOrder = $_GET['sortOrder'] ?? 'ASC';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : null;

        $users = $userService->getAllUsers($sortBy, $sortOrder, $limit, $offset);
        echo json_encode([
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ]);
    } elseif ($path === '/users' && $method === 'POST') {
        // Create a new user
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $userService->createUser($data);
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'userId' => $userId
        ]);
    } elseif (preg_match('/\/users\/(\d+)$/', $path, $matches) && $method === 'GET') {
        // Get user by ID
        $userId = (int)$matches[1];
        $user = $userService->getUserById($userId);
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } elseif (preg_match('/\/users\/(\d+)$/', $path, $matches) && $method === 'PUT') {
        // Update user
        $userId = (int)$matches[1];
        $data = json_decode(file_get_contents('php://input'), true);
        $rowCount = $userService->updateUser($userId, $data);
        if ($rowCount > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } elseif (preg_match('/\/users\/(\d+)$/', $path, $matches) && $method === 'DELETE') {
        // Delete user
        $userId = (int)$matches[1];
        $rowCount = $userService->deleteUser($userId);
        if ($rowCount > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
