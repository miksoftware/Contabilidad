<?php
// layouts/app.php - Layout principal de la aplicación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Si no se ha definido $titulo, usar uno por defecto
if (!isset($titulo)) {
    $titulo = 'Contabilidad Familiar';
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'layouts/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php if (isset($pageHeader) && $pageHeader): ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php if (isset($globalPageIcon) && $globalPageIcon): ?>
                        <i class="<?php echo $globalPageIcon; ?> me-2"></i>
                    <?php endif; ?>
                    <?php echo $globalPageTitle ?? 'Página'; ?>
                </h1>
                <?php if (isset($globalPageActions) && $globalPageActions): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php echo $globalPageActions; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Alert container para mensajes globales -->
            <?php if (isset($mensaje) && $mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje ?? 'info'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Contenido de la página -->
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php else: ?>
                <!-- El contenido se incluirá aquí desde la página que llama al layout -->
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
