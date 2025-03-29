<?php
require_once 'config.php';

header("Content-Type: application/json");

// Rate limiting - allow 3 attempts per hour
session_start();
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 0;
    $_SESSION['last_reset_time'] = time();
}

if (time() - $_SESSION['last_reset_time'] > 3600) {
    $_SESSION['reset_attempts'] = 0;
}

if ($_SESSION['reset_attempts'] >= 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Try again later.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and new password are required']);
    exit;
}

$email = $data['email'];
$newPassword = $data['new_password'];

try {
    // Verify email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Generic error message to prevent email enumeration
        $_SESSION['reset_attempts']++;
        echo json_encode(['success' => false, 'message' => 'If this email exists in our system, the password has been reset']);
        exit;
    }
    
    // Update password directly
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    // Record the reset (for audit purposes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, expires_at) VALUES (:user_id, :expires_at)");
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->bindParam(':expires_at', $expiresAt);
    $stmt->execute();
    
    $_SESSION['reset_attempts']++;
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
}
?>