<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Probando includes y headers...<br>";

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_role'] = 'admin';

echo "Sesión iniciada<br>";

try {
    require_once 'config/database.php';
    echo "Database incluido correctamente<br>";
    
    $titulo = 'Test - Dashboard';
    echo "Título establecido<br>";
    
    // Verificar que el header existe
    if (file_exists('includes/header.php')) {
        echo "Header file exists<br>";
        
        // Incluir header
        include 'includes/header.php';
        echo "<br><strong>Header incluido exitosamente</strong><br>";
        
        // Incluir footer
        if (file_exists('includes/footer.php')) {
            echo "Footer file exists<br>";
            include 'includes/footer.php';
            echo "<br><strong>Footer incluido exitosamente</strong><br>";
        } else {
            echo "<strong style='color: red;'>Footer no encontrado</strong><br>";
        }
        
    } else {
        echo "<strong style='color: red;'>Header no encontrado</strong><br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error: " . $e->getMessage() . "</strong><br>";
}
?>
