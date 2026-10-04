#!/usr/bin/env bash
# =========================================================================
# CoffeeDesk — pruebas de los módulos (bash + curl): categorías, menú,
# inventario, recetas y pedidos (descuento y devolución de stock).
#
# Uso:
#   bash tests/modulos.sh [URL_BASE]        # por defecto http://localhost/coffeedesk
#
# Requiere la base con todos los scripts de sql/ importados, incluido el 90
# (usuarios admin y mesero). Crea sus propios datos con nombres únicos, así
# que se puede repetir sin limpiar la base. Usar una base de PRUEBA.
#
# Variables opcionales:
#   MYSQL_CMD  igual que en integracion.sh: vacía intentos_login al empezar
#              (por si integracion.sh dejó bloqueados a admin o mesero).
# =========================================================================
set -u

BASE="${1:-http://localhost/coffeedesk}"
BASE="${BASE%/}"
ORIGEN="$(echo "$BASE" | sed -E 's#^(https?://[^/]+).*#\1#')"
TMP="$(mktemp -d)"
PASA=0
FALLA=0
trap 'rm -rf "$TMP"' EXIT

ok()    { echo "  ✔ $1"; PASA=$((PASA + 1)); }
fallo() { echo "  ✖ $1${2:+  → $2}"; FALLA=$((FALLA + 1)); }
seccion() { echo; echo "── $1"; }
comprobar() { if eval "$2"; then ok "$1"; else fallo "$1" "${3:-}"; fi; }

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
# Cuerpo en una sola línea (los atributos de un botón ocupan varias líneas)
plano() { tr '\n' ' ' < "$TMP/b" | tr -s ' '; }

login() {
    local jar="$1" tok
    pedir "$jar" GET index.php > /dev/null
    tok="$(token_csrf)"
    pedir "$jar" POST php/auth/login.php \
        --data-urlencode "csrf=$tok" --data-urlencode "usuario=$2" --data-urlencode "clave=$3" > /dev/null
}

# enviar JAR PAGINA_CON_TOKEN ENDPOINT [--data-urlencode campo=valor …]
#   Toma un token de PAGINA_CON_TOKEN, hace el POST y sigue la redirección.
#   Deja en $TMP/flash el mensaje que mostró la página de destino.
enviar() {
    local jar="$1" pagina="$2" endpoint="$3" tok loc
    shift 3
    pedir "$jar" GET "$pagina" > /dev/null
    tok="$(token_csrf)"
    pedir "$jar" POST "$endpoint" --data-urlencode "csrf=$tok" "$@" > /dev/null
    loc="$(ubicacion)"
    curl -s -o "$TMP/b" -b "$jar" -c "$jar" "$ORIGEN${loc%%#*}"
    plano | grep -o 'role="alert"><strong>[^<]*</strong>[^<]*' | sed 's/.*<\/strong> *//' > "$TMP/flash"
}
flash() { cat "$TMP/flash"; }
flash_tiene() { grep -q -- "$1" "$TMP/flash"; }

# id_de PAGINA NOMBRE → id del botón Editar de esa fila (data-id seguido de data-nombre)
id_de() {
    local jar="$1" pagina="$2" nombre="$3"
    pedir "$jar" GET "$pagina" > /dev/null
    plano | grep -o "data-id=\"[0-9]*\" data-nombre=\"$nombre\"" | head -1 | sed 's/data-id="\([0-9]*\)".*/\1/'
}
# stock_de NOMBRE_INSUMO → stock que muestra inventario.php
stock_de() {
    pedir "$JA" GET inventario.php > /dev/null
    plano | grep -o "data-nombre=\"$1\" [^>]*data-stock=\"[0-9.]*\"" | head -1 | sed 's/.*data-stock="\([0-9.]*\)".*/\1/'
}

echo "CoffeeDesk · pruebas de módulos contra $BASE"
if [ -n "${MYSQL_CMD:-}" ]; then
    echo "DELETE FROM intentos_login;" | $MYSQL_CMD || echo "  (no se pudo vaciar intentos_login)"
fi

JA="$(nuevo_jar)"; login "$JA" admin 'Admin123*'
JM="$(nuevo_jar)"; login "$JM" mesero 'Mesero123*'
pedir "$JA" GET panel.php > /dev/null
cuerpo_tiene 'Panel de administración' || { echo "No se pudo entrar como admin (¿bloqueado? define MYSQL_CMD)"; exit 1; }
pedir "$JM" GET panel.php > /dev/null
cuerpo_tiene 'Panel de atención' || { echo "No se pudo entrar como mesero (¿bloqueado? define MYSQL_CMD)"; exit 1; }

SUF="$RANDOM$RANDOM"
CAT="Categoría prueba $SUF"
INS="Insumo prueba $SUF"
PROD="Producto prueba $SUF"

