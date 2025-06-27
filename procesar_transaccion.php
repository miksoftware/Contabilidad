<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_POST) {
    try {
        $tipo = $_POST['tipo'] ?? '';
        $cantidad = floatval($_POST['cantidad'] ?? 0);
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $cuenta_id = intval($_POST['cuenta_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $descripcion = trim($_POST['descripcion'] ?? '');
        $usuario_id = $_SESSION['user_id'];

        // Debug: Log de datos recibidos
        error_log("Procesando transacción: tipo=$tipo, cantidad=$cantidad, categoria_id=$categoria_id, cuenta_id=$cuenta_id, fecha=$fecha, descripcion=$descripcion");

        // Validaciones
        if (empty($tipo) || !in_array($tipo, ['ingreso', 'gasto'])) {
            throw new Exception('Tipo de transacción inválido');
        }

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }

        if ($categoria_id <= 0) {
            throw new Exception('Debes seleccionar una categoría válida');
        }

        if ($cuenta_id <= 0) {
            throw new Exception('Debes seleccionar una cuenta válida');
        }

        if (empty($fecha)) {
            throw new Exception('La fecha es obligatoria');
        }

        if (empty($descripcion)) {
            throw new Exception('La descripción es obligatoria');
        }

        // Verificar que la categoría existe y corresponda al tipo
        $categoria = $db->fetch(
            "SELECT id, nombre, tipo FROM categorias WHERE id = ? AND activa = 1",
            [$categoria_id]
        );

        if (!$categoria) {
            throw new Exception('Categoría no encontrada o inactiva');
        }

        if ($categoria['tipo'] !== $tipo) {
            throw new Exception("La categoría '{$categoria['nombre']}' es de tipo '{$categoria['tipo']}' pero seleccionaste '{$tipo}'");
        }

        // Verificar que la cuenta esté activa
        $cuenta = $db->fetch(
            "SELECT id, nombre, saldo_actual FROM cuentas WHERE id = ? AND activa = 1",
            [$cuenta_id]
        );

        if (!$cuenta) {
            throw new Exception('Cuenta no encontrada o inactiva');
        }

        // Iniciar transacción
        $db->getConnection()->beginTransaction();

        // Insertar la transacción
        $db->query(
            "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$usuario_id, $categoria_id, $cuenta_id, $tipo, $cantidad, $descripcion, $fecha]
        );

        // Actualizar saldo de la cuenta
        $nuevoSaldo = $cuenta['saldo_actual'];
        if ($tipo === 'ingreso') {
            $nuevoSaldo += $cantidad;
        } else {
            $nuevoSaldo -= $cantidad;
        }

        $db->query(
            "UPDATE cuentas SET saldo_actual = ? WHERE id = ?",
            [$nuevoSaldo, $cuenta_id]
        );

        // Confirmar transacción
        $db->getConnection()->commit();

        $response['success'] = true;
        $response['message'] = 'Transacción registrada exitosamente';

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        $response['message'] = $e->getMessage();
    }
}

// Si es una petición AJAX, devolver JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Si no es AJAX, redirigir con mensaje
if ($response['success']) {
    $_SESSION['mensaje'] = $response['message'];
    $_SESSION['tipo_mensaje'] = 'success';
} else {
    $_SESSION['mensaje'] = $response['message'];
    $_SESSION['tipo_mensaje'] = 'danger';
}

header('Location: dashboard.php');
exit();
?>
