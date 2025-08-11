<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Si llega usuario_id por GET, usar ese; si no, usar el de la sesión
$requested_user_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : intval($_SESSION['user_id']);

try {
    // Obtener las cuentas activas del usuario solicitado
    $cuentas_result = $db->fetchAll(
        "SELECT id, nombre, saldo_actual FROM cuentas WHERE activa = 1 AND (usuario_id = ? OR usuario_id IS NULL) ORDER BY nombre ASC",
        [$requested_user_id]
    );

    $cuentas = [];
    foreach ($cuentas_result as $row) {
        $cuentas[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'saldo_actual' => (float)$row['saldo_actual']
        ];
    }

    header('Content-Type: application/json');
    // El frontend de transferencias.php espera un array plano
    echo json_encode($cuentas);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las cuentas: ' . $e->getMessage()]);
}
?>
