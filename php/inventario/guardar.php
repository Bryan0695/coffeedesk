<?php
/**
 * Crear, editar y reactivar insumos.
 *
 * id vacío:
 *   - crea un insumo nuevo;
 *   - o reactiva uno eliminado con el mismo nombre (el nombre es UNIQUE).
 *
 * id con valor:
 *   - edita un insumo activo existente.
 *
 * Responsable: Jeremy
 */

require_once __DIR__ . '/../auth/sesion.php';
require_once __DIR__ . '/../dao/InventarioDAO.php';
require_once __DIR__ . '/../models/Insumo.php';

requiere_rol(ROL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido(post_texto('csrf'))) {
    mensaje_flash('error', 'Solicitud no válida.');
    redirigir('inventario.php');
}

$idTexto     = trim(post_texto('id'));
$nombre      = trim(post_texto('nombre'));
$unidad      = trim(post_texto('unidad'));
$stock       = trim(post_texto('stock'));
$stockMinimo = trim(post_texto('stock_minimo'));

$esEdicion = $idTexto !== '';
$id        = $esEdicion ? post_entero('id') : null;

$dao = new InventarioDAO();

// ---- Validación -------------------------------------------------------------
$errores = [];

if ($esEdicion && ($id === null || $id <= 0)) {
    $errores[] = 'El identificador del insumo no es válido.';
}
if (mb_strlen($nombre) < 1 || mb_strlen($nombre) > 80) {
    $errores[] = 'El nombre debe tener entre 1 y 80 caracteres.';
}
if (!in_array($unidad, ['unidades', 'kg', 'g', 'litros', 'ml'], true)) {
    $errores[] = 'Unidad inválida.';
}
if (!is_numeric($stock) || (float) $stock < 0 || (float) $stock > 99999) {
    $errores[] = 'El stock debe ser un número entre 0 y 99999.';
}
if (!is_numeric($stockMinimo) || (float) $stockMinimo < 0 || (float) $stockMinimo > 99999) {
    $errores[] = 'El stock mínimo debe ser un número entre 0 y 99999.';
}

if ($errores) {
    mensaje_flash('error', $errores[0]);
    redirigir('inventario.php#form-insumo');
}

// ---- Editar: el insumo debe existir y estar activo ---------------------------
if ($esEdicion) {
    $actual = $dao->obtenerPorId($id);
    if ($actual === null || (int) $actual['activo'] !== 1) {
        mensaje_flash('error', 'El insumo que intentas editar no existe o fue eliminado.');
        redirigir('inventario.php');
    }
}

// ---- Nombre repetido (el UNIQUE de la tabla incluye a los eliminados) --------
$existente = $dao->obtenerPorNombre($nombre, $id ?? 0);

if ($existente !== null && (int) $existente['activo'] === 1) {
    mensaje_flash('error', 'Ya existe un insumo con ese nombre.');
    redirigir('inventario.php#form-insumo');
}
if ($existente !== null && $esEdicion) {
    mensaje_flash('error', 'Ese nombre pertenece a un insumo eliminado; créalo de nuevo para reactivarlo.');
    redirigir('inventario.php#form-insumo');
}

// ---- Guardar ------------------------------------------------------------------
$insumo = new Insumo();
$insumo->setNombre($nombre);
$insumo->setUnidad($unidad);
$insumo->setStock((float) $stock);
$insumo->setStockMinimo((float) $stockMinimo);
$insumo->setActivo(1);

if ($esEdicion) {
    $insumo->setId($id);
    $dao->actualizar($insumo);
    mensaje_flash('exito', 'Insumo actualizado.');
} elseif ($existente !== null) {
    $dao->reactivar((int) $existente['id'], $insumo);
    mensaje_flash('exito', 'El insumo "' . $nombre . '" volvió al inventario.');
} else {
    $dao->crear($insumo);
    mensaje_flash('exito', 'Insumo registrado.');
}

redirigir('inventario.php');
