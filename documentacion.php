<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación del Sistema - Contabilidad Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        body {
            background: var(--gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .documentation-container {
            padding: 2rem 0;
        }
        
        .doc-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .doc-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }
        
        .doc-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .doc-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20" fill="%23ffffff20"><circle cx="20" cy="10" r="3"/><circle cx="50" cy="10" r="3"/><circle cx="80" cy="10" r="3"/></svg>');
            background-repeat: repeat;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .feature-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--info));
        }
        
        .feature-card:hover {
            border-color: var(--secondary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .tool-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .tool-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 5px solid var(--secondary);
        }
        
        .tool-card:hover {
            transform: translateX(10px);
            border-left-color: var(--success);
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-active { background: var(--success); }
        .status-warning { background: var(--warning); }
        .status-error { background: var(--danger); }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255,255,255,0.95);
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: white;
            transform: scale(1.05);
        }
        
        .quick-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .quick-action-btn {
            background: var(--secondary);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 5px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <a href="diagnostico.php" class="back-button">
        <i class="fas fa-arrow-left me-2"></i>Volver al Panel
    </a>
    
    <div class="quick-actions">
        <button class="quick-action-btn" onclick="location.href='dashboard.php'" title="Dashboard">
            <i class="fas fa-home"></i>
        </button>
        <button class="quick-action-btn" onclick="location.href='diagnostico.php'" title="Panel de Diagnóstico">
            <i class="fas fa-stethoscope"></i>
        </button>
    </div>

    <div class="container documentation-container">
        <!-- Header Principal -->
        <div class="doc-card">
            <div class="doc-header">
                <h1><i class="fas fa-book-open me-3"></i>Documentación del Sistema</h1>
                <p class="mb-0 fs-5">Guía Completa de Herramientas de Diagnóstico y Reparación</p>
                <div class="mt-3">
                    <span class="badge bg-light text-dark me-2">Versión 2.0</span>
                    <span class="badge bg-light text-dark">Actualizado 2025</span>
                </div>
            </div>
        </div>

        <!-- Resumen del Sistema -->
        <div class="doc-card">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-info-circle me-2"></i>¿Qué es este Sistema?</h3>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-8">
                        <p class="lead">
                            Este es un <strong>Centro de Diagnóstico Integral</strong> para el Sistema de Contabilidad Familiar. 
                            Centraliza todas las herramientas necesarias para mantener, verificar y reparar el sistema.
                        </p>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-check-circle text-success me-2"></i>Funcionalidades</h5>
                                <ul class="list-unstyled">
                                    <li><span class="status-indicator status-active"></span>Diagnósticos automáticos</li>
                                    <li><span class="status-indicator status-active"></span>Reparaciones inteligentes</li>
                                    <li><span class="status-indicator status-active"></span>Interfaz visual moderna</li>
                                    <li><span class="status-indicator status-active"></span>Documentación integrada</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-shield-alt text-info me-2"></i>Beneficios</h5>
                                <ul class="list-unstyled">
                                    <li><span class="status-indicator status-active"></span>Organización centralizada</li>
                                    <li><span class="status-indicator status-active"></span>Solución rápida de problemas</li>
                                    <li><span class="status-indicator status-active"></span>Mantenimiento preventivo</li>
                                    <li><span class="status-indicator status-active"></span>Código limpio y organizado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded">
                            <h6><i class="fas fa-folder text-warning me-2"></i>Estructura del Proyecto</h6>
                            <pre class="small mb-0"><code>Contabilidad/
├── diagnostico.php
├── test/
│   ├── README.md
│   ├── test_engine.php
│   ├── diagnostico_*.php
│   ├── test_*.php
│   └── reparacion_*.php
└── [sistema principal]</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Herramientas Disponibles -->
        <div class="doc-card">
            <div class="card-header bg-success text-white">
                <h3><i class="fas fa-toolbox me-2"></i>Herramientas Disponibles</h3>
            </div>
            <div class="tool-showcase">
                <div class="tool-card">
                    <h5><i class="fas fa-database text-primary me-2"></i>Diagnóstico de Base de Datos</h5>
                    <p class="text-muted">Verifica conexión, estructura de tablas y integridad de datos.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary">Automático</span>
                        <small class="text-muted">test_engine.php</small>
                    </div>
                </div>

                <div class="tool-card">
                    <h5><i class="fas fa-users text-info me-2"></i>Verificación de Usuarios</h5>
                    <p class="text-muted">Examina estructura de usuarios, roles y permisos.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-info">Verificación</span>
                        <small class="text-muted">verificar_usuarios.php</small>
                    </div>
                </div>

                <div class="tool-card">
                    <h5><i class="fas fa-sign-in-alt text-warning me-2"></i>Prueba de Autenticación</h5>
                    <p class="text-muted">Prueba el sistema de login y autenticación.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-warning">Prueba</span>
                        <small class="text-muted">diagnostico_login.php</small>
                    </div>
                </div>

                <div class="tool-card">
                    <h5><i class="fas fa-tags text-success me-2"></i>Verificación de Datos</h5>
                    <p class="text-muted">Comprueba categorías y cuentas necesarias.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-success">Datos</span>
                        <small class="text-muted">test_categorias.php</small>
                    </div>
                </div>

                <div class="tool-card">
                    <h5><i class="fas fa-form text-danger me-2"></i>Prueba de Formularios</h5>
                    <p class="text-muted">Verifica que los formularios carguen correctamente.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-danger">Formulario</span>
                        <small class="text-muted">test_formulario.php</small>
                    </div>
                </div>

                <div class="tool-card">
                    <h5><i class="fas fa-wrench text-secondary me-2"></i>Reparación Automática</h5>
                    <p class="text-muted">Corrige problemas comunes automáticamente.</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary">Reparación</span>
                        <small class="text-muted">corregir_*.php</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Casos de Uso -->
        <div class="doc-card">
            <div class="card-header bg-info text-white">
                <h3><i class="fas fa-map-signs me-2"></i>Casos de Uso Comunes</h3>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <h5><i class="fas fa-rocket text-primary me-2"></i>Primera Instalación</h5>
                    <ol class="mt-3">
                        <li><strong>Diagnóstico Completo</strong> - Verificar estado general</li>
                        <li><strong>Corregir Usuarios</strong> - Si hay errores de estructura</li>
                        <li><strong>Crear Datos de Ejemplo</strong> - Para tener datos iniciales</li>
                        <li><strong>Probar Login</strong> - Verificar autenticación</li>
                    </ol>
                    <div class="text-center mt-3">
                        <a href="diagnostico.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-play me-1"></i>Comenzar Diagnóstico
                        </a>
                    </div>
                </div>

                <div class="feature-card">
                    <h5><i class="fas fa-sync text-warning me-2"></i>Después de Actualización</h5>
                    <ol class="mt-3">
                        <li><strong>Migración de BD</strong> - Actualizar estructura</li>
                        <li><strong>Verificar Estado</strong> - Comprobar funcionamiento</li>
                        <li><strong>Probar Formularios</strong> - Validar funcionalidad</li>
                        <li><strong>Revisar Usuarios</strong> - Comprobar accesos</li>
                    </ol>
                    <div class="text-center mt-3">
                        <button class="btn btn-warning btn-sm" onclick="runMigration()">
                            <i class="fas fa-database me-1"></i>Ejecutar Migración
                        </button>
                    </div>
                </div>

                <div class="feature-card">
                    <h5><i class="fas fa-bug text-danger me-2"></i>Solución de Problemas</h5>
                    <div class="mt-3">
                        <div class="alert alert-light p-2 mb-2">
                            <strong>Error de Login:</strong> Ejecutar "Corregir Usuarios"
                        </div>
                        <div class="alert alert-light p-2 mb-2">
                            <strong>Formulario no carga:</strong> Verificar "Categorías y Cuentas"
                        </div>
                        <div class="alert alert-light p-2 mb-2">
                            <strong>Error de BD:</strong> Ejecutar "Migración"
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="diagnostico.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-stethoscope me-1"></i>Panel de Diagnóstico
                        </a>
                    </div>
                </div>

                <div class="feature-card">
                    <h5><i class="fas fa-shield-alt text-success me-2"></i>Mantenimiento Preventivo</h5>
                    <ul class="mt-3">
                        <li>Ejecutar diagnóstico completo semanalmente</li>
                        <li>Verificar estructura de BD mensualmente</li>
                        <li>Revisar usuarios y permisos</li>
                        <li>Mantener datos de ejemplo actualizados</li>
                    </ul>
                    <div class="text-center mt-3">
                        <button class="btn btn-success btn-sm" onclick="runCompleteCheck()">
                            <i class="fas fa-check-circle me-1"></i>Verificación Completa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Técnica -->
        <div class="doc-card">
            <div class="card-header bg-dark text-white">
                <h3><i class="fas fa-code me-2"></i>Información Técnica</h3>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-server text-primary me-2"></i>Requisitos del Sistema</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">PHP 7.4 o superior</li>
                            <li class="list-group-item">MySQL 5.7 o superior</li>
                            <li class="list-group-item">Apache/Nginx web server</li>
                            <li class="list-group-item">Extensiones: PDO, MySQLi</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-lock text-warning me-2"></i>Consideraciones de Seguridad</h5>
                        <div class="alert alert-warning">
                            <p><strong>⚠️ Importante:</strong></p>
                            <ul class="mb-0">
                                <li>No usar en producción sin protección</li>
                                <li>Restringir acceso a la carpeta /test/</li>
                                <li>Eliminar archivos de diagnóstico en producción</li>
                                <li>Usar autenticación adicional si es necesario</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5><i class="fas fa-file-code text-info me-2"></i>Archivos de Configuración</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded">
                                    <h6>Base de Datos</h6>
                                    <code>config/database.php</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded">
                                    <h6>Motor de Pruebas</h6>
                                    <code>test/test_engine.php</code>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-3 rounded">
                                    <h6>Panel Principal</h6>
                                    <code>diagnostico.php</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enlaces de Navegación -->
        <div class="doc-card">
            <div class="card-header bg-secondary text-white">
                <h3><i class="fas fa-compass me-2"></i>Navegación Rápida</h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="diagnostico.php" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-stethoscope fa-2x d-block mb-2"></i>
                            <strong>Panel de Diagnóstico</strong>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="dashboard.php" class="btn btn-outline-success w-100 p-3">
                            <i class="fas fa-home fa-2x d-block mb-2"></i>
                            <strong>Dashboard Principal</strong>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="test/README.md" class="btn btn-outline-info w-100 p-3" target="_blank">
                            <i class="fas fa-book fa-2x d-block mb-2"></i>
                            <strong>Documentación Técnica</strong>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="login.php" class="btn btn-outline-secondary w-100 p-3">
                            <i class="fas fa-sign-in-alt fa-2x d-block mb-2"></i>
                            <strong>Sistema de Login</strong>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function runMigration() {
            if (confirm('¿Ejecutar migración de base de datos? Esta acción puede modificar la estructura.')) {
                window.open('diagnostico.php', '_blank');
                setTimeout(() => {
                    alert('Dirígete al panel de diagnóstico y selecciona "Migración de BD"');
                }, 1000);
            }
        }

        function runCompleteCheck() {
            window.open('diagnostico.php', '_blank');
            setTimeout(() => {
                alert('Dirígete al panel y ejecuta "Diagnóstico Completo"');
            }, 1000);
        }

        // Animaciones al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.doc-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
