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
    $accion = $_POST['accion'] ?? 'crear';
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $cuenta_id = intval($_POST['cuenta_id'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $usuario_id = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $id = intval($_POST['id'] ?? 0);

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

        // Verificar que la cuenta esté activa y sea propia/compartida si no es admin
        if ($isAdmin) {
            $cuenta = $db->fetch(
                "SELECT id, nombre, saldo_actual FROM cuentas WHERE id = ? AND activa = 1",
                [$cuenta_id]
            );
        } else {
            $cuenta = $db->fetch(
                "SELECT id, nombre, saldo_actual FROM cuentas WHERE id = ? AND activa = 1 AND (usuario_id = ? OR usuario_id IS NULL)",
                [$cuenta_id, $usuario_id]
            );
        }

        if (!$cuenta) {
            throw new Exception('Cuenta no encontrada o inactiva');
        }

        // Iniciar transacción DB
        $db->getConnection()->beginTransaction();

        if ($accion === 'crear') {
            // Insertar
            $db->query(
                "INSERT INTO transacciones (usuario_id, categoria_id, cuenta_id, tipo, cantidad, descripcion, fecha) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$usuario_id, $categoria_id, $cuenta_id, $tipo, $cantidad, $descripcion, $fecha]
            );

            // Actualizar saldo
            $nuevoSaldo = $cuenta['saldo_actual'] + ($tipo === 'ingreso' ? $cantidad : -$cantidad);
            $db->query("UPDATE cuentas SET saldo_actual = ? WHERE id = ?", [$nuevoSaldo, $cuenta_id]);

            $response['success'] = true;
            $response['message'] = 'Transacción registrada exitosamente';
        } elseif ($accion === 'editar') {
            if ($id <= 0) throw new Exception('ID de transacción inválido');
            // Cargar transacción original
            $orig = $db->fetch("SELECT * FROM transacciones WHERE id = ?", [$id]);
            if (!$orig) throw new Exception('Transacción no encontrada');
            if (!$isAdmin && intval($orig['usuario_id']) !== intval($usuario_id)) {
                throw new Exception('No autorizado para editar esta transacción');
            }

            // Revertir saldo en cuenta original
            $cuentaOrig = $db->fetch("SELECT id, saldo_actual FROM cuentas WHERE id = ?", [$orig['cuenta_id']]);
            if ($cuentaOrig) {
                $saldoRevertido = $cuentaOrig['saldo_actual'] + ($orig['tipo'] === 'gasto' ? $orig['cantidad'] : -$orig['cantidad']);
                $db->query("UPDATE cuentas SET saldo_actual = ? WHERE id = ?", [$saldoRevertido, $orig['cuenta_id']]);
            }

            // Actualizar transacción
            $db->query(
                "UPDATE transacciones SET categoria_id = ?, cuenta_id = ?, tipo = ?, cantidad = ?, descripcion = ?, fecha = ? WHERE id = ?",
                [$categoria_id, $cuenta_id, $tipo, $cantidad, $descripcion, $fecha, $id]
            );

            // Aplicar saldo en cuenta nueva
            $cuentaNew = $db->fetch("SELECT id, saldo_actual FROM cuentas WHERE id = ?", [$cuenta_id]);
            $saldoAplicado = $cuentaNew['saldo_actual'] + ($tipo === 'ingreso' ? $cantidad : -$cantidad);
            $db->query("UPDATE cuentas SET saldo_actual = ? WHERE id = ?", [$saldoAplicado, $cuenta_id]);

            $response['success'] = true;
            $response['message'] = 'Transacción actualizada exitosamente';
        } elseif ($accion === 'eliminar') {
            if ($id <= 0) throw new Exception('ID de transacción inválido');
            // Cargar transacción original
            $orig = $db->fetch("SELECT * FROM transacciones WHERE id = ?", [$id]);
            if (!$orig) throw new Exception('Transacción no encontrada');
            if (!$isAdmin && intval($orig['usuario_id']) !== intval($usuario_id)) {
                throw new Exception('No autorizado para eliminar esta transacción');
            }

            // Revertir saldo en cuenta original
            $cuentaOrig = $db->fetch("SELECT id, saldo_actual FROM cuentas WHERE id = ?", [$orig['cuenta_id']]);
            if ($cuentaOrig) {
                $saldoRevertido = $cuentaOrig['saldo_actual'] + ($orig['tipo'] === 'gasto' ? $orig['cantidad'] : -$orig['cantidad']);
                $db->query("UPDATE cuentas SET saldo_actual = ? WHERE id = ?", [$saldoRevertido, $orig['cuenta_id']]);
            }

            // Eliminar transacción
            $db->query("DELETE FROM transacciones WHERE id = ?", [$id]);

            $response['success'] = true;
            $response['message'] = 'Transacción eliminada exitosamente';
        } else {
            throw new Exception('Acción no soportada');
        }

        // Confirmar
        $db->getConnection()->commit();

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

header('Location: transacciones.php');
exit();
?>
