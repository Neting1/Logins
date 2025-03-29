<?php
require_once 'config.php';

$user = authenticateUser();
$payslipId = $_GET['id'] ?? null;

if (!$payslipId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payslip ID is required']);
    exit;
}

try {
    // Get payslip info first
    if ($user['role'] === 'user') {
        $stmt = $pdo->prepare("SELECT file_path FROM payslips WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $payslipId);
        $stmt->bindParam(':user_id', $user['userId']);
    } else {
        // Admin can delete any payslip
        $stmt = $pdo->prepare("SELECT file_path FROM payslips WHERE id = :id");
        $stmt->bindParam(':id', $payslipId);
    }
    
    $stmt->execute();
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payslip) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payslip not found']);
        exit;
    }
    
    // Delete the record from database
    $stmt = $pdo->prepare("DELETE FROM payslips WHERE id = :id");
    $stmt->bindParam(':id', $payslipId);
    $stmt->execute();
    
    // Delete the file
    if (file_exists($payslip['file_path'])) {
        unlink($payslip['file_path']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Payslip deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>