# Portal Cebeista - Guia de la Estructura del Proyecto

Este documento existe porque el proyecto creció rápido y necesitas saber cómo está organizado todo. Está escrito pensando en alguien que conoce Laravel pero perdió el hilo del proyecto.

---

## Resumen de qué hace el portal

El Portal Cebeista es un sistema de gestión escolar con dos tipos de usuarios:

1. **Staff interno** (admin, docentes): Inician sesión con usuario/contraseña y gestionan notas, alumnos, facturación, pagos.
2. **Padres de familia**: No crean cuenta. Se verifican con su cédula + el código del estudiante, y consultan el estado de cuenta.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 10, PHP 8.1 |
| Base de datos | MySQL en servidor remoto (98.142.110.10) |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Build | Vite |
| AJAX | Axios |
| Auth de API | Laravel Sanctum |

---

## Estructura de carpetas (lo que importa)

```
portalcbi/
├── app/
│   ├── Http/
│   │   ├── Controllers/      ← Toda la lógica de las páginas
│   │   └── Middleware/       ← Hay uno personalizado para padres
│   └── Models/
│       └── User.php          ← El único modelo (tabla PRINUSERS)
├── config/
│   └── database.php          ← Aquí está la conexión a la base de datos
├── database/
│   └── migrations/           ← 34 migraciones que definen las tablas
├── resources/
│   └── views/                ← Todas las páginas HTML (Blade)
├── routes/
│   └── web.php               ← Todas las rutas de la aplicación
└── .env                      ← Contraseñas y configuración sensible
```

---

## Los Controladores — el corazón del proyecto

Están en `app/Http/Controllers/`. Hay 13 en total:

### AdminController
Gestiona todo lo administrativo del sistema:
- Crear y eliminar usuarios del sistema
- Activar o desactivar docentes
- Mover materias de un docente a otro (en bloque o una por una)

### AlumnoController
CRUD completo de estudiantes:
- Listar con filtros (por grado, curso, sede, estado, email del padre)
- Crear, editar, ver detalles, vista de impresión
- Incluye datos personales, información de padres, historial académico

### NotasController
Ingreso y consulta de notas:
- El docente selecciona materia y curso, ve sus estudiantes, y registra notas
- Verifica que el período esté abierto antes de dejar ingresar notas
- Tiene un reporte para admin que muestra qué docentes ya ingresaron notas

### PagosController
Registro de pagos de estudiantes:
- Listar pagos con paginación
- Crear nuevo pago con fecha, valor, concepto y mes

### FacturacionController
Registro de facturas a estudiantes:
- Listar facturas con paginación
- Crear nueva factura con concepto, valor, mes, orden y centro de costos

### CarteraController
Dashboard financiero. Calcula y muestra:
- Total facturado vs total pagado
- Cartera vencida (la diferencia)
- Porcentaje de recaudo
- Top 10 estudiantes deudores
- Análisis de facturación por mes

### FechasController
Controla qué períodos académicos están abiertos o cerrados:
- El admin define fechas de inicio y fin para cada período
- Los demás controladores consultan esto antes de permitir acciones
- Tipos de períodos: P (notas), D (derroteros), S (salvavidas), B (boletines), F (finales)

### ImportacionController
Permite subir archivos CSV masivos:
- Importar registros de pagos desde CSV
- Importar facturas desde CSV
- Detecta automáticamente el separador del CSV y acepta varios formatos de fecha

### PadresController
Solo tiene un método: `estadoCuenta()`
- Muestra facturas, pagos y saldo del estudiante
- Solo accesible si el padre ya se verificó

### PadreVerificacionController
Maneja la verificación de padres:
- Recibe cédula + código del estudiante
- Lo busca en la tabla INFO_PADRES
- Si coincide, guarda en sesión que el padre está verificado

### ControlEstudianteController
Consulta rápida para administrativos:
- Ingresa el código de un estudiante
- Devuelve su información básica y estado financiero

### ProfileController (heredado de Breeze)
Gestión del perfil del usuario logueado:
- Cambiar nombre, email
- Cambiar contraseña
- Eliminar cuenta

---

## Las Rutas — `routes/web.php`

### Rutas públicas (sin login)

| Ruta | Qué hace |
|------|---------|
| `GET /` | Página de inicio — formulario de verificación de padres |
| `POST /verificar-padre` | Procesa la verificación del padre |
| `POST /padres/salir` | Cierra la sesión del padre |
| `GET /login` | Formulario de login del staff |
| `POST /login` | Procesa el login |
| `POST /logout` | Cierra sesión del staff |

### Rutas del staff (requieren `auth`)

**Alumnos:**
- `GET /alumnos` → Listado con filtros
- `GET /alumnos/crear` → Formulario nuevo alumno
- `POST /alumnos` → Guardar nuevo alumno
- `GET /alumnos/{codigo}` → Ver detalles
- `GET /alumnos/{codigo}/editar` → Formulario edición
- `PUT /alumnos/{codigo}` → Actualizar alumno
- `GET /alumnos/{codigo}/imprimir` → Vista para imprimir

