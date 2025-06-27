<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Formulario de Transacciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Test del Formulario de Transacciones</h2>
        
        <div class="alert alert-info">
            <strong>Prueba:</strong> Este formulario debería mostrar las categorías y cuentas disponibles.
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Formulario de Nueva Transacción</h5>
            </div>
            <div class="card-body">
                <form id="transaccionForm" action="procesar_transaccion.php" method="POST">
                    <?php 
                    session_start();
                    $_SESSION['user_id'] = 1; // Simular usuario logueado
                    include 'formulario_transaccion.php'; 
                    ?>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Registrar Transacción</button>
                            <button type="button" class="btn btn-secondary" onclick="testFormulario()">Test Formulario</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Consola de Debug</h5>
            <div id="console" class="bg-dark text-light p-3" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                Presiona F12 para ver la consola del navegador...
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Capturar logs de consola y mostrarlos en la página
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        const consoleDiv = document.getElementById('console');

        function addToConsole(message, type = 'log') {
            const time = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'warn' ? 'orange' : 'lightgreen';
            consoleDiv.innerHTML += `<div style="color: ${color};">[${time}] ${message}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '), 'log');
        };

        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };

        console.warn = function(...args) {
            originalWarn.apply(console, args);
            addToConsole(args.join(' '), 'warn');
        };

        function testFormulario() {
            console.log('=== INICIANDO TEST DEL FORMULARIO ===');
            
            // Test de elementos
            const elementos = ['tipo', 'categoria_id', 'cuenta_id', 'cantidad', 'fecha', 'descripcion'];
            elementos.forEach(id => {
                const elem = document.getElementById(id);
                if (elem) {
                    console.log(`✓ Elemento ${id} encontrado`);
                    if (elem.tagName === 'SELECT') {
                        console.log(`  - Opciones disponibles: ${elem.options.length}`);
                        for (let i = 0; i < elem.options.length; i++) {
                            console.log(`    ${i}: "${elem.options[i].text}" (valor: ${elem.options[i].value})`);
                        }
                    }
                } else {
                    console.error(`✗ Elemento ${id} NO encontrado`);
                }
            });
            
            // Test de filtro de categorías
            console.log('=== PROBANDO FILTRO DE CATEGORÍAS ===');
            const tipoSelect = document.getElementById('tipo');
            if (tipoSelect) {
                // Probar filtro con "ingreso"
                tipoSelect.value = 'ingreso';
                filtrarCategorias();
                
                setTimeout(() => {
                    // Probar filtro con "gasto"
                    tipoSelect.value = 'gasto';
                    filtrarCategorias();
                    
                    setTimeout(() => {
                        // Resetear
                        tipoSelect.value = '';
                        filtrarCategorias();
                    }, 1000);
                }, 1000);
            }
        }

        // Interceptar envío del formulario para debugging
        document.getElementById('transaccionForm').addEventListener('submit', function(e) {
            console.log('=== ENVIANDO FORMULARIO ===');
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            // No preventDefault para permitir el envío real
        });
    </script>
</body>
</html>
