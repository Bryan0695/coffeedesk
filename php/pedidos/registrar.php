<?php
/**
 * Registro de pedidos.
 *
 * Funciones:
 * - valida sesión y rol;
 * - valida CSRF;
 * - valida mesa, cliente, productos y cantidades;
 * - consulta precios reales en MySQL;
 * - calcula subtotal y total en servidor;
 * - registra pedido y detalle dentro de una transacción.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mensaje_flash(
        'error',
        'La operación solicitada no es válida.'
    );

    redirigir('pedidos.php');
}

// CSRF
if (!csrf_valido(post_texto('csrf'))) {
    mensaje_flash(
        'error',
        'La solicitud no pudo verificarse. Recarga la página e inténtalo nuevamente.'
    );

    redirigir('pedidos.php');
}


// Configuración
$maxMesas = 10;
$maxCantidad = 20;
$maxLineas = 50;

// Datos generales
$mesa = post_entero('mesa');
$cliente = trim(post_texto('cliente'));

// Validar mesa
if ($mesa === null || $mesa < 1 || $mesa > $maxMesas) {
    mensaje_flash(
        'error',
        'Debes seleccionar una mesa válida.'
    );

    redirigir('pedidos.php');
}

// Validar cliente\
if (mb_strlen($cliente) > 60) {
    mensaje_flash(
        'error',
        'El nombre del cliente no puede superar los 60 caracteres.'
    );

    redirigir('pedidos.php');
}

// En la base puede guardarse como NULL si quedó vacío.
$clienteDb = $cliente !== ''
    ? $cliente
    : null;


// Obtener arrays del formulario
$productosPost = $_POST['producto_id'] ?? null;
$cantidadesPost = $_POST['cantidad'] ?? null;

if (
    !is_array($productosPost)
    || !is_array($cantidadesPost)
) {
    mensaje_flash(
        'error',
        'Debes agregar al menos un producto al pedido.'
    );

    redirigir('pedidos.php');
}

// Deben llegar la misma cantidad de productos y cantidades.
if (count($productosPost) !== count($cantidadesPost)) {
    mensaje_flash(
        'error',
        'Los datos del pedido están incompletos.'
    );

    redirigir('pedidos.php');
}

if (
    count($productosPost) === 0
    || count($productosPost) > $maxLineas
) {
    mensaje_flash(
        'error',
        'La cantidad de productos del pedido no es válida.'
    );

    redirigir('pedidos.php');
}

// Convertir y validar líneas
$lineas = [];

/*
 * Vamos a agrupar productos repetidos.
 * Ejemplo:
 * Capuchino x2
 * Capuchino x1

 * se convierte en:
 * Capuchino x3
 *
 * Esto también evita problemas con:
 * UNIQUE (pedido_id, producto_id)
 */

foreach ($productosPost as $indice => $productoValor) {

    $cantidadValor = $cantidadesPost[$indice] ?? null;

    // Un atacante podría enviar arrays anidados.
    if (
        !is_string($productoValor)
        || !is_string($cantidadValor)
    ) {
        mensaje_flash(
            'error',
            'Uno de los productos enviados no es válido.'
        );

        redirigir('pedidos.php');
    }

    $productoId = filter_var(
        trim($productoValor),
        FILTER_VALIDATE_INT
    );

    $cantidad = filter_var(
        trim($cantidadValor),
        FILTER_VALIDATE_INT
    );

    if ($productoId === false || $productoId <= 0) {
        mensaje_flash(
            'error',
            'Debes seleccionar productos válidos.'
        );

        redirigir('pedidos.php');
    }

    if (
        $cantidad === false
        || $cantidad < 1
        || $cantidad > $maxCantidad
    ) {
        mensaje_flash(
            'error',
            'La cantidad de cada producto debe estar entre 1 y '
            . $maxCantidad
            . '.'
        );

        redirigir('pedidos.php');
    }

    $productoId = (int) $productoId;
    $cantidad = (int) $cantidad;

    if (!isset($lineas[$productoId])) {
        $lineas[$productoId] = 0;
    }

    $lineas[$productoId] += $cantidad;

    // Evitamos que un producto repetido supere el máximo.
    if ($lineas[$productoId] > $maxCantidad) {
        mensaje_flash(
            'error',
            'La cantidad total de un producto no puede superar '
            . $maxCantidad
            . '.'
        );

        redirigir('pedidos.php');
    }
}

