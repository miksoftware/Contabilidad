# 💰 Sistema de Contabilidad Familiar

Un sistema completo de gestión financiera personal y familiar desarrollado en PHP con MySQL, diseñado para llevar un control detallado de ingresos, gastos, cuentas y presupuestos.

## 🚀 Características Principales

### 📊 Dashboard Interactivo
- Resumen financiero del mes actual
- Gráficos de gastos por categoría
- Balance total y por cuentas
- Últimas transacciones
- Metas de ahorro

### 💳 Gestión de Transacciones
- Registro de ingresos y gastos
- Categorización automática
- Filtros avanzados por fecha, tipo, categoría
- Exportación a CSV y Excel
- Transferencias entre usuarios

### 🏦 Gestión de Cuentas
- Múltiples cuentas bancarias
- Saldos en tiempo real
- Activación/desactivación de cuentas
- Asociación con categorías

### 📈 Categorías Personalizables
- Categorías de ingresos y gastos
- Colores e iconos personalizados
- Gestión de estados activo/inactivo
- Organización por tipo

### 👥 Sistema de Usuarios
- Múltiples usuarios por familia
- Roles de administrador y usuario
- Gestión de permisos
- Perfiles personalizados

### 🎯 Metas de Ahorro
- Establecimiento de objetivos financieros
- Seguimiento de progreso
- Gestión de contribuciones
- Estados de completado

### 💰 Presupuestos
- Planificación mensual de gastos
- Seguimiento de cumplimiento
- Alertas de sobregasto
- Categorización detallada

### 📊 Reportes Avanzados
- Reportes por período personalizado
- Análisis por categorías
- Gráficos interactivos
- Exportación múltiple (PDF, Excel, CSV)

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3, jQuery 3.6
- **Gráficos**: Chart.js
- **Iconos**: Font Awesome 6.0
- **Arquitectura**: MVC Pattern
- **Seguridad**: PDO con prepared statements, validación de sesiones

## 📦 Requisitos del Sistema

### Servidor Web
- Apache 2.4+ o Nginx
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Módulos PHP requeridos:
  - PDO MySQL
  - Session
  - JSON
  - Filter

### Navegador
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 🔧 Instalación

### 1. Clonar el Repositorio
```bash
git clone https://github.com/miksoftware/Contabilidad.git
cd Contabilidad
```

### 2. Configuración de Base de Datos
1. Crear una base de datos MySQL:
```sql
CREATE DATABASE contabilidad_familiar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importar el esquema:
```bash
mysql -u usuario -p contabilidad_familiar < database/schema.sql
```

### 3. Configuración
1. Copiar y editar el archivo de configuración:
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'contabilidad_familiar');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_CHARSET', 'utf8mb4');
```

### 4. Instalación Automática
Visita `tu-dominio.com/install.php` para completar la instalación automática que:
- Verifica requisitos del sistema
- Configura la base de datos
- Crea el usuario administrador inicial
- Instala datos de ejemplo

## 🗄️ Estructura de la Base de Datos

### Tablas Principales

#### `usuarios`
- Gestión de usuarios del sistema
- Roles y permisos
- Información de perfil

#### `cuentas`
- Cuentas bancarias y financieras
- Saldos actuales
- Asociación con usuarios

#### `categorias`
- Categorías de ingresos y gastos
- Colores e iconos personalizados
- Estados activo/inactivo

#### `transacciones`
- Registro de todas las operaciones
- Relación con cuentas y categorías
- Historial completo

#### `metas_ahorro`
- Objetivos financieros
- Progreso y contribuciones
- Estados de completado

#### `presupuestos`
- Planificación mensual
- Límites por categoría
- Seguimiento de gastos

## 📁 Estructura del Proyecto

```
Contabilidad/
├── config/
│   └── database.php          # Configuración de BD
├── database/
│   └── schema.sql           # Esquema de base de datos
├── includes/
│   ├── header.php           # Header común
│   └── footer.php           # Footer común
├── assets/                  # Recursos estáticos
├── categorias.php           # Gestión de categorías
├── cuentas.php             # Gestión de cuentas
├── dashboard.php           # Panel principal
├── transacciones.php       # Gestión de transacciones
├── metas.php              # Metas de ahorro
├── presupuestos.php       # Gestión de presupuestos
├── reportes.php           # Reportes y análisis
├── usuarios.php           # Gestión de usuarios (admin)
├── perfil.php             # Perfil de usuario
├── transferencias.php     # Transferencias entre usuarios
├── login.php              # Autenticación
├── logout.php             # Cierre de sesión
├── install.php            # Instalador automático
└── README.md              # Documentación
```

