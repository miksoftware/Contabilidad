<?php
session_start();

// Solo permitir acceso a administradores
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado. Solo administradores pueden ejecutar migraciones.');
}

require_once 'config/database.php';

echo "<h2>Script de Migración de Base de Datos</h2>";
echo "<pre>";

try {
    echo "Iniciando migración...\n\n";
    
    // 1. Verificar si la columna 'role' existe en usuarios
    $columns = $db->fetchAll("SHOW COLUMNS FROM usuarios LIKE 'role'");
    if (empty($columns)) {
        echo "1. Agregando columna 'role' a tabla usuarios...\n";
        $db->query("ALTER TABLE usuarios ADD COLUMN role ENUM('admin', 'usuario') DEFAULT 'usuario' AFTER password");
        
        // Copiar datos de 'rol' a 'role' si existe
        $rolColumns = $db->fetchAll("SHOW COLUMNS FROM usuarios LIKE 'rol'");
        if (!empty($rolColumns)) {
            $db->query("UPDATE usuarios SET role = rol");
            $db->query("ALTER TABLE usuarios DROP COLUMN rol");
        }
        echo "   ✓ Columna 'role' agregada exitosamente\n\n";
    } else {
        echo "1. ✓ Columna 'role' ya existe en tabla usuarios\n\n";
    }
    
    // 2. Verificar tabla presupuestos_items
    $tables = $db->fetchAll("SHOW TABLES LIKE 'presupuestos_items'");
    if (empty($tables)) {
        echo "2. Creando tabla presupuestos_items...\n";
        $db->query("
            CREATE TABLE presupuestos_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(150) NOT NULL,
                categoria_id INT NOT NULL,
                usuario_id INT NOT NULL,
                tipo ENUM('ingreso', 'gasto') NOT NULL,
                monto DECIMAL(12,2) NOT NULL,
                fecha_vencimiento DATE,
                es_recurrente BOOLEAN DEFAULT TRUE,
                frecuencia ENUM('semanal', 'quincenal', 'mensual', 'bimestral', 'trimestral', 'anual') DEFAULT 'mensual',
                activo BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            )
        ");
        echo "   ✓ Tabla presupuestos_items creada exitosamente\n\n";
    } else {
        echo "2. ✓ Tabla presupuestos_items ya existe\n\n";
    }
    
    // 2b. Verificar tabla presupuestos_pagos
    $tables = $db->fetchAll("SHOW TABLES LIKE 'presupuestos_pagos'");
    if (empty($tables)) {
        echo "2b. Creando tabla presupuestos_pagos...\n";
        $db->query("
            CREATE TABLE presupuestos_pagos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                mes_año VARCHAR(7) NOT NULL,
                fecha_pago DATE NOT NULL,
                monto_pagado DECIMAL(12,2) NOT NULL,
                usuario_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES presupuestos_items(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                UNIQUE KEY unique_pago (item_id, mes_año)
            )
        ");
        echo "   ✓ Tabla presupuestos_pagos creada exitosamente\n\n";
    } else {
        echo "2b. ✓ Tabla presupuestos_pagos ya existe\n\n";
    }
    
    // 2c. Migrar datos de tabla presupuestos antigua si existe
    $tables = $db->fetchAll("SHOW TABLES LIKE 'presupuestos'");
    if (!empty($tables)) {
        echo "2c. Migrando datos de tabla presupuestos antigua...\n";
        
        // Verificar si hay datos en la tabla antigua
        $oldData = $db->fetchAll("SELECT * FROM presupuestos");
        
        if (!empty($oldData)) {
            foreach ($oldData as $item) {
                // Migrar a la nueva estructura
                try {
                    $db->query(
                        "INSERT INTO presupuestos_items (nombre, categoria_id, usuario_id, tipo, monto, es_recurrente, frecuencia, activo) 
                         VALUES (?, ?, ?, 'gasto', ?, 1, 'mensual', 1)",
                        [
                            $item['nombre'] ?? 'Presupuesto migrado',
                            $item['categoria_id'],
                            $item['usuario_id'],
                            $item['limite_mensual'] ?? 0
                        ]
                    );
                } catch (Exception $e) {
                    echo "   Advertencia: No se pudo migrar presupuesto ID " . $item['id'] . ": " . $e->getMessage() . "\n";
                }
            }
            echo "   ✓ Datos migrados de tabla presupuestos\n";
        }
        
        // Renombrar tabla antigua
        $db->query("RENAME TABLE presupuestos TO presupuestos_old");
        echo "   ✓ Tabla presupuestos antigua renombrada a presupuestos_old\n\n";
    }
    
    // 3. Verificar columnas adicionales en cuentas
    $columns = $db->fetchAll("SHOW COLUMNS FROM cuentas");
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('banco', $columnNames)) {
        echo "3. Agregando columnas adicionales a tabla cuentas...\n";
        $db->query("ALTER TABLE cuentas ADD COLUMN banco VARCHAR(100) AFTER tipo");
        $db->query("ALTER TABLE cuentas ADD COLUMN numero_cuenta VARCHAR(50) AFTER banco");
        $db->query("ALTER TABLE cuentas ADD COLUMN usuario_id INT AFTER numero_cuenta");
        $db->query("ALTER TABLE cuentas ADD FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL");
        echo "   ✓ Columnas agregadas a tabla cuentas\n\n";
    } else {
        echo "3. ✓ Tabla cuentas tiene todas las columnas necesarias\n\n";
    }
    
    // 4. Verificar datos por defecto
    $adminUser = $db->fetch("SELECT * FROM usuarios WHERE role = 'admin' LIMIT 1");
    if (!$adminUser) {
        echo "4. Creando usuario administrador por defecto...\n";
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO usuarios (nombre, email, password, role) VALUES (?, ?, ?, ?)",
            ['Administrador', 'admin@contabilidad.local', $adminPassword, 'admin']
        );
        echo "   ✓ Usuario administrador creado (email: admin@contabilidad.local, password: admin123)\n\n";
    } else {
        echo "4. ✓ Usuario administrador ya existe\n\n";
    }
    
    // 5. Verificar que existan categorías
    $categorias = $db->fetchAll("SELECT COUNT(*) as total FROM categorias");
    if ($categorias[0]['total'] == 0) {
        echo "5. Insertando categorías por defecto...\n";
        $categoriasDefault = [
            // Ingresos
            ['Salario', 'ingreso', '#28a745', 'fas fa-wallet'],
            ['Freelance', 'ingreso', '#17a2b8', 'fas fa-laptop'],
            ['Inversiones', 'ingreso', '#ffc107', 'fas fa-chart-line'],
            ['Otros Ingresos', 'ingreso', '#6f42c1', 'fas fa-plus-circle'],
            
            // Gastos
            ['Alimentación', 'gasto', '#dc3545', 'fas fa-utensils'],
            ['Transporte', 'gasto', '#fd7e14', 'fas fa-car'],
            ['Servicios', 'gasto', '#20c997', 'fas fa-home'],
            ['Entretenimiento', 'gasto', '#e83e8c', 'fas fa-gamepad'],
            ['Salud', 'gasto', '#6f42c1', 'fas fa-heartbeat'],
            ['Educación', 'gasto', '#0dcaf0', 'fas fa-graduation-cap'],
            ['Ropa', 'gasto', '#198754', 'fas fa-tshirt'],
            ['Otros Gastos', 'gasto', '#6c757d', 'fas fa-minus-circle']
        ];
        
        foreach ($categoriasDefault as $categoria) {
            $db->query(
                "INSERT INTO categorias (nombre, tipo, color, icono) VALUES (?, ?, ?, ?)",
                $categoria
            );
        }
        echo "   ✓ Categorías por defecto insertadas\n\n";
    } else {
        echo "5. ✓ Categorías ya existen\n\n";
    }
    
    // 6. Verificar que existan cuentas
    $cuentas = $db->fetchAll("SELECT COUNT(*) as total FROM cuentas");
    if ($cuentas[0]['total'] == 0) {
        echo "6. Insertando cuentas por defecto...\n";
        $cuentasDefault = [
            ['Efectivo', 'efectivo', '#28a745'],
            ['Cuenta Corriente', 'banco', '#007bff'],
            ['Cuenta de Ahorro', 'ahorro', '#ffc107'],
            ['Tarjeta de Crédito', 'tarjeta', '#dc3545']
        ];
        
        foreach ($cuentasDefault as $cuenta) {
            $db->query(
                "INSERT INTO cuentas (nombre, tipo, color, saldo_inicial, saldo_actual) VALUES (?, ?, ?, 0.00, 0.00)",
                $cuenta
            );
        }
        echo "   ✓ Cuentas por defecto insertadas\n\n";
    } else {
        echo "6. ✓ Cuentas ya existen\n\n";
    }
    
    // 7. Insertar items de presupuesto de ejemplo
    $presupuestos_items = $db->fetchAll("SELECT COUNT(*) as total FROM presupuestos_items");
    if ($presupuestos_items[0]['total'] == 0) {
        echo "7. Insertando items de presupuesto de ejemplo...\n";
        
        // Obtener IDs de categorías de gastos
        $categoriaServicios = $db->fetch("SELECT id FROM categorias WHERE nombre = 'Servicios' AND tipo = 'gasto'");
        $categoriaAlimentacion = $db->fetch("SELECT id FROM categorias WHERE nombre = 'Alimentación' AND tipo = 'gasto'");
        $categoriaTransporte = $db->fetch("SELECT id FROM categorias WHERE nombre = 'Transporte' AND tipo = 'gasto'");
        
        if ($categoriaServicios) {
            $serviciosDefault = [
                ['Luz', $categoriaServicios['id'], 150.00, 15],
                ['Agua', $categoriaServicios['id'], 80.00, 20],
                ['Gas', $categoriaServicios['id'], 60.00, 25],
                ['Internet', $categoriaServicios['id'], 120.00, 5],
                ['Teléfono', $categoriaServicios['id'], 90.00, 10]
            ];
            
            foreach ($serviciosDefault as $servicio) {
                $fecha_venc = date('Y-m-') . sprintf('%02d', $servicio[3]);
                $db->query(
                    "INSERT INTO presupuestos_items (nombre, categoria_id, usuario_id, tipo, monto, fecha_vencimiento, es_recurrente, frecuencia) 
                     VALUES (?, ?, 1, 'gasto', ?, ?, 1, 'mensual')",
                    [$servicio[0], $servicio[1], $servicio[2], $fecha_venc]
                );
            }
        }
        
        if ($categoriaAlimentacion) {
            $db->query(
                "INSERT INTO presupuestos_items (nombre, categoria_id, usuario_id, tipo, monto, es_recurrente, frecuencia) 
                 VALUES ('Mercado Semanal', ?, 1, 'gasto', 250.00, 1, 'semanal')",
                [$categoriaAlimentacion['id']]
            );
        }
        
        if ($categoriaTransporte) {
            $db->query(
                "INSERT INTO presupuestos_items (nombre, categoria_id, usuario_id, tipo, monto, es_recurrente, frecuencia) 
                 VALUES ('Combustible', ?, 1, 'gasto', 200.00, 1, 'semanal')",
                [$categoriaTransporte['id']]
            );
        }
        
        echo "   ✓ Items de presupuesto de ejemplo insertados\n\n";
    } else {
        echo "7. ✓ Items de presupuesto ya existen\n\n";
    }
    
    echo "🎉 ¡Migración completada exitosamente!\n\n";
    echo "Resumen:\n";
    echo "- Base de datos actualizada a la última versión\n";
    echo "- Todas las tablas tienen la estructura correcta\n";
    echo "- Datos por defecto verificados\n\n";
    
    echo "Puedes volver al <a href='dashboard.php'>Dashboard</a>\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la migración: " . $e->getMessage() . "\n";
    echo "Por favor, revisa el error y ejecuta la migración nuevamente.\n";
}

echo "</pre>";
?>
