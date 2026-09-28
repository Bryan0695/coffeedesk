<?php
/**
 * Cabecera común para páginas internas (después del login): barra lateral con
 * la navegación según el rol y el usuario conectado.
 *
 * Antes de incluirla, la página debe definir:
 *   $tituloPagina = 'Menú';
 * y llamar a requiere_login() o requiere_rol(). Si se olvida, la cabecera
 * exige igualmente una sesión iniciada.
 *
 * Responsable: Bryan Gallegos (menú por rol) · Frederick (diseño)
 */

if (usuario_actual() === null) {
    requiere_login();
}
$u = usuario_actual();
$paginaActual = basename($_SERVER['SCRIPT_NAME']);

// Menú de navegación: [archivo, texto, icono, roles que lo ven]
$enlaces = [
    ['panel.php',      'Inicio',     'inicio',     [ROL_ADMIN, ROL_MESERO]],
    ['pedidos.php',    'Pedidos',    'pedidos',    [ROL_ADMIN, ROL_MESERO]],
    ['menu.php',       'Menú',       'menu',       [ROL_ADMIN, ROL_MESERO]],
    ['inventario.php', 'Inventario', 'inventario', [ROL_ADMIN]],
];

require __DIR__ . '/head.php';
?>
<body class="app">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

    <header class="barra-lateral">
        <p class="marca"><span class="marca-logo"><?= icono('taza') ?></span> CoffeeDesk</p>

        <nav aria-label="Navegación principal">
            <ul class="menu-principal">
                <?php foreach ($enlaces as [$archivo, $texto, $icono, $roles]): ?>
                    <?php if (tiene_rol(...$roles)): ?>
                        <li>
                            <a href="<?= e(url($archivo)) ?>"
                               <?= $paginaActual === $archivo ? 'aria-current="page"' : '' ?>>
                                <?= icono($icono) ?><span><?= e($texto) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="usuario-sesion">
            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
            <p class="usuario-datos">
                <span class="usuario-nombre"><?= e($u['nombre']) ?></span>
                <span class="etiqueta-rol"><?= e(ucfirst($u['rol'])) ?></span>
            </p>
            <form action="<?= e(url('php/auth/logout.php')) ?>" method="post">
                <?= csrf_campo() ?>
                <button type="submit" class="boton-salir" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <?= icono('salir') ?>
                </button>
            </form>
        </div>
    </header>

    <main id="contenido" class="contenido">
        <?= mostrar_flash() ?>
