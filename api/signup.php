<?php
require_once '../utils/cors.php';
require_once '../config/database.php';
require_once '../utils/auth.php';
require_once '../utils/shareCode.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate password
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Check if user already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate unique share code with retry logic
    $shareCode = null;
    $codeGenerationAttempts = 0;
    $maxCodeGenerationAttempts = 5;
    
    while ($codeGenerationAttempts < $maxCodeGenerationAttempts) {
        try {
            $shareCode = generateShareCode($db);
            break; // Successfully generated unique code
        } catch (Exception $e) {
            $codeGenerationAttempts++;
            if ($codeGenerationAttempts >= $maxCodeGenerationAttempts) {
                error_log("Failed to generate unique share code: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to generate share code. Please try again.']);
                exit;
            }
            // Wait a bit before retrying (microseconds)
            usleep(100000); // 0.1 seconds
        }
    }

    // Insert new user (default role is 'user')
    // Database UNIQUE constraint on share_code will catch any race conditions
    $user = null;
    $insertRetryAttempts = 0;
    $maxInsertRetries = 3;
    
    while ($insertRetryAttempts < $maxInsertRetries) {
        try {
            $insertQuery = "INSERT INTO users (email, password, role, share_code, created_at) 
                            VALUES (:email, :password, 'user', :share_code, NOW()) 
                            RETURNING id, email, role, share_code, created_at";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':password', $hashedPassword);
            $insertStmt->bindParam(':share_code', $shareCode);
            $insertStmt->execute();

            $user = $insertStmt->fetch(PDO::FETCH_ASSOC);
            
            // Double-check share_code was assigned
            if (empty($user['share_code'])) {
                throw new PDOException("Share code was not assigned to user");
            }
            
            break; // Success - exit retry loop
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Check if error is due to unique constraint violation (share_code or email)
            // PostgreSQL error code 23505 is unique_violation
            if ($errorCode == 23505 || strpos(strtolower($errorMessage), 'unique') !== false || 
                strpos(strtolower($errorMessage), 'duplicate') !== false) {
                
                // Check if it's a share_code collision (not email)
                if (strpos(strtolower($errorMessage), 'email') === false) {
                    $insertRetryAttempts++;
                    if ($insertRetryAttempts >= $maxInsertRetries) {
                        error_log("Failed to insert user after {$maxInsertRetries} attempts due to share code collision");
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
                        exit;
                    }
                    // Generate new share code and retry
                    try {
                        $shareCode = generateShareCode($db);
                        usleep(100000); // Small delay before retry
                        continue; // Retry the insert
                    } catch (Exception $codeException) {
                        error_log("Failed to regenerate share code: " . $codeException->getMessage());
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
                        exit;
                    }
                } else {
                    // Email already exists - shouldn't happen as we check earlier, but handle it
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Email already registered']);
                    exit;
                }
            } else {
                // Other database error - rethrow
                throw $e;
            }
        }
    }
    
    if (!$user) {
        error_log("Failed to insert user after all retry attempts");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
        exit;
    }

    // Generate token
    $token = generateToken($user['id'], $email, $user['role']);

    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'share_code' => $user['share_code'],
            'created_at' => $user['created_at']
        ]
    ]);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
}
?>
