<?php
require_once 'api/config.php';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    
    echo "Database connection successful!<br>";
    echo "Users table exists with $count records.";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>