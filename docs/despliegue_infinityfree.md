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
2. **Importar**, en orden: `01_usuarios_roles.sql`, `02_intentos_login.sql` y después los de Gabo y Jeremy (`03_…`, `04_…`).
3. **No** importar:
   - `00_crear_bd_local.sql` (InfinityFree no permite `CREATE DATABASE`).
   - ⚠ **`90_seed_solo_local.sql` ni ningún `9X_`**: crean usuarios con contraseñas públicas (`admin / Admin123*`). Con eso cualquiera entraría como administrador.
4. Comprobar: `SELECT * FROM esquema_version;` debe listar 1, 2, 3… sin huecos.
5. Si una base del hosting ya tenía las tablas de la versión anterior (sin `esquema_version`), bórralas desde phpMyAdmin y vuelve a importar: el 01 nuevo falla a propósito si las tablas existen.

## 4. Crear el administrador del hosting
En **tu computadora** (no en el hosting):

```bash
C:\xampp\php\php.exe herramientas\crear_admin.php
```

Pide nombre, usuario, rol y contraseña (mínimo 10 caracteres; rechaza las contraseñas de prueba). En Windows la contraseña se ve al escribirla, así que nadie debe estar mirando la pantalla. El script imprime un `INSERT`: pégalo en phpMyAdmin → pestaña **SQL** de la base del hosting.

Guarda esa contraseña en un lugar seguro. No la escribas en el repositorio, el informe ni el chat del grupo.

## 5. Preparar el paquete y las credenciales
1. Generar el paquete desde la rama `main` actualizada. `.gitattributes` excluye `docs/`, `sql/`, `herramientas/`, `tests/`, `.github/`, `README.md` y los archivos de Git:
   ```bash
   git checkout main && git pull
   git archive --format=zip -o coffeedesk-deploy.zip main
   ```
2. Descomprimir el zip en una carpeta aparte (no encima de tu repo).
3. En esa carpeta, copiar `config/credenciales.hosting.example.php` como `config/credenciales.php` y completar el bloque `bd` con los datos del paso 2. Dejar `'entorno' => 'hosting'` y `'forzar_https' => false` por ahora.

## 6. Subir archivos
Opción A — **File Manager** del panel (sencillo): abrir `htdocs/`, borrar el `index2.html` de ejemplo y subir el **contenido** de la carpeta descomprimida (no la carpeta en sí).

Opción B — **FTP con FileZilla** (más rápido): los datos FTP están en *Account Details* (host `ftpupload.net`, puerto 21). Subir el contenido a `/htdocs`.

No subas nada que no venga en el zip. En especial, nunca subas `.git/`, `README.md` ni `sql/`.

La carpeta `logs/` sí se sube (con su `.htaccess`). Si `logs/php_error.log` no aparece al provocar un error, revisa en el File Manager que la carpeta tenga permiso de escritura.

## 7. Activar HTTPS
1. Panel → **SSL Certificates** → pedir el certificado gratuito para el dominio y esperar la validación.
2. Abrir `https://<dominio>/` y comprobar que carga sin avisos del navegador.
3. **Solo entonces** cambiar `'forzar_https' => true` en `config/credenciales.php` del hosting. Con eso se redirige `http://` → `https://` y se envía HSTS.
4. Si al activarlo la página entra en un bucle de redirecciones, vuelve a `false`: el proxy de InfinityFree no está informando que la petición es HTTPS (ver §8, "Detrás del proxy").

La cookie de sesión se marca `Secure` automáticamente cuando la petición llega por HTTPS.

## 8. Verificar
Checklist (capturas de cada punto para el informe):

- [ ] `https://<dominio>/` carga el login sin errores.
- [ ] Login con el administrador creado en el §4. **`admin / Admin123*` debe responder "Usuario o contraseña incorrectos".**
- [ ] Un mesero (créalo también con `crear_admin.php`, rol `mesero`) no puede abrir `/inventario.php`.
- [ ] Cada módulo (menú, pedidos, inventario) guarda y lee datos en la base remota.
- [ ] Hora correcta (P-13): un registro nuevo muestra la hora de Ecuador.

Comprobación con `curl` (Git Bash). Todas deben devolver **403**:

```bash
D=https://<dominio>
for r in README.md .git/HEAD .htaccess config/credenciales.php config/credenciales.example.php \
         sql/01_usuarios_roles.sql logs/php_error.log docs/plan_pruebas.md \
         php/partials/cabecera.php php/comun/html.php php/auth/sesion.php; do
  printf '%-40s %s\n' "$r" "$(curl -s -o /dev/null -w '%{http_code}' "$D/$r")"
done
curl -sI "$D/" | grep -i -E 'content-security-policy|x-frame-options|set-cookie'
```

`.git/HEAD` puede dar 404 si no se subió `.git` (es lo esperado); lo importante es que nunca devuelva 200.

**Detrás del proxy** (verificar una vez y anotar el resultado en el informe): subir temporalmente un archivo `diag.php` con

```php
<?php header('Content-Type: text/plain');
foreach (['REMOTE_ADDR', 'HTTPS', 'HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_FOR', 'SERVER_PORT'] as $k) {
    echo $k, ' = ', $_SERVER[$k] ?? '(no definido)', "\n";
}
```

abrirlo por `https://` y **borrarlo inmediatamente después**.

- Si `REMOTE_ADDR` es siempre la misma IP aunque entres desde redes distintas, es la IP del proxy. El límite por usuario sigue funcionando, pero el de 20 intentos por IP pasa a ser global para todo el sitio.
- Si `HTTPS` y `HTTP_X_FORWARDED_PROTO` no indican https y `SERVER_PORT` no es 443, no actives `forzar_https`.

## Problemas frecuentes
| Síntoma | Causa probable |
|---|---|
| "Algo salió mal … Código de referencia: XXXX" | Error de PHP o de SQL. Buscar el código en `logs/php_error.log` desde el File Manager. |
| "Servicio no disponible" / error de conexión en el log | Datos del bloque `bd` mal copiados (host o prefijo `if0_`). |
| "config/credenciales.php debe declarar entorno…" | Falta `'entorno' => 'hosting'` o el bloque `bd`. |
| Página en blanco | Error antes de que se configure el log: probar el mismo paquete en XAMPP. |
| "Table doesn't exist" | Mayúsculas en nombres de tabla, o script no importado (`SELECT * FROM esquema_version`). |
| "Duplicate entry" al importar | Ese script ya estaba importado: no hace falta repetirlo. |
| El login redirige en bucle | `base_url` debe ser `''` en hosting, o `forzar_https` activado sin HTTPS detectado. |
| "Demasiados intentos fallidos" para todos | La IP es la del proxy y se llegó a 20 fallos en 5 min; esperar 5 minutos. |
| Cambios no se ven | Caché de InfinityFree: esperar o abrir en ventana privada. |
| No conecta desde MySQL Workbench | InfinityFree no permite conexiones remotas a MySQL; usar su phpMyAdmin. |
