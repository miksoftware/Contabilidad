-- Base de datos para aplicación de contabilidad familiar
CREATE DATABASE IF NOT EXISTS contabilidad_familiar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE contabilidad_familiar;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ingreso', 'gasto') NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    icono VARCHAR(50) DEFAULT 'fas fa-circle',
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de cuentas/bancos
CREATE TABLE cuentas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('efectivo', 'banco', 'tarjeta', 'ahorro') NOT NULL,
    banco VARCHAR(100),
    numero_cuenta VARCHAR(50),
    usuario_id INT,
    saldo_inicial DECIMAL(12,2) DEFAULT 0.00,
    saldo_actual DECIMAL(12,2) DEFAULT 0.00,
    color VARCHAR(7) DEFAULT '#28a745',
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabla de transacciones
CREATE TABLE transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    cuenta_id INT NOT NULL,
    tipo ENUM('ingreso', 'gasto', 'transferencia') NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (cuenta_id) REFERENCES cuentas(id) ON DELETE RESTRICT
);

-- Tabla de metas de ahorro
CREATE TABLE metas_ahorro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    cantidad_objetivo DECIMAL(12,2) NOT NULL,
    cantidad_actual DECIMAL(12,2) DEFAULT 0.00,
    fecha_objetivo DATE,
    completada BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de presupuestos (items recurrentes como servicios)
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
);

-- Tabla de pagos de presupuestos (registro de pagos realizados)
CREATE TABLE presupuestos_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    mes_año VARCHAR(7) NOT NULL, -- Formato YYYY-MM
    fecha_pago DATE NOT NULL,
    monto_pagado DECIMAL(12,2) NOT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES presupuestos_items(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pago (item_id, mes_año)
);

-- Insertar usuarios por defecto (contraseña para ambos: 123456)
INSERT INTO usuarios (nombre, email, password, role) VALUES 
('Administrador', 'admin@contabilidad.local', '$2y$10$mk94P3prR0LD5VT4/VHOYeBq6IvQJNL9.kPT3StCj37C.XcxfmThK', 'admin'),
('Usuario Ejemplo', 'usuario@contabilidad.local', '$2y$10$mk94P3prR0LD5VT4/VHOYeBq6IvQJNL9.kPT3StCj37C.XcxfmThK', 'usuario');

-- Insertar categorías por defecto
INSERT INTO categorias (nombre, tipo, color, icono) VALUES 
-- Ingresos
('Salario', 'ingreso', '#28a745', 'fas fa-wallet'),
('Freelance', 'ingreso', '#17a2b8', 'fas fa-laptop'),
('Inversiones', 'ingreso', '#ffc107', 'fas fa-chart-line'),
('Otros Ingresos', 'ingreso', '#6f42c1', 'fas fa-plus-circle'),

-- Gastos
('Alimentación', 'gasto', '#dc3545', 'fas fa-utensils'),
('Transporte', 'gasto', '#fd7e14', 'fas fa-car'),
('Servicios', 'gasto', '#20c997', 'fas fa-home'),
('Entretenimiento', 'gasto', '#e83e8c', 'fas fa-gamepad'),
('Salud', 'gasto', '#6f42c1', 'fas fa-heartbeat'),
('Educación', 'gasto', '#0dcaf0', 'fas fa-graduation-cap'),
('Ropa', 'gasto', '#198754', 'fas fa-tshirt'),
('Otros Gastos', 'gasto', '#6c757d', 'fas fa-minus-circle');

-- Insertar cuentas por defecto
INSERT INTO cuentas (nombre, tipo, saldo_inicial, saldo_actual, color) VALUES 
('Efectivo', 'efectivo', 0.00, 0.00, '#28a745'),
('Cuenta Corriente', 'banco', 0.00, 0.00, '#007bff'),
('Cuenta de Ahorro', 'ahorro', 0.00, 0.00, '#ffc107'),
('Tarjeta de Crédito', 'tarjeta', 0.00, 0.00, '#dc3545');

-- Insertar algunas metas de ejemplo
INSERT INTO metas_ahorro (nombre, descripcion, cantidad_objetivo, fecha_objetivo) VALUES 
('Fondo de Emergencia', 'Ahorro para emergencias equivalente a 6 meses de gastos', 50000.00, '2025-12-31'),
('Vacaciones 2025', 'Ahorro para las vacaciones familiares de verano', 15000.00, '2025-07-01'),
('Nuevo Auto', 'Ahorro para la inicial de un auto nuevo', 80000.00, '2026-06-30');
