<?php
/**
 * Crear y editar productos del menú.
 *
 * Solo los administradores pueden realizar esta operación.
 *
 * Si "id" viene vacío:
 *     INSERT -> producto nuevo.
 *
 * Si "id" contiene un entero válido:
 *     UPDATE -> producto existente.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../conexion.php';

// -----------------------------------------------------------------------------
// Seguridad
// -----------------------------------------------------------------------------

requiere_rol(ROL_ADMIN);

// Este archivo solamente acepta peticiones POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mensaje_flash('error', 'La operación solicitada no es válida.');
    redirigir('menu.php');
}

// Validación CSRF.
if (!csrf_valido(post_texto('csrf'))) {
    mensaje_flash(
        'error',
        'La solicitud no pudo verificarse. Recarga la página e inténtalo nuevamente.'
    );
    redirigir('menu.php');
}

// -----------------------------------------------------------------------------
// Obtener datos
// -----------------------------------------------------------------------------

$idTexto = trim(post_texto('id'));
$nombre = trim(post_texto('nombre'));
$categoriaId = post_entero('categoria_id');
$precioTexto = trim(post_texto('precio'));
$disponible = isset($_POST['disponible']) ? 1 : 0; // Un checkbox no marcado no se envía en POST.

$esEdicion = $idTexto !== ''; // Determinamos si estamos creando o editando.

$id = null;

if ($esEdicion) {
    $id = post_entero('id');
}

// -----------------------------------------------------------------------------
// Validaciones
// -----------------------------------------------------------------------------

$errores = [];

// ----- ID --------------------------------------------------------------------

if ($esEdicion && ($id === null || $id <= 0)) {
    $errores[] = 'El identificador del producto no es válido.';
}

// ----- Nombre ----------------------------------------------------------------

if ($nombre === '') {
    $errores[] = 'El nombre del producto es obligatorio.';
} elseif (mb_strlen($nombre) > 80) {
    $errores[] = 'El nombre del producto no puede superar los 80 caracteres.';
}

// ----- Categoría -------------------------------------------------------------

if ($categoriaId === null || $categoriaId <= 0) {
    $errores[] = 'Debes seleccionar una categoría válida.';
}

// ----- Precio ----------------------------------------------------------------

// Permitimos:
// 2
// 2.5
// 2.50
//
// También normalizamos coma por punto por seguridad.
$precioTexto = str_replace(',', '.', $precioTexto);

if (
    $precioTexto === ''
    || !preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $precioTexto)
) {
    $errores[] = 'El precio debe ser un número válido entre 0.01 y 999.99.';
}

// Trabajamos primero en centavos para evitar errores de punto flotante.
$precioCentavos = null;

if (
    $precioTexto !== ''
    && preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $precioTexto)
) {
    [$entero, $decimales] = array_pad(explode('.', $precioTexto, 2),2,'');

    $decimales = str_pad($decimales, 2, '0');

    $precioCentavos =
        ((int) $entero * 100)
        + (int) substr($decimales, 0, 2);

    if ($precioCentavos < 1 || $precioCentavos > 99999) {
        $errores[] = 'El precio debe estar entre 0.01 y 999.99.';
    }
}

// -----------------------------------------------------------------------------
// Si ya existen errores básicos, no consultamos ni modificamos la BD.
// -----------------------------------------------------------------------------

if ($errores) {
    mensaje_flash('error', $errores[0]);
    redirigir('menu.php#form-producto');
}

// -----------------------------------------------------------------------------
// Validar que la categoría exista y esté activa
// -----------------------------------------------------------------------------

$categoria = consultar_uno(
    'SELECT id, nombre
     FROM categorias
     WHERE id = ?
     AND activo = 1
     LIMIT 1',
    [$categoriaId]
);

if ($categoria === null) {
    mensaje_flash(
        'error',
        'La categoría seleccionada no existe o ya no está activa.'
    );

    redirigir('menu.php#form-producto');
}

// -----------------------------------------------------------------------------
// Si estamos editando, comprobar que el producto exista.
// -----------------------------------------------------------------------------

if ($esEdicion) {
    $productoExistente = consultar_uno(
        'SELECT id, nombre
         FROM productos
         WHERE id = ?
         LIMIT 1',
        [$id]
    );

    if ($productoExistente === null) {
        mensaje_flash(
            'error',
            'El producto que intentas editar no existe o fue eliminado.'
        );

        redirigir('menu.php');
    }
}

// -----------------------------------------------------------------------------
// Evitar productos con el mismo nombre
// -----------------------------------------------------------------------------

if ($esEdicion) {
    $productoDuplicado = consultar_uno(
        'SELECT id
         FROM productos
         WHERE nombre = ?
           AND id <> ?
         LIMIT 1',
        [$nombre, $id]
    );
} else {
    $productoDuplicado = consultar_uno(
        'SELECT id
         FROM productos
         WHERE nombre = ?
         LIMIT 1',
        [$nombre]
    );
}

if ($productoDuplicado !== null) {
    mensaje_flash(
        'error',
        'Ya existe un producto registrado con ese nombre.'
    );

    redirigir('menu.php#form-producto');
}

// -----------------------------------------------------------------------------
// Convertir los centavos nuevamente al formato DECIMAL que utiliza MySQL.
//
// 250 -> "2.50"
// 375 -> "3.75"
// -----------------------------------------------------------------------------

$precio = number_format($precioCentavos / 100,2,'.','');

// -----------------------------------------------------------------------------
// Guardar
// -----------------------------------------------------------------------------

try {

    // -------------------------------------------------------------------------
    // EDITAR
    // -------------------------------------------------------------------------

    if ($esEdicion) {

        ejecutar(
            'UPDATE productos
             SET nombre = ?,
                 categoria_id = ?,
                 precio = ?,
                 disponible = ?
             WHERE id = ?',
            [
                $nombre,
                $categoriaId,
                $precio,
                $disponible,
                $id
            ]
        );

        mensaje_flash(
            'exito',
            'El producto se actualizó correctamente.'
        );

        redirigir('menu.php');
    }

    // -------------------------------------------------------------------------
    // CREAR
    // -------------------------------------------------------------------------

    insertar(
        'INSERT INTO productos (
            categoria_id,
            nombre,
            precio,
            disponible
         )
         VALUES (?, ?, ?, ?)',
        [
            $categoriaId,
            $nombre,
            $precio,
            $disponible
        ]
    );

    mensaje_flash(
        'exito',
        'El producto se agregó correctamente.'
    );

    redirigir('menu.php');

} catch (mysqli_sql_exception $e) {

    /*
     * 1062 = Duplicate entry.
     *
     * Aunque previamente comprobamos duplicados, la restricción UNIQUE
     * de MySQL sigue siendo la protección definitiva ante dos solicitudes
     * concurrentes.
     */
    if ((int) $e->getCode() === 1062) {

        mensaje_flash(
            'error',
            'Ya existe un producto registrado con ese nombre.'
        );

        redirigir('menu.php#form-producto');
    }

    // Los demás errores continúan hacia el manejador global.
    throw $e;
}