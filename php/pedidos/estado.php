<?php
/**
 * Cambio de estado de pedidos. Solo se procesan pedidos pendientes:
 *   pendiente → entregado   (administrador o mesero)
 *   pendiente → anulado     (solo administrador; devuelve el stock descontado)
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/PedidoDAO.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);
exigir_post_con_csrf('pedidos.php');

$id          = post_id('id');
$nuevoEstado = trim(post_texto('estado'));

if ($id === null) {
    fallar('El identificador del pedido no es válido.', 'pedidos.php');
}
if (!in_array($nuevoEstado, [ESTADO_ENTREGADO, ESTADO_ANULADO], true)) {
    fallar('El estado solicitado no es válido.', 'pedidos.php');
}
if ($nuevoEstado === ESTADO_ANULADO && !es_admin()) {
    fallar('Solo un administrador puede anular pedidos.', 'pedidos.php');
}

$pedidos = new PedidoDAO();

$pedido = $pedidos->obtenerPorId($id);
if ($pedido === null) {
    fallar('El pedido no existe.', 'pedidos.php');
}
if ($pedido['estado'] !== ESTADO_PENDIENTE) {
    fallar('Este pedido ya fue procesado y no puede cambiar de estado.', 'pedidos.php');
}

// Al anular, el stock se devuelve en la misma transacción (si algo falla, no cambia nada)
$inventario = new InventarioDAO();
try {
    $cambiado = transaccion(function () use ($id, $nuevoEstado, $pedidos, $inventario): bool {
        if (!$pedidos->cambiarEstado($id, $nuevoEstado)) {
            return false;
        }
        if ($nuevoEstado === ESTADO_ANULADO) {
            $inventario->reponerStock($id);
        }
        return true;
    });
} catch (mysqli_sql_exception $e) {
    // 1213/1205: un pedido se registraba a la vez con los mismos insumos; basta con reintentar
    if (in_array($e->getCode(), [1205, 1213], true)) {
        error_log('[CoffeeDesk] Estado de pedido no cambiado por concurrencia: ' . $e->getMessage());
        fallar('Otro pedido se estaba registrando al mismo tiempo. Inténtalo nuevamente.', 'pedidos.php');
    }
    throw $e;
}

if (!$cambiado) {
    fallar('Este pedido ya fue procesado y no puede cambiar de estado.', 'pedidos.php');
}

terminar('exito', $nuevoEstado === ESTADO_ENTREGADO
    ? 'El pedido #' . $id . ' fue marcado como entregado.'
    : 'El pedido #' . $id . ' fue anulado y su stock se devolvió al inventario.', 'pedidos.php');
