<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Diagnóstico - Contabilidad Familiar</title>
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
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            padding: 30px 0;
        }
        
        .diagnostic-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .diagnostic-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border: none;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
            margin: 5px;
        }
        
        .status-success { background: var(--success-color); color: white; }
        .status-warning { background: var(--warning-color); color: white; }
        .status-danger { background: var(--danger-color); color: white; }
        .status-info { background: var(--info-color); color: white; }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .test-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        .test-item:hover {
            background: #e9ecef;
            border-left-color: var(--primary-color);
        }
        
        .btn-test {
            background: linear-gradient(45deg, var(--secondary-color), var(--info-color));
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-test:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: scale(1.05);
        }
        
        .result-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--secondary-color);
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
            background: rgba(255,255,255,0.9);
            border: none;
            padding: 10px 15px;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn back-btn">
        <i class="fas fa-arrow-left"></i> Volver al Sistema
    </a>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header Principal -->
                <div class="diagnostic-card">
                    <div class="card-header-custom text-center">
                        <h1><i class="fas fa-stethoscope me-3"></i>Panel de Diagnóstico</h1>
                        <p class="mb-0">Sistema de Contabilidad Familiar - Herramientas de Verificación y Pruebas</p>
                    </div>
                    <div class="card-body p-4">
                        <div id="system-status" class="text-center">
                            <div class="loading"></div>
                            <span class="ms-2">Verificando estado del sistema...</span>
                        </div>
                    </div>
                </div>

                <!-- Diagnósticos Rápidos -->
                <div class="diagnostic-card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-tachometer-alt me-2"></i>Diagnósticos Rápidos</h4>
                    </div>
                    <div class="card-body">
                        <div class="test-grid">
                            <div class="test-item">
                                <h5><i class="fas fa-database text-primary"></i> Estado de Base de Datos</h5>
                                <p class="text-muted">Verifica conexión, tablas y estructura</p>
                                <button class="btn btn-test" onclick="runTest('database')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-users text-info"></i> Sistema de Usuarios</h5>
                                <p class="text-muted">Verifica usuarios, roles y autenticación</p>
                                <button class="btn btn-test" onclick="runTest('users')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-sign-in-alt text-warning"></i> Test de Login</h5>
                                <p class="text-muted">Prueba el sistema de autenticación</p>
                                <button class="btn btn-test" onclick="runTest('login')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-tags text-success"></i> Categorías y Cuentas</h5>
                                <p class="text-muted">Verifica datos necesarios para transacciones</p>
                                <button class="btn btn-test" onclick="runTest('data')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-exchange-alt text-danger"></i> Formulario de Transacciones</h5>
                                <p class="text-muted">Prueba el formulario principal del sistema</p>
                                <button class="btn btn-test" onclick="runTest('transactions')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-chart-line text-secondary"></i> Diagnóstico Completo</h5>
                                <p class="text-muted">Análisis integral de todo el sistema</p>
                                <button class="btn btn-test" onclick="runTest('complete')">
                                    <i class="fas fa-play me-1"></i> Ejecutar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Herramientas de Reparación -->
                <div class="diagnostic-card">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-tools me-2"></i>Herramientas de Reparación</h4>
                    </div>
                    <div class="card-body">
                        <div class="test-grid">
                            <div class="test-item">
                                <h5><i class="fas fa-user-cog text-primary"></i> Corregir Usuarios</h5>
                                <p class="text-muted">Repara estructura de tabla usuarios</p>
                                <button class="btn btn-warning" onclick="runRepair('users')">
                                    <i class="fas fa-wrench me-1"></i> Reparar
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-plus-circle text-info"></i> Crear Datos de Ejemplo</h5>
                                <p class="text-muted">Crea categorías y cuentas básicas</p>
                                <button class="btn btn-warning" onclick="runRepair('data')">
                                    <i class="fas fa-magic me-1"></i> Crear
                                </button>
                            </div>

                            <div class="test-item">
                                <h5><i class="fas fa-database text-success"></i> Migración de BD</h5>
                                <p class="text-muted">Actualiza estructura de base de datos</p>
                                <button class="btn btn-warning" onclick="runRepair('migration')">
                                    <i class="fas fa-sync me-1"></i> Migrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área de Resultados -->
                <div id="results-section" style="display: none;">
                    <div class="diagnostic-card">
                        <div class="card-header bg-info text-white">
                            <h4><i class="fas fa-clipboard-list me-2"></i>Resultados de Diagnóstico</h4>
                            <button class="btn btn-sm btn-outline-light float-end" onclick="clearResults()">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="results-content" class="result-container">
                                <!-- Los resultados aparecerán aquí -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enlaces Rápidos -->
                <div class="diagnostic-card">
                    <div class="card-header bg-dark text-white">
                        <h4><i class="fas fa-link me-2"></i>Enlaces Rápidos</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="dashboard.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-home me-1"></i> Dashboard
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="login.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="categorias.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-tags me-1"></i> Categorías
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="cuentas.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-wallet me-1"></i> Cuentas
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
                        <div class="row text-center">
                            <div class="col-md-3">
                                <span class="status-badge ${data.database ? 'status-success' : 'status-danger'}">
                                    <i class="fas fa-database me-1"></i>
                                    Base de Datos ${data.database ? 'OK' : 'Error'}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge ${data.users > 0 ? 'status-success' : 'status-warning'}">
                                    <i class="fas fa-users me-1"></i>
                                    ${data.users} Usuarios
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge ${data.categories > 0 ? 'status-success' : 'status-warning'}">
                                    <i class="fas fa-tags me-1"></i>
                                    ${data.categories} Categorías
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge ${data.accounts > 0 ? 'status-success' : 'status-warning'}">
                                    <i class="fas fa-wallet me-1"></i>
                                    ${data.accounts} Cuentas
                                </span>
                            </div>
                        </div>
                    `;
                } else {
                    statusHTML = `<span class="status-badge status-danger"><i class="fas fa-exclamation-triangle me-1"></i>Error del Sistema</span>`;
                }
                
                statusDiv.innerHTML = statusHTML;
            } catch (error) {
                document.getElementById('system-status').innerHTML = 
                    `<span class="status-badge status-danger"><i class="fas fa-exclamation-triangle me-1"></i>Error de Conexión</span>`;
            }
        }

        async function runTest(testType) {
            showResults();
            const resultsDiv = document.getElementById('results-content');
            resultsDiv.innerHTML = '<div class="loading"></div> Ejecutando diagnóstico...';
            
            try {
                const response = await fetch(`test/test_engine.php?action=test&type=${testType}`);
                const data = await response.text();
                resultsDiv.innerHTML = data;
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">Error ejecutando prueba: ${error.message}</div>`;
            }
        }

        async function runRepair(repairType) {
            showResults();
            const resultsDiv = document.getElementById('results-content');
            resultsDiv.innerHTML = '<div class="loading"></div> Ejecutando reparación...';
            
            try {
                const response = await fetch(`test/test_engine.php?action=repair&type=${repairType}`);
                const data = await response.text();
                resultsDiv.innerHTML = data;
                
                // Actualizar estado del sistema después de la reparación
                setTimeout(() => {
                    checkSystemStatus();
                }, 2000);
            } catch (error) {
                resultsDiv.innerHTML = `<div class="alert alert-danger">Error ejecutando reparación: ${error.message}</div>`;
            }
        }

        function showResults() {
            document.getElementById('results-section').style.display = 'block';
            document.getElementById('results-section').scrollIntoView({ behavior: 'smooth' });
        }

        function clearResults() {
            document.getElementById('results-section').style.display = 'none';
        }
    </script>
</body>
</html>