# ------------------------------------------------------------------------
seccion "Protección de los endpoints (solo POST + CSRF, roles)"
for ep in php/menu/guardar.php php/menu/eliminar.php php/menu/categoria_guardar.php \
          php/menu/categoria_eliminar.php php/menu/categoria_reactivar.php \
          php/menu/receta_guardar.php php/menu/receta_quitar.php \
          php/inventario/guardar.php php/inventario/eliminar.php \
          php/pedidos/registrar.php php/pedidos/estado.php; do
    codigo="$(pedir "$JA" POST "$ep" --data 'nombre=x')"
    loc="$(ubicacion)"
    curl -s -o "$TMP/b" -b "$JA" -c "$JA" "$ORIGEN${loc%%#*}"
    [ "$codigo" = 302 ] && cuerpo_tiene 'La solicitud no pudo verificarse' \
        && ok "$ep sin token CSRF → rechazado con el mensaje común" \
        || fallo "$ep sin token CSRF" "HTTP $codigo"
done
codigo="$(pedir "$JA" GET php/menu/guardar.php)"
[ "$codigo" = 302 ] && ok "GET a php/menu/guardar.php → 302 (solo POST)" || fallo "GET a guardar.php" "HTTP $codigo"
enviar "$JM" menu.php php/menu/guardar.php --data-urlencode "nombre=$PROD" --data-urlencode 'categoria_id=1' --data-urlencode 'precio=1'
flash_tiene 'No tienes permiso' && ok "M-06 mesero no puede crear productos" || fallo "M-06 mesero crea productos" "$(flash)"

# ------------------------------------------------------------------------
seccion "Categorías"
enviar "$JA" categorias.php php/menu/categoria_guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$CAT"
flash_tiene 'se agregó' && cuerpo_tiene "$CAT" && ok "Crear categoría" || fallo "Crear categoría" "$(flash)"
enviar "$JA" categorias.php php/menu/categoria_guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$CAT"
flash_tiene 'Ya existe una categoría' && ok "Categoría repetida → error" || fallo "Categoría repetida" "$(flash)"
ID_CAT="$(id_de "$JA" categorias.php "$CAT")"
[ -n "$ID_CAT" ] && ok "La categoría tiene botón Editar (id $ID_CAT)" || fallo "No se encontró el id de la categoría"
enviar "$JA" categorias.php php/menu/categoria_guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$(printf 'x%.0s' $(seq 1 61))"
flash_tiene '60 caracteres' && ok "Nombre de categoría de 61 caracteres → error" || fallo "Categoría larga" "$(flash)"

# ------------------------------------------------------------------------
seccion "Inventario"
enviar "$JA" inventario.php php/inventario/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$INS" \
    --data-urlencode 'unidad=unidades' --data-urlencode 'stock=100' --data-urlencode 'stock_minimo=10'
flash_tiene 'Insumo registrado' && ok "I-01 crear insumo" || fallo "I-01 crear insumo" "$(flash)"
ID_INS="$(id_de "$JA" inventario.php "$INS")"
[ "$(stock_de "$INS")" = 100 ] && ok "Stock inicial 100" || fallo "Stock inicial" "$(stock_de "$INS")"
enviar "$JA" inventario.php php/inventario/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$INS" \
    --data-urlencode 'unidad=unidades' --data-urlencode 'stock=1' --data-urlencode 'stock_minimo=1'
flash_tiene 'Ya existe un insumo' && ok "Insumo repetido → error" || fallo "Insumo repetido" "$(flash)"
enviar "$JA" inventario.php php/inventario/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=Otro $SUF" \
    --data-urlencode 'unidad=toneladas' --data-urlencode 'stock=1' --data-urlencode 'stock_minimo=1'
flash_tiene 'Unidad' && ok "Unidad no permitida → error" || fallo "Unidad no permitida" "$(flash)"
enviar "$JA" inventario.php php/inventario/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=Otro $SUF" \
    --data-urlencode 'unidad=kg' --data-urlencode 'stock=1.2345' --data-urlencode 'stock_minimo=1'
flash_tiene 'stock debe ser' && ok "Stock con 4 decimales → error" || fallo "Stock con 4 decimales" "$(flash)"

# ------------------------------------------------------------------------
seccion "Menú"
enviar "$JA" menu.php php/menu/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$PROD" \
    --data-urlencode "categoria_id=$ID_CAT" --data-urlencode 'precio=2,5' --data-urlencode 'disponible=1'
flash_tiene 'se agregó' && ok "M-01 crear producto (precio con coma 2,5)" || fallo "M-01 crear producto" "$(flash)"
ID_PROD="$(id_de "$JA" menu.php "$PROD")"
plano | grep -q "data-id=\"$ID_PROD\" data-nombre=\"$PROD\" data-categoria=\"$ID_CAT\" data-precio=\"2.50\"" \
    && ok "El producto se guardó con precio 2.50" || fallo "Precio guardado distinto de 2.50"
