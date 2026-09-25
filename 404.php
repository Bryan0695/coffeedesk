<?php
/**
 * Página "no encontrada". .htaccess envía aquí toda ruta que no existe.
 * Es pública: si hay sesión ofrece volver al panel, si no, al login.
 *
 * Responsable: Frederick Torres
 */
require_once __DIR__ . '/php/auth/sesion.php';

http_response_code(404);

$conSesion = usuario_actual() !== null;
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$tituloPagina = 'Página no encontrada';
require __DIR__ . '/php/partials/head.php';
?>
<body class="pagina-error">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

    <header class="error-cabecera">
        <a class="marca" href="<?= e(url($conSesion ? 'panel.php' : 'index.php')) ?>">
            <span class="marca-logo"><?= icono('taza') ?></span> CoffeeDesk
        </a>
    </header>

    <main id="contenido" class="error-contenido">
        <p class="error-codigo" aria-hidden="true">4<span class="error-taza"><?= icono('taza') ?></span>4</p>
        <h1>No encontramos esta página</h1>
        <p class="descripcion">
            Puede que la dirección esté mal escrita, que la página se haya movido
            o que esta función todavía no esté disponible.
        </p>
        <?php if ($ruta !== ''): ?>
            <p class="error-ruta"><span class="visualmente-oculto">Dirección solicitada: </span><code><?= e($ruta) ?></code></p>
        <?php endif; ?>

        <div class="error-acciones">
            <?php if ($conSesion): ?>
                <a class="boton-primario" href="<?= e(url('panel.php')) ?>"><?= icono('inicio') ?> Volver al inicio</a>
                <a class="boton-secundario" href="<?= e(url('pedidos.php')) ?>"><?= icono('pedidos') ?> Ir a pedidos</a>
            <?php else: ?>
                <a class="boton-primario" href="<?= e(url('index.php')) ?>"><?= icono('flecha') ?> Ir a iniciar sesión</a>
            <?php endif; ?>
        </div>
    </main>

<?php require __DIR__ . '/php/partials/pie_pagina.php'; ?>
</body>
</html>
