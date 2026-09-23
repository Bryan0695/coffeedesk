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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

// 1. CSRF
if (!csrf_valido($_POST['csrf'] ?? null)) {
    mensaje_flash('error', 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.');
    redirigir('index.php');
}

// 2. Bloqueo temporal por intentos fallidos
$bloqueadoHasta = $_SESSION['login_bloqueado_hasta'] ?? 0;
if ($bloqueadoHasta > time()) {
    $min = (int) ceil(($bloqueadoHasta - time()) / 60);
    mensaje_flash('error', "Demasiados intentos fallidos. Espera {$min} minuto(s).");
    redirigir('index.php');
}

// 3. Validación en el servidor
$usuario = trim($_POST['usuario'] ?? '');
$clave   = $_POST['clave'] ?? '';
$errores = [];

if ($usuario === '') {
    $errores[] = 'El usuario es obligatorio.';
} elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $usuario)) {
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

// 4. Buscar al usuario (consulta preparada)
$db = conectar();
$sql = 'SELECT u.id, u.nombre, u.usuario, u.clave_hash, u.activo, r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON r.id = u.rol_id
        WHERE u.usuario = ?
        LIMIT 1';
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $usuario);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 5. Verificar contraseña cifrada
$credencialesOk = $fila !== null && password_verify($clave, $fila['clave_hash']);

if (!$credencialesOk) {
    $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
    if ($_SESSION['login_intentos'] >= LOGIN_MAX_INTENTOS) {
        $_SESSION['login_bloqueado_hasta'] = time() + LOGIN_MINUTOS_BLOQUEO * 60;
        $_SESSION['login_intentos'] = 0;
    }
    $_SESSION['login_usuario_previo'] = $usuario;
    // Mensaje genérico: no revela si el usuario existe
    mensaje_flash('error', 'Usuario o contraseña incorrectos.');
    redirigir('index.php');
}

if ((int) $fila['activo'] !== 1) {
    mensaje_flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
    redirigir('index.php');
}

// 6. Actualizar el hash si PHP cambió su algoritmo por defecto
if (password_needs_rehash($fila['clave_hash'], PASSWORD_DEFAULT)) {
    $nuevo = password_hash($clave, PASSWORD_DEFAULT);
    $up = $db->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?');
    $up->bind_param('si', $nuevo, $fila['id']);
    $up->execute();
    $up->close();
}

// 7. Registrar último acceso
$acc = $db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
$acc->bind_param('i', $fila['id']);
$acc->execute();
$acc->close();

// 8. Abrir sesión
unset($_SESSION['login_usuario_previo']);
abrir_sesion_usuario($fila);
mensaje_flash('exito', 'Bienvenido, ' . $fila['nombre'] . '.');
redirigir('panel.php');
