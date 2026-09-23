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

**Stack:** HTML5 · CSS3 · JavaScript · PHP 8.0+ (mysqli) · **MySQL 8**

> **Sobre la base de datos:** el proyecto usa **MySQL**. Los scripts SQL y el login se probaron en **MySQL 8.0** (CI) y en la **MariaDB de XAMPP**. Para no romper esa compatibilidad, no uses sintaxis exclusiva de MariaDB ni de MySQL 8 (por ejemplo `INSERT … AS alias`, `VALUES()` en `ON DUPLICATE KEY` o anchos de entero como `TINYINT(1)`).
>
> **Sobre PHP:** el código debe funcionar en **PHP 8.0** (el de XAMPP). No uses enums, `readonly`, el tipo `never`, `mysqli::execute_query` ni otras novedades de 8.1+. El CI lo comprueba.

## 1. Estructura del proyecto

```
coffeedesk/
├── index.php              ← login
├── panel.php              ← inicio después del login
├── pedidos.php            ← (Gabo)
├── menu.php               ← (Gabo)
├── inventario.php         ← (Jeremy) solo administrador
├── css/estilos.css        ← estilos (Frederick)
├── js/login.js            ← validación del login en el cliente
├── config/
│   ├── config.php                         ← carga credenciales, entorno y errores
│   ├── constantes.php                     ← roles, límites, PATRON_USUARIO, CSP…
│   ├── credenciales.example.php           ← plantilla local (sí va a Git)
│   ├── credenciales.hosting.example.php   ← plantilla del hosting (sí va a Git)
│   └── credenciales.php                   ← datos reales (NO va a Git)
├── php/
│   ├── conexion.php       ← conectar(), consultar(), ejecutar(), insertar(), transaccion()
│   ├── auth/
│   │   ├── sesion.php     ← ÚNICO archivo que incluyen las páginas (reúne todo lo demás)
│   │   ├── login.php      ← procesa el formulario (POST)
│   │   ├── logout.php     ← cierra sesión (POST)
│   │   ├── estado.php     ← usuario actual en JSON (GET)
│   │   ├── autorizacion.php, csrf.php, arranque_sesion.php, limite_intentos.php
│   ├── comun/             ← html.php (e), respuesta.php, flash.php, validacion.php,
│   │                        cabeceras.php, errores.php
│   └── partials/          ← head.php, cabecera.php, pie.php, pie_pagina.php
├── sql/
│   ├── 00_crear_bd_local.sql      ← solo XAMPP
│   ├── 01_usuarios_roles.sql      ← versión 1: esquema_version, roles, usuarios
│   ├── 02_intentos_login.sql      ← versión 2: límite de intentos de login
│   └── 90_seed_solo_local.sql     ← usuarios de prueba (¡NUNCA en el hosting!)
├── herramientas/
│   ├── generar_hash.php   ← hash de una contraseña
│   └── crear_admin.php    ← genera el INSERT del admin del hosting
├── tests/
│   ├── verificar_endpoints.php    ← todo endpoint llama a requiere_*()
│   └── integracion.sh             ← pruebas con curl
├── logs/                  ← php_error.log (no va a Git; bloqueado por .htaccess)
├── docs/                  ← despliegue, plan de pruebas, auditorías
├── .github/workflows/ci.yml
└── .htaccess              ← bloquea config/, sql/, logs/, docs/, .git, *.md… desde el navegador
```

Los archivos de Gabo y Jeremy (CRUD) van en `php/menu/`, `php/pedidos/` y `php/inventario/`, y sus scripts SQL empiezan en **`sql/03_…`** (ver §5).

---

## 2. Instalación en XAMPP (cada integrante)

1. Instalar XAMPP y arrancar **Apache** y **MySQL** desde el panel.
2. Clonar el repo dentro de `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone <URL-del-repo> coffeedesk
   ```
3. Copiar `config/credenciales.example.php` como `config/credenciales.php` (ya viene listo para XAMPP, con `'entorno' => 'local'`).
4. Abrir <http://localhost/phpmyadmin>:
   - Pestaña **SQL** → pegar y ejecutar `sql/00_crear_bd_local.sql`.
   - Seleccionar la base `coffeedesk` → **Importar**, en este orden: `01_usuarios_roles.sql`, `02_intentos_login.sql`, los `03_…` en adelante de Gabo y Jeremy y, **al final**, `90_seed_solo_local.sql`.
5. Entrar a <http://localhost/coffeedesk>.

**¿Ya tenías la base de antes de la auditoría?** Solo tenía datos de prueba: bórrala (`DROP DATABASE coffeedesk;`) y repite el paso 4. El 01 nuevo falla a propósito si las tablas ya existen.

