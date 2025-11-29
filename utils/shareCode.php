<?php
// Generate a unique 6-character alphanumeric share code
function generateShareCode($db) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxAttempts = 100;
    $attempt = 0;
    
    do {
        // Generate random 6-character code
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists in database
        $checkQuery = "SELECT id FROM users WHERE share_code = :code LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':code', $code, PDO::PARAM_STR);
        $checkStmt->execute();
        
        // If code doesn't exist, it's unique
        if ($checkStmt->rowCount() === 0) {
            return $code;
        }
        
        $attempt++;
    } while ($attempt < $maxAttempts);
    
    // If we couldn't generate a unique code after max attempts, throw error
    throw new Exception("Failed to generate unique share code after {$maxAttempts} attempts. Please try again.");
}

// Check if a share code exists (helper function)
function shareCodeExists($db, $code) {
    $query = "SELECT id FROM users WHERE share_code = :code LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':code', $code, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}
?>

