<?php
// api/health.php
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
        $stmt = $pdo->prepare("SELECT * FROM health WHERE user_id = ? ORDER BY record_date DESC");
        $stmt->execute([$owner_id]);
        $health = $stmt->fetchAll();
        echo json_encode($health);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO health (user_id, animal_id, record_date, diagnosis, treatment, veterinarian) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$owner_id, $data['animalId'], $data['recordDate'], $data['diagnosis'], $data['treatment'], $data['veterinarian']]);
        
        $data['id'] = $pdo->lastInsertId();
        http_response_code(201);
        echo json_encode($data);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];

        $sql = "UPDATE health SET animal_id = ?, record_date = ?, diagnosis = ?, treatment = ?, veterinarian = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['animalId'], $data['recordDate'], $data['diagnosis'], $data['treatment'], $data['veterinarian'], $id, $owner_id]);

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;

        if ($id > 0) {
            $sql = "DELETE FROM health WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $owner_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Record not found or not owned by user.']);
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