**¿Tu `credenciales.php` es del formato viejo** (bloques `local` y `hosting`)? Sigue funcionando, pero migra al formato de `credenciales.example.php`: con el viejo, abrir la app desde el móvil por la IP de la LAN carga las credenciales del hosting.

**Usuarios de prueba (solo locales)**

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin123*` | administrador |
| `mesero` | `Mesero123*` | mesero |

Estas contraseñas son **públicas** (están en este README). Existen solo si importas `90_seed_solo_local.sql`, que **nunca** se importa en el hosting. El administrador del hosting se crea con `herramientas/crear_admin.php` (ver [despliegue](docs/despliegue_infinityfree.md)).

Para crear otra contraseña cifrada:
```bash
C:\xampp\php\php.exe herramientas\generar_hash.php "NuevaClave1"
```

---

## 3. Flujo de trabajo con Git

- Rama `main`: solo lo que ya funciona y revisó Frederick. El CI (GitHub Actions) debe estar en verde.
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

## 4. Cómo programar tus páginas (para Gabo, Jeremy y Frederick)

### 4.1 Proteger la página

Al inicio de **toda** página o archivo PHP accesible desde el navegador:

```php
require_once __DIR__ . '/php/auth/sesion.php';   // ajusta la ruta según la carpeta
require_once __DIR__ . '/php/conexion.php';      // solo si usas la base de datos

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

`tests/verificar_endpoints.php` (y el CI) fallan si un archivo no llama a ningún `requiere_*()`. Si un archivo nuevo es una librería que solo se incluye, agrégalo a la lista de excepciones de ese script, con el motivo.

Protege **cada endpoint de escritura** con `requiere_rol_api(ROL_ADMIN)`; ocultar el botón no basta.

### 4.2 Leer datos del formulario

No uses `$_POST['x']` directamente: un atacante puede enviar `x[]=…` y provocar un error 500.

```php
$nombre   = trim(post_texto('nombre'));   // '' si falta o no es texto
$cantidad = post_entero('cantidad');      // int, o null si falta o no es un entero
$busqueda = trim(get_texto('q'));         // equivalentes para la URL
$pagina   = get_entero('pagina') ?? 1;

if ($cantidad === null || $cantidad <= 0) {
    responder_json('error', 'La cantidad debe ser un entero mayor que 0.', null, 422);
}
```

### 4.3 Consultar la base de datos

Todas las consultas son preparadas. **Nunca** concatenes variables en el SQL.

```php
$productos = consultar(
    'SELECT id, nombre, precio FROM productos WHERE categoria_id = ? ORDER BY nombre LIMIT ?',
    [$idCategoria, 50]
);                                                                  // lista de filas
$producto  = consultar_uno('SELECT * FROM productos WHERE id = ?', [$id]);   // fila o null
$afectadas = ejecutar('UPDATE productos SET agotado = 1 WHERE id = ?', [$id]);
$idNuevo   = insertar('INSERT INTO categorias (nombre) VALUES (?)', [$nombre]);
```

El tipo de cada parámetro sale del valor PHP (`int`/`bool` → entero, `float` → decimal, el resto → texto). Pasa los números como `int` (por ejemplo con `post_entero()`): `LIMIT ?` con un texto falla.

**Transacciones** (por ejemplo, un pedido que descuenta stock): si algo lanza una excepción dentro, se deshace todo.

```php
$idPedido = transaccion(function () use ($mesa, $lineas) {
    $id = insertar('INSERT INTO pedidos (mesa, registrado_por) VALUES (?, ?)',
                   [$mesa, usuario_actual()['id']]);
    foreach ($lineas as [$idProducto, $cantidad]) {
        $ok = ejecutar('UPDATE insumos SET stock = stock - ? WHERE id = ? AND stock >= ?',
                       [$cantidad, $idProducto, $cantidad]);
        if ($ok !== 1) {
            throw new RuntimeException('Stock insuficiente');   // rollback automático
        }
        insertar('INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad) VALUES (?, ?, ?)',
                 [$id, $idProducto, $cantidad]);
    }
    return $id;
});
```

Para dinero usa `DECIMAL(10,2)` en MySQL y opera en centavos enteros en PHP; no sumes `float`.

### 4.4 Formularios, mensajes y vistas

