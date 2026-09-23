<?php
/**
 * Usuario conectado, roles y protección de páginas y endpoints JSON.
 * Requiere php/comun/{flash,respuesta}.php y csrf.php.
 */

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
    $_SESSION['inicio_sesion'] = $_SESSION['revalidado_en'] = time();
    unset($_SESSION['csrf']); // la sesión autenticada recibe un token CSRF nuevo
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

/**
 * Devuelve el usuario conectado o null.
 *
 * F-007: cada SESION_SEGUNDOS_REVALIDACION se comprueba en la BD que la cuenta
 * siga activa y se actualiza su rol; además la sesión dura como máximo
 * SESION_HORAS_MAXIMAS aunque haya actividad.
 */
function usuario_actual(): ?array
{
    $u = $_SESSION['usuario'] ?? null;
    if ($u === null) {
        return null;
    }

    $ahora = time();
    if ($ahora - ($_SESSION['inicio_sesion'] ?? 0) > SESION_HORAS_MAXIMAS * 3600) {
        cerrar_sesion();
        mensaje_flash('aviso', 'Tu sesión llegó a su duración máxima. Vuelve a ingresar.');
        return null;
    }

    if ($ahora - ($_SESSION['revalidado_en'] ?? 0) > SESION_SEGUNDOS_REVALIDACION) {
        require_once __DIR__ . '/../conexion.php';
        $fila = consultar_uno(
            'SELECT u.nombre, u.activo, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.id = ?',
            [$u['id']]
        );
        if ($fila === null || (int) $fila['activo'] !== 1) {
            cerrar_sesion();
            mensaje_flash('error', 'Tu cuenta ya no tiene acceso. Contacta al administrador.');
            return null;
        }
        $_SESSION['usuario']['nombre'] = $fila['nombre'];
        $_SESSION['usuario']['rol']    = $fila['rol'];
        $_SESSION['revalidado_en']     = $ahora;
    }

    return $_SESSION['usuario'];
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
        // No pisar un aviso más específico ("sesión expirada", "cuenta sin acceso")
        if (!hay_flash()) {
            mensaje_flash('aviso', 'Inicia sesión para continuar.');
        }
        redirigir('index.php');
    }
}

function requiere_rol(string ...$roles): void
{
    requiere_login();
    if (!tiene_rol(...$roles)) {
        // Redirección 302 al panel con aviso (las páginas HTML no muestran un 403 crudo)
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
