<?php
session_start();
require_once 'config/database.php';

// Debug específico para el dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Dashboard</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// 1. Verificar sesión de usuario
echo "<h2>👤 Verificación de Sesión</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<span class='ok'>✅ Usuario autenticado: ID " . $_SESSION['user_id'] . "</span><br>";
    if (isset($_SESSION['username'])) {
        echo "<span class='ok'>✅ Username: " . $_SESSION['username'] . "</span><br>";
    }
    if (isset($_SESSION['role'])) {
        echo "<span class='ok'>✅ Rol: " . $_SESSION['role'] . "</span><br>";
    }
} else {
    echo "<span class='error'>❌ No hay sesión activa</span><br>";
    echo "<a href='login.php'>Ir al login</a><br>";
}

// 2. Verificar archivos del dashboard
echo "<h2>📁 Archivos del Dashboard</h2>";
$archivos_dashboard = [
    'dashboard.php',
    'includes/header.php',
    'includes/footer.php',
    'assets/css/style.css',
    'assets/js/dashboard.js'
];

foreach ($archivos_dashboard as $archivo) {
    if (file_exists($archivo)) {
        echo "<span class='ok'>✅ $archivo existe</span><br>";
        if (is_readable($archivo)) {
            $size = filesize($archivo);
            echo "<span class='ok'>✅ $archivo es legible ($size bytes)</span><br>";
        }
    } else {
        echo "<span class='error'>❌ $archivo NO existe</span><br>";
    }
}

// 3. Probar consultas del dashboard
if (isset($_SESSION['user_id'])) {
    echo "<h2>📊 Datos del Dashboard</h2>";
    $user_id = $_SESSION['user_id'];
    
    try {
        // Contar cuentas
        $cuentas = $db->fetch("SELECT COUNT(*) as total FROM cuentas WHERE usuario_id = ? AND activa = 1", [$user_id]);
        echo "<span class='ok'>✅ Cuentas activas: " . $cuentas['total'] . "</span><br>";
        
        // Obtener saldo total
        $saldo = $db->fetch("SELECT SUM(saldo) as total FROM cuentas WHERE usuario_id = ? AND activa = 1", [$user_id]);
        echo "<span class='ok'>✅ Saldo total: $" . number_format($saldo['total'] ?? 0, 2) . "</span><br>";
        
        // Contar transacciones del mes
        $transacciones = $db->fetch("
            SELECT COUNT(*) as total 
            FROM transacciones t
            JOIN cuentas c ON t.cuenta_id = c.id
            WHERE c.usuario_id = ? 
            AND MONTH(t.fecha) = MONTH(CURRENT_DATE())
            AND YEAR(t.fecha) = YEAR(CURRENT_DATE())
        ", [$user_id]);
        echo "<span class='ok'>✅ Transacciones este mes: " . $transacciones['total'] . "</span><br>";
        
        // Verificar categorías
        $categorias = $db->fetch("SELECT COUNT(*) as total FROM categorias WHERE activa = 1");
        echo "<span class='ok'>✅ Categorías activas: " . $categorias['total'] . "</span><br>";
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error en consultas: " . $e->getMessage() . "</span><br>";
    }
}

// 4. Verificar JavaScript y AJAX
echo "<h2>🔧 Funcionalidad JavaScript</h2>";
?>
<script>
// Probar si jQuery está cargado
if (typeof jQuery !== 'undefined') {
    document.write("<span class='ok'>✅ jQuery está cargado (versión " + jQuery.fn.jquery + ")</span><br>");
} else {
    document.write("<span class='error'>❌ jQuery NO está cargado</span><br>");
}

// Probar si Chart.js está cargado
if (typeof Chart !== 'undefined') {
    document.write("<span class='ok'>✅ Chart.js está cargado</span><br>");
} else {
    document.write("<span class='warning'>⚠️ Chart.js NO está cargado</span><br>");
}

// Probar AJAX
if (typeof jQuery !== 'undefined') {
    jQuery.ajax({
        url: 'obtener_cuentas_usuario.php',
        method: 'GET',
        success: function(data) {
            if (data && data.cuentas) {
                document.write("<span class='ok'>✅ AJAX funcionando - " + data.cuentas.length + " cuentas obtenidas</span><br>");
            } else {
                document.write("<span class='error'>❌ AJAX respuesta inválida</span><br>");
            }
        },
        error: function(xhr, status, error) {
            document.write("<span class='error'>❌ Error AJAX: " + error + "</span><br>");
        }
    });
}
</script>

<?php
// 5. Verificar permisos y estructura
echo "<h2>🔐 Permisos y Estructura</h2>";

// Verificar permisos de escritura en carpetas necesarias
$carpetas_escritura = ['uploads', 'logs', 'temp'];
foreach ($carpetas_escritura as $carpeta) {
    if (is_dir($carpeta)) {
        if (is_writable($carpeta)) {
            echo "<span class='ok'>✅ Carpeta $carpeta es escribible</span><br>";
        } else {
            echo "<span class='error'>❌ Carpeta $carpeta NO es escribible</span><br>";
        }
    } else {
        echo "<span class='warning'>⚠️ Carpeta $carpeta no existe</span><br>";
    }
}

// 6. Simular contenido del dashboard
if (isset($_SESSION['user_id'])) {
    echo "<h2>🎯 Simulación del Dashboard</h2>";
    try {
        $user_id = $_SESSION['user_id'];
        
        // Obtener datos para el resumen
        $resumen = $db->fetch("
            SELECT 
                COUNT(DISTINCT c.id) as total_cuentas,
                SUM(c.saldo) as saldo_total,
                COUNT(DISTINCT t.id) as total_transacciones
            FROM cuentas c
            LEFT JOIN transacciones t ON c.id = t.cuenta_id AND MONTH(t.fecha) = MONTH(CURRENT_DATE())
            WHERE c.usuario_id = ? AND c.activa = 1
        ", [$user_id]);
        
        if ($resumen) {
            echo "<div style='background:#f8f9fa;padding:15px;border-radius:5px;margin:10px 0;'>";
            echo "<h4>📊 Resumen Financiero</h4>";
            echo "<p><strong>Cuentas:</strong> " . ($resumen['total_cuentas'] ?? 0) . "</p>";
            echo "<p><strong>Saldo Total:</strong> $" . number_format($resumen['saldo_total'] ?? 0, 2) . "</p>";
            echo "<p><strong>Transacciones este mes:</strong> " . ($resumen['total_transacciones'] ?? 0) . "</p>";
            echo "</div>";
            echo "<span class='ok'>✅ El dashboard debería mostrar estos datos correctamente</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error simulando dashboard: " . $e->getMessage() . "</span><br>";
    }
}

echo "<hr>";
echo "<h2>🚀 Instrucciones para Hosting</h2>";
echo "<ol>";
echo "<li><strong>Sube este archivo (debug_dashboard.php) a tu hosting</strong></li>";
echo "<li><strong>Visita: tu-dominio.com/debug_dashboard.php</strong></li>";
echo "<li><strong>Revisa todos los puntos marcados con ❌</strong></li>";
echo "<li><strong>Si hay errores de base de datos:</strong> verifica config/database.php</li>";
echo "<li><strong>Si hay errores de archivos:</strong> verifica que se hayan subido correctamente</li>";
echo "<li><strong>Si hay errores de permisos:</strong> contacta soporte técnico del hosting</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Debug completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='dashboard.php'>🏠 Ir al Dashboard</a> | <a href='verificar_hosting.php'>🔍 Diagnóstico General</a></p>";
?>
