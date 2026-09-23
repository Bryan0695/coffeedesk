<?php
/**
 * Mensajes de éxito / error / aviso que sobreviven a una redirección.
 * Requiere una sesión iniciada y php/comun/html.php (e()).
 */

/** tipo: 'exito' | 'error' | 'aviso' */
function mensaje_flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** ¿Hay un mensaje pendiente de mostrar? (no lo consume) */
function hay_flash(): bool
{
    return isset($_SESSION['flash']);
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
