<?php
// Motor de pruebas centralizado para el sistema de diagnóstico
require_once '../config/database.php';

$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';

// Headers para AJAX
header('Content-Type: text/html; charset=utf-8');

try {
    switch ($action) {
        case 'status':
            echo getSystemStatus();
            break;
        case 'test':
            echo runDiagnosticTest($type);
            break;
        case 'repair':
            echo runRepairTool($type);
            break;
        default:
            echo '<div class="alert alert-danger">Acción no válida</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

function getSystemStatus() {
    global $db;
    
    $status = [
        'success' => true,
        'database' => false,
        'users' => 0,
        'categories' => 0,
        'accounts' => 0
    ];
    
    try {
        // Test de conexión
        $db->getConnection();
        $status['database'] = true;
        
        // Contar usuarios activos
        $users = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $status['users'] = $users['total'] ?? 0;
        
        // Contar categorías activas
        $categories = $db->fetch("SELECT COUNT(*) as total FROM categorias WHERE activa = 1");
        $status['categories'] = $categories['total'] ?? 0;
        
        // Contar cuentas activas
        $accounts = $db->fetch("SELECT COUNT(*) as total FROM cuentas WHERE activa = 1");
        $status['accounts'] = $accounts['total'] ?? 0;
        
    } catch (Exception $e) {
        $status['success'] = false;
    }
    
    header('Content-Type: application/json');
    return json_encode($status);
}

function runDiagnosticTest($type) {
    global $db;
    
    switch ($type) {
        case 'database':
            return testDatabase();
        case 'users':
            return testUsers();
        case 'login':
            return testLogin();
        case 'data':
            return testData();
        case 'transactions':
            return testTransactions();
        case 'complete':
            return testComplete();
        default:
            return '<div class="alert alert-warning">Tipo de prueba no reconocido</div>';
    }
}

function runRepairTool($type) {
    global $db;
    
    switch ($type) {
        case 'users':
            return repairUsers();
        case 'data':
            return createSampleData();
        case 'migration':
            return runMigration();
        default:
            return '<div class="alert alert-warning">Herramienta de reparación no reconocida</div>';
    }
}

function testDatabase() {
    global $db;
    
    $output = '<h4><i class="fas fa-database text-primary"></i> Test de Base de Datos</h4>';
    $issues = [];
    $successes = [];
    
    try {
        // Test de conexión
        $db->getConnection();
        $successes[] = "✅ Conexión a la base de datos exitosa";
        
        // Verificar tablas principales
        $tables = ['usuarios', 'categorias', 'cuentas', 'transacciones', 'metas_ahorro'];
        foreach ($tables as $table) {
            try {
                $result = $db->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    $successes[] = "✅ Tabla '$table' existe";
                    
                    // Contar registros
                    $count = $db->fetch("SELECT COUNT(*) as total FROM `$table`");
                    $successes[] = "ℹ️ Tabla '$table' tiene {$count['total']} registros";
                } else {
                    $issues[] = "⚠️ Tabla '$table' no existe";
                }
            } catch (Exception $e) {
                $issues[] = "❌ Error verificando tabla '$table': " . $e->getMessage();
            }
        }
        
        // Verificar tablas adicionales con nombres alternativos
        $alt_tables = ['presupuestos_items', 'presupuestos_pagos'];
        foreach ($alt_tables as $table) {
            try {
                $result = $db->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    $count = $db->fetch("SELECT COUNT(*) as total FROM `$table`");
                    $successes[] = "✅ Tabla '$table' existe con {$count['total']} registros";
                }
            } catch (Exception $e) {
                // Silencioso, estas tablas son opcionales
            }
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error de conexión: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function testUsers() {
    global $db;
    
    $output = '<h4><i class="fas fa-users text-info"></i> Test del Sistema de Usuarios</h4>';
    $issues = [];
    $successes = [];
    
    try {
        // Verificar estructura de la tabla usuarios
        $estructura = $db->fetchAll("DESCRIBE usuarios");
        $campos = array_column($estructura, 'Field');
        
        $campos_requeridos = ['id', 'nombre', 'email', 'password', 'rol', 'activo'];
        $campos_faltantes = array_diff($campos_requeridos, $campos);
        
        if (empty($campos_faltantes)) {
            $successes[] = "✅ Tabla usuarios tiene todos los campos necesarios: " . implode(', ', $campos);
        } else {
            $issues[] = "❌ Faltan campos en la tabla usuarios: " . implode(', ', $campos_faltantes);
        }
        
        // Verificar usuarios
        $usuarios = $db->fetchAll("SELECT id, nombre, email, rol, activo FROM usuarios");
        $successes[] = "ℹ️ Total de usuarios en el sistema: " . count($usuarios);
        
        $usuarios_activos = array_filter($usuarios, function($u) { return $u['activo']; });
        if (!empty($usuarios_activos)) {
            $successes[] = "✅ Usuarios activos: " . count($usuarios_activos);
            
            $output .= '<div class="mt-3"><h5>Usuarios Disponibles:</h5>';
            $output .= '<table class="table table-sm">';
            $output .= '<tr><th>Email</th><th>Nombre</th><th>Rol</th><th>Estado</th></tr>';
            
            foreach ($usuarios_activos as $usuario) {
                $badge = $usuario['rol'] === 'admin' ? 'danger' : 'info';
                $output .= "<tr>";
                $output .= "<td><code>{$usuario['email']}</code></td>";
                $output .= "<td>{$usuario['nombre']}</td>";
                $output .= "<td><span class='badge bg-$badge'>{$usuario['rol']}</span></td>";
                $output .= "<td><span class='badge bg-success'>Activo</span></td>";
                $output .= "</tr>";
            }
            $output .= '</table></div>';
        } else {
            $issues[] = "❌ No hay usuarios activos en el sistema";
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error verificando usuarios: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function testLogin() {
    global $db;
    
    $output = '<h4><i class="fas fa-sign-in-alt text-warning"></i> Test del Sistema de Login</h4>';
    $issues = [];
    $successes = [];
    
    try {
        // Buscar usuario de prueba
        $usuario_test = $db->fetch("SELECT id, email, password FROM usuarios WHERE email = 'admin@test.com' AND activo = 1");
        
        if ($usuario_test) {
            $successes[] = "✅ Usuario de prueba encontrado: admin@test.com";
            
            // Verificar hash de contraseña
            if (strlen($usuario_test['password']) >= 60 && substr($usuario_test['password'], 0, 4) === '$2y$') {
                $successes[] = "✅ Hash de contraseña válido";
                
                // Test de verificación
                if (password_verify('123456', $usuario_test['password'])) {
                    $successes[] = "✅ Verificación de contraseña exitosa";
                    
                    $output .= '<div class="alert alert-success mt-3">';
                    $output .= '<h5>🎉 Login Test Exitoso</h5>';
                    $output .= '<p>Puedes usar estas credenciales:</p>';
                    $output .= '<ul>';
                    $output .= '<li><strong>Email:</strong> <code>admin@test.com</code></li>';
                    $output .= '<li><strong>Contraseña:</strong> <code>123456</code></li>';
                    $output .= '</ul>';
                    $output .= '<a href="../login.php" class="btn btn-primary">Ir al Login</a>';
                    $output .= '</div>';
                } else {
                    $issues[] = "❌ La contraseña de prueba no verifica correctamente";
                }
            } else {
                $issues[] = "❌ Hash de contraseña inválido";
            }
        } else {
            $issues[] = "⚠️ Usuario de prueba admin@test.com no encontrado";
        }
        
        // Verificar otros usuarios
        $otros_usuarios = $db->fetchAll("SELECT email FROM usuarios WHERE email != 'admin@test.com' AND activo = 1");
        if (!empty($otros_usuarios)) {
            $successes[] = "ℹ️ Otros usuarios disponibles: " . count($otros_usuarios);
            foreach ($otros_usuarios as $user) {
                $successes[] = "ℹ️ Usuario: {$user['email']}";
            }
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error en test de login: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function testData() {
    global $db;
    
    $output = '<h4><i class="fas fa-tags text-success"></i> Test de Categorías y Cuentas</h4>';
    $issues = [];
    $successes = [];
    
    try {
        // Test de categorías
        $categorias = $db->fetchAll("SELECT * FROM categorias WHERE activa = 1");
        $successes[] = "ℹ️ Total categorías activas: " . count($categorias);
        
        if (count($categorias) > 0) {
            $ingreso = array_filter($categorias, function($c) { return $c['tipo'] === 'ingreso'; });
            $gasto = array_filter($categorias, function($c) { return $c['tipo'] === 'gasto'; });
            
            $successes[] = "✅ Categorías de ingreso: " . count($ingreso);
            $successes[] = "✅ Categorías de gasto: " . count($gasto);
            
            if (count($ingreso) > 0 && count($gasto) > 0) {
                $successes[] = "✅ Sistema balanceado - hay categorías de ambos tipos";
            } else {
                $issues[] = "⚠️ Sistema desbalanceado - faltan categorías de " . (count($ingreso) === 0 ? 'ingreso' : 'gasto');
            }
        } else {
            $issues[] = "❌ No hay categorías activas - el formulario de transacciones no funcionará";
        }
        
        // Test de cuentas
        $cuentas = $db->fetchAll("SELECT * FROM cuentas WHERE activa = 1");
        $successes[] = "ℹ️ Total cuentas activas: " . count($cuentas);
        
        if (count($cuentas) > 0) {
            $successes[] = "✅ Hay cuentas disponibles para transacciones";
            
            $saldo_total = 0;
            foreach ($cuentas as $cuenta) {
                $saldo_total += $cuenta['saldo_actual'];
            }
            $successes[] = "ℹ️ Saldo total del sistema: $" . number_format($saldo_total, 2);
        } else {
            $issues[] = "❌ No hay cuentas activas - el formulario de transacciones no funcionará";
        }
        
        // Mostrar datos si existen
        if (count($categorias) > 0 || count($cuentas) > 0) {
            $output .= '<div class="mt-3">';
            
            if (count($categorias) > 0) {
                $output .= '<h5>Categorías Disponibles:</h5>';
                $output .= '<div class="row">';
                foreach (['ingreso', 'gasto'] as $tipo) {
                    $cats_tipo = array_filter($categorias, function($c) use ($tipo) { return $c['tipo'] === $tipo; });
                    if (!empty($cats_tipo)) {
                        $output .= '<div class="col-md-6">';
                        $output .= '<h6>' . ucfirst($tipo) . 's:</h6>';
                        $output .= '<ul>';
                        foreach ($cats_tipo as $cat) {
                            $output .= "<li>{$cat['nombre']}</li>";
                        }
                        $output .= '</ul>';
                        $output .= '</div>';
                    }
                }
                $output .= '</div>';
            }
            
            if (count($cuentas) > 0) {
                $output .= '<h5>Cuentas Disponibles:</h5>';
                $output .= '<ul>';
                foreach ($cuentas as $cuenta) {
                    $output .= "<li>{$cuenta['nombre']} - Saldo: $" . number_format($cuenta['saldo_actual'], 2) . "</li>";
                }
                $output .= '</ul>';
            }
            
            $output .= '</div>';
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error verificando datos: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function testTransactions() {
    global $db;
    
    $output = '<h4><i class="fas fa-exchange-alt text-danger"></i> Test del Formulario de Transacciones</h4>';
    $issues = [];
    $successes = [];
    
    try {
        // Verificar prerrequisitos
        $categorias_activas = $db->fetch("SELECT COUNT(*) as total FROM categorias WHERE activa = 1");
        $cuentas_activas = $db->fetch("SELECT COUNT(*) as total FROM cuentas WHERE activa = 1");
        
        if ($categorias_activas['total'] > 0) {
            $successes[] = "✅ Hay {$categorias_activas['total']} categorías para el formulario";
        } else {
            $issues[] = "❌ No hay categorías - el formulario no funcionará";
        }
        
        if ($cuentas_activas['total'] > 0) {
            $successes[] = "✅ Hay {$cuentas_activas['total']} cuentas para el formulario";
        } else {
            $issues[] = "❌ No hay cuentas - el formulario no funcionará";
        }
        
        // Test de estructura de transacciones
        $estructura_trans = $db->fetchAll("DESCRIBE transacciones");
        $campos_trans = array_column($estructura_trans, 'Field');
        $campos_req_trans = ['id', 'usuario_id', 'categoria_id', 'cuenta_id', 'tipo', 'cantidad', 'descripcion', 'fecha'];
        
        $faltantes_trans = array_diff($campos_req_trans, $campos_trans);
        if (empty($faltantes_trans)) {
            $successes[] = "✅ Tabla transacciones tiene estructura correcta";
        } else {
            $issues[] = "❌ Faltan campos en transacciones: " . implode(', ', $faltantes_trans);
        }
        
        // Verificar transacciones existentes
        $total_transacciones = $db->fetch("SELECT COUNT(*) as total FROM transacciones");
        $successes[] = "ℹ️ Total de transacciones en el sistema: {$total_transacciones['total']}";
        
        if ($total_transacciones['total'] > 0) {
            $ultima_transaccion = $db->fetch("SELECT fecha, tipo, cantidad FROM transacciones ORDER BY id DESC LIMIT 1");
            $successes[] = "ℹ️ Última transacción: {$ultima_transaccion['tipo']} de $" . number_format($ultima_transaccion['cantidad'], 2) . " el {$ultima_transaccion['fecha']}";
        }
        
        // Resultado final
        if ($categorias_activas['total'] > 0 && $cuentas_activas['total'] > 0 && empty($faltantes_trans)) {
            $output .= '<div class="alert alert-success mt-3">';
            $output .= '<h5>🎉 Formulario de Transacciones Listo</h5>';
            $output .= '<p>Todos los componentes necesarios están disponibles.</p>';
            $output .= '<a href="../dashboard.php" class="btn btn-success">Ir a Crear Transacción</a>';
            $output .= '</div>';
        } else {
            $output .= '<div class="alert alert-warning mt-3">';
            $output .= '<h5>⚠️ Formulario Requiere Atención</h5>';
            $output .= '<p>Algunos componentes necesarios no están disponibles.</p>';
            $output .= '</div>';
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error verificando formulario de transacciones: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function testComplete() {
    $output = '<h4><i class="fas fa-chart-line text-secondary"></i> Diagnóstico Completo del Sistema</h4>';
    
    $output .= '<div class="accordion" id="completeTestAccordion">';
    
    // Test 1: Base de datos
    $output .= '<div class="accordion-item">';
    $output .= '<h2 class="accordion-header">';
    $output .= '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#db-test">';
    $output .= '<i class="fas fa-database me-2"></i>Base de Datos';
    $output .= '</button>';
    $output .= '</h2>';
    $output .= '<div id="db-test" class="accordion-collapse collapse show">';
    $output .= '<div class="accordion-body">' . testDatabase() . '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Test 2: Usuarios
    $output .= '<div class="accordion-item">';
    $output .= '<h2 class="accordion-header">';
    $output .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#users-test">';
    $output .= '<i class="fas fa-users me-2"></i>Sistema de Usuarios';
    $output .= '</button>';
    $output .= '</h2>';
    $output .= '<div id="users-test" class="accordion-collapse collapse">';
    $output .= '<div class="accordion-body">' . testUsers() . '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Test 3: Datos
    $output .= '<div class="accordion-item">';
    $output .= '<h2 class="accordion-header">';
    $output .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#data-test">';
    $output .= '<i class="fas fa-tags me-2"></i>Categorías y Cuentas';
    $output .= '</button>';
    $output .= '</h2>';
    $output .= '<div id="data-test" class="accordion-collapse collapse">';
    $output .= '<div class="accordion-body">' . testData() . '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Test 4: Transacciones
    $output .= '<div class="accordion-item">';
    $output .= '<h2 class="accordion-header">';
    $output .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trans-test">';
    $output .= '<i class="fas fa-exchange-alt me-2"></i>Formulario de Transacciones';
    $output .= '</button>';
    $output .= '</h2>';
    $output .= '<div id="trans-test" class="accordion-collapse collapse">';
    $output .= '<div class="accordion-body">' . testTransactions() . '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    $output .= '</div>'; // Cierre accordion
    
    return $output;
}

function repairUsers() {
    global $db;
    
    $output = '<h4><i class="fas fa-user-cog text-primary"></i> Reparación de Usuarios</h4>';
    $successes = [];
    $issues = [];
    
    try {
        // Verificar y corregir estructura
        $estructura = $db->fetchAll("DESCRIBE usuarios");
        $campos_existentes = array_column($estructura, 'Field');
        
        $campos_necesarios = [
            ['nombre' => 'rol', 'tipo' => "ENUM('admin', 'usuario') DEFAULT 'usuario'", 'posicion' => 'AFTER email'],
            ['nombre' => 'activo', 'tipo' => 'TINYINT(1) DEFAULT 1', 'posicion' => 'AFTER rol'],
            ['nombre' => 'created_at', 'tipo' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'posicion' => ''],
            ['nombre' => 'updated_at', 'tipo' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'posicion' => '']
        ];
        
        foreach ($campos_necesarios as $campo) {
            if (!in_array($campo['nombre'], $campos_existentes)) {
                try {
                    $sql = "ALTER TABLE usuarios ADD COLUMN {$campo['nombre']} {$campo['tipo']}";
                    if (!empty($campo['posicion'])) {
                        $sql .= " {$campo['posicion']}";
                    }
                    $db->query($sql);
                    $successes[] = "✅ Columna '{$campo['nombre']}' agregada";
                } catch (Exception $e) {
                    $issues[] = "❌ Error agregando '{$campo['nombre']}': " . $e->getMessage();
                }
            } else {
                $successes[] = "✅ Columna '{$campo['nombre']}' ya existe";
            }
        }
        
        // Crear usuario de prueba si no existe
        $usuario_test = $db->fetch("SELECT id FROM usuarios WHERE email = 'admin@test.com'");
        if (!$usuario_test) {
            try {
                $hash_test = password_hash("123456", PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)",
                    ["Admin Test", "admin@test.com", $hash_test, "admin", 1]
                );
                $successes[] = "✅ Usuario de prueba creado: admin@test.com / 123456";
            } catch (Exception $e) {
                $issues[] = "❌ Error creando usuario de prueba: " . $e->getMessage();
            }
        } else {
            $successes[] = "✅ Usuario de prueba ya existe";
        }
        
        // Actualizar usuarios sin rol
        $usuarios_sin_rol = $db->fetchAll("SELECT id, email FROM usuarios WHERE rol IS NULL OR rol = ''");
        foreach ($usuarios_sin_rol as $usuario) {
            try {
                $nuevo_rol = ($usuario['id'] == 1) ? 'admin' : 'usuario';
                $db->query("UPDATE usuarios SET rol = ? WHERE id = ?", [$nuevo_rol, $usuario['id']]);
                $successes[] = "✅ Rol asignado a {$usuario['email']}: $nuevo_rol";
            } catch (Exception $e) {
                $issues[] = "❌ Error asignando rol a {$usuario['email']}: " . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error general en reparación: " . $e->getMessage();
    }
    
    if (empty($issues)) {
        $output .= '<div class="alert alert-success mt-3">';
        $output .= '<h5>🎉 Reparación Completada</h5>';
        $output .= '<p>La tabla de usuarios ha sido corregida exitosamente.</p>';
        $output .= '</div>';
    }
    
    return $output . formatResults($successes, $issues);
}

function createSampleData() {
    global $db;
    
    $output = '<h4><i class="fas fa-plus-circle text-info"></i> Crear Datos de Ejemplo</h4>';
    $successes = [];
    $issues = [];
    
    try {
        // Crear categorías si no existen
        $categorias_existentes = $db->fetch("SELECT COUNT(*) as total FROM categorias");
        
        if ($categorias_existentes['total'] == 0) {
            $categorias_ejemplo = [
                ['nombre' => 'Salario', 'tipo' => 'ingreso', 'activa' => 1],
                ['nombre' => 'Freelance', 'tipo' => 'ingreso', 'activa' => 1],
                ['nombre' => 'Inversiones', 'tipo' => 'ingreso', 'activa' => 1],
                ['nombre' => 'Alimentación', 'tipo' => 'gasto', 'activa' => 1],
                ['nombre' => 'Transporte', 'tipo' => 'gasto', 'activa' => 1],
                ['nombre' => 'Servicios', 'tipo' => 'gasto', 'activa' => 1],
                ['nombre' => 'Entretenimiento', 'tipo' => 'gasto', 'activa' => 1],
                ['nombre' => 'Salud', 'tipo' => 'gasto', 'activa' => 1],
            ];
            
            foreach ($categorias_ejemplo as $categoria) {
                try {
                    $db->query(
                        "INSERT INTO categorias (nombre, tipo, activa) VALUES (?, ?, ?)",
                        [$categoria['nombre'], $categoria['tipo'], $categoria['activa']]
                    );
                } catch (Exception $e) {
                    $issues[] = "❌ Error creando categoría '{$categoria['nombre']}': " . $e->getMessage();
                }
            }
            
            $successes[] = "✅ Se crearon " . count($categorias_ejemplo) . " categorías de ejemplo";
        } else {
            $successes[] = "✅ Ya existen categorías en el sistema";
        }
        
        // Crear cuentas si no existen
        $cuentas_existentes = $db->fetch("SELECT COUNT(*) as total FROM cuentas");
        
        if ($cuentas_existentes['total'] == 0) {
            $cuentas_ejemplo = [
                ['nombre' => 'Cuenta Corriente', 'tipo' => 'corriente', 'saldo_inicial' => 1000.00, 'saldo_actual' => 1000.00, 'activa' => 1],
                ['nombre' => 'Cuenta de Ahorros', 'tipo' => 'ahorro', 'saldo_inicial' => 5000.00, 'saldo_actual' => 5000.00, 'activa' => 1],
                ['nombre' => 'Efectivo', 'tipo' => 'efectivo', 'saldo_inicial' => 200.00, 'saldo_actual' => 200.00, 'activa' => 1],
            ];
            
            foreach ($cuentas_ejemplo as $cuenta) {
                try {
                    $db->query(
                        "INSERT INTO cuentas (nombre, tipo, saldo_inicial, saldo_actual, activa) VALUES (?, ?, ?, ?, ?)",
                        [$cuenta['nombre'], $cuenta['tipo'], $cuenta['saldo_inicial'], $cuenta['saldo_actual'], $cuenta['activa']]
                    );
                } catch (Exception $e) {
                    $issues[] = "❌ Error creando cuenta '{$cuenta['nombre']}': " . $e->getMessage();
                }
            }
            
            $successes[] = "✅ Se crearon " . count($cuentas_ejemplo) . " cuentas de ejemplo";
        } else {
            $successes[] = "✅ Ya existen cuentas en el sistema";
        }
        
        if (empty($issues)) {
            $output .= '<div class="alert alert-success mt-3">';
            $output .= '<h5>🎉 Datos de Ejemplo Creados</h5>';
            $output .= '<p>El sistema ahora tiene datos básicos para funcionar.</p>';
            $output .= '<a href="../dashboard.php" class="btn btn-success">Ir al Dashboard</a>';
            $output .= '</div>';
        }
        
    } catch (Exception $e) {
        $issues[] = "❌ Error general creando datos: " . $e->getMessage();
    }
    
    return $output . formatResults($successes, $issues);
}

function runMigration() {
    $output = '<h4><i class="fas fa-database text-success"></i> Migración de Base de Datos</h4>';
    
    $output .= '<div class="alert alert-info">';
    $output .= '<h5>ℹ️ Migración de Base de Datos</h5>';
    $output .= '<p>Esta herramienta ejecutará el script de migración completo.</p>';
    $output .= '<p><strong>¿Deseas continuar?</strong></p>';
    $output .= '<a href="../migracion.php" class="btn btn-warning" target="_blank">Ejecutar Migración</a>';
    $output .= '</div>';
    
    return $output;
}

function formatResults($successes, $issues) {
    $output = '';
    
    if (!empty($successes)) {
        $output .= '<div class="mt-3">';
        $output .= '<h5 class="text-success">✅ Resultados Exitosos:</h5>';
        $output .= '<ul class="list-unstyled">';
        foreach ($successes as $success) {
            $output .= "<li class='text-success mb-1'>$success</li>";
        }
        $output .= '</ul>';
        $output .= '</div>';
    }
    
    if (!empty($issues)) {
        $output .= '<div class="mt-3">';
        $output .= '<h5 class="text-warning">⚠️ Problemas Detectados:</h5>';
        $output .= '<ul class="list-unstyled">';
        foreach ($issues as $issue) {
            $output .= "<li class='text-warning mb-1'>$issue</li>";
        }
        $output .= '</ul>';
        $output .= '</div>';
    }
    
    return $output;
}
?>
