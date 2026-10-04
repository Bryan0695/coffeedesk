<?php
/**
 * Crear, editar y reactivar insumos (solo administrador).
 *
 * id vacío:      crea el insumo o, si hay uno eliminado con el mismo nombre, lo reactiva.
 * id con valor:  edita un insumo activo.
 *
 * Responsable: Jeremy
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN);
exigir_post_con_csrf('inventario.php');

$volverAlForm = 'inventario.php#form-insumo';

$esEdicion     = trim(post_texto('id')) !== '';
$id            = $esEdicion ? post_id('id') : null;
$nombre        = trim(post_texto('nombre'));
$unidad        = trim(post_texto('unidad'));
$stock         = trim(post_texto('stock'));
$stockMinimo   = trim(post_texto('stock_minimo'));
$stockOriginal = trim(post_texto('stock_original'));

// ---- Validación -------------------------------------------------------------
if ($esEdicion && $id === null) {
    fallar('El identificador del insumo no es válido.', $volverAlForm);
}
if (!largo_valido($nombre, NOMBRE_MAX_INSUMO)) {
    fallar('El nombre debe tener entre 1 y ' . NOMBRE_MAX_INSUMO . ' caracteres.', $volverAlForm);
}
if (!in_array($unidad, UNIDADES_INSUMO, true)) {
    fallar('Unidad inválida.', $volverAlForm);
}
if (!cantidad_valida($stock)) {
    fallar('El stock debe ser un número entre 0 y 99999 con hasta 3 decimales.', $volverAlForm);
}
if (!cantidad_valida($stockMinimo)) {
    fallar('El stock mínimo debe ser un número entre 0 y 99999 con hasta 3 decimales.', $volverAlForm);
}
if ($esEdicion && !cantidad_valida($stockOriginal)) {
    fallar('Recarga la página y vuelve a editar el insumo.', $volverAlForm);
}

$dao = new InventarioDAO();

if ($esEdicion) {
    $actual = $dao->obtenerPorId($id);
    if ($actual === null || (int) $actual['activo'] !== 1) {
        fallar('El insumo que intentas editar no existe o fue eliminado.', 'inventario.php');
    }
}

// ---- Nombre repetido (el UNIQUE de la tabla incluye a los eliminados) --------
$existente = $dao->obtenerPorNombre($nombre, $id ?? 0);

if ($existente !== null && (int) $existente['activo'] === 1) {
    fallar('Ya existe un insumo con ese nombre.', $volverAlForm);
}
if ($existente !== null && $esEdicion) {
    fallar('Ese nombre pertenece a un insumo eliminado; créalo de nuevo para reactivarlo.', $volverAlForm);
}

// ---- Guardar ------------------------------------------------------------------
$insumo = new Insumo($nombre, $unidad, $stock, $stockMinimo, $id);

try {
    if ($esEdicion) {
        if (!$dao->actualizar($insumo, $stockOriginal)) {
            $ahora = $dao->obtenerPorId($id);
            fallar('El stock de "' . $nombre . '" cambió mientras editabas (ahora hay '
                . cantidad((string) $ahora['stock']) . ' ' . $ahora['unidad'] . '). Revisa el valor y vuelve a guardar.',
                'inventario.php');
        }
        terminar('exito', mensaje_edicion($actual['stock'], $stock, $stockOriginal, $unidad), 'inventario.php');
    }
    if ($existente !== null) {
        $dao->reactivar((int) $existente['id'], $insumo);
        terminar('exito', 'El insumo "' . $nombre . '" volvió al inventario.', 'inventario.php');
    }
    $dao->crear($insumo);
    terminar('exito', 'Insumo registrado.', 'inventario.php');
} catch (mysqli_sql_exception $e) {
    // 1062 = nombre duplicado: dos administradores guardaron el mismo nombre a la vez
    if ($e->getCode() === 1062) {
        fallar('Ya existe un insumo con ese nombre.', $volverAlForm);
    }
    throw $e;
}

/**
 * Si hubo ventas o anulaciones mientras se editaba (el stock en la BD ya no
 * era el que vio el administrador), se avisa que se aplicó solo la diferencia.
 */
function mensaje_edicion(string $stockEnBd, string $stockNuevo, string $stockOriginal, string $unidad): string
{
    if ((float) $stockEnBd === (float) $stockOriginal || (float) $stockNuevo === (float) $stockOriginal) {
        return 'Insumo actualizado.';
    }
    $diferencia = (float) $stockNuevo - (float) $stockOriginal;
    return 'Insumo actualizado. Hubo movimientos mientras editabas, así que se '
        . ($diferencia > 0 ? 'sumaron ' : 'restaron ') . cantidad((string) abs($diferencia))
        . ' ' . $unidad . ' al stock actual en lugar de reemplazarlo.';
}
