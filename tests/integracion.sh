#!/usr/bin/env bash
# =========================================================================
# CoffeeDesk — pruebas de integración (bash + curl). Corre en Git Bash y en CI.
#
# Uso:
#   bash tests/integracion.sh [URL_BASE]        # por defecto http://localhost/coffeedesk
#
# Variables opcionales:
#   MYSQL_CMD   comando mysql ya apuntado a la base; con él se vacía intentos_login
#               al empezar y al terminar, y se prueba también el límite por IP.
#               Sin él, repetir las pruebas antes de 5 minutos da falsos fallos
#               (los usuarios quedan bloqueados por la propia prueba P-09).
#               Ej.: MYSQL_CMD="/c/xampp/mysql/bin/mysql.exe -uroot coffeedesk_prueba"
#   CON_APACHE=1  comprueba los bloqueos 403 del .htaccess (php -S no lo aplica).
#
# Requiere la base con 01, 02 y 90 importados (usuarios de prueba admin y mesero).
#
# Presupuesto de intentos fallidos (límites: 5 por usuario, 20 por IP en 5 min):
#   P-04 admin 1 (se borra al entrar bien en P-05) · tiempos: admin 3 + inexistente 3
#   · P-09: mesero 5  → IP = 11 antes de la prueba opcional del límite por IP.
# =========================================================================
set -u

BASE="${1:-http://localhost/coffeedesk}"
BASE="${BASE%/}"
TMP="$(mktemp -d)"
PASA=0
FALLA=0

limpiar_intentos() {
    if [ -n "${MYSQL_CMD:-}" ]; then
        echo "DELETE FROM intentos_login;" | $MYSQL_CMD || echo "  (no se pudo vaciar intentos_login con MYSQL_CMD)"
    fi
}
trap 'limpiar_intentos; rm -rf "$TMP"' EXIT

ok()    { echo "  ✔ $1"; PASA=$((PASA + 1)); }
fallo() { echo "  ✖ $1${2:+  → $2}"; FALLA=$((FALLA + 1)); }
seccion() { echo; echo "── $1"; }

# pedir JAR METODO RUTA [opciones de curl…]  → imprime el código HTTP;
# deja las cabeceras en $TMP/h y el cuerpo en $TMP/b
pedir() {
    local jar="$1" metodo="$2" ruta="$3"
    shift 3
    [ -f "$jar" ] || : > "$jar"
    curl -s -o "$TMP/b" -D "$TMP/h" -w '%{http_code}' -b "$jar" -c "$jar" -X "$metodo" "$@" "$BASE/$ruta"
}
ubicacion() { grep -i '^location:' "$TMP/h" | tr -d '\r' | sed 's/^[^:]*: *//' | tail -1; }
token_csrf() { sed -n 's/.*name="csrf" value="\([0-9a-f]*\)".*/\1/p' "$TMP/b" | head -1; }
cuerpo_tiene() { grep -q -- "$1" "$TMP/b"; }
nuevo_jar() { local j; j="$TMP/jar_$RANDOM$RANDOM"; : > "$j"; echo "$j"; }

# login JAR USUARIO CLAVE → código HTTP del POST (la ubicación queda en $TMP/h)
login() {
    local jar="$1" tok
    pedir "$jar" GET index.php > /dev/null
    tok="$(token_csrf)"
    pedir "$jar" POST php/auth/login.php \
        --data-urlencode "csrf=$tok" --data-urlencode "usuario=$2" --data-urlencode "clave=$3"
}

# ms_login JAR USUARIO CLAVE → milisegundos del POST de login (reutiliza el token del jar)
ms_login() {
    local jar="$1" tok
    pedir "$jar" GET index.php > /dev/null
    tok="$(token_csrf)"
    curl -s -o /dev/null -w '%{time_total}' -b "$jar" -c "$jar" \
        --data-urlencode "csrf=$tok" --data-urlencode "usuario=$2" --data-urlencode "clave=$3" \
        "$BASE/php/auth/login.php" | awk '{ printf "%d", $1 * 1000 }'
}
mediana3() { printf '%s\n' "$@" | sort -n | sed -n 2p; }

echo "CoffeeDesk · pruebas de integración contra $BASE"
limpiar_intentos

# ------------------------------------------------------------------------
seccion "Disponibilidad y cabeceras de seguridad (F-006)"
J="$(nuevo_jar)"
codigo="$(pedir "$J" GET index.php)"
if [ "$codigo" = 200 ] && cuerpo_tiene 'id="form-login"'; then
    ok "index.php responde 200 con el formulario"
else
    fallo "index.php no responde bien" "HTTP $codigo"
    echo; echo "No se puede continuar sin la página de login."; exit 1
