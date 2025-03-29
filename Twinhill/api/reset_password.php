<?php
require_once 'config.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token']) || !isset($data['code']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$token = $data['token'];
$code = $data['code'];
$newPassword = $data['new_password'];

try {
    // Verify token and code
    $stmt = $pdo->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = :token AND pr.used = 0 AND pr.expires_at > NOW()");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resetRequest) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset link']);
        exit;
    }
    
    // In a real app, you would verify the code sent via email
    // For this example, we're using the code returned in the forgot_password response
    if ($code !== $resetRequest['verification_code']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit;
    }
    
    // Update password
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':user_id', $resetRequest['user_id']);
    $stmt->execute();
    
    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
    $stmt->bindParam(':id', $resetRequest['id']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>