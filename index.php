<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Redirigir al login
header('Location: login.php');
exit();
?>
