# Despliegue en InfinityFree

Responsable: Bryan Gallegos · Apoyo: Gabo

## 1. Crear la cuenta y el sitio
1. Registrarse en <https://www.infinityfree.com> → **Create Account**.
2. Elegir un subdominio gratuito, por ejemplo `coffeedesk.infinityfreeapp.com` (o similar, según los dominios que ofrezca).
3. Esperar a que la cuenta quede **Active** (puede tardar unos minutos).

## 2. Crear la base de datos
1. En el panel de la cuenta → **MySQL Databases** → crear base con el nombre `coffeedesk`.
   InfinityFree le antepone el prefijo: queda `if0_XXXXXXXX_coffeedesk`.
2. Anotar los datos que muestra el panel:
   - **MySQL Hostname** (ej. `sql123.infinityfree.com`)
   - **MySQL Username** (ej. `if0_XXXXXXXX`)
   - **MySQL Password** (la contraseña de la cuenta de hosting, visible en *Account Details*)
   - **MySQL DB Name**

## 3. Importar las tablas
1. En **MySQL Databases** → botón **phpMyAdmin** junto a la base.
2. **Importar** los scripts en orden: `01_usuarios_roles.sql`, luego los de Gabo (`02_…`, `03_…`).
3. **No** importar `00_crear_bd_local.sql` (InfinityFree no permite `CREATE DATABASE`).
4. Los nombres de tabla deben estar en minúsculas: en el hosting (Linux) `Usuarios` ≠ `usuarios`.

## 4. Configurar credenciales
En tu copia local, completar el bloque `hosting` de `config/credenciales.php` con los datos del paso 2 y `'base_url' => ''`.

## 5. Subir archivos
Opción A — **File Manager** del panel (sencillo): abrir `htdocs/`, borrar el `index2.html` de ejemplo y subir el contenido del proyecto (no la carpeta `coffeedesk` en sí, sino lo que está dentro).

Opción B — **FTP con FileZilla** (más rápido): los datos FTP están en *Account Details* (host `ftpupload.net`, puerto 21). Subir a `/htdocs`.

No subir: `.git/`, `docs/`, `herramientas/`, `sql/` (opcional). **Sí** subir `config/credenciales.php` y `.htaccess`.

## 6. Activar HTTPS
Panel → **SSL Certificates** → pedir certificado gratuito para el dominio y esperar la validación. Con HTTPS la cookie de sesión se marca `Secure` automáticamente.

## 7. Verificar
- [ ] `https://<dominio>/` carga el login sin errores.
- [ ] Login con `admin` y con `mesero`.
- [ ] El mesero no puede abrir `/inventario.php`.
- [ ] `https://<dominio>/config/credenciales.php` y `/sql/` devuelven 403 o página vacía.
- [ ] Cada módulo (menú, pedidos, inventario) guarda y lee datos en la base remota.
- [ ] Capturas de cada punto para el informe.

## Problemas frecuentes
| Síntoma | Causa probable |
|---|---|
| "Servicio no disponible" | Datos del bloque `hosting` mal copiados (host o prefijo `if0_`). |
| Página en blanco | Error PHP oculto en producción: probar el mismo código en XAMPP. |
| "Table doesn't exist" | Mayúsculas en nombres de tabla, o script no importado. |
| El login redirige en bucle | `base_url` debe ser `''` en hosting. |
| Cambios no se ven | Caché de InfinityFree: esperar o abrir en ventana privada. |
| No conecta desde MySQL Workbench | InfinityFree no permite conexiones remotas a MySQL; usar su phpMyAdmin. |
