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

$tituloPagina = 'Iniciar sesión';
require __DIR__ . '/php/partials/head.php';
?>
<body class="pagina-login">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

    <div class="login-contenedor">
        <header class="login-marca">
            <p class="marca"><span class="marca-logo"><?= icono('taza') ?></span> CoffeeDesk</p>
            <div class="login-lema">
                <h1>Tu cafetería, en orden.</h1>
                <p>Pedidos por mesa, menú del día e inventario de la cocina en un solo lugar.</p>
            </div>
            <ul class="login-puntos">
                <li><?= icono('pedidos') ?> Pedidos con total automático</li>
                <li><?= icono('menu') ?> Menú con disponibilidad al día</li>
                <li><?= icono('inventario') ?> Alertas de stock bajo</li>
            </ul>
        </header>

    <main id="contenido" class="tarjeta-login">
        <h2>Iniciar sesión</h2>
        <p class="texto-suave">Ingresa con tu usuario del personal.</p>

        <?= mostrar_flash() ?>

        <form id="form-login" action="<?= e(url('php/auth/login.php')) ?>" method="post" novalidate>
            <?= csrf_campo() ?>

            <div class="campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario"
                       value="<?= e($usuarioPrevio) ?>"
                       autocomplete="username" required
                       minlength="3" maxlength="30"
                       pattern="<?= e(PATRON_USUARIO) ?>"
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

            <button type="submit" class="boton-primario boton-bloque">Ingresar</button>
        </form>
    </main>
    </div>

<?php require __DIR__ . '/php/partials/pie_pagina.php'; ?>

    <script src="<?= e(url('js/login.js')) ?>"></script>
</body>
</html>
