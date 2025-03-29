<?php
require_once 'config.php';

$admin = authenticateUser();

// Only admin can access this endpoint
if ($admin['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if it's a POST request and files are uploaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['payslipFile'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = $_POST['userId'] ?? '';
$monthYear = $_POST['monthYear'] ?? '';
$file = $_FILES['payslipFile'];

// Validate inputs
if (empty($userId) || empty($monthYear) || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file or user/month/year']);
    exit;
}

// Validate file type (PDF only)
$fileType = mime_content_type($file['tmp_name']);
if ($fileType !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/payslips/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$fileName = uniqid('payslip_') . '.pdf';
$filePath = $uploadDir . $fileName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit;
}

// Store payslip info in database
try {
    $stmt = $pdo->prepare("INSERT INTO payslips (user_id, file_name, file_path, month_year) VALUES (:user_id, :file_name, :file_path, :month_year)");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':file_name', $file['name']);
    $stmt->bindParam(':file_path', $filePath);
    $stmt->bindParam(':month_year', $monthYear);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Payslip uploaded successfully']);
} catch (PDOException $e) {
    // Delete the uploaded file if database operation fails
    unlink($filePath);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>