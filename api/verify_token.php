<?php
require_once '../utils/errors.php';
require_once '../utils/cors.php';
require_once '../config/database.php';
require_once '../utils/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get token from Authorization header
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token not provided']);
    exit;
}

// Verify token
$decoded = verifyToken($token);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

// Get user from database
$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT id, email, role, share_code, created_at FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $decoded['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'share_code' => $user['share_code'],
            'created_at' => $user['created_at']
        ]
    ]);

} catch(PDOException $e) {
    error_log("Database error in verify_token: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    $errorMessage = 'Verification failed. Please try again later.';
    if (ini_get('display_errors')) {
        $errorMessage .= ' Error: ' . $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch(Exception $e) {
    error_log("General error in verify_token: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    $errorMessage = 'Verification failed. Please try again later.';
    if (ini_get('display_errors')) {
        $errorMessage .= ' Error: ' . $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}
?>
