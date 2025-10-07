<?php
// api/db.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // For development only. In production, restrict this to your domain.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$host = 'localhost';
$db_name = 'farm_manager_pro'; // Corrected database name
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Start session for user authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Function to get the owner ID (for delegate access)
function getOwnerId() {
    return $_SESSION['owner_id'] ?? $_SESSION['user_id'];
}
?>