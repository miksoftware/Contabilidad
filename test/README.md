# 🔧 Herramientas de Diagnóstico y Pruebas
## Sistema de Contabilidad Familiar

Esta carpeta contiene todas las herramientas de diagnóstico, verificación y reparación del sistema de contabilidad familiar. Todo está centralizado aquí para mantener el código principal limpio y organizado.

## 📁 Estructura de Archivos

### 🎛️ **Panel Principal**
- **`test_engine.php`** - Motor principal que ejecuta todas las pruebas y reparaciones
- **`../diagnostico.php`** - Panel web visual para ejecutar diagnósticos (archivo principal en la raíz)

### 🔍 **Herramientas de Diagnóstico**
- **`diagnostico_completo.php`** - Análisis integral de todo el sistema
- **`diagnostico_login.php`** - Verificación específica del sistema de autenticación
- **`estado_bd.php`** - Estado y conexión de la base de datos
- **`verificacion_final.php`** - Verificación final después de instalación/migración
- **`revisar_estructura.php`** - Revisión de estructura de tablas y esquema

### 🧪 **Pruebas Específicas**
- **`test_categorias.php`** - Pruebas del sistema de categorías
- **`test_formulario.php`** - Pruebas del formulario de transacciones
- **`verificar_usuarios.php`** - Verificación del sistema de usuarios

### 🔧 **Herramientas de Reparación**
- **`corregir_tabla_usuarios.php`** - Corrige estructura de la tabla usuarios
- **`crear_datos_ejemplo.php`** - Crea categorías y cuentas básicas para pruebas
- **`migracion.php`** - Actualiza estructura de base de datos

## 🚀 Cómo Usar

### Opción 1: Panel Web (Recomendado)
1. Ve a: `http://localhost/Contabilidad/diagnostico.php`
2. Usa la interfaz visual para ejecutar diagnósticos
3. Los resultados se muestran dinámicamente

### Opción 2: Acceso Directo
Puedes ejecutar cualquier archivo directamente:
```
http://localhost/Contabilidad/test/diagnostico_completo.php
http://localhost/Contabilidad/test/test_categorias.php
```

## 📋 Tipos de Verificaciones

### 🔍 **Diagnósticos Rápidos**
| Herramienta | Descripción | Archivo |
|-------------|-------------|---------|
| Estado BD | Verifica conexión, tablas y estructura | `estado_bd.php` |
| Sistema Usuarios | Verifica usuarios, roles y autenticación | `verificar_usuarios.php` |
| Test Login | Prueba el sistema de autenticación | `diagnostico_login.php` |
| Categorías/Cuentas | Verifica datos para transacciones | `test_categorias.php` |
| Formulario | Prueba formulario principal | `test_formulario.php` |
| Completo | Análisis integral del sistema | `diagnostico_completo.php` |

### 🔧 **Herramientas de Reparación**
| Herramienta | Descripción | Archivo |
|-------------|-------------|---------|
| Corregir Usuarios | Repara estructura de tabla usuarios | `corregir_tabla_usuarios.php` |
| Crear Datos | Crea categorías y cuentas básicas | `crear_datos_ejemplo.php` |
| Migración BD | Actualiza estructura de base de datos | `migracion.php` |

## ⚡ Funciones del Motor de Pruebas

El archivo `test_engine.php` centraliza todas las funciones:

### API Endpoints:
- `?action=status` - Estado general del sistema
- `?action=test&type=database` - Test de base de datos
- `?action=test&type=users` - Test de usuarios
- `?action=test&type=login` - Test de login
- `?action=test&type=data` - Test de datos (categorías/cuentas)
- `?action=test&type=transactions` - Test de transacciones
- `?action=test&type=complete` - Diagnóstico completo
- `?action=repair&type=users` - Reparar usuarios
- `?action=repair&type=data` - Crear datos de ejemplo
- `?action=repair&type=migration` - Ejecutar migración

## 🎯 Casos de Uso Comunes

### 🆕 **Primera Instalación**
1. Ejecutar **Diagnóstico Completo**
2. Si faltan datos: **Crear Datos de Ejemplo**
3. Si hay errores de usuarios: **Corregir Usuarios**
4. Verificar con **Test de Login**

### 🔄 **Después de Actualización**
1. Ejecutar **Migración BD** 
2. Verificar con **Diagnóstico Completo**
3. Probar **Formulario de Transacciones**

### 🐛 **Solución de Problemas**
1. **Estado BD** - Para problemas de conexión
2. **Test Login** - Para problemas de autenticación
3. **Test Categorías** - Para problemas en formularios
4. **Verificar Usuarios** - Para problemas de sesión

## 🛡️ Seguridad y Producción

⚠️ **IMPORTANTE**: Estos archivos de diagnóstico contienen información sensible del sistema.

### Recomendaciones de Seguridad:
1. **Eliminar o proteger** esta carpeta en producción
2. **Crear archivo .htaccess** para restringir acceso:
   ```apache
   <Files "*">
       Require ip 127.0.0.1
       Require ip ::1
   </Files>
   ```
3. **Usar autenticación** adicional si es necesario

## 📊 Códigos de Estado

### ✅ **Éxito**
- Verde: Todo funciona correctamente
- Sistema operativo

### ⚠️ **Advertencia**
- Amarillo: Funciona pero con observaciones
- Datos faltantes no críticos
- Configuraciones recomendadas

### ❌ **Error**
- Rojo: Requiere atención inmediata
- Errores que impiden el funcionamiento
- Datos faltantes críticos

## 🔗 Enlaces Útiles

- **Panel Principal**: `../diagnostico.php`
- **Dashboard**: `../dashboard.php`
- **Login**: `../login.php`
- **Gestión Categorías**: `../categorias.php`
- **Gestión Cuentas**: `../cuentas.php`

## 📝 Notas de Desarrollo

### Estructura del Sistema:
```
Contabilidad/
├── diagnostico.php          # Panel principal de diagnóstico
├── test/                    # Esta carpeta
│   ├── test_engine.php     # Motor de pruebas
│   ├── diagnostico_*.php   # Diagnósticos específicos
│   ├── test_*.php          # Pruebas específicas
│   ├── corregir_*.php      # Herramientas de reparación
│   └── README.md           # Esta documentación
├── config/                  # Configuración
├── includes/               # Archivos incluidos
└── [resto del sistema]     # Archivos principales
```

### Mantenimiento:
- Los archivos de test son independientes del sistema principal
- Se pueden ejecutar en cualquier momento sin afectar datos
- Las reparaciones tienen confirmaciones antes de ejecutar cambios

---
**Sistema de Contabilidad Familiar** - Herramientas de Diagnóstico v2.0
