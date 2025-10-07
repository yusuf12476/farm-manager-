<?php
// api/transactions.php
require_once 'db.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$owner_id = getOwnerId();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all transaction records for the user
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC");
        $stmt->execute([$owner_id]);
        $transactions = $stmt->fetchAll();
        echo json_encode($transactions);
        break;

    case 'POST':
        // Add a new transaction record
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO transactions (user_id, type, category, amount, date, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$owner_id, $data['type'], $data['category'], $data['amount'], $data['date'], $data['notes']]);
        
        $data['id'] = $pdo->lastInsertId();
        http_response_code(201);
        echo json_encode($data);
        break;

    case 'PUT':
        // Update an existing transaction record
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];

        $sql = "UPDATE transactions SET type = ?, category = ?, amount = ?, date = ?, notes = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['type'], $data['category'], $data['amount'], $data['date'], $data['notes'], $id, $owner_id]);

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        // Delete a transaction record
        $id = $_GET['id'] ?? 0;

        if ($id > 0) {
            $sql = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $owner_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Record not found or you do not have permission to delete it.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ID.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}
?>