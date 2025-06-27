<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

require_once 'config/database.php';

// Debug inicial
$debug_info = [];

try {
    // Obtener categorías y cuentas activas
    $categorias = $db->fetchAll(
        "SELECT * FROM categorias WHERE activa = 1 ORDER BY tipo, nombre"
    );
    $debug_info[] = "Categorías obtenidas: " . count($categorias);
    
    $cuentas = $db->fetchAll(
        "SELECT * FROM cuentas WHERE activa = 1 ORDER BY nombre"
    );
    $debug_info[] = "Cuentas obtenidas: " . count($cuentas);
    
} catch (Exception $e) {
    $debug_info[] = "Error en consulta: " . $e->getMessage();
    $categorias = [];
    $cuentas = [];
}

// Mostrar información de debug
echo "<div class='alert alert-info mb-3'>";
echo "<strong>Debug Info:</strong><br>";
foreach ($debug_info as $info) {
    echo "• " . $info . "<br>";
}
echo "</div>";

// Advertencias si no hay datos
if (empty($categorias)) {
    echo "<div class='alert alert-warning'>⚠ No hay categorías disponibles. <a href='categorias.php' target='_blank' class='btn btn-sm btn-primary'>Crear categorías</a></div>";
}

if (empty($cuentas)) {
    echo "<div class='alert alert-warning'>⚠ No hay cuentas disponibles. <a href='cuentas.php' target='_blank' class='btn btn-sm btn-primary'>Crear cuentas</a></div>";
}
?>

<div class="row">
    <div class="col-md-6">
        <label for="tipo" class="form-label">Tipo de Transacción</label>
        <select class="form-select" id="tipo" name="tipo" required onchange="filtrarCategorias()">
            <option value="">Seleccionar tipo</option>
            <option value="ingreso">Ingreso</option>
            <option value="gasto">Gasto</option>
        </select>
    </div>
    <div class="col-md-6">
        <label for="cantidad" class="form-label">Cantidad</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" class="form-control" id="cantidad" name="cantidad" 
                   step="0.01" min="0.01" required placeholder="0.00">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <label for="categoria_id" class="form-label">
            Categoría 
            <small class="text-muted">(<?php echo count($categorias); ?> disponibles)</small>
        </label>
        <select class="form-select" id="categoria_id" name="categoria_id" required>
            <option value="">Seleccionar categoría</option>
            <?php if (!empty($categorias)): ?>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" 
                            data-tipo="<?php echo $categoria['tipo']; ?>"
                            style="color: <?php echo $categoria['tipo'] === 'ingreso' ? 'green' : 'red'; ?>;">
                        <?php echo htmlspecialchars($categoria['nombre']); ?> (<?php echo ucfirst($categoria['tipo']); ?>)
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No hay categorías disponibles</option>
            <?php endif; ?>
        </select>
        <?php if (empty($categorias)): ?>
            <div class="form-text text-danger">
                Necesitas crear al menos una categoría antes de registrar transacciones.
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <label for="cuenta_id" class="form-label">
            Cuenta 
            <small class="text-muted">(<?php echo count($cuentas); ?> disponibles)</small>
        </label>
        <select class="form-select" id="cuenta_id" name="cuenta_id" required>
            <option value="">Seleccionar cuenta</option>
            <?php if (!empty($cuentas)): ?>
                <?php foreach ($cuentas as $cuenta): ?>
                    <option value="<?php echo $cuenta['id']; ?>">
                        <?php echo htmlspecialchars($cuenta['nombre']); ?> 
                        (Saldo: $<?php echo number_format($cuenta['saldo_actual'], 2); ?>)
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No hay cuentas disponibles</option>
            <?php endif; ?>
        </select>
        <?php if (empty($cuentas)): ?>
            <div class="form-text text-danger">
                Necesitas crear al menos una cuenta antes de registrar transacciones.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" class="form-control" id="fecha" name="fecha" 
               value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="col-md-6">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="descripcion" name="descripcion" 
               placeholder="Descripción de la transacción" required>
    </div>
</div>

<script>
function filtrarCategorias() {
    const tipo = document.getElementById('tipo').value;
    const categoriaSelect = document.getElementById('categoria_id');
    const opciones = categoriaSelect.querySelectorAll('option');
    
    console.log('=== Filtrando Categorías ===');
    console.log('Tipo seleccionado:', tipo);
    console.log('Total opciones encontradas:', opciones.length);
    
    // Resetear selección
    categoriaSelect.value = '';
    
    let opcionesVisibles = 0;
    let opcionesTotales = 0;
    
    opciones.forEach((opcion, index) => {
        console.log(`Opción ${index}:`, {
            valor: opcion.value,
            texto: opcion.textContent,
            tipoData: opcion.getAttribute('data-tipo')
        });
        
        if (opcion.value === '') {
            // Opción por defecto, siempre visible
            opcion.style.display = 'block';
        } else {
            opcionesTotales++;
            const tipoCategoria = opcion.getAttribute('data-tipo');
            const mostrar = (tipoCategoria === tipo);
            opcion.style.display = mostrar ? 'block' : 'none';
            if (mostrar) {
                opcionesVisibles++;
                console.log('Mostrando:', opcion.textContent);
            } else {
                console.log('Ocultando:', opcion.textContent);
            }
        }
    });
    
    console.log(`Resultado: ${opcionesVisibles} de ${opcionesTotales} opciones visibles`);
    
    // Mostrar mensaje si no hay opciones
    if (opcionesTotales === 0) {
        console.warn('No hay categorías disponibles en el select');
    } else if (tipo && opcionesVisibles === 0) {
        console.warn(`No hay categorías del tipo "${tipo}" disponibles`);
    }
}

// Debug inicial y validaciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Formulario de Transacción Cargado ===');
    console.log('Categorías disponibles:', <?php echo count($categorias); ?>);
    console.log('Cuentas disponibles:', <?php echo count($cuentas); ?>);
    
    // Verificar elementos del formulario
    const elementos = {
        'tipo': document.getElementById('tipo'),
        'categoria_id': document.getElementById('categoria_id'),
        'cuenta_id': document.getElementById('cuenta_id'),
        'cantidad': document.getElementById('cantidad'),
        'fecha': document.getElementById('fecha'),
        'descripcion': document.getElementById('descripcion')
    };
    
    Object.entries(elementos).forEach(([nombre, elemento]) => {
        if (elemento) {
            console.log(`✓ Elemento "${nombre}" encontrado`);
        } else {
            console.error(`✗ Elemento "${nombre}" NO encontrado`);
        }
    });
    
    // Verificar opciones de categorías
    const categoriaSelect = document.getElementById('categoria_id');
    if (categoriaSelect) {
        const opciones = categoriaSelect.querySelectorAll('option');
        console.log(`Select de categorías tiene ${opciones.length} opciones:`);
        opciones.forEach((opcion, index) => {
            console.log(`  ${index}: "${opcion.textContent}" (valor: ${opcion.value})`);
        });
    }
    
    // Verificar opciones de cuentas
    const cuentaSelect = document.getElementById('cuenta_id');
    if (cuentaSelect) {
        const opciones = cuentaSelect.querySelectorAll('option');
        console.log(`Select de cuentas tiene ${opciones.length} opciones:`);
        opciones.forEach((opcion, index) => {
            console.log(`  ${index}: "${opcion.textContent}" (valor: ${opcion.value})`);
        });
    }
});
</script>
