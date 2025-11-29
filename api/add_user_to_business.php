<?php
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
if (!isset($data['business_id']) || !isset($data['share_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Business ID and share code are required']);
    exit;
}

$businessId = (int)$data['business_id'];
$shareCode = trim(strtoupper($data['share_code']));

// Validate share code format (6 characters alphanumeric)
if (strlen($shareCode) !== 6 || !preg_match('/^[A-Z0-9]{6}$/', $shareCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid share code format']);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Verify business exists and admin owns it
    $businessQuery = "SELECT id, name FROM businesses WHERE id = :business_id AND created_by = :admin_id";
    $businessStmt = $db->prepare($businessQuery);
    $businessStmt->bindParam(':business_id', $businessId);
    $businessStmt->bindParam(':admin_id', $decoded['user_id']);
    $businessStmt->execute();

    if ($businessStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business not found or you do not have permission']);
        exit;
    }

    $business = $businessStmt->fetch(PDO::FETCH_ASSOC);

    // Find user by share code
    $userQuery = "SELECT id, email FROM users WHERE share_code = :share_code";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':share_code', $shareCode);
    $userStmt->execute();

    if ($userStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found with this share code']);
        exit;
    }

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'];

    // Check if user is already in this business
    $checkQuery = "SELECT id FROM business_users WHERE business_id = :business_id AND user_id = :user_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':business_id', $businessId);
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'User is already added to this business']);
        exit;
    }

    // Add user to business
    $insertQuery = "INSERT INTO business_users (business_id, user_id, added_by, added_at) 
                    VALUES (:business_id, :user_id, :added_by, NOW()) 
                    RETURNING id, business_id, user_id, added_at";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':business_id', $businessId);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':added_by', $decoded['user_id']);
    $insertStmt->execute();

    $businessUser = $insertStmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User added to business successfully',
        'data' => [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ],
            'business' => [
                'id' => $business['id'],
                'name' => $business['name']
            ]
        ]
    ]);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add user to business. Please try again later.']);
}
?>