- Formularios POST: incluir `<?= csrf_campo() ?>` dentro del `<form>` y validar con `csrf_valido(post_texto('csrf'))`.
- Mensajes después de redirigir: `mensaje_flash('exito', 'Guardado.')`; la cabecera ya los imprime con `mostrar_flash()`.
- Imprimir datos del usuario o de la BD en HTML: siempre con `e($texto)`.
- Usuario conectado: `usuario_actual()['id']` (útil para guardar quién registró un pedido).
- Página interna: definir `$tituloPagina`, incluir `php/partials/cabecera.php` y cerrar con `php/partials/pie.php`. Para cargar JS propio: `$scripts = ['js/pedidos.js'];` antes de incluir `pie.php`.
- **Nada de `<script>` en línea ni atributos `style=""`**: la política de seguridad (CSP) los bloquea. Todo el JS va en `js/` y todo el CSS en `css/`.
- Para saber desde JS quién está conectado: `fetch('php/auth/estado.php')` → `{ estado, mensaje, datos: { id, nombre, usuario, rol } }` o 401.

### Matriz de permisos

| Módulo / acción | Administrador | Mesero |
|---|:---:|:---:|
| Ver menú, buscar y filtrar | ✔ | ✔ |
| Crear, editar, eliminar productos y categorías | ✔ | ✖ |
| Registrar pedidos y ver sus totales | ✔ | ✔ |
| Anular o eliminar pedidos | ✔ | ✖ |
| Inventario (insumos, stock, alertas) | ✔ | ✖ |

---

## 5. Scripts SQL: reglas

- **Nunca edites un script que ya se importó** en alguna base (la tuya, la de un compañero o el hosting). Los cambios van en un script nuevo con `ALTER TABLE`.
- Numeración: `01` y `02` son de autenticación; **Gabo y Jeremy empiezan en `03_`** (`03_menu.sql`, `04_pedidos.sql`, …). Pónganse de acuerdo en el número antes de crear el archivo.
- Cada script empieza registrando su número:
  ```sql
  SET NAMES utf8mb4;
  INSERT INTO esquema_version (version, descripcion) VALUES (3, 'Categorías y productos');
  CREATE TABLE categorias ( … );
  ```
  Si alguien lo importa dos veces, falla en esa línea (`Duplicate entry '3'`) antes de tocar nada.
- Qué tiene una base: `SELECT * FROM esquema_version;`
- Nombres de tabla en minúsculas (en el hosting, Linux, `Usuarios` ≠ `usuarios`).
- Los datos de prueba van en `90_…` en adelante, nunca en los scripts de esquema.

---

## 6. Seguridad incluida

- Contraseñas con `password_hash()` / `password_verify()` (bcrypt, coste 12 fijo) y re-cifrado automático si cambia el algoritmo.
- Consultas preparadas en todo acceso a la base (helpers `consultar`, `ejecutar`…).
- Validación en cliente (`js/login.js`) **y** en servidor, con la misma regla (`PATRON_USUARIO`).
- `session_regenerate_id()` al iniciar y al cerrar sesión; modo estricto de sesión.
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` con HTTPS.
- Cierre tras 30 min de inactividad, vida máxima de 12 h, y revisión cada minuto de que la cuenta siga activa y con el mismo rol.
- Bloqueo de 5 min tras 5 intentos fallidos por usuario o 20 por IP, guardado en la base (no se evita borrando la cookie).
- Mismo tiempo de respuesta exista o no el usuario, y mensaje genérico.
- Token CSRF en todos los formularios POST, renovado al iniciar sesión.
- Cabeceras CSP, `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`.
- Errores visibles solo en local; en el hosting se registran en `logs/php_error.log` con un código de referencia.
- `.htaccess` bloquea `config/`, `sql/`, `logs/`, `docs/`, `.git`, `*.md`, `*.sql`…

---

## 7. Pruebas

```bash
# Sintaxis con el PHP de XAMPP (8.0)
for f in $(git ls-files '*.php'); do C:/xampp/php/php.exe -l "$f" || break; done

# Todo endpoint protegido
C:\xampp\php\php.exe tests\verificar_endpoints.php

# Integración (Git Bash), con Apache de XAMPP y la base que uses:
MYSQL_CMD="/c/xampp/mysql/bin/mysql.exe -uroot coffeedesk" CON_APACHE=1 \
  bash tests/integracion.sh http://localhost/coffeedesk
```

`integracion.sh` necesita los usuarios de `90_seed_solo_local.sql`. Con `MYSQL_CMD` vacía la tabla `intentos_login` al empezar y al terminar; sin él, repetirlo antes de 5 minutos da falsos fallos (la propia prueba deja bloqueado al mesero). El plan manual está en [`docs/plan_pruebas.md`](docs/plan_pruebas.md).

El CI de GitHub Actions ejecuta todo lo anterior (salvo los 403 de Apache) en cada push y Pull Request.

Despliegue: ver [`docs/despliegue_infinityfree.md`](docs/despliegue_infinityfree.md).
