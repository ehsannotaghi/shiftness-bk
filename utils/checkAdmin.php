<?php
require_once 'auth.php';

// Check if user is admin from token
function isAdmin($token) {
    $decoded = verifyToken($token);
    
    if (!$decoded) {
        return false;
    }
    
    // Check if role is admin (from token or database)
    if (isset($decoded['role']) && $decoded['role'] === 'admin') {
        return true;
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

