<?php
require_once 'config.php';

header("Content-Type: application/json");

try {
    // Authenticate the user
    $auth = authenticateUser();
    if (!$auth) {
        throw new Exception('Authorization failed', 401);
    }

    if (empty($_GET['id'])) {
        throw new Exception('Payslip ID is required', 400);
    }

    $payslipId = (int)$_GET['id'];

    // Check if user has access to this payslip (either as owner or admin)
    $stmt = $pdo->prepare("SELECT p.file_path 
                          FROM payslips p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.id = ? AND (p.user_id = ? OR ? = 'admin')");
    $stmt->execute([$payslipId, $auth['user_id'], $auth['role']]);
    $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payslip) {
        throw new Exception('Payslip not found or access denied', 404);
    }

    // Verify file exists
    if (!file_exists($payslip['file_path'])) {
        throw new Exception('File not found on server', 404);
    }

    // Serve the file
    if (isset($_GET['view'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="payslip_' . $payslipId . '.pdf"');
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="payslip_' . $payslipId . '.pdf"');
    }
    
    header('Content-Length: ' . filesize($payslip['file_path']));
    readfile($payslip['file_path']);
    exit;

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>