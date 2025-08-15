<?php
// layouts/sidebar.php - Componente reutilizable del sidebar
?>
<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'transacciones.php') ? 'active' : ''; ?>" href="transacciones.php">
                    <i class="fas fa-exchange-alt me-2"></i>Transacciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'categorias.php') ? 'active' : ''; ?>" href="categorias.php">
                    <i class="fas fa-tags me-2"></i>Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'cuentas.php') ? 'active' : ''; ?>" href="cuentas.php">
                    <i class="fas fa-university me-2"></i>Cuentas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'metas.php') ? 'active' : ''; ?>" href="metas.php">
                    <i class="fas fa-bullseye me-2"></i>Metas de Ahorro
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'reportes.php') ? 'active' : ''; ?>" href="reportes.php">
                    <i class="fas fa-chart-bar me-2"></i>Reportes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'presupuestos.php') ? 'active' : ''; ?>" href="presupuestos.php">
                    <i class="fas fa-calculator me-2"></i>Presupuestos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'transferencias.php') ? 'active' : ''; ?>" href="transferencias.php">
                    <i class="fas fa-arrows-alt-h me-2"></i>Transferencias
                </a>
            </li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">
                    <i class="fas fa-users me-2"></i>Usuarios
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
