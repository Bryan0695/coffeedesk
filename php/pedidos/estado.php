<?php
/**
 * Cambio de estado de pedidos.
 *
 * Estados permitidos:
 * pendiente -> entregado
 * pendiente -> anulado
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

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

// Datos
$id             = post_entero('id');
$nuevoEstado    = trim(post_texto('estado'));

// Validar ID
if ($id === null || $id <= 0) {

    mensaje_flash(
        'error',
        'El identificador del pedido no es válido.'
    );

    redirigir('pedidos.php');
}

// Estados permitidos desde la interfaz

$estadosPermitidos = [
    'entregado',
    'anulado',
];

if (!in_array($nuevoEstado, $estadosPermitidos, true)) {

    mensaje_flash(
        'error',
        'El estado solicitado no es válido.'
    );

    redirigir('pedidos.php');
}

// Validación del estado anular en pedido. Solo admin puede anualr
if ($nuevoEstado === 'anulado' && !es_admin()) {

    mensaje_flash(
        'error',
        'Solo un administrador puede anular pedidos.'
    );

    redirigir('pedidos.php');
}

// Consultar pedido

$pedido = consultar_uno(
    'SELECT id, estado
     FROM pedidos
     WHERE id = ?
     LIMIT 1',
    [$id]
);

if ($pedido === null) {

    mensaje_flash(
        'error',
        'El pedido no existe.'
    );

    redirigir('pedidos.php');
}

// Solo los pedidos pendientes pueden cambiar de estado

if ($pedido['estado'] !== 'pendiente') {

    mensaje_flash(
        'error',
        'Este pedido ya fue procesado y no puede cambiar de estado.'
    );

    redirigir('pedidos.php');
}

// Actualizar. Al anular, los insumos descontados al registrar el pedido se
// devuelven en la misma transacción (si algo falla, no cambia nada).

$inventarioDAO = new InventarioDAO();

$cambiado = transaccion(
    function () use ($id, $nuevoEstado, $inventarioDAO): bool {

        // El AND estado = 'pendiente' evita devolver el stock dos veces
        // si dos personas anulan el mismo pedido a la vez.
        $filas = ejecutar(
            'UPDATE pedidos
             SET estado = ?
             WHERE id = ?
               AND estado = ?',
            [
                $nuevoEstado,
                $id,
                'pendiente'
            ]
        );

        if ($filas !== 1) {
            return false;
        }

        if ($nuevoEstado === 'anulado') {
            $lineas = consultar(
                'SELECT producto_id, cantidad
                 FROM pedido_detalle
                 WHERE pedido_id = ?',
                [$id]
            );

            foreach ($lineas as $linea) {
                $inventarioDAO->reponerStock(
                    (int) $linea['producto_id'],
                    (int) $linea['cantidad']
                );
            }
        }

        return true;
    }
);

if (!$cambiado) {

    mensaje_flash(
        'error',
        'Este pedido ya fue procesado y no puede cambiar de estado.'
    );

    redirigir('pedidos.php');
}

// Mensaje

if ($nuevoEstado === 'entregado') {

    mensaje_flash(
        'exito',
        'El pedido #' . $id . ' fue marcado como entregado.'
    );

} else {

    mensaje_flash(
        'exito',
        'El pedido #' . $id . ' fue anulado y su stock se devolvió al inventario.'
    );
}

redirigir('pedidos.php');