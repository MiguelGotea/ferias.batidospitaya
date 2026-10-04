<?php
// Encabezados para desarrollo (quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Registrar toda la solicitud para diagnóstico
file_put_contents('request.log', date('Y-m-d H:i:s') . "\n" . print_r([
    'POST' => $_POST,
    'INPUT' => file_get_contents('php://input'),
    'HEADERS' => getallheaders()
], true), FILE_APPEND);

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Función para enviar respuesta JSON consistente
function sendJsonResponse($success, $message = '', $data = []) {
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data) $response = array_merge($response, $data);
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido');
    }

    // Obtener datos JSON
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        sendJsonResponse(false, 'No se recibieron datos');
    }

    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Error decodificando JSON: ' . json_last_error_msg());
    }

    // Validar datos mínimos
    if (empty($data['productos']) || !isset($data['tipoPago'])) {
        sendJsonResponse(false, 'Datos incompletos');
    }

    // Procesar la venta
    $nombreCliente = !empty($data['nombreCliente']) ? trim($data['nombreCliente']) : null;
    $clubPitaya = !empty($data['clubPitaya']) ? trim($data['clubPitaya']) : null;
    $result = procesarVenta($data['productos'], $data['tipoPago'], $nombreCliente, $clubPitaya);
    
    if (!$result['success']) {
        sendJsonResponse(false, $result['message'] ?? 'Error al procesar venta');
    }

    sendJsonResponse(true, 'Venta procesada', ['ventaId' => $result['ventaId']]);

} catch (Exception $e) {
    error_log('Error en procesar.php: ' . $e->getMessage());
    sendJsonResponse(false, 'Error interno: ' . $e->getMessage());
}