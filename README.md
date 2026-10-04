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
├── recetas.php            ← insumos que descuenta cada producto (solo administrador)
├── categorias.php         ← solo administrador
├── inventario.php         ← (Jeremy) solo administrador
├── css/estilos.css        ← estilos (Frederick)
├── js/                    ← login.js, comun.js (validación, filtros, modo edición) y uno por módulo
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
│   ├── comun/             ← html.php (e), dinero.php, respuesta.php, flash.php,
│   │                        validacion.php, cabeceras.php, errores.php
│   ├── dao/               ← TODO el SQL de los módulos: una clase por tabla
│   │                        (ProductoDAO, CategoriaDAO, PedidoDAO, RecetaDAO, InventarioDAO)
│   ├── models/            ← Insumo.php
│   ├── menu/, pedidos/, inventario/   ← endpoints POST: validan y llaman al DAO
│   └── partials/          ← head.php, cabecera.php, pie.php, pie_pagina.php
├── sql/
│   ├── 00_crear_bd_local.sql      ← solo XAMPP
│   ├── 01_usuarios_roles.sql      ← versión 1: esquema_version, roles, usuarios
│   ├── 02_intentos_login.sql      ← versión 2: límite de intentos de login
│   ├── 03_menu.sql … 07_productos_activo.sql  ← menú, pedidos, inventario y recetas
│   ├── 08_pedido_insumo.sql       ← versión 8: lo que descontó cada pedido (para anularlo)
│   ├── 90_seed_solo_local.sql     ← usuarios de prueba (¡NUNCA en el hosting!)
│   └── 91_seed_catalogo_solo_local.sql  ← catálogo de prueba (solo local)
├── herramientas/
│   ├── generar_hash.php   ← hash de una contraseña
│   └── crear_admin.php    ← genera el INSERT del admin del hosting
├── tests/
│   ├── verificar_endpoints.php    ← todo endpoint llama a requiere_*()
│   ├── dinero.php                 ← conversión de importes (consola)
│   ├── modulos.sh                 ← categorías, menú, inventario, recetas y pedidos (curl)
│   └── integracion.sh             ← login, roles, cabeceras y .htaccess (curl)
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

**¿Tu `credenciales.php` es del formato viejo** (bloques `local` y `hosting`)? Ya no se acepta (elegía el entorno según la cabecera Host): cópialo de nuevo desde `credenciales.example.php`.

**¿Tu base es anterior al 08?** Importa `sql/08_pedido_insumo.sql` (también en el hosting) **a la vez que actualizas el código**, sin registrar pedidos entre una cosa y la otra: el código nuevo sin la tabla da error al registrar o anular, y los pedidos que registre el código viejo con la tabla ya creada no devolverían stock al anularse.

**Usuarios de prueba (solo locales)**

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin123*` | administrador |
| `mesero` | `Mesero123*` | mesero |

Estas contraseñas son **públicas** (están en este README). Existen solo si importas `90_seed_solo_local.sql`, que **nunca** se importa en el hosting. Ojo: Apache de XAMPP escucha en todas las interfaces, así que cualquiera en tu misma red puede entrar como `admin` mientras XAMPP esté abierto. En redes que no sean la de tu casa, cambia en `C:\xampp\apache\conf\httpd.conf` la línea `Listen 80` por `Listen 127.0.0.1:80`. El administrador del hosting se crea con `herramientas/crear_admin.php` (ver [despliegue](docs/despliegue_infinityfree.md)).

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

Archivos que responden JSON:

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
$id       = post_id('id');                // int > 0, o null
$producto = get_id('producto');           // lo mismo para la URL (?producto=5)

if ($cantidad === null || $cantidad <= 0) {
    fallar('La cantidad debe ser un entero mayor que 0.', 'pedidos.php');   // mensaje + redirección
}
```

Otras validaciones comunes (`php/comun/validacion.php` y `dinero.php`): `cantidad_valida()` (stock con hasta 3 decimales), `largo_valido()`, `texto_a_centavos()` (precio escrito por el usuario). Los límites (mesas, cantidades, largo de los nombres, estados, unidades) están en `config/constantes.php`: la vista y el servidor leen la misma constante.

### 4.3 Consultar la base de datos

El SQL de los módulos va en **`php/dao/`**, una clase por tabla; las páginas y los endpoints solo llaman a sus métodos (`(new ProductoDAO())->listarActivos()`). Todas las consultas son preparadas. **Nunca** concatenes variables en el SQL.

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

