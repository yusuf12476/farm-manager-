<?php
// api/equipment.php
require_once 'db.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$owner_id = getOwnerId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method == 'POST' && $action == 'add_log') {
    // Special action to add a maintenance log to an existing equipment item
    $data = json_decode(file_get_contents('php://input'), true);
    $equipment_id = $data['equipmentId'];

    // First, verify the user owns the equipment
    $stmt = $pdo->prepare("SELECT user_id FROM equipment WHERE id = ?");
    $stmt->execute([$equipment_id]);
    $equipment_owner = $stmt->fetchColumn();

    if ($equipment_owner != $owner_id) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not own this equipment.']);
        exit();
    }

    $sql = "INSERT INTO maintenance_log (equipment_id, description, cost, date) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipment_id, $data['description'], $data['cost'], $data['date']]);

    $data['id'] = $pdo->lastInsertId();
    http_response_code(201);
    echo json_encode($data);
    exit();
}


switch ($method) {
    case 'GET':
        // Get all equipment and their maintenance logs
        $stmt = $pdo->prepare("
            SELECT e.*, ml.id as log_id, ml.description, ml.cost, ml.date as log_date
            FROM equipment e
            LEFT JOIN maintenance_log ml ON e.id = ml.equipment_id
            WHERE e.user_id = ?
            ORDER BY e.name ASC, ml.date DESC
        ");
        $stmt->execute([$owner_id]);
        $results = $stmt->fetchAll();

        // Group maintenance logs under their equipment
        $equipment = [];
        foreach ($results as $row) {
            $equipment_id = $row['id'];
            if (!isset($equipment[$equipment_id])) {
                $equipment[$equipment_id] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'model' => $row['model'],
                    'purchaseDate' => $row['purchase_date'],
                    'createdAt' => $row['created_at'],
                    'maintenanceLog' => []
                ];
            }
            if ($row['log_id']) {
                $equipment[$equipment_id]['maintenanceLog'][] = [
                    'id' => $row['log_id'],
                    'description' => $row['description'],
                    'cost' => $row['cost'],
                    'date' => $row['log_date']
                ];
            }
        }
        echo json_encode(array_values($equipment));
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO equipment (user_id, name, model, purchase_date) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$owner_id, $data['name'], $data['model'], $data['purchaseDate']]);
        
        $data['id'] = $pdo->lastInsertId();
        $data['maintenanceLog'] = []; // Return with empty log array
        http_response_code(201);
        echo json_encode($data);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];

        $sql = "UPDATE equipment SET name = ?, model = ?, purchase_date = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['name'], $data['model'], $data['purchaseDate'], $id, $owner_id]);

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if ($id > 0) {
            // Use a transaction to ensure both deletions succeed or fail together
            $pdo->beginTransaction();
            try {
                // First, delete associated maintenance logs
                $stmt1 = $pdo->prepare("DELETE FROM maintenance_log WHERE equipment_id = ?");
                $stmt1->execute([$id]);

                // Then, delete the equipment itself
                $stmt2 = $pdo->prepare("DELETE FROM equipment WHERE id = ? AND user_id = ?");
                $stmt2->execute([$id, $owner_id]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete equipment and its logs.']);
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