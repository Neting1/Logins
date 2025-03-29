<?php
require_once 'config.php';

// Set headers first
header("Content-Type: application/json");

try {
    // Verify database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Authenticate admin
    if (!function_exists('authenticateAdmin')) {
        throw new Exception('Authentication function missing');
    }

    $admin = authenticateAdmin();
    if (!$admin || !isset($admin['admin_id']) || !isset($admin['token'])) {
        throw new Exception('Invalid admin credentials', 401);
    }

    // Get and validate input
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput === false) {
        throw new Exception('Failed to read input data');
    }

    if (empty($jsonInput)) {
        throw new Exception('No data received', 400);
    }

    $data = json_decode($jsonInput, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    if (!isset($data['email']) || empty(trim($data['email']))) {
        throw new Exception('Email is required', 400);
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }

    // Start transaction
    if (!$pdo->beginTransaction()) {
        throw new Exception('Failed to start transaction');
    }

    // Password change logic
    if (!empty($data['new_password'])) {
        if (empty($data['current_password'])) {
            throw new Exception('Current password is required for password change', 400);
        }

        // Get current password hash
        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE admin_id = :admin_id");
        $stmt->bindParam(':admin_id', $admin['admin_id'], PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to verify current password');
        }

        $dbAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dbAdmin) {
            throw new Exception('Admin not found', 404);
        }

        if (!password_verify($data['current_password'], $dbAdmin['password_hash'])) {
            throw new Exception('Current password is incorrect', 401);
        }

        // Validate new password
        if (strlen($data['new_password']) < 8) {
            throw new Exception('Password must be at least 8 characters', 400);
        }

        // Update password
        $passwordHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE admins SET password_hash = :password_hash, updated_at = NOW() WHERE admin_id = :admin_id");
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':admin_id', $admin['admin_id'], PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception('Password update failed');
        }
    }

    // Update email
    $stmt = $pdo->prepare("UPDATE admins SET email = :email, updated_at = NOW() WHERE admin_id = :admin_id");
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':admin_id', $admin['admin_id'], PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception('Email update failed');
    }

    // Get updated admin data
    $stmt = $pdo->prepare("SELECT admin_id, username, email FROM admins WHERE admin_id = :admin_id");
    $stmt->bindParam(':admin_id', $admin['admin_id'], PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch updated admin data');
    }

    $updatedAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$updatedAdmin) {
        throw new Exception('Admin record not found after update', 404);
    }

    // Commit transaction
    if (!$pdo->commit()) {
        throw new Exception('Failed to commit changes');
    }

    // Successful response
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'id' => $updatedAdmin['admin_id'],
            'username' => $updatedAdmin['username'],
            'email' => $updatedAdmin['email']
        ]
    ]);

} catch (PDOException $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error_code' => $e->getCode()
    ]);
    
    // Log full error for debugging (remove in production)
    error_log("PDOException: " . $e->getMessage());

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $statusCode
    ]);
    
    // Log full error for debugging (remove in production)
    error_log("Exception: " . $e->getMessage());
}

// Close database connection if needed
if (isset($pdo)) {
    $pdo = null;
}
?>