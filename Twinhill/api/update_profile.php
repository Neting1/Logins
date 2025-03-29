<?php
require_once 'config.php';

$user = authenticateUser();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

try {
    // Verify current password if changing password
    if (!empty($data['new_password'])) {
        if (empty($data['current_password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password is required to change password']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user['userId']);
        $stmt->execute();
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($data['current_password'], $dbUser['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Update password
        $passwordHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':id', $user['userId']);
        $stmt->execute();
    }
    
    // Update email
    $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':id', $user['userId']);
    $stmt->execute();
    
    // Get updated user data
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user['userId']);
    $stmt->execute();
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'id' => $updatedUser['id'],
            'username' => $updatedUser['username'],
            'email' => $updatedUser['email'],
            'role' => $updatedUser['role'],
            'token' => $user['token'] || ''
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>