fi
grep -qi '^x-frame-options: *deny' "$TMP/h" && ok "X-Frame-Options: DENY" || fallo "Falta X-Frame-Options: DENY"
grep -qi "^content-security-policy:.*frame-ancestors 'none'" "$TMP/h" && ok "Content-Security-Policy con frame-ancestors 'none'" || fallo "Falta la CSP (modo enforce)"
grep -qi '^x-content-type-options: *nosniff' "$TMP/h" && ok "X-Content-Type-Options: nosniff" || fallo "Falta X-Content-Type-Options"
grep -i '^set-cookie: *COFFEEDESK_SID=' "$TMP/h" | grep -qi 'httponly' && ok "Cookie de sesión HttpOnly" || fallo "La cookie de sesión no es HttpOnly"
! grep -qi '^x-powered-by:' "$TMP/h" && ok "Sin X-Powered-By (no revela la versión de PHP)" || fallo "Se envía X-Powered-By" "$(grep -i '^x-powered-by:' "$TMP/h" | tr -d '\r')"
! grep -qi '^strict-transport-security:' "$TMP/h" && ok "Sin HSTS mientras forzar_https es false" || fallo "HSTS enviado sin forzar_https"
grep -q 'pattern="\[A-Za-z0-9._\\-\]{3,30}"' "$TMP/b" && ok "El input usuario lleva pattern con el guion escapado (F-015)" || fallo "pattern del usuario ausente o distinto"

# ------------------------------------------------------------------------
seccion "Autenticación y roles (plan de pruebas P-01, P-03…P-08)"

J="$(nuevo_jar)"
codigo="$(pedir "$J" GET panel.php)"; loc="$(ubicacion)"
pedir "$J" GET index.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/index.php}" != "$loc" ] && cuerpo_tiene 'Inicia sesión para continuar'; then
    ok "P-01 sin sesión, panel.php redirige al login con aviso"
else
    fallo "P-01 acceso sin sesión" "HTTP $codigo → $loc"
fi

J="$(nuevo_jar)"
codigo="$(login "$J" "a'" "x")"
pedir "$J" GET index.php > /dev/null
[ "$codigo" = 302 ] && cuerpo_tiene 'solo admite letras' && ok "P-03 usuario con caracteres inválidos rechazado" || fallo "P-03" "HTTP $codigo"

J="$(nuevo_jar)"
codigo="$(login "$J" admin mala)"
pedir "$J" GET index.php > /dev/null
[ "$codigo" = 302 ] && cuerpo_tiene 'Usuario o contraseña incorrectos' && ok "P-04 contraseña incorrecta → mensaje genérico" || fallo "P-04" "HTTP $codigo"

JA="$(nuevo_jar)"
codigo="$(login "$JA" admin 'Admin123*')"; loc="$(ubicacion)"
pedir "$JA" GET panel.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/panel.php}" != "$loc" ] && cuerpo_tiene 'Panel de administración' && cuerpo_tiene 'inventario.php'; then
    ok "P-05 login administrador → panel de administración con Inventario"
else
    fallo "P-05 login administrador" "HTTP $codigo → $loc"
fi

JM="$(nuevo_jar)"
codigo="$(login "$JM" mesero 'Mesero123*')"; loc="$(ubicacion)"
pedir "$JM" GET panel.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/panel.php}" != "$loc" ] && cuerpo_tiene 'Panel de atención' && ! cuerpo_tiene 'inventario.php'; then
    ok "P-06 login mesero → panel de atención sin Inventario"
else
    fallo "P-06 login mesero" "HTTP $codigo → $loc"
fi

codigo="$(pedir "$JM" GET inventario.php)"; loc="$(ubicacion)"
pedir "$JM" GET panel.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/panel.php}" != "$loc" ] && cuerpo_tiene 'No tienes permiso'; then
    ok "P-07 mesero en inventario.php → vuelve al panel con aviso"
else
    fallo "P-07 mesero fuerza URL restringida" "HTTP $codigo → $loc"
fi

# ------------------------------------------------------------------------
seccion "Endpoint JSON php/auth/estado.php"
J="$(nuevo_jar)"
codigo="$(pedir "$J" GET php/auth/estado.php -H 'Accept: application/json')"
[ "$codigo" = 401 ] && cuerpo_tiene '"estado":"error"' && ok "Sin sesión → 401 JSON" || fallo "estado.php sin sesión" "HTTP $codigo"
codigo="$(pedir "$JM" GET php/auth/estado.php -H 'Accept: application/json')"
[ "$codigo" = 200 ] && cuerpo_tiene '"rol":"mesero"' && ok "Con sesión de mesero → 200 y rol mesero" || fallo "estado.php con mesero" "HTTP $codigo"
codigo="$(pedir "$JA" GET php/auth/estado.php -H 'Accept: application/json')"
[ "$codigo" = 200 ] && cuerpo_tiene '"rol":"administrador"' && ok "Con sesión de admin → 200 y rol administrador" || fallo "estado.php con admin" "HTTP $codigo"
codigo="$(pedir "$JA" POST php/auth/estado.php -H 'Accept: application/json')"
[ "$codigo" = 405 ] && ok "POST a estado.php → 405" || fallo "estado.php con POST" "HTTP $codigo"

