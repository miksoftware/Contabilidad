<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Diagnóstico - Contabilidad Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        body {
            background: var(--gradient-bg);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .main-container {
            padding: 40px 0;
        }
        
        .hero-section {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
        }
        
        .hero-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .hero-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff10"><polygon points="0,0 1000,0 1000,60 0,100"/></svg>');
            background-size: cover;
        }
        
        .diagnostic-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .diagnostic-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .card-header-modern {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--secondary-color);
            padding: 20px 25px;
            position: relative;
        }
        
        .card-header-modern::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), var(--info-color));
        }
        
        .status-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 25px;
        }
        
        .status-badge {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .status-badge:hover::before {
            left: 100%;
        }
        
        .status-success { border-color: var(--success-color); color: var(--success-color); }
        .status-warning { border-color: var(--warning-color); color: var(--warning-color); }
        .status-danger { border-color: var(--danger-color); color: var(--danger-color); }
        .status-info { border-color: var(--info-color); color: var(--info-color); }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            padding: 25px;
        }
        
        .test-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .test-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--secondary-color), var(--info-color));
            transition: width 0.3s ease;
        }
        
        .test-item:hover::before {
            width: 8px;
        }
        
        .test-item:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .btn-test {
            background: linear-gradient(45deg, var(--secondary-color), var(--info-color));
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-test::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-test:hover::before {
            left: 100%;
        }
        
        .btn-test:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-repair {
            background: linear-gradient(45deg, var(--warning-color), #e67e22);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-repair:hover {
            background: linear-gradient(45deg, #d68910, var(--warning-color));
            color: white;
            transform: scale(1.05);
        }
        
        .result-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid #e9ecef;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .loading {
            display: inline-block;
            width: 25px;
            height: 25px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255,255,255,0.95);
            border: none;
            padding: 12px 18px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: white;
            transform: scale(1.05);
        }
        
        .documentation-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            border: 1px solid #e9ecef;
        }
        
        .feature-highlight {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(155, 89, 182, 0.1) 100%);
            border-left: 4px solid var(--secondary-color);
            padding: 20px;
            border-radius: 0 10px 10px 0;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn back-btn">
        <i class="fas fa-arrow-left"></i> Volver al Sistema
    </a>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-header">
                        <h1><i class="fas fa-stethoscope me-3"></i>Centro de Diagnóstico y Documentación</h1>
                        <p class="mb-0 fs-5">Sistema de Contabilidad Familiar - Herramientas Completas de Verificación</p>
                    </div>
                    <div class="status-dashboard" id="system-status">
                        <div class="loading"></div>
                        <span class="ms-2">Verificando estado del sistema...</span>
                    </div>
                </div>

                <!-- Documentación del Sistema -->
                <div class="diagnostic-card">
                    <div class="card-header-modern">
                        <h4><i class="fas fa-book me-2"></i>Documentación del Sistema</h4>
                    </div>
                    <div class="documentation-section">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><i class="fas fa-info-circle text-primary me-2"></i>¿Qué es este Centro de Diagnóstico?</h5>
                                <p class="text-muted">
                                    Este panel centraliza todas las herramientas de verificación, diagnóstico y reparación 
                                    del sistema de contabilidad familiar. Permite mantener el sistema funcionando correctamente 
                                    y solucionar problemas de forma rápida y eficiente.
                                </p>
                                
                                <div class="feature-highlight">
                                    <h6><i class="fas fa-lightbulb text-warning me-2"></i>Características Principales:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Diagnósticos Automáticos:</strong> Verifica base de datos, usuarios, formularios</li>
                                        <li><strong>Reparaciones Inteligentes:</strong> Corrige problemas automáticamente</li>
                                        <li><strong>Interfaz Visual:</strong> Resultados claros y fáciles de entender</li>
                                        <li><strong>Organización Centralizada:</strong> Todo en un solo lugar</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded">
                                    <h6><i class="fas fa-folder-open text-info me-2"></i>Archivos de Test</h6>
                                    <p class="small text-muted mb-2">
                                        Todos los archivos de diagnóstico están organizados en:
                                    </p>
                                    <code class="d-block bg-dark text-light p-2 rounded">
                                        /test/README.md
                                    </code>
                                    <a href="test/README.md" class="btn btn-sm btn-outline-info mt-2" target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i>Ver Documentación Completa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnósticos Rápidos -->
                <div class="diagnostic-card">
                    <div class="card-header-modern">
                        <h4><i class="fas fa-tachometer-alt me-2"></i>Diagnósticos Rápidos</h4>
                        <small class="text-muted">Verificaciones rápidas del estado del sistema</small>
                    </div>
                    <div class="test-grid">
                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-database text-primary fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Estado de Base de Datos</h5>
                                    <small class="text-muted">Conexión, tablas y estructura</small>
                                </div>
                            </div>
                            <p class="text-muted">Verifica que la base de datos esté conectada correctamente y que todas las tablas necesarias existan con la estructura correcta.</p>
                            <button class="btn btn-test w-100" onclick="runTest('database')">
                                <i class="fas fa-play me-2"></i>Verificar Base de Datos
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-users text-info fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Sistema de Usuarios</h5>
                                    <small class="text-muted">Usuarios, roles y autenticación</small>
                                </div>
                            </div>
                            <p class="text-muted">Examina la estructura de usuarios, verifica roles y permisos, y valida que el sistema de autenticación funcione correctamente.</p>
                            <button class="btn btn-test w-100" onclick="runTest('users')">
                                <i class="fas fa-play me-2"></i>Verificar Usuarios
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-sign-in-alt text-warning fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Prueba de Login</h5>
                                    <small class="text-muted">Sistema de autenticación</small>
                                </div>
                            </div>
                            <p class="text-muted">Realiza pruebas del sistema de login, verifica hashing de contraseñas y valida el flujo de autenticación completo.</p>
                            <button class="btn btn-test w-100" onclick="runTest('login')">
                                <i class="fas fa-play me-2"></i>Probar Login
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-tags text-success fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Categorías y Cuentas</h5>
                                    <small class="text-muted">Datos para transacciones</small>
                                </div>
                            </div>
                            <p class="text-muted">Verifica que existan categorías y cuentas necesarias para que el formulario de transacciones funcione correctamente.</p>
                            <button class="btn btn-test w-100" onclick="runTest('data')">
                                <i class="fas fa-play me-2"></i>Verificar Datos
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-exchange-alt text-danger fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Formulario de Transacciones</h5>
                                    <small class="text-muted">Funcionalidad principal</small>
                                </div>
                            </div>
                            <p class="text-muted">Prueba el formulario principal del sistema para asegurar que se cargan las categorías y cuentas correctamente.</p>
                            <button class="btn btn-test w-100" onclick="runTest('transactions')">
                                <i class="fas fa-play me-2"></i>Probar Formulario
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-chart-line text-secondary fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Diagnóstico Completo</h5>
                                    <small class="text-muted">Análisis integral</small>
                                </div>
                            </div>
                            <p class="text-muted">Ejecuta un análisis completo de todo el sistema, verificando todos los componentes y generando un reporte detallado.</p>
                            <button class="btn btn-test w-100" onclick="runTest('complete')">
                                <i class="fas fa-play me-2"></i>Ejecutar Diagnóstico Completo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Herramientas de Reparación -->
                <div class="diagnostic-card">
                    <div class="card-header-modern">
                        <h4><i class="fas fa-tools me-2"></i>Herramientas de Reparación</h4>
                        <small class="text-muted">Soluciones automáticas para problemas comunes</small>
                    </div>
                    <div class="test-grid">
                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-cog text-primary fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Corregir Estructura de Usuarios</h5>
                                    <small class="text-muted">Repara tabla usuarios</small>
                                </div>
                            </div>
                            <p class="text-muted">Añade columnas faltantes en la tabla usuarios (foto_perfil, tema_preferido, etc.) y corrige la estructura.</p>
                            <button class="btn btn-repair w-100" onclick="runRepair('users')">
                                <i class="fas fa-wrench me-2"></i>Reparar Usuarios
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-plus-circle text-info fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Crear Datos de Ejemplo</h5>
                                    <small class="text-muted">Categorías y cuentas básicas</small>
                                </div>
                            </div>
                            <p class="text-muted">Crea automáticamente categorías de ingresos/gastos y cuentas básicas para que el sistema sea funcional inmediatamente.</p>
                            <button class="btn btn-repair w-100" onclick="runRepair('data')">
                                <i class="fas fa-magic me-2"></i>Crear Datos de Ejemplo
                            </button>
                        </div>

                        <div class="test-item">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-database text-success fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Migración de Base de Datos</h5>
                                    <small class="text-muted">Actualiza estructura</small>
                                </div>
                            </div>
                            <p class="text-muted">Ejecuta la migración completa de la base de datos para actualizar la estructura a la versión más reciente.</p>
                            <button class="btn btn-repair w-100" onclick="runRepair('migration')">
                                <i class="fas fa-sync me-2"></i>Ejecutar Migración
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Área de Resultados -->
                <div id="results-section" style="display: none;">
                    <div class="diagnostic-card">
                        <div class="card-header-modern">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4><i class="fas fa-clipboard-list me-2"></i>Resultados de Diagnóstico</h4>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearResults()">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="result-container" id="results-content">
                            <!-- Los resultados aparecerán aquí -->
                        </div>
                    </div>
                </div>

                <!-- Guía de Uso -->
                <div class="diagnostic-card">
                    <div class="card-header-modern">
                        <h4><i class="fas fa-graduation-cap me-2"></i>Guía de Uso</h4>
                    </div>
                    <div class="documentation-section">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-play-circle text-success me-2"></i>Primeros Pasos</h5>
                                <ol class="list-group list-group-flush">
                                    <li class="list-group-item border-0 px-0">
                                        <strong>1. Diagnóstico Completo:</strong> Ejecuta una verificación integral del sistema
                                    </li>
                                    <li class="list-group-item border-0 px-0">
                                        <strong>2. Corregir Errores:</strong> Usa las herramientas de reparación según los resultados
                                    </li>
                                    <li class="list-group-item border-0 px-0">
                                        <strong>3. Verificar Login:</strong> Prueba que la autenticación funcione
                                    </li>
                                    <li class="list-group-item border-0 px-0">
                                        <strong>4. Crear Datos:</strong> Si es la primera vez, crea datos de ejemplo
                                    </li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-exclamation-triangle text-warning me-2"></i>Problemas Comunes</h5>
                                <div class="alert alert-info">
                                    <strong>Error de Login:</strong> Ejecuta "Corregir Usuarios" y luego "Crear Datos de Ejemplo"
                                </div>
                                <div class="alert alert-warning">
                                    <strong>Formulario No Funciona:</strong> Verifica que existan categorías y cuentas activas
                                </div>
                                <div class="alert alert-danger">
                                    <strong>Error de BD:</strong> Ejecuta "Migración de Base de Datos"
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enlaces Rápidos Mejorados -->
                <div class="diagnostic-card">
                    <div class="card-header-modern">
                        <h4><i class="fas fa-link me-2"></i>Accesos Rápidos del Sistema</h4>
                    </div>
                    <div class="documentation-section">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="dashboard.php" class="btn btn-outline-primary w-100 p-3">
                                    <i class="fas fa-home fa-2x d-block mb-2"></i>
                                    <strong>Dashboard</strong>
                                    <small class="d-block text-muted">Panel principal</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="login.php" class="btn btn-outline-secondary w-100 p-3">
                                    <i class="fas fa-sign-in-alt fa-2x d-block mb-2"></i>
                                    <strong>Login</strong>
                                    <small class="d-block text-muted">Iniciar sesión</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="categorias.php" class="btn btn-outline-success w-100 p-3">
                                    <i class="fas fa-tags fa-2x d-block mb-2"></i>
                                    <strong>Categorías</strong>
                                    <small class="d-block text-muted">Gestionar categorías</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="cuentas.php" class="btn btn-outline-info w-100 p-3">
                                    <i class="fas fa-wallet fa-2x d-block mb-2"></i>
                                    <strong>Cuentas</strong>
                                    <small class="d-block text-muted">Gestionar cuentas</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="transacciones.php" class="btn btn-outline-warning w-100 p-3">
                                    <i class="fas fa-exchange-alt fa-2x d-block mb-2"></i>
                                    <strong>Transacciones</strong>
                                    <small class="d-block text-muted">Ver movimientos</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="formulario_transaccion.php" class="btn btn-outline-danger w-100 p-3">
                                    <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                    <strong>Nueva Transacción</strong>
                                    <small class="d-block text-muted">Agregar movimiento</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="usuarios.php" class="btn btn-outline-dark w-100 p-3">
                                    <i class="fas fa-users fa-2x d-block mb-2"></i>
                                    <strong>Usuarios</strong>
                                    <small class="d-block text-muted">Gestionar usuarios</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="test/README.md" class="btn btn-outline-info w-100 p-3" target="_blank">
                                    <i class="fas fa-book fa-2x d-block mb-2"></i>
                                    <strong>Documentación Técnica</strong>
                                    <small class="d-block text-muted">Guía técnica completa</small>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="documentacion.php" class="btn btn-outline-primary w-100 p-3">
                                    <i class="fas fa-book-open fa-2x d-block mb-2"></i>
                                    <strong>Documentación Visual</strong>
                                    <small class="d-block text-muted">Guía visual interactiva</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Estado inicial del sistema
        document.addEventListener('DOMContentLoaded', function() {
            checkSystemStatus();
        });

        async function checkSystemStatus() {
            try {
                const response = await fetch('test/test_engine.php?action=status');
                const data = await response.json();
                
                const statusDiv = document.getElementById('system-status');
                let statusHTML = '';
                
                if (data.success) {
                    statusHTML = `
                        <div class="status-badge ${data.database ? 'status-success' : 'status-danger'}">
                            <i class="fas fa-database fa-2x d-block mb-2"></i>
                            <h6>Base de Datos</h6>
                            <span class="badge ${data.database ? 'bg-success' : 'bg-danger'}">${data.database ? 'Conectada' : 'Error'}</span>
                        </div>
                        <div class="status-badge ${data.users > 0 ? 'status-success' : 'status-warning'}">
                            <i class="fas fa-users fa-2x d-block mb-2"></i>
                            <h6>Usuarios</h6>
                            <span class="badge ${data.users > 0 ? 'bg-success' : 'bg-warning'}">${data.users} registrados</span>
                        </div>
                        <div class="status-badge ${data.categories > 0 ? 'status-success' : 'status-warning'}">
                            <i class="fas fa-tags fa-2x d-block mb-2"></i>
                            <h6>Categorías</h6>
                            <span class="badge ${data.categories > 0 ? 'bg-success' : 'bg-warning'}">${data.categories} activas</span>
                        </div>
                        <div class="status-badge ${data.accounts > 0 ? 'status-success' : 'status-warning'}">
                            <i class="fas fa-wallet fa-2x d-block mb-2"></i>
                            <h6>Cuentas</h6>
                            <span class="badge ${data.accounts > 0 ? 'bg-success' : 'bg-warning'}">${data.accounts} activas</span>
                        </div>
                    `;
                } else {
                    statusHTML = `
                        <div class="status-badge status-danger">
                            <i class="fas fa-exclamation-triangle fa-2x d-block mb-2"></i>
                            <h6>Error del Sistema</h6>
                            <span class="badge bg-danger">No disponible</span>
                        </div>
                    `;
                }
                
                statusDiv.innerHTML = statusHTML;
            } catch (error) {
                document.getElementById('system-status').innerHTML = `
                    <div class="status-badge status-danger">
                        <i class="fas fa-exclamation-triangle fa-2x d-block mb-2"></i>
                        <h6>Error de Conexión</h6>
                        <span class="badge bg-danger">Sin conexión</span>
                    </div>
                `;
            }
        }

        async function runTest(testType) {
            showResults();
            const resultsDiv = document.getElementById('results-content');
            resultsDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading mb-3"></div>
                    <h5>Ejecutando diagnóstico...</h5>
                    <p class="text-muted">Por favor espere mientras se verifica el sistema</p>
                </div>
            `;
            
            try {
                const response = await fetch(`test/test_engine.php?action=test&type=${testType}`);
                const data = await response.text();
                resultsDiv.innerHTML = data;
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Error de Conexión</h5>
                        <p>No se pudo ejecutar la prueba: ${error.message}</p>
                        <p class="mb-0"><strong>Sugerencia:</strong> Verifica que el servidor esté funcionando y que los archivos de test estén en su lugar.</p>
                    </div>
                `;
            }
        }

        async function runRepair(repairType) {
            if (!confirm('¿Estás seguro de que quieres ejecutar esta reparación? Esta acción puede modificar la base de datos.')) {
                return;
            }
            
            showResults();
            const resultsDiv = document.getElementById('results-content');
            resultsDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading mb-3"></div>
                    <h5>Ejecutando reparación...</h5>
                    <p class="text-muted">Por favor espere mientras se repara el sistema</p>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> No cierres esta ventana hasta que termine el proceso
                    </div>
                </div>
            `;
            
            try {
                const response = await fetch(`test/test_engine.php?action=repair&type=${repairType}`);
                const data = await response.text();
                resultsDiv.innerHTML = data;
                
                // Actualizar estado del sistema después de la reparación
                setTimeout(() => {
                    checkSystemStatus();
                }, 3000);
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Error de Reparación</h5>
                        <p>No se pudo ejecutar la reparación: ${error.message}</p>
                        <p class="mb-0"><strong>Sugerencia:</strong> Verifica los permisos de la base de datos y que los archivos de reparación estén disponibles.</p>
                    </div>
                `;
            }
        }

        function showResults() {
            const resultsSection = document.getElementById('results-section');
            resultsSection.style.display = 'block';
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function clearResults() {
            document.getElementById('results-section').style.display = 'none';
        }

        // Agregar funcionalidad de teclado
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearResults();
            }
        });
    </script>
</body>
</html>
