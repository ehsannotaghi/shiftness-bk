<?php
require_once 'auth.php';

// Check if user is admin from token
function isAdmin($token) {
    $decoded = verifyToken($token);
    
    if (!$decoded) {
        return false;
    }
    
    // Check if role is admin (from token)
    if (isset($decoded['role']) && $decoded['role'] === 'admin') {
        return true;
    }
    
    // Also check database to be sure
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT role FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $decoded['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user['role'] === 'admin';
        }
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
    }
    
    return false;
}

// Middleware function to require admin access
function requireAdmin() {
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
    
    return true;
}
?>