for precio in 0 -1 '' 1.234 1000 abc; do
    enviar "$JA" menu.php php/menu/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=Otro $SUF" \
        --data-urlencode "categoria_id=$ID_CAT" --data-urlencode "precio=$precio"
    flash_tiene 'precio' && ok "M-02 precio «$precio» → error" || fallo "M-02 precio «$precio»" "$(flash)"
done
enviar "$JA" menu.php php/menu/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$PROD" \
    --data-urlencode "categoria_id=$ID_CAT" --data-urlencode 'precio=1'
flash_tiene 'Ya existe un producto' && ok "Producto repetido → error" || fallo "Producto repetido" "$(flash)"
enviar "$JA" categorias.php php/menu/categoria_eliminar.php --data-urlencode "id=$ID_CAT"
flash_tiene 'No se puede eliminar' && ok "No se elimina una categoría con productos activos" || fallo "Eliminar categoría con productos" "$(flash)"

# ------------------------------------------------------------------------
seccion "Recetas"
enviar "$JA" "recetas.php?producto=$ID_PROD" php/menu/receta_guardar.php --data-urlencode "producto_id=$ID_PROD" \
    --data-urlencode "insumo_id=$ID_INS" --data-urlencode 'cantidad=3'
flash_tiene 'se guardó en la receta' && cuerpo_tiene "$INS" && ok "Agregar insumo a la receta (3 por unidad)" || fallo "Agregar a la receta" "$(flash)"
enviar "$JA" "recetas.php?producto=$ID_PROD" php/menu/receta_guardar.php --data-urlencode "producto_id=$ID_PROD" \
    --data-urlencode "insumo_id=$ID_INS" --data-urlencode 'cantidad='
flash_tiene 'cantidad' && ok "Cantidad de receta vacía → error" || fallo "Cantidad de receta vacía" "$(flash)"
enviar "$JA" inventario.php php/inventario/eliminar.php --data-urlencode "id=$ID_INS"
flash_tiene 'No se puede eliminar' && ok "I-01 no se elimina un insumo usado en una receta" || fallo "Eliminar insumo en uso" "$(flash)"

# ------------------------------------------------------------------------
seccion "Pedidos y stock"
enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=11' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=1'
flash_tiene 'mesa válida' && ok "Mesa fuera de rango → error" || fallo "Mesa 11" "$(flash)"
for c in 0 -1 21 1.5 x; do
    enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=1' \
        --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode "cantidad[]=$c"
    flash_tiene 'entre 1 y 20' && ok "O-02 cantidad «$c» → error" || fallo "O-02 cantidad «$c»" "$(flash)"
done
enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=1' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=15' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=6'
flash_tiene 'no puede superar 20' && ok "El mismo producto repetido suma más de 20 → error" || fallo "Producto repetido > 20" "$(flash)"

enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=3' --data-urlencode "cliente=Cliente $SUF" \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=1' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=1'
PEDIDO1="$(flash | sed -n 's/.*pedido #\([0-9]*\).*/\1/p')"
[ -n "$PEDIDO1" ] && ok "O-01 pedido registrado (#$PEDIDO1)" || fallo "O-01 registrar pedido" "$(flash)"
plano | grep -q "#$PEDIDO1</th>.*Cliente $SUF</td> *<td class=\"numero\">\$5.00" \
    && ok "O-05 el pedido muestra cliente y total \$5.00 (2 × \$2.50)" || fallo "Total o cliente del pedido"
[ "$(stock_de "$INS")" = 94 ] && ok "O-04 el pedido descontó 2 × 3 = 6 unidades (queda 94)" || fallo "O-04 stock tras el pedido" "$(stock_de "$INS")"

enviar "$JM" pedidos.php php/pedidos/estado.php --data-urlencode "id=$PEDIDO1" --data-urlencode 'estado=anulado'
flash_tiene 'Solo un administrador' && ok "El mesero no puede anular" || fallo "Mesero anula" "$(flash)"

# El admin cambia la receta DESPUÉS de vender: al anular se debe devolver lo que se descontó (6), no 2 × 5
enviar "$JA" "recetas.php?producto=$ID_PROD" php/menu/receta_guardar.php --data-urlencode "producto_id=$ID_PROD" \
    --data-urlencode "insumo_id=$ID_INS" --data-urlencode 'cantidad=5'
enviar "$JA" pedidos.php php/pedidos/estado.php --data-urlencode "id=$PEDIDO1" --data-urlencode 'estado=anulado'
flash_tiene 'fue anulado' && ok "El admin anula el pedido" || fallo "Anular pedido" "$(flash)"
[ "$(stock_de "$INS")" = 100 ] && ok "Anular devuelve lo descontado aunque la receta cambió (vuelve a 100)" \
    || fallo "Stock tras anular con la receta cambiada" "$(stock_de "$INS") (esperado 100)"
