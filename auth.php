<?php
// api/auth.php
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['userId'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($user_id) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and password are required.']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (user_id, password_hash, is_owner) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $password_hash]);

        echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['userId'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_id_string'] = $user['user_id'];
            if (!$user['is_owner']) {
                $_SESSION['owner_id'] = $user['owner_id'];
            }
            echo json_encode(['success' => true, 'userId' => $user['user_id']]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials.']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'status':
        if (isAuthenticated()) {
            echo json_encode(['loggedIn' => true, 'userId' => $_SESSION['user_id_string']]);
        } else {
            echo json_encode(['loggedIn' => false]);
        }
        break;

    case 'register_check':
        // Check if an owner account exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_owner = 1");
        $hasOwner = $stmt->fetchColumn() > 0;
        echo json_encode(['hasOwner' => $hasOwner]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found.']);
        break;
}
?>