**Notas:**
- `GET /notas` → Ingresar notas
- `POST /notas/guardar` → Guardar notas
- `GET /notas/reporte` → Reporte de ingreso

**Facturación y Pagos:**
- `GET /facturacion`, `/facturacion/crear`, `POST /facturacion`
- `GET /pagos`, `/pagos/crear`, `POST /pagos`
- `GET /cartera` → Dashboard financiero

**Importación:**
- `GET /importacion/registro-pagos`, `POST /importacion/registro-pagos`
- `GET /importacion/facturacion`, `POST /importacion/facturacion`

**Administración:**
- `GET /admin/fechas` → Ver períodos
- `POST /admin/fechas` → Crear/editar período
- `DELETE /admin/fechas/{codigo}` → Eliminar período
- `GET /admin/usuarios` → Ver usuarios y docentes
- `POST /admin/usuarios` → Crear usuario
- `DELETE /admin/usuarios/{user}` → Eliminar usuario
- `POST /admin/docentes/{codigo}/toggle` → Activar/desactivar docente
- `POST /admin/asignaciones/mover` → Mover todas las materias de un docente
- `POST /admin/asignaciones/mover-una` → Mover una materia específica

**Control:**
- `GET /control/estudiante` → Consulta estado financiero

### Rutas de padres (requieren `padre.verificado`)

- `GET /padres/portal` → Portal de padres
- `GET /padres/estado-cuenta` → Estado de cuenta del hijo

---

## Las Vistas — `resources/views/`

```
views/
├── layouts/
│   ├── app-sidebar.blade.php   ← Layout principal del staff (con menú lateral)
│   ├── guest.blade.php         ← Layout para login y páginas sin autenticar
│   └── padres.blade.php        ← Layout del portal de padres
├── components/                 ← Botones, inputs, modales reutilizables
├── admin/
│   ├── fechas.blade.php        ← Gestión de períodos
│   └── usuarios.blade.php      ← Gestión de usuarios y docentes
├── alumnos/
│   ├── index.blade.php         ← Listado con búsqueda
│   ├── create.blade.php        ← Formulario crear
│   ├── edit.blade.php          ← Formulario editar
│   ├── show.blade.php          ← Ver detalles completos
│   └── print.blade.php         ← Vista de impresión
├── auth/
│   └── login.blade.php         ← Formulario de login
├── cartera/
│   └── index.blade.php         ← Dashboard financiero con gráficos
├── control/
│   └── estudiante.blade.php    ← Consulta rápida de estudiante
├── facturacion/
│   ├── index.blade.php         ← Listado de facturas
│   └── create.blade.php        ← Nueva factura
├── importacion/
│   ├── facturacion.blade.php   ← Importar CSV de facturas
│   └── registro_pagos.blade.php ← Importar CSV de pagos
├── notas/
│   ├── index.blade.php         ← Ingresar notas
│   └── reporte.blade.php       ← Reporte de avance
├── padres/
│   ├── portal.blade.php        ← Menú del portal de padres
│   └── estado_cuenta.blade.php ← Estado de cuenta
├── pagos/
│   ├── index.blade.php         ← Listado de pagos
│   └── create.blade.php        ← Nuevo pago
├── profile/
│   └── edit.blade.php          ← Editar perfil de usuario
├── dashboard.blade.php         ← Pantalla de inicio del staff
└── welcome.blade.php           ← Página de inicio (verificación padres)
```

---

## La Base de Datos — `database/migrations/`

> **Nota importante:** El proyecto usa `DB::table()` (query builder) en casi todo el código, no modelos Eloquent. El único modelo real es `User.php`.

### Tablas principales:

| Tabla | Qué guarda |
|-------|-----------|
| `PRINUSERS` | Usuarios del sistema (USER, PASSWORD, PROFILE) |
| `ESTUDIANTES` | Datos personales de cada estudiante |
| `INFO_PADRES` | Información de madre, padre y acudiente |
| `INFO_ACADEM` | Historial académico (qué colegios cursó antes) |
| `CODIGOS_DOC` | Catálogo de docentes |
| `CODIGOSMAT` | Catálogo de materias |
| `ASIGNACION_PCM` | Qué materia imparte qué docente en qué curso |
| `FECHAS` | Períodos abiertos/cerrados (P1, P2, P3, P4, D, S, B, F) |
| `NOTAS_2024` | Notas del año 2024 |
| `NOTAS_2025` | Notas del año 2025 |
| `NOTAS_2026` | Notas del año 2026 |
| `LOGROS_2024/2025` | Logros académicos por año |
| `OBSERVACIONES_2024/2025` | Observaciones por año |
| `facturacion` | Facturas generadas a estudiantes |
| `registro_pagos` | Pagos recibidos de estudiantes |

### Relaciones lógicas (aunque no están en modelos Eloquent):

