<?php
// Secret key for token generation (change this to a secure random string in production)
define('JWT_SECRET', 'your-secret-key-change-this-in-production-12345');

// Generate a simple token (for production, use a proper JWT library like firebase/php-jwt)
function generateToken($userId, $email) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'email' => $email,
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60) // 7 days
    ]));
    
    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature = base64_encode($signature);
    
    return "$header.$payload.$signature";
}

// Verify token
function verifyToken($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $expectedSignature = base64_encode($expectedSignature);
    
    if ($signature !== $expectedSignature) {
        return false;
    }
    
    // Decode payload
    $decoded = json_decode(base64_decode($payload), true);
    
    if (!$decoded) {
        return false;
    }
    
    // Check expiration
    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        return false;
    }
    
    return $decoded;
}
?>

