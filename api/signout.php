<?php
require_once '../utils/cors.php';

// Sign out is handled on the client side by removing the token
// This endpoint is just for consistency
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Signed out successfully']);
?>
