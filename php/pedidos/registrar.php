<?php
/**
 * Registro de pedidos (administrador y mesero).
 *
 * - valida mesa, cliente, productos y cantidades;
 * - toma los precios de MySQL (el total del navegador es solo informativo);
 * - dentro de una transacción: comprueba que los productos sigan a la venta,
 *   guarda el pedido y su detalle y descuenta los insumos de cada receta.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';
require_once __DIR__ . '/../dao/PedidoDAO.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);
exigir_post_con_csrf('pedidos.php');

/**
 * Lee las listas producto_id[] y cantidad[] del formulario y devuelve
 * [producto_id => cantidad]. Agrupa los productos repetidos (Capuchino x2 +
 * Capuchino x1 = Capuchino x3), lo que además respeta el UNIQUE
 * (pedido_id, producto_id) del detalle. Ante cualquier error redirige.
 */
function leer_lineas_pedido(): array
{
    $productos  = $_POST['producto_id'] ?? null;
    $cantidades = $_POST['cantidad'] ?? null;

    if (!is_array($productos) || !is_array($cantidades) || $productos === []) {
        fallar('Debes agregar al menos un producto al pedido.', 'pedidos.php');
    }
    if (count($productos) !== count($cantidades)) {
        fallar('Los datos del pedido están incompletos.', 'pedidos.php');
    }
    if (count($productos) > PEDIDO_MAX_LINEAS) {
        fallar('La cantidad de productos del pedido no es válida.', 'pedidos.php');
    }

    $lineas = [];
    foreach ($productos as $i => $valorProducto) {
        // a_entero() devuelve null también si llega un array anidado
        $productoId = id_valido(a_entero($valorProducto));
        $cantidad   = a_entero($cantidades[$i] ?? null);

        if ($productoId === null) {
            fallar('Debes seleccionar productos válidos.', 'pedidos.php');
        }
        if ($cantidad === null || $cantidad < 1 || $cantidad > PEDIDO_MAX_CANTIDAD) {
            fallar('La cantidad de cada producto debe estar entre 1 y ' . PEDIDO_MAX_CANTIDAD . '.', 'pedidos.php');
        }

        $lineas[$productoId] = ($lineas[$productoId] ?? 0) + $cantidad;
        if ($lineas[$productoId] > PEDIDO_MAX_CANTIDAD) {
            fallar('La cantidad total de un producto no puede superar ' . PEDIDO_MAX_CANTIDAD . '.', 'pedidos.php');
        }
    }
    return $lineas;
}

/**
 * Precio, subtotal y total en centavos con los precios de la BD.
 * Devuelve [lineas del detalle, total en centavos].
 */
function calcular_detalle(array $productosDb, array $lineas): array
{
    $detalle = [];
    $total = 0;
    foreach ($productosDb as $producto) {
        $productoId = (int) $producto['id'];
        $cantidad   = $lineas[$productoId];
        $precio     = precio_a_centavos((string) $producto['precio']);
        $detalle[]  = [
            'producto_id' => $productoId,
            'cantidad'    => $cantidad,
            'precio'      => $precio,
            'subtotal'    => $precio * $cantidad,
        ];
        $total += $precio * $cantidad;
    }
    return [$detalle, $total];
}

// ---- Validación -------------------------------------------------------------
$mesa    = post_entero('mesa');
$cliente = trim(post_texto('cliente'));

if ($mesa === null || $mesa < 1 || $mesa > PEDIDO_MAX_MESAS) {
    fallar('Debes seleccionar una mesa válida.', 'pedidos.php');
}
if (mb_strlen($cliente) > PEDIDO_MAX_CLIENTE) {
    fallar('El nombre del cliente no puede superar los ' . PEDIDO_MAX_CLIENTE . ' caracteres.', 'pedidos.php');
}

$lineas    = leer_lineas_pedido();
$usuarioId = (int) usuario_actual()['id'];

// ---- Registrar ----------------------------------------------------------------
$productos  = new ProductoDAO();
$pedidos    = new PedidoDAO();
$inventario = new InventarioDAO();

try {
    $pedidoId = transaccion(function () use ($mesa, $cliente, $usuarioId, $lineas, $productos, $pedidos, $inventario): int {
        // Dentro de la transacción: un producto marcado como agotado a la vez no se vende
        $productosDb = $productos->obtenerParaVenta(array_keys($lineas));
        [$detalle, $total] = calcular_detalle($productosDb, $lineas);

        $pedidoId = $pedidos->crear($mesa, $cliente !== '' ? $cliente : null, $usuarioId, centavos_a_decimal($total));

        foreach ($detalle as $linea) {
            $pedidos->agregarLinea(
                $pedidoId,
                $linea['producto_id'],
                $linea['cantidad'],
                centavos_a_decimal($linea['precio']),
                centavos_a_decimal($linea['subtotal'])
            );
            $inventario->descontarStock($pedidoId, $linea['producto_id'], $linea['cantidad']);
        }
        return $pedidoId;
    });
} catch (ProductoNoDisponibleException $e) {
    fallar('Uno o más productos ya no se encuentran disponibles para la venta.', 'pedidos.php');
} catch (StockInsuficienteException $e) {
    fallar('No se pudo registrar el pedido: no hay stock suficiente de "' . $e->getInsumo() . '".', 'pedidos.php');
} catch (mysqli_sql_exception $e) {
    // La transacción ya hizo rollback. 1213 (deadlock) y 1205 (espera de bloqueo
    // agotada) pasan cuando dos pedidos usan los mismos insumos a la vez: basta
    // con reintentar. Cualquier otro error lo registra el manejador global.
    if (in_array($e->getCode(), [1205, 1213], true)) {
        error_log('[CoffeeDesk] Pedido no registrado por concurrencia: ' . $e->getMessage());
        fallar('Otro pedido se estaba registrando al mismo tiempo. Inténtalo nuevamente.', 'pedidos.php');
    }
    throw $e;
}

terminar('exito', 'El pedido #' . $pedidoId . ' se registró correctamente.', 'pedidos.php');
