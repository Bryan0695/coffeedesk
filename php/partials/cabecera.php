<?php
/**
 * Cabecera común para páginas internas (después del login).
 * Muestra el menú según el rol del usuario.
 *
 * Antes de incluirla, la página debe definir:
 *   $tituloPagina = 'Menú';
 * y llamar a requiere_login() o requiere_rol(). Si se olvida, la cabecera
 * exige igualmente una sesión iniciada.
 *
 * Responsable: Bryan Gallegos (menú por rol) · Frederick (estilos)
 */

if (usuario_actual() === null) {
    requiere_login();
}
$u = usuario_actual();
$paginaActual = basename($_SERVER['SCRIPT_NAME']);

// Menú de navegación: [archivo, texto, roles que lo ven]
$enlaces = [
    ['panel.php',      'Inicio',     [ROL_ADMIN, ROL_MESERO]],
    ['pedidos.php',    'Pedidos',    [ROL_ADMIN, ROL_MESERO]],
    ['menu.php',       'Menú',       [ROL_ADMIN, ROL_MESERO]],
    ['inventario.php', 'Inventario', [ROL_ADMIN]],
];

require __DIR__ . '/head.php';
?>
<body>
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

    <header class="cabecera">
        <p class="marca"><span aria-hidden="true">☕</span> CoffeeDesk</p>

        <nav aria-label="Navegación principal">
            <ul class="menu-principal">
                <?php foreach ($enlaces as [$archivo, $texto, $roles]): ?>
                    <?php if (tiene_rol(...$roles)): ?>
                        <li>
                            <a href="<?= e(url($archivo)) ?>"
                               <?= $paginaActual === $archivo ? 'aria-current="page"' : '' ?>>
                                <?= e($texto) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="usuario-sesion">
            <p><?= e($u['nombre']) ?> <span class="etiqueta-rol">(<?= e($u['rol']) ?>)</span></p>
            <form action="<?= e(url('php/auth/logout.php')) ?>" method="post">
                <?= csrf_campo() ?>
                <button type="submit" class="boton-secundario">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main id="contenido" class="contenido">
        <?= mostrar_flash() ?>
