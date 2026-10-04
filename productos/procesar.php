<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;
$nombre = $_POST['nombre'] ?? '';
$precio = $_POST['precio'] ?? 0;

try {
    switch ($action) {
        case 'crear':
        case 'editar':
            if (empty($nombre) || $precio <= 0) {
                throw new Exception('Datos incompletos o inválidos');
            }
            
            if ($action === 'crear') {
                $stmt = $db->prepare("INSERT INTO productos (nombre, precio) VALUES (?, ?)");
                $stmt->execute([$nombre, $precio]);
            } else {
                $stmt = $db->prepare("UPDATE productos SET nombre = ?, precio = ? WHERE id = ?");
                $stmt->execute([$nombre, $precio, $id]);
            }
            break;
            
        case 'toggle':
            $stmt = $db->prepare("UPDATE productos SET activo = NOT activo WHERE id = ?");
            $stmt->execute([$id]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}