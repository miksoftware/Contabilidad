<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Obtener las cuentas del usuario autenticado
    $cuentas_result = $db->fetchAll("
        SELECT 
            c.id,
            c.nombre,
            c.saldo,
            cat.nombre as categoria,
            cat.color
        FROM cuentas c 
        LEFT JOIN categorias cat ON c.categoria_id = cat.id 
        WHERE c.usuario_id = ? 
        AND c.activa = 1
        ORDER BY c.nombre ASC
    ", [$user_id]);
    
    $cuentas = [];
    foreach ($cuentas_result as $row) {
        $cuentas[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'saldo' => floatval($row['saldo']),
            'categoria' => $row['categoria'] ?? 'Sin categoría',
            'color' => $row['color'] ?? '#6c757d'
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['cuentas' => $cuentas]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las cuentas: ' . $e->getMessage()]);
}
?>
