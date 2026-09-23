<?php
/**
 * Procesa el formulario de inicio de sesión.
 *
 * Método: POST
 * Campos: usuario, clave, csrf
 * Éxito:  redirige a panel.php
 * Error:  redirige a index.php con mensaje
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/limite_intentos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

// 1. CSRF
if (!csrf_valido(post_texto('csrf'))) {
    mensaje_flash('error', 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.');
    redirigir('index.php');
}

// 2. Validación en el servidor (post_texto: un campo enviado como lista llega como '')
$usuario = trim(post_texto('usuario'));
$clave   = post_texto('clave');
$errores = [];

if ($usuario === '') {
    $errores[] = 'El usuario es obligatorio.';
} elseif (!preg_match('/^' . PATRON_USUARIO . '$/', $usuario)) {
    $errores[] = 'El usuario solo admite letras, números, punto, guion y guion bajo (3 a 30 caracteres).';
}
if ($clave === '') {
    $errores[] = 'La contraseña es obligatoria.';
}

if ($errores) {
    $_SESSION['login_usuario_previo'] = $usuario; // para no volver a escribirlo
    mensaje_flash('error', implode(' ', $errores));
    redirigir('index.php');
}

// 3. Límite de intentos fallidos por usuario e IP (F-002). Va antes de
//    consultar al usuario: al superarlo, ni la clave correcta entra.
$ip = ip_cliente();
if (login_bloqueado($usuario, $ip)) {
    $_SESSION['login_usuario_previo'] = $usuario;
    mensaje_flash('error', 'Demasiados intentos fallidos. Espera ' . LOGIN_MINUTOS_BLOQUEO . ' minuto(s).');
    redirigir('index.php');
}

// 4. Buscar al usuario (consulta preparada)
$fila = consultar_uno(
    'SELECT u.id, u.nombre, u.usuario, u.clave_hash, u.activo, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.rol_id
     WHERE u.usuario = ?
     LIMIT 1',
    [$usuario]
);

// 5. Verificar contraseña cifrada. Siempre se ejecuta password_verify (con un
//    hash ficticio si el usuario no existe) para que el tiempo de respuesta no
//    revele qué usuarios existen (F-003).
$credencialesOk = password_verify($clave, $fila['clave_hash'] ?? HASH_FICTICIO) && $fila !== null;

if (!$credencialesOk) {
    registrar_intento_fallido($usuario, $ip);
    $_SESSION['login_usuario_previo'] = $usuario;
    // Mensaje genérico: no revela si el usuario existe
    mensaje_flash('error', 'Usuario o contraseña incorrectos.');
    redirigir('index.php');
}

if ((int) $fila['activo'] !== 1) {
    mensaje_flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
    redirigir('index.php');
}

// 6. Actualizar el hash si cambió el algoritmo o el coste (coste fijo, F-010)
if (password_needs_rehash($fila['clave_hash'], PASSWORD_DEFAULT, HASH_OPCIONES)) {
    ejecutar(
        'UPDATE usuarios SET clave_hash = ? WHERE id = ?',
        [password_hash($clave, PASSWORD_DEFAULT, HASH_OPCIONES), (int) $fila['id']]
    );
}

// 7. Registrar último acceso y limpiar los intentos fallidos
ejecutar('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?', [(int) $fila['id']]);
limpiar_intentos($usuario);

// 8. Abrir sesión
unset($_SESSION['login_usuario_previo']);
abrir_sesion_usuario($fila);
mensaje_flash('exito', 'Bienvenido, ' . $fila['nombre'] . '.');
redirigir('panel.php');