Para dinero usa `DECIMAL(10,2)` en MySQL y opera en centavos enteros en PHP con `php/comun/dinero.php`: `precio_a_centavos('2.50')` → 250 y `centavos_a_decimal(250)` → `'2.50'`. No uses `float` ni `round($x * 100)`.

### 4.4 Formularios, mensajes y vistas

- Formularios POST: incluir `<?= csrf_campo() ?>` dentro del `<form>`; el endpoint empieza con `exigir_post_con_csrf('pagina.php')` (rechaza GET y tokens inválidos con el mismo mensaje en todos los módulos).
- Mensajes después de redirigir: `terminar('exito', 'Guardado.', 'menu.php')` o, para errores, `fallar('…', 'menu.php')` (guardan el mensaje y redirigen); la cabecera ya los imprime con `mostrar_flash()`.
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
- Cookie de sesión `HttpOnly`, `SameSite=Lax` y `Secure` con HTTPS (en el hosting, activa `forzar_https` en cuanto funcione el SSL: mientras esté en `false` la cookie viaja sin cifrar).
- La redirección a HTTPS usa el `dominio` de `credenciales.php`, no la cabecera `Host`.
- Cierre tras 30 min de inactividad, vida máxima de 12 h, y revisión cada minuto de que la cuenta siga activa y con el mismo rol.
- Bloqueo de 5 min tras 5 intentos fallidos de un usuario desde una misma IP o 20 desde una IP, guardado en la base (no se evita borrando la cookie). Fallar desde otro equipo no bloquea la cuenta del administrador, y los intentos simultáneos desde una misma IP se atienden de uno en uno (`GET_LOCK`), así que no se puede rebasar el límite con peticiones en paralelo.
- Mismo tiempo de respuesta exista o no el usuario, y mensaje genérico.
- Token CSRF en todos los formularios POST, renovado al iniciar sesión.
- Cabeceras CSP, `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`.
- Errores visibles solo en local; en el hosting se registran en `logs/php_error.log` con un código de referencia.
- `.htaccess` bloquea `config/`, `sql/`, `logs/`, `docs/`, `.git`, `*.md`, `*.sql`… sin distinguir mayúsculas (`/CONFIG/` también da 403 en Windows).
- Al anular un pedido se devuelve exactamente el stock que se descontó (tabla `pedido_insumo`), aunque la receta haya cambiado.
- Los productos de un pedido se vuelven a comprobar dentro de la transacción (`FOR UPDATE`): uno marcado como agotado al mismo tiempo no se vende.

---

## 7. Pruebas

```bash
# Sintaxis con el PHP de XAMPP (8.0)
for f in $(git ls-files '*.php'); do C:/xampp/php/php.exe -l "$f" || break; done

# Todo endpoint protegido y conversión de importes
C:\xampp\php\php.exe tests\verificar_endpoints.php
C:\xampp\php\php.exe tests\dinero.php

# Módulos e integración (Git Bash), con Apache de XAMPP y una base de PRUEBA
# (modulos.sh crea categorías, productos, insumos y pedidos con nombres únicos):
export MYSQL_CMD="/c/xampp/mysql/bin/mysql.exe -uroot coffeedesk_prueba"
bash tests/modulos.sh http://localhost/coffeedesk
CON_APACHE=1 bash tests/integracion.sh http://localhost/coffeedesk
```

`modulos.sh` prueba la protección de los 11 endpoints, el CRUD de categorías, productos, insumos y recetas, y que los pedidos descuenten el stock, lo devuelvan al anularse (aunque la receta haya cambiado) y fallen sin tocar nada cuando no alcanza.

`integracion.sh` necesita los usuarios de `90_seed_solo_local.sql`. Con `MYSQL_CMD` vacía la tabla `intentos_login` al empezar y al terminar; sin él, repetirlo antes de 5 minutos da falsos fallos (la propia prueba deja bloqueado al mesero). El plan manual está en [`docs/plan_pruebas.md`](docs/plan_pruebas.md).

El CI de GitHub Actions ejecuta todo lo anterior (salvo los 403 de Apache) en cada push y Pull Request.

Despliegue: ver [`docs/despliegue_infinityfree.md`](docs/despliegue_infinityfree.md).