if ($lineas === []) {
    mensaje_flash(
        'error',
        'Debes agregar al menos un producto al pedido.'
    );

    redirigir('pedidos.php');
}

// Consultar productos reales
/*
 * No usamos los precios enviados por JavaScript.
 * Construimos:
 * WHERE p.id IN (?, ?, ?)
 */

$idsProductos = array_keys($lineas);

$marcadores = implode(
    ', ',
    array_fill(0, count($idsProductos), '?')
);

$productosDb = consultar(
    'SELECT
        p.id,
        p.nombre,
        p.precio,
        p.disponible
     FROM productos AS p
     INNER JOIN categorias AS c
        ON c.id = p.categoria_id
     WHERE p.id IN (' . $marcadores . ')
       AND p.disponible = 1
       AND p.activo = 1
       AND c.activo = 1',
    $idsProductos
);

// Todos los productos enviados deben seguir disponibles.
if (count($productosDb) !== count($idsProductos)) {
    mensaje_flash(
        'error',
        'Uno o más productos ya no se encuentran disponibles para la venta.'
    );

    redirigir('pedidos.php');
}

// Función auxiliar para convertir DECIMAL de MySQL a centavos
function precio_a_centavos(string $precio): int
{
    $partes = explode('.', $precio, 2);
    $entero = (int) ($partes[0] ?? '0');
    $decimales = $partes[1] ?? '';

    $decimales = str_pad(substr($decimales, 0, 2),2,'0');

    return ($entero * 100) + (int) $decimales;
}

// Preparar detalle y calcular total
$detalle = [];
$totalCentavos = 0;

foreach ($productosDb as $producto) {

    $productoId         = (int) $producto['id'];
    $cantidad           = $lineas[$productoId];
    $precioCentavos     = precio_a_centavos((string) $producto['precio']);
    $subtotalCentavos   = $precioCentavos * $cantidad;
    $totalCentavos += $subtotalCentavos;

    $detalle[] = [
        'producto_id'       => $productoId,
        'nombre'            => $producto['nombre'],
        'cantidad'          => $cantidad,
        'precio_centavos'   => $precioCentavos,
        'subtotal_centavos' => $subtotalCentavos,
    ];
}

// Protección adicional.
if ($totalCentavos <= 0) {
    mensaje_flash(
        'error',
        'No fue posible calcular correctamente el total del pedido.'
    );

    redirigir('pedidos.php');
}

// Usuario que registra el pedido
$usuario = usuario_actual();

if ($usuario === null) {
    mensaje_flash(
        'error',
        'La sesión ya no se encuentra disponible.'
    );

    redirigir('index.php');
}

$usuarioId = (int) $usuario['id'];

// Convertir total a DECIMAL
$totalDb = number_format(
    $totalCentavos / 100,
    2,
    '.',
    ''
);

// Registrar pedido + detalle
try {

    $pedidoId = transaccion(
        function () use (
            $mesa,
            $clienteDb,
            $usuarioId,
            $totalDb,
            $detalle
        ) {

            // Cabecera del pedido
            $pedidoId = insertar(
                'INSERT INTO pedidos (
                    mesa,
                    cliente,
                    registrado_por,
                    estado,
                    total
                 )
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $mesa,
                    $clienteDb,
                    $usuarioId,
                    'pendiente',
                    $totalDb
                ]
            );

            // Detalle
            foreach ($detalle as $linea) {

                $precioDb = number_format($linea['precio_centavos'] / 100,2,'.','');
                $subtotalDb = number_format($linea['subtotal_centavos'] / 100,2,'.','');

                insertar(
                    'INSERT INTO pedido_detalle (
                        pedido_id,
                        producto_id,
                        cantidad,
                        precio_unitario,
                        subtotal
                     )
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $pedidoId,
                        $linea['producto_id'],
                        $linea['cantidad'],
                        $precioDb,
                        $subtotalDb
                    ]
                );
            }

            return $pedidoId;
        }
    );

} catch (mysqli_sql_exception $e) {

    /*
     * Puede ocurrir, por ejemplo, si un producto deja de existir
     * entre la consulta y el INSERT.
     * La transacción ya habrá realizado rollback.
     */
    throw $e;
}

// Resultado final 
mensaje_flash(
    'exito',
    'El pedido #' . $pedidoId . ' se registró correctamente.'
);

redirigir('pedidos.php');