## 🔐 Seguridad

### Medidas Implementadas
- **Autenticación**: Sistema de sesiones seguro
- **Autorización**: Control de acceso por roles
- **SQL Injection**: Uso exclusivo de prepared statements
- **XSS**: Escapado de datos de salida
- **CSRF**: Validación de tokens en formularios
- **Contraseñas**: Hash seguro con password_hash()

### Roles de Usuario
- **Administrador**: Acceso completo al sistema
- **Usuario**: Acceso a sus propios datos únicamente

## 🎯 Uso del Sistema

### 1. Primer Acceso
1. Ejecutar instalación en `/install.php`
2. Crear usuario administrador
3. Configurar categorías básicas
4. Crear cuentas iniciales

### 2. Configuración Inicial
1. **Categorías**: Crear categorías de ingresos y gastos
2. **Cuentas**: Agregar cuentas bancarias con saldos iniciales
3. **Usuarios**: Invitar usuarios familiares (admin)
4. **Metas**: Establecer objetivos de ahorro

### 3. Uso Diario
1. **Registrar Transacciones**: Anotar ingresos y gastos diarios
2. **Revisar Dashboard**: Consultar resumen financiero
3. **Actualizar Metas**: Contribuir a objetivos de ahorro
4. **Generar Reportes**: Analizar tendencias mensuales

## 📊 Funcionalidades Avanzadas

### Exportación de Datos
- **CSV**: Formato universal para análisis
- **Excel**: Hojas de cálculo avanzadas
- **PDF**: Reportes formales

### Filtros y Búsquedas
- Por rango de fechas
- Por categorías específicas
- Por tipos de transacción
- Por cuentas
- Por usuarios

### Gráficos Interactivos
- Distribución de gastos por categoría
- Tendencias mensuales
- Comparativas anuales
- Progreso de metas

## 🔄 API y Endpoints

### Principales Endpoints AJAX
- `procesar_transaccion.php` - Procesar nuevas transacciones
- `formulario_transaccion.php` - Cargar formulario dinámico
- `obtener_cuentas_usuario.php` - Obtener cuentas del usuario
- `exportar_transacciones.php` - Exportar datos
- `exportar_reporte.php` - Generar reportes

## 🐛 Resolución de Problemas

### Problemas Comunes

#### Error de Conexión a Base de Datos
```
Solución: Verificar credenciales en config/database.php
```

#### Sesión No Iniciada
```
Solución: Verificar permisos de la carpeta de sesiones PHP
```

#### Gráficos No Cargan
```
Solución: Verificar conectividad a CDN de Chart.js
```

#### Error 500
```
Solución: Revisar logs de PHP y permisos de archivos
```

## 🚀 Próximas Características

- [ ] App móvil nativa
- [ ] Integración con bancos (API)
- [ ] Notificaciones push
- [ ] Análisis predictivo
- [ ] Modo multi-idioma
- [ ] Tema oscuro
- [ ] Backup automático
- [ ] Integración con sistemas contables

## 🤝 Contribución

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-caracteristica`)
3. Commit cambios (`git commit -am 'Agregar nueva característica'`)
4. Push a la rama (`git push origin feature/nueva-caracteristica`)
5. Crear Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## 👨‍💻 Autor

**miksoftware**
- GitHub: [@miksoftware](https://github.com/miksoftware)
- Email: contacto@miksoftware.com

## 🙏 Agradecimientos

- Bootstrap por el framework CSS
- Chart.js por los gráficos interactivos
- Font Awesome por los iconos
- La comunidad PHP por las mejores prácticas

---

⭐ Si este proyecto te ha sido útil, ¡no olvides darle una estrella!

## 📞 Soporte

¿Necesitas ayuda? 
- 📧 Email: soporte@miksoftware.com
- 💬 Issues: [GitHub Issues](https://github.com/miksoftware/Contabilidad/issues)
- 📚 Wiki: [Documentación completa](https://github.com/miksoftware/Contabilidad/wiki)