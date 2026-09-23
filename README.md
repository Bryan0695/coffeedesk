# CoffeeDesk ☕

Sistema web de gestión de pedidos e inventario para una cafetería.
UEES · Desarrollo de Aplicaciones Web · Examen del Segundo Parcial.

| Integrante | Parte |
|---|---|
| Frederick Torres | Frontend, accesibilidad, revisión de backend |
| Bryan Gallegos | Entorno, repositorio, conexión, login y roles, despliegue, pruebas, informe |
| Gabo | Script SQL, menú y pedidos |
| Jeremy | Contratos, inventario, búsqueda y filtros, video |

---

## 1. Estructura del proyecto

```
coffeedesk/
├── index.php              ← login
├── panel.php              ← inicio después del login
├── pedidos.php            ← (Gabo)
├── menu.php               ← (Gabo)
├── inventario.php         ← (Jeremy) solo administrador
├── css/estilos.css        ← estilos base (Frederick amplía)
├── js/login.js            ← validación del login en el cliente
├── config/
│   ├── config.php                 ← detecta local / hosting, constantes
│   ├── credenciales.example.php   ← plantilla (sí va a Git)
│   └── credenciales.php           ← datos reales (NO va a Git)
├── php/
│   ├── conexion.php       ← conectar() → mysqli
│   ├── auth/
│   │   ├── sesion.php     ← requiere_login(), requiere_rol(), responder_json()…
│   │   ├── login.php      ← procesa el formulario (POST)
│   │   └── logout.php     ← cierra sesión (POST)
│   └── partials/
│       ├── cabecera.php   ← header + nav según rol
│       └── pie.php
├── sql/
│   ├── 00_crear_bd_local.sql   ← solo XAMPP
│   └── 01_usuarios_roles.sql   ← tablas roles y usuarios + usuarios de prueba
├── herramientas/generar_hash.php
├── docs/
│   ├── despliegue_infinityfree.md
│   └── plan_pruebas.md
└── .htaccess              ← bloquea config/, sql/, docs/… desde el navegador
```

Los archivos de Gabo y Jeremy (CRUD) van en `php/menu/`, `php/pedidos/`, `php/inventario/` y sus scripts SQL en `sql/02_…`, `sql/03_…`.

---

## 2. Instalación en XAMPP (cada integrante)

1. Instalar XAMPP y arrancar **Apache** y **MySQL** desde el panel.
2. Clonar el repo dentro de `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone <URL-del-repo> coffeedesk
   ```
3. Copiar `config/credenciales.example.php` como `config/credenciales.php` (el bloque `local` ya viene listo para XAMPP).
4. Abrir <http://localhost/phpmyadmin>:
   - Pestaña **SQL** → pegar y ejecutar `sql/00_crear_bd_local.sql`.
   - Seleccionar la base `coffeedesk` → **Importar** → `sql/01_usuarios_roles.sql` (y luego los scripts de Gabo en orden).
5. Entrar a <http://localhost/coffeedesk>.

**Usuarios de prueba**

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin123*` | administrador |
| `mesero` | `Mesero123*` | mesero |

Para crear otra contraseña cifrada:
```bash
C:\xampp\php\php.exe herramientas\generar_hash.php "NuevaClave1"
```

---

## 3. Flujo de trabajo con Git

- Rama `main`: solo lo que ya funciona y revisó Frederick.
- Cada uno trabaja en su rama: `feature/login`, `feature/menu-pedidos`, `feature/inventario`, `feature/frontend`.
  ```bash
  git checkout -b feature/menu-pedidos
  git add .
  git commit -m "Agrega CRUD de productos"
  git push -u origin feature/menu-pedidos
  ```
- Se abre un Pull Request hacia `main`; Frederick revisa y aprueba.
- Antes de empezar cada día: `git checkout main && git pull`, luego `git merge main` en tu rama.

---

## 4. Cómo proteger tus páginas (para Gabo, Jeremy y Frederick)

Al inicio de **toda** página o archivo PHP:

```php
require_once __DIR__ . '/php/auth/sesion.php';   // ajusta la ruta según la carpeta
require_once __DIR__ . '/php/conexion.php';

requiere_login();                     // cualquier usuario con sesión
requiere_rol(ROL_ADMIN);              // solo administrador
requiere_rol(ROL_ADMIN, ROL_MESERO);  // ambos
```

Archivos que responden JSON (según los contratos de Jeremy):

```php
requiere_rol_api(ROL_ADMIN);   // responde 401/403 en JSON si no cumple
// ...lógica...
responder_json('exito', 'Producto registrado.', ['id' => $nuevoId]);
responder_json('error', 'El precio debe ser mayor que 0.', null, 422);
```

Formularios POST: incluir `<?= csrf_campo() ?>` dentro del `<form>` y validar con `csrf_valido($_POST['csrf'] ?? null)`.

Mensajes después de redirigir: `mensaje_flash('exito', 'Guardado.')` y en la vista `<?= mostrar_flash() ?>` (la cabecera ya lo imprime).

Imprimir datos del usuario o de la BD en HTML: siempre con `e($texto)`.

Usuario conectado: `usuario_actual()['id']` (útil para guardar quién registró un pedido).

### Matriz de permisos

| Módulo / acción | Administrador | Mesero |
|---|:---:|:---:|
| Ver menú, buscar y filtrar | ✔ | ✔ |
| Crear, editar, eliminar productos y categorías | ✔ | ✖ |
| Registrar pedidos y ver sus totales | ✔ | ✔ |
| Anular o eliminar pedidos | ✔ | ✖ |
| Inventario (insumos, stock, alertas) | ✔ | ✖ |

---

## 5. Seguridad incluida en el login

- Contraseñas cifradas con `password_hash()` / `password_verify()` (bcrypt), con re-cifrado automático si PHP cambia de algoritmo.
- Consultas preparadas (`prepare` + `bind_param`) en todo acceso a la base.
- Validación en cliente (`js/login.js`) **y** en servidor (`php/auth/login.php`).
- `session_regenerate_id()` al iniciar y al cerrar sesión (evita fijación de sesión).
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` cuando hay HTTPS.
- Cierre automático tras 30 min de inactividad.
- Bloqueo de 5 min después de 5 intentos fallidos.
- Token CSRF en login y logout.
- Mensaje genérico "usuario o contraseña incorrectos" (no revela qué usuarios existen).
- Errores técnicos visibles solo en local; ocultos en el hosting.

Despliegue: ver [`docs/despliegue_infinityfree.md`](docs/despliegue_infinityfree.md).
