<?php
// Script de instalación automática para Contabilidad Familiar
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Contabilidad Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { background: rgba(255,255,255,0.95); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="install-container p-5">
                    <div class="text-center mb-4">
                        <h1 class="h2 text-primary">
                            <i class="fas fa-chart-line me-2"></i>
                            Contabilidad Familiar
                        </h1>
                        <p class="text-muted">Instalación y Configuración</p>
                    </div>

                    <?php if ($step == 1): ?>
                        <!-- Paso 1: Verificar requisitos -->
                        <div class="step-header mb-4">
                            <h3><i class="fas fa-check-circle me-2"></i>Paso 1: Verificar Requisitos</h3>
                        </div>

                        <?php
                        $requirements = [
                            'PHP Version' => ['current' => PHP_VERSION, 'required' => '8.0', 'status' => version_compare(PHP_VERSION, '8.0', '>=')],
                            'PDO Extension' => ['status' => extension_loaded('pdo')],
                            'PDO MySQL' => ['status' => extension_loaded('pdo_mysql')],
                            'OpenSSL' => ['status' => extension_loaded('openssl')],
                            'Config Directory' => ['status' => is_writable(__DIR__ . '/config')],
                        ];

                        $allPassed = true;
                        ?>

                        <div class="requirements-check">
                            <?php foreach ($requirements as $name => $req): ?>
                                <?php 
                                $status = $req['status'];
                                $allPassed = $allPassed && $status;
                                ?>
                                <div class="d-flex justify-content-between align-items-center p-3 mb-2 border rounded">
                                    <span><?php echo $name; ?></span>
                                    <div>
                                        <?php if (isset($req['current'])): ?>
                                            <small class="text-muted me-2"><?php echo $req['current']; ?></small>
                                        <?php endif; ?>
                                        <?php if ($status): ?>
                                            <i class="fas fa-check-circle text-success fa-lg"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger fa-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <?php if ($allPassed): ?>
                                <p class="text-success"><i class="fas fa-check me-2"></i>Todos los requisitos están cumplidos</p>
                                <a href="?step=2" class="btn btn-primary btn-lg">
                                    Continuar <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            <?php else: ?>
                                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Algunos requisitos no están cumplidos</p>
                                <button class="btn btn-secondary" onclick="location.reload()">
                                    <i class="fas fa-redo me-2"></i>Verificar Nuevamente
                                </button>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($step == 2): ?>
                        <!-- Paso 2: Configurar base de datos -->
                        <div class="step-header mb-4">
                            <h3><i class="fas fa-database me-2"></i>Paso 2: Configurar Base de Datos</h3>
                        </div>

                        <?php
                        if ($_POST) {
                            $host = $_POST['host'] ?? 'localhost';
                            $username = $_POST['username'] ?? 'root';
                            $password = $_POST['password'] ?? '';
                            $database = $_POST['database'] ?? 'contabilidad_familiar';

                            try {
                                $pdo = new PDO("mysql:host=$host", $username, $password);
                                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                
                                // Crear base de datos si no existe
                                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                $pdo->exec("USE `$database`");
                                
                                // Ejecutar script SQL
                                $sql = file_get_contents(__DIR__ . '/database/schema.sql');
                                $sql = str_replace('contabilidad_familiar', $database, $sql);
                                
                                $statements = explode(';', $sql);
                                foreach ($statements as $statement) {
                                    $statement = trim($statement);
                                    if (!empty($statement)) {
                                        $pdo->exec($statement);
                                    }
                                }
                                
                                // Actualizar archivo de configuración
                                $configContent = "<?php
define('DB_HOST', '$host');
define('DB_NAME', '$database');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private \$connection;

    public function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException \$e) {
            die(\"Error de conexión: \" . \$e->getMessage());
        }
    }

    public function getConnection() {
        return \$this->connection;
    }

    public function query(\$sql, \$params = []) {
        \$stmt = \$this->connection->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }

    public function fetch(\$sql, \$params = []) {
        \$stmt = \$this->query(\$sql, \$params);
        return \$stmt->fetch();
    }

    public function fetchAll(\$sql, \$params = []) {
        \$stmt = \$this->query(\$sql, \$params);
        return \$stmt->fetchAll();
    }

    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// Instancia global de la base de datos
\$db = new Database();
?>";
                                
                                file_put_contents(__DIR__ . '/config/database.php', $configContent);
                                
                                echo '<div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    ¡Base de datos configurada correctamente!
                                </div>';
                                echo '<div class="text-center">
                                    <a href="?step=3" class="btn btn-primary btn-lg">
                                        Finalizar Instalación <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>';
                                
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Error: ' . htmlspecialchars($e->getMessage()) . '
                                </div>';
                            }
                        }
                        
                        if (!$_POST || isset($e)): ?>
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Servidor</label>
                                        <input type="text" name="host" class="form-control" value="localhost" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Base de Datos</label>
                                        <input type="text" name="database" class="form-control" value="contabilidad_familiar" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Usuario</label>
                                        <input type="text" name="username" class="form-control" value="root" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contraseña</label>
                                        <input type="password" name="password" class="form-control" placeholder="Dejar vacío si no tiene">
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-database me-2"></i>Configurar Base de Datos
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($step == 3): ?>
                        <!-- Paso 3: Instalación completada -->
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="text-success mb-4">¡Instalación Completada!</h3>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-users me-2"></i>Usuarios de Prueba Creados</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Administrador</strong><br>
                                        📧 admin@contabilidad.local<br>
                                        🔑 admin123
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Usuario</strong><br>
                                        📧 usuario@contabilidad.local<br>
                                        🔑 usuario123
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Iniciar Aplicación
                                </a>
                                <a href="README.md" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-book me-2"></i>Ver Documentación
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