# ------------------------------------------------------------------------
seccion "Cierre de sesión (P-08)"
codigo="$(pedir "$JA" GET php/auth/logout.php)"
pedir "$JA" GET panel.php > /dev/null
[ "$codigo" = 302 ] && cuerpo_tiene 'Panel de administración' && ok "logout por GET no cierra la sesión" || fallo "logout por GET" "HTTP $codigo"

pedir "$JM" GET panel.php > /dev/null
tok="$(token_csrf)"
cp "$JM" "$TMP/jar_mesero_viejo"
codigo="$(pedir "$JM" POST php/auth/logout.php --data-urlencode "csrf=$tok")"; loc="$(ubicacion)"
pedir "$JM" GET index.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/index.php}" != "$loc" ] && cuerpo_tiene 'Cerraste sesión correctamente'; then
    ok "P-08 cerrar sesión → login con mensaje"
else
    fallo "P-08 cerrar sesión" "HTTP $codigo → $loc"
fi
codigo="$(pedir "$TMP/jar_mesero_viejo" GET panel.php)"; loc="$(ubicacion)"
[ "$codigo" = 302 ] && [ "${loc%/index.php}" != "$loc" ] && ok "P-08 la cookie anterior al logout ya no abre panel.php" || fallo "P-08 cookie vieja" "HTTP $codigo → $loc"

# ------------------------------------------------------------------------
seccion "Entradas enviadas como lista (F-009, P-15)"
J="$(nuevo_jar)"
pedir "$J" GET index.php > /dev/null
codigo="$(pedir "$J" POST php/auth/login.php --data 'csrf[]=x&usuario=admin&clave=x')"
[ "$codigo" = 302 ] && ok "login con csrf[] → 302 (no 500)" || fallo "login con csrf[]" "HTTP $codigo"
pedir "$J" GET index.php > /dev/null
tok="$(token_csrf)"
codigo="$(pedir "$J" POST php/auth/login.php --data "csrf=$tok&usuario[]=admin&clave[]=x")"
[ "$codigo" = 302 ] && ok "login con usuario[] y clave[] → 302 (no 500)" || fallo "login con usuario[]" "HTTP $codigo"
codigo="$(pedir "$J" POST php/auth/logout.php --data 'csrf[]=x')"
[ "$codigo" = 302 ] && ok "logout con csrf[] → 302 (no 500)" || fallo "logout con csrf[]" "HTTP $codigo"

# ------------------------------------------------------------------------
seccion "Tiempo de respuesta: usuario existente vs inexistente (F-003)"
J="$(nuevo_jar)"
# El orden se alterna en cada ronda para que el calentamiento no favorezca a ninguno
a1="$(ms_login "$J" admin mala1)"; n1="$(ms_login "$J" usuario_que_no_existe mala1)"
n2="$(ms_login "$J" usuario_que_no_existe mala2)"; a2="$(ms_login "$J" admin mala2)"
a3="$(ms_login "$J" admin mala3)"; n3="$(ms_login "$J" usuario_que_no_existe mala3)"
ma="$(mediana3 "$a1" "$a2" "$a3")"; mn="$(mediana3 "$n1" "$n2" "$n3")"
dif=$((ma > mn ? ma - mn : mn - ma))
[ "$dif" -lt 100 ] && ok "Medianas: existente ${ma} ms, inexistente ${mn} ms (diferencia ${dif} ms < 100)" \
                   || fallo "Diferencia de tiempo delata usuarios" "existente ${ma} ms, inexistente ${mn} ms"

# ------------------------------------------------------------------------
seccion "Bloqueo por intentos fallidos con cookie nueva en cada intento (F-002, P-09)"
bien=0
for i in 1 2 3 4 5; do
    J="$(nuevo_jar)"
    codigo="$(login "$J" mesero "incorrecta$i")"
    pedir "$J" GET index.php > /dev/null
    [ "$codigo" = 302 ] && cuerpo_tiene 'Usuario o contraseña incorrectos' && bien=$((bien + 1))
done
[ "$bien" = 5 ] && ok "5 intentos fallidos, cada uno con una sesión nueva" || fallo "Intentos fallidos previos" "$bien de 5 con el mensaje esperado"
J="$(nuevo_jar)"
codigo="$(login "$J" mesero 'Mesero123*')"; loc="$(ubicacion)"
pedir "$J" GET index.php > /dev/null
if [ "$codigo" = 302 ] && [ "${loc%/panel.php}" = "$loc" ] && cuerpo_tiene 'Demasiados intentos fallidos'; then
    ok "P-09 6.º intento con la clave CORRECTA y cookie nueva → bloqueado"