```
ESTUDIANTES
    ├── INFO_PADRES       (1 a 1, por CODIGO)
    ├── INFO_ACADEM       (1 a 1, por CODIGO)
    ├── NOTAS_XXXX        (1 a muchos, por CODIGO_ALUM)
    ├── LOGROS_XXXX       (1 a 1, por CODIGO_ALUM)
    ├── facturacion       (1 a muchos, por codigo_alumno)
    └── registro_pagos    (1 a muchos, por codigo_alumno)

CODIGOS_DOC
    └── ASIGNACION_PCM    (1 a muchos, por CODIGO_DOC)

ASIGNACION_PCM
    ├── CODIGOS_DOC       (referencia a docente)
    └── CODIGOSMAT        (referencia a materia)
```

---

## El Middleware personalizado — `VerificadoPadre`

Este middleware protege las rutas de padres. Lo que hace es simple:

```php
// Si session('padre_verificado') no es true → redirige a / con error
// Si es true → deja pasar
```

Está registrado en el Kernel como `padre.verificado` y se usa en las rutas `/padres/*`.

---

## El único Modelo — `User.php`

Aunque hay muchas tablas, solo hay un modelo Eloquent real:

```php
// app/Models/User.php
protected $table = 'PRINUSERS';
protected $primaryKey = 'USER';
public $incrementing = false;   // La clave no es autoincrement
public $timestamps = false;     // No tiene created_at / updated_at
```

Los perfiles (PROFILE) determinan qué puede ver cada usuario:
- `SuperAd` → Acceso total
- `Admin` → Acceso administrativo
- Código de docente → Solo ve sus materias y puede ingresar notas

---

## Flujos principales

### Flujo de docente ingresando notas

1. Docente va a `/login`, entra con su usuario (que es su código de docente)
2. Va a `/notas`
3. Selecciona la materia (las que tiene asignadas en `ASIGNACION_PCM`)
4. Selecciona el curso
5. El sistema verifica que el período esté abierto (`FECHAS`)
6. Si está abierto, muestra la lista de estudiantes con campos para la nota
7. Guarda en `NOTAS_XXXX` (la tabla del año actual)

### Flujo de padre consultando estado de cuenta

1. Padre va a `/` (página de inicio)
2. Ingresa su cédula y el código del estudiante
3. El sistema busca en `INFO_PADRES` si esa cédula pertenece al acudiente de ese estudiante
4. Si coincide, guarda `padre_verificado = true` en la sesión
5. El padre es redirigido a `/padres/portal`
6. Puede ir a `/padres/estado-cuenta` para ver facturas, pagos y saldo

### Flujo financiero

1. Admin crea facturas en `/facturacion/crear` (o importa CSV)
2. Admin registra pagos en `/pagos/crear` (o importa CSV)
3. El dashboard en `/cartera` calcula automáticamente:
   - `Cartera = SUM(facturacion.valor) - SUM(registro_pagos.valor)`
   - `% Recaudo = (pagado / facturado) * 100`
   - Lista los 10 estudiantes con mayor saldo pendiente

---

## Cosas que pueden confundir

**¿Por qué hay tablas de notas por año?**
Las tablas `NOTAS_2024`, `NOTAS_2025`, `NOTAS_2026` son separadas por año académico. El código construye el nombre de la tabla dinámicamente usando `date('Y')`.

**¿Por qué no hay modelos para Alumnos, Notas, etc.?**
El proyecto usa `DB::table('NOMBRE_TABLA')->...` directamente en los controladores. Es una decisión de diseño que hace el código más directo pero sin las ventajas de Eloquent (relaciones, scopes, etc.).

**¿Por qué algunas tablas están en MAYÚSCULAS y otras en minúsculas?**
Las tablas en MAYÚSCULAS (`ESTUDIANTES`, `NOTAS_2025`, etc.) vienen de la base de datos original del colegio. Las en minúsculas (`facturacion`, `registro_pagos`) son las nuevas que se crearon con el portal.

**¿Qué es `ASIGNACION_PCM`?**
PCM es "Plan de Clases de la Materia". Esta tabla dice: "El docente X imparte la materia Y en el curso Z, con N horas semanales (IHS)". Es la tabla que determina qué ve cada docente cuando entra al sistema.

**¿Qué significa el campo PROFILE en PRINUSERS?**
- Si dice `SuperAd` → superadmin
- Si dice `Admin` → administrador normal
- Si dice otro valor → es el código del docente y solo puede ingresar notas

---

## Configuración de la base de datos

Está en `.env`:
```
DB_CONNECTION=mysql
DB_HOST=98.142.110.10     ← Servidor remoto (no local)
DB_PORT=3306
DB_DATABASE=cbicole_portal
DB_USERNAME=cbicole_danicaradmin
```

La base de datos **no es local** — está en un servidor externo. Si no tienes conexión a internet, la aplicación no funciona.

---

## Comandos útiles para trabajar en el proyecto

```bash
# Iniciar servidor local de desarrollo (en Laragon ya está corriendo)
php artisan serve

# Ver todas las rutas registradas
php artisan route:list

# Limpiar caché si algo raro pasa
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Compilar assets (CSS/JS) en modo desarrollo
npm run dev

# Compilar assets para producción
npm run build

# Ver la base de datos interactivamente
php artisan tinker
```

---

*Documento generado el 30 de marzo de 2026.*
