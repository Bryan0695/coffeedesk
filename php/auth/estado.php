<?php
/**
 * Estado de la sesión en JSON, para el JS del frontend.
 *
 * Método: GET
 * 200 → { estado: "exito", datos: { id, nombre, usuario, rol } }
 * 401 → sin sesión o sesión expirada
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder_json('error', 'Método no permitido.', null, 405);
}

requiere_login_api();
$u = usuario_actual();

// Solo lectura: se libera el bloqueo de la sesión para no frenar otras
// peticiones fetch que el navegador haga en paralelo.
session_write_close();

responder_json('exito', 'Sesión activa.', [
    'id'      => $u['id'],
    'nombre'  => $u['nombre'],
    'usuario' => $u['usuario'],
    'rol'     => $u['rol'],
]);
