<?php
require_once 'config.php';

$admin = authenticateUser();

// Only admin can access this endpoint
if ($admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get filter parameters
$userId = $_GET['userId'] ?? null;
$month = $_GET['month'] ?? null;

try {
    $query = "SELECT p.*, u.username 
              FROM payslips p 
              JOIN users u ON p.user_id = u.id";
    
    $conditions = [];
    $params = [];
    
    if ($userId && $userId !== 'all') {
        $conditions[] = "p.user_id = :user_id";
        $params[':user_id'] = $userId;
    }
    
    if ($month) {
        $conditions[] = "p.month_year LIKE :month";
        $params[':month'] = substr($month, 0, 7) . '%';
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY p.upload_date DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'payslips' => $payslips]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>