enviar "$JA" pedidos.php php/pedidos/estado.php --data-urlencode "id=$PEDIDO1" --data-urlencode 'estado=anulado'
flash_tiene 'ya fue procesado' && [ "$(stock_de "$INS")" = 100 ] && ok "Anular dos veces no devuelve el stock dos veces" || fallo "Doble anulación" "$(flash)"

enviar "$JA" "recetas.php?producto=$ID_PROD" php/menu/receta_guardar.php --data-urlencode "producto_id=$ID_PROD" \
    --data-urlencode "insumo_id=$ID_INS" --data-urlencode 'cantidad=6'
enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=2' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=20'
flash_tiene 'no hay stock suficiente' && [ "$(stock_de "$INS")" = 100 ] \
    && ok "O-03 pedido de 20 × 6 = 120 con stock 100 → error y no descuenta nada" || fallo "O-03 sin stock" "$(flash) · stock $(stock_de "$INS")"

enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=4' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=1'
PEDIDO2="$(flash | sed -n 's/.*pedido #\([0-9]*\).*/\1/p')"
enviar "$JM" pedidos.php php/pedidos/estado.php --data-urlencode "id=$PEDIDO2" --data-urlencode 'estado=entregado'
flash_tiene 'marcado como entregado' && [ "$(stock_de "$INS")" = 94 ] \
    && ok "Entregar un pedido no devuelve stock (queda 94)" || fallo "Entregar pedido" "$(flash) · stock $(stock_de "$INS")"

# Producto agotado: no se puede vender
enviar "$JA" menu.php php/menu/guardar.php --data-urlencode "id=$ID_PROD" --data-urlencode "nombre=$PROD" \
    --data-urlencode "categoria_id=$ID_CAT" --data-urlencode 'precio=2.50'
flash_tiene 'se actualizó' && ok "M-03/M-04 editar producto y marcarlo agotado" || fallo "Editar producto" "$(flash)"
enviar "$JM" pedidos.php php/pedidos/registrar.php --data-urlencode 'mesa=1' \
    --data-urlencode "producto_id[]=$ID_PROD" --data-urlencode 'cantidad[]=1'
flash_tiene 'ya no se encuentran disponibles' && ok "M-04 un producto agotado no se puede pedir" || fallo "Pedir producto agotado" "$(flash)"

# ------------------------------------------------------------------------
seccion "Bajas y reactivaciones"
enviar "$JA" "recetas.php?producto=$ID_PROD" php/menu/receta_quitar.php --data-urlencode "producto_id=$ID_PROD" --data-urlencode "insumo_id=$ID_INS"
flash_tiene 'se quitó de la receta' && ok "Quitar insumo de la receta" || fallo "Quitar de la receta" "$(flash)"
enviar "$JA" menu.php php/menu/eliminar.php --data-urlencode "id=$ID_PROD"
flash_tiene 'se eliminó' && ! cuerpo_tiene "data-nombre=\"$PROD\"" && ok "M-05 eliminar producto" || fallo "M-05 eliminar producto" "$(flash)"
enviar "$JA" menu.php php/menu/guardar.php --data-urlencode 'id=' --data-urlencode "nombre=$PROD" \
    --data-urlencode "categoria_id=$ID_CAT" --data-urlencode 'precio=3' --data-urlencode 'disponible=1'
flash_tiene 'volvió al menú' && ok "Crear un producto eliminado lo reactiva" || fallo "Reactivar producto" "$(flash)"
enviar "$JA" menu.php php/menu/eliminar.php --data-urlencode "id=$ID_PROD"
enviar "$JA" inventario.php php/inventario/eliminar.php --data-urlencode "id=$ID_INS"
flash_tiene 'Insumo eliminado' && ok "I-01 eliminar insumo sin recetas" || fallo "Eliminar insumo" "$(flash)"
enviar "$JA" categorias.php php/menu/categoria_eliminar.php --data-urlencode "id=$ID_CAT"
flash_tiene 'fue eliminada' && ok "Eliminar categoría sin productos" || fallo "Eliminar categoría" "$(flash)"
enviar "$JA" categorias.php php/menu/categoria_reactivar.php --data-urlencode "id=$ID_CAT"
flash_tiene 'reactivada' && ok "Reactivar categoría" || fallo "Reactivar categoría" "$(flash)"
enviar "$JA" categorias.php php/menu/categoria_eliminar.php --data-urlencode "id=$ID_CAT"

echo
echo "Resultado: $PASA correctas, $FALLA fallidas"
[ "$FALLA" -eq 0 ]
