<?php
/**
 * Vista de inicio de sesión.
 * Estructura base accesible; Frederick puede ajustar el diseño en css/estilos.css.
 *
 * Responsable (lógica): Bryan Gallegos
 */
require_once __DIR__ . '/php/auth/sesion.php';

// Si ya inició sesión, va directo al panel
if (usuario_actual() !== null) {
    redirigir('panel.php');
}

$usuarioPrevio = $_SESSION['login_usuario_previo'] ?? '';
unset($_SESSION['login_usuario_previo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | CoffeeDesk</title>
    <link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="pagina-login">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

    <header class="cabecera-login">
        <p class="logo" aria-hidden="true">☕</p>
        <h1>CoffeeDesk</h1>
        <p class="subtitulo">Gestión de pedidos e inventario</p>
    </header>

    <main id="contenido" class="tarjeta-login">
        <h2>Iniciar sesión</h2>

        <?= mostrar_flash() ?>

        <form id="form-login" action="<?= e(url('php/auth/login.php')) ?>" method="post" novalidate>
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario"
                       value="<?= e($usuarioPrevio) ?>"
                       autocomplete="username" required
                       minlength="3" maxlength="30"
                       aria-describedby="error-usuario">
                <p class="error-campo" id="error-usuario" aria-live="polite"></p>
            </div>

            <div class="campo">
                <label for="clave">Contraseña</label>
                <div class="clave-envoltura">
                    <input type="password" id="clave" name="clave"
                           autocomplete="current-password" required
                           aria-describedby="error-clave">
                    <button type="button" id="ver-clave" class="boton-secundario"
                            aria-controls="clave" aria-pressed="false">Mostrar</button>
                </div>
                <p class="error-campo" id="error-clave" aria-live="polite"></p>
            </div>

            <button type="submit" class="boton-primario">Ingresar</button>
        </form>
    </main>

    <footer class="pie">
        <p>&copy; <?= date('Y') ?> CoffeeDesk · UEES · Desarrollo de Aplicaciones Web</p>
    </footer>

    <script src="<?= e(url('js/login.js')) ?>"></script>
</body>
</html>
