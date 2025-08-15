<?php
// layouts/helpers.php - Funciones helper para el sistema de layouts

/**
 * Función para comenzar una sección del layout
 */
function startLayout($pageTitle, $pageIcon = null, $pageActions = null) {
    global $titulo, $pageHeader, $globalPageTitle, $globalPageIcon, $globalPageActions;
    
    $titulo = $pageTitle . ' - Contabilidad Familiar';
    $pageHeader = true;
    $globalPageTitle = $pageTitle;
    $globalPageIcon = $pageIcon;
    $globalPageActions = $pageActions;
    
    ob_start(); // Comenzar buffer de salida
}

/**
 * Función para finalizar una sección del layout
 */
function endLayout() {
    global $content;
    $content = ob_get_clean(); // Obtener el contenido del buffer
    include 'layouts/app.php'; // Incluir el layout principal
}

/**
 * Función para renderizar un layout simple sin header personalizado
 */
function renderWithLayout($pageTitle, $contentCallback = null) {
    global $titulo;
    $titulo = $pageTitle . ' - Contabilidad Familiar';
    
    include 'includes/header.php';
    ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'layouts/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php 
                if ($contentCallback && is_callable($contentCallback)) {
                    $contentCallback();
                } 
                ?>
            </main>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
}

/**
 * Función para verificar si la página actual es la especificada
 */
function isCurrentPage($page) {
    return basename($_SERVER['PHP_SELF']) === $page;
}

/**
 * Función para generar la clase active para el sidebar
 */
function activeClass($page) {
    return isCurrentPage($page) ? 'active' : '';
}
?>
