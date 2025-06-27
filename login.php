<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            // Log del intento de login
            error_log("Intento de login para: $email");
            
            // Verificar primero qué columnas existen en la tabla usuarios
            $estructura = $db->fetchAll("DESCRIBE usuarios");
            $campos_disponibles = array_column($estructura, 'Field');
            
            // Construir consulta basada en columnas disponibles
            $select_fields = ['id', 'nombre', 'email', 'password'];
            if (in_array('rol', $campos_disponibles)) {
                $select_fields[] = 'rol';
            }
            if (in_array('activo', $campos_disponibles)) {
                $select_fields[] = 'activo';
            }
            
            $select_query = "SELECT " . implode(', ', $select_fields) . " FROM usuarios WHERE email = ?";
            
            // Agregar condición de activo solo si la columna existe
            if (in_array('activo', $campos_disponibles)) {
                $select_query .= " AND activo = 1";
            }
            
            $user = $db->fetch($select_query, [$email]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'] ?? 'usuario'; // Default si no existe la columna
                
                error_log("Login exitoso para usuario ID: " . $user['id']);
                header('Location: dashboard.php');
                exit();
            } else {
                // Información de depuración mejorada
                if (!$user) {
                    error_log("Usuario no encontrado o inactivo: $email");
                    
                    // Verificar si el usuario existe pero está inactivo (solo si la columna activo existe)
                    if (in_array('activo', $campos_disponibles)) {
                        $user_inactivo = $db->fetch("SELECT id, activo FROM usuarios WHERE email = ?", [$email]);
                        if ($user_inactivo && !$user_inactivo['activo']) {
                            $error = 'Tu cuenta está desactivada. Contacta al administrador.';
                        } else {
                            $error = 'Usuario no encontrado: ' . $email;
                        }
                    } else {
                        // Si no hay columna activo, solo verificar existencia
                        $user_existe = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
                        if (!$user_existe) {
                            $error = 'Usuario no encontrado: ' . $email;
                        } else {
                            $error = 'Usuario encontrado pero problema con la consulta';
                        }
                    }
                } else {
                    error_log("Contraseña incorrecta para: $email");
                    $error = 'Contraseña incorrecta para: ' . $email;
                }
                
                // Para producción, descomentar esta línea y comentar las anteriores:
                // $error = 'Credenciales incorrectas.';
            }
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error en el servidor: ' . $e->getMessage() . ' - <a href="corregir_tabla_usuarios.php">Corregir tabla usuarios</a>';
        }
    }
}

$titulo = 'Iniciar Sesión - Contabilidad Familiar';
include 'includes/header.php';
?>

<div class="login-container d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3 mb-3 fw-bold text-primary">
                            <i class="fas fa-chart-line me-2"></i>
                            Contabilidad Familiar
                        </h1>
                        <p class="text-muted">Inicia sesión para continuar</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Correo Electrónico
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                placeholder="tu@email.com"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Contraseña
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    required
                                    placeholder="Tu contraseña"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <small class="text-muted">
                            <strong>Usuarios de prueba:</strong><br>
                            📧 admin@test.com | 🔑 123456<br>
                            📧 admin@contabilidad.local | 🔑 admin123<br>
                            📧 usuario@contabilidad.local | 🔑 usuario123
                        </small>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="mt-3 text-center">
                        <small>
                            <a href="diagnostico_login.php" class="text-warning">
                                🔧 Diagnosticar problemas de login
                            </a>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
