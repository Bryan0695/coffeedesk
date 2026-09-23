<?php
/**
 * Manejo de sesiones y control de acceso por rol.
 *
 * USO EN CUALQUIER PÁGINA O ARCHIVO PHP:
 *
 *   require_once __DIR__ . '/php/auth/sesion.php';
 *   requiere_login();                 // cualquier usuario autenticado
 *   requiere_rol(ROL_ADMIN);          // solo administradores
 *   requiere_rol(ROL_ADMIN, ROL_MESERO); // cualquiera de los dos
 *
 * EN ARCHIVOS QUE RESPONDEN JSON (contratos de Jeremy):
 *   requiere_login_api();
 *   requiere_rol_api(ROL_ADMIN);
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../../config/config.php';

iniciar_sesion();

/** Inicia la sesión con cookies seguras y controla la inactividad. */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_name(SESION_NOMBRE);
    session_set_cookie_params([
        'lifetime' => 0,          // se borra al cerrar el navegador
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => $https,     // solo por HTTPS cuando exista
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',
    ]);
    session_start();

    // Cierre automático por inactividad
    if (isset($_SESSION['ultimo_movimiento'])) {
        $inactivo = time() - $_SESSION['ultimo_movimiento'];
        if ($inactivo > SESION_MINUTOS_INACTIVIDAD * 60 && usuario_actual() !== null) {
            cerrar_sesion();
            mensaje_flash('aviso', 'Tu sesión expiró por inactividad. Vuelve a ingresar.');
        }
    }
    $_SESSION['ultimo_movimiento'] = time();
}

/** Guarda al usuario en la sesión después de validar su contraseña. */
function abrir_sesion_usuario(array $usuario): void
{
    session_regenerate_id(true); // evita fijación de sesión
    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'usuario'=> $usuario['usuario'],
        'rol'    => $usuario['rol'],
    ];
    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
}

/**
 * Cierra la sesión: vacía todos los datos y cambia el identificador
 * (la sesión anterior se elimina del servidor). Queda una sesión vacía
 * que solo sirve para mostrar el mensaje de "sesión cerrada".
 */
function cerrar_sesion(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Devuelve el usuario conectado o null. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function tiene_rol(string ...$roles): bool
{
    $u = usuario_actual();
    return $u !== null && in_array($u['rol'], $roles, true);
}

function es_admin(): bool
{
    return tiene_rol(ROL_ADMIN);
}

// ---- Protección de páginas HTML -----------------------------------------

function requiere_login(): void
{
    if (usuario_actual() === null) {
        mensaje_flash('aviso', 'Inicia sesión para continuar.');
        redirigir('index.php');
    }
}

function requiere_rol(string ...$roles): void
{
    requiere_login();
    if (!tiene_rol(...$roles)) {
        http_response_code(403);
        mensaje_flash('error', 'No tienes permiso para acceder a esa sección.');
        redirigir('panel.php');
    }
}

// ---- Protección de archivos que responden JSON --------------------------

function requiere_login_api(): void
{
    if (usuario_actual() === null) {
        responder_json('error', 'Sesión no iniciada o expirada.', null, 401);
    }
}

function requiere_rol_api(string ...$roles): void
{
    requiere_login_api();
    if (!tiene_rol(...$roles)) {
        responder_json('error', 'No tienes permiso para realizar esta operación.', null, 403);
    }
}

/**
 * Respuesta JSON con el formato común del proyecto: { estado, mensaje, datos }.
 * Termina la ejecución.
 */
function responder_json(string $estado, string $mensaje, $datos = null, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['estado' => $estado, 'mensaje' => $mensaje, 'datos' => $datos],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// ---- CSRF (protege formularios POST) -------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Imprime el campo oculto dentro de un <form method="post"> */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---- Mensajes de éxito / error entre redirecciones -----------------------

/** tipo: 'exito' | 'error' | 'aviso' */
function mensaje_flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Devuelve el mensaje pendiente (y lo borra) o null. */
function tomar_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** HTML accesible para mostrar el mensaje flash (role="alert" lo anuncia el lector de pantalla). */
function mostrar_flash(): string
{
    $f = tomar_flash();
    if ($f === null) {
        return '';
    }
    $iconos = ['exito' => '✔ Éxito:', 'error' => '✖ Error:', 'aviso' => '⚠ Aviso:'];
    // Se usa texto + icono, no solo color (WCAG 1.4.1)
    return sprintf(
        '<div class="alerta alerta-%s" role="alert"><strong>%s</strong> %s</div>',
        e($f['tipo']),
        $iconos[$f['tipo']] ?? '',
        e($f['texto'])
    );
}

// ---- Utilidades ----------------------------------------------------------

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/** Escapa texto para imprimirlo en HTML (evita XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
