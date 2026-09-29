<?php
/**
 * Baja lógica de productos del menú.
 *
 * Solo los administradores pueden realizar esta operación.
 * No elimina físicamente el registro:
 *
 *     activo = 0
 *
 * De esta manera se preservan las referencias históricas
 * que puedan existir desde pedido_detalle.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';

// Seguridad
requiere_rol(ROL_ADMIN);

// Solo aceptamos POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mensaje_flash(
        'error',
        'La operación solicitada no es válida.'
    );

    redirigir('menu.php');
}

// Validar CSRF.
if (!csrf_valido(post_texto('csrf'))) {
    mensaje_flash(
        'error',
        'La solicitud no pudo verificarse. Recarga la página e inténtalo nuevamente.'
    );

    redirigir('menu.php');
}

// Obtener y validar ID
$id = post_entero('id');

if ($id === null || $id <= 0) {
    mensaje_flash(
        'error',
        'El identificador del producto no es válido.'
    );

    redirigir('menu.php');
}

// Verificar que el producto exista y siga activo

$producto = consultar_uno(
    'SELECT id, nombre, activo
     FROM productos
     WHERE id = ?
     LIMIT 1',
    [$id]
);

if ($producto === null) {
    mensaje_flash(
        'error',
        'El producto no existe o ya fue eliminado.'
    );

    redirigir('menu.php');
}

if ((int) $producto['activo'] === 0) {

    mensaje_flash(
        'aviso',
        'El producto ya se encuentra eliminado.'
    );

    redirigir('menu.php');
}

// Baja lógica

try {

    ejecutar(
        'UPDATE productos
         SET    activo = 0,
                disponible = 0
         WHERE id = ?
         AND activo = 1',
        [$id]
    );

    mensaje_flash(
        'exito',
        'El producto "' . $producto['nombre'] . '" se eliminó correctamente.'
    );

    redirigir('menu.php');

} catch (mysqli_sql_exception $e) {

    // Dejamos que el manejador global registre el error real.
    throw $e;
}