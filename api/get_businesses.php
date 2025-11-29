<?php
require_once '../utils/cors.php';
require_once '../config/database.php';
require_once '../utils/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    $userId = $decoded['user_id'];
    $isAdmin = isset($decoded['role']) && $decoded['role'] === 'admin';

    if ($isAdmin) {
        // Admin: Get all businesses they created
        $query = "SELECT b.id, b.name, b.description, b.created_at,
                         COUNT(DISTINCT bu.user_id) as user_count
                  FROM businesses b
                  LEFT JOIN business_users bu ON b.id = bu.business_id
                  WHERE b.created_by = :user_id
                  GROUP BY b.id, b.name, b.description, b.created_at
                  ORDER BY b.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    } else {
        // User: Get businesses they belong to
        $query = "SELECT b.id, b.name, b.description, b.created_at,
                         u.email as created_by_email,
                         bu.added_at
                  FROM businesses b
                  INNER JOIN business_users bu ON b.id = bu.business_id
                  LEFT JOIN users u ON b.created_by = u.id
                  WHERE bu.user_id = :user_id
                  ORDER BY bu.added_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'businesses' => $businesses
    ]);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch businesses. Please try again later.']);
}
?>

