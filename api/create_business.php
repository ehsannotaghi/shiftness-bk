<?php
require_once '../utils/errors.php';
require_once '../utils/cors.php';
require_once '../config/database.php';
require_once '../utils/auth.php';
require_once '../utils/checkAdmin.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify admin access
$headers = getallheaders();
$token = null;
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (!$token || !isAdmin($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Get user info from token
$decoded = verifyToken($token);
if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['name']) || empty(trim($data['name']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Business name is required']);
    exit;
}

$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';

// Validate name length
if (strlen($name) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Business name is too long']);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Insert new business
    $insertQuery = "INSERT INTO businesses (name, description, created_by, created_at) 
                    VALUES (:name, :description, :created_by, NOW()) 
                    RETURNING id, name, description, created_by, created_at";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':name', $name);
    $insertStmt->bindParam(':description', $description);
    $insertStmt->bindParam(':created_by', $decoded['user_id']);
    $insertStmt->execute();

    $business = $insertStmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Business created successfully',
        'business' => [
            'id' => $business['id'],
            'name' => $business['name'],
            'description' => $business['description'],
            'created_by' => $business['created_by'],
            'created_at' => $business['created_at']
        ]
    ]);

} catch(PDOException $e) {
    error_log("Database error in create_business: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    $errorMessage = 'Failed to create business. Please try again later.';
    if (ini_get('display_errors')) {
        $errorMessage .= ' Error: ' . $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch(Exception $e) {
    error_log("General error in create_business: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    $errorMessage = 'Failed to create business. Please try again later.';
    if (ini_get('display_errors')) {
        $errorMessage .= ' Error: ' . $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}
?>

