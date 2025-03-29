<?php
require_once 'config.php';

header("Content-Type: application/json");

try {
    // Authenticate user
    $auth = authenticateUser();
    if (!$auth) {
        throw new Exception('Authorization failed', 401);
    }

    // Build query based on user role
    if ($auth['role'] === 'admin') {
        $query = "SELECT p.id, p.month_year, p.file_name, p.upload_date, u.username 
                 FROM payslips p
                 JOIN users u ON p.user_id = u.id";
        $params = [];
    } else {
        $query = "SELECT id, month_year, file_name, upload_date 
                 FROM payslips 
                 WHERE user_id = ?";
        $params = [$auth['user_id']];
    }

    // Apply month filter if provided
    if (!empty($_GET['month'])) {
        $query .= (strpos($query, 'WHERE') === false ? ' WHERE ' : ' AND ');
        $query .= "month_year = ?";
        $params[] = $_GET['month'];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'payslips' => $payslips
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>