else
    fallo "P-09 el bloqueo se evita con una cookie nueva" "HTTP $codigo → $loc"
fi

if [ -n "${MYSQL_CMD:-}" ]; then
    # IP acumula 11 fallos (ver presupuesto arriba): 9 más con usuarios distintos → 20
    for i in 1 2 3 4 5 6 7 8 9; do
        login "$(nuevo_jar)" "ip_prueba_$i" x > /dev/null
    done
    J="$(nuevo_jar)"
    codigo="$(login "$J" admin 'Admin123*')"; loc="$(ubicacion)"
    pedir "$J" GET index.php > /dev/null
    [ "${loc%/panel.php}" = "$loc" ] && cuerpo_tiene 'Demasiados intentos fallidos' \
        && ok "Límite por IP (20): hasta admin con la clave correcta queda bloqueado" \
        || fallo "Límite por IP" "HTTP $codigo → $loc"
else
    echo "  (límite por IP no probado: define MYSQL_CMD para poder limpiar después)"
fi

# ------------------------------------------------------------------------
if [ "${CON_APACHE:-0}" = 1 ]; then
    seccion "Bloqueos del .htaccess (F-008) — solo con Apache"
    # La prueba del límite por IP deja la IP bloqueada; esta sección vuelve a entrar como admin
    limpiar_intentos
    J="$(nuevo_jar)"
    for ruta in README.md .git/HEAD .htaccess .gitignore \
                config/credenciales.php config/credenciales.example.php config/constantes.php \
                sql/01_usuarios_roles.sql logs/php_error.log docs/plan_pruebas.md \
                php/partials/cabecera.php php/comun/html.php \
                herramientas/crear_admin.php tests/integracion.sh; do
        codigo="$(pedir "$J" GET "$ruta")"
        [ "$codigo" = 403 ] && ok "403 en /$ruta" || fallo "/$ruta accesible" "HTTP $codigo"
    done
    # php/auth/: librerías bloqueadas (también un archivo futuro que no existe: bloqueo por defecto)
    for ruta in php/auth/sesion.php php/auth/csrf.php php/auth/autorizacion.php \
                php/auth/arranque_sesion.php php/auth/limite_intentos.php php/auth/archivo_nuevo.php; do
        codigo="$(pedir "$J" GET "$ruta")"
        [ "$codigo" = 403 ] && ok "403 en /$ruta (librería)" || fallo "/$ruta accesible" "HTTP $codigo"
    done
    # …y los tres endpoints de php/auth/ siguen respondiendo (no 403)
    J="$(nuevo_jar)"
    codigo="$(pedir "$J" GET php/auth/login.php)"; loc="$(ubicacion)"
    [ "$codigo" = 302 ] && [ "${loc%/index.php}" != "$loc" ] && ok "php/auth/login.php accesible (GET → 302 al login)" || fallo "php/auth/login.php" "HTTP $codigo → $loc"
    codigo="$(pedir "$J" GET php/auth/logout.php)"; loc="$(ubicacion)"
    [ "$codigo" = 302 ] && [ "${loc%/panel.php}" != "$loc" ] && ok "php/auth/logout.php accesible (GET → 302 al panel)" || fallo "php/auth/logout.php" "HTTP $codigo → $loc"
    codigo="$(pedir "$J" GET php/auth/estado.php -H 'Accept: application/json')"
    [ "$codigo" = 401 ] && cuerpo_tiene '"estado":"error"' && ok "php/auth/estado.php accesible (sin sesión → 401 JSON)" || fallo "php/auth/estado.php" "HTTP $codigo"
    JL="$(nuevo_jar)"
    codigo="$(login "$JL" admin 'Admin123*')"; loc="$(ubicacion)"
    codigo2="$(pedir "$JL" GET php/auth/estado.php -H 'Accept: application/json')"
    [ "$codigo" = 302 ] && [ "${loc%/panel.php}" != "$loc" ] && [ "$codigo2" = 200 ] \
        && ok "login.php y estado.php funcionan juntos bajo Apache (login → 200 en estado)" \
        || fallo "login + estado bajo Apache" "login HTTP $codigo → $loc · estado HTTP $codigo2"
    for ruta in css/estilos.css js/login.js; do
        codigo="$(pedir "$J" GET "$ruta")"
        [ "$codigo" = 200 ] && ok "200 en /$ruta (recurso público)" || fallo "/$ruta no se sirve" "HTTP $codigo"
    done
else
    echo; echo "── (bloqueos del .htaccess no probados: define CON_APACHE=1 contra Apache)"
fi

# ------------------------------------------------------------------------
echo
echo "Resultado: $PASA correctas, $FALLA fallidas"
[ "$FALLA" -eq 0 ]
