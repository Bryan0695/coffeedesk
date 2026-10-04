<?php
/**
 * Menú: búsqueda, filtro por categoría y (solo administrador) alta, edición y baja de productos.
 *
 * Vista: Frederick · Lógica y datos: Gabo (php/menu/, php/dao/ProductoDAO.php).
 */
require_once __DIR__ . '/php/auth/sesion.php';
require_once __DIR__ . '/php/dao/ProductoDAO.php';
require_once __DIR__ . '/php/dao/CategoriaDAO.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);

$categorias = (new CategoriaDAO())->listarActivas();

// MySQL guarda el precio como DECIMAL(10,2); la vista trabaja en centavos
$productos = array_map(
    static fn (array $p): array => [
        'id'           => (int) $p['id'],
        'nombre'       => $p['nombre'],
        'categoria_id' => (int) $p['categoria_id'],
        'categoria'    => $p['categoria'],
        'precio'       => precio_a_centavos((string) $p['precio']),
        'disponible'   => (bool) $p['disponible'],
        'sin_receta'   => (int) $p['insumos_receta'] === 0, // sus ventas no descuentan stock
    ],
    (new ProductoDAO())->listarActivos()
);

$tituloPagina = 'Menú';
$scripts = ['js/comun.js', 'js/menu.js'];
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <h1>Menú</h1>
                <p class="descripcion"><?= es_admin()
                    ? 'Administra los productos, sus precios y su disponibilidad.'
                    : 'Consulta los productos, sus precios y su disponibilidad.' ?></p>
            </div>
            <?php if (es_admin()): ?>
                <div class="acciones-encabezado">
                    <a class="boton-secundario" href="<?= e(url('categorias.php')) ?>"><?= icono('categorias') ?> Administrar categorías</a>
                    <a class="boton-primario" href="#form-producto"><?= icono('mas') ?> Nuevo producto</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="<?= es_admin() ? 'disposicion' : '' ?>">
            <section class="tarjeta" aria-labelledby="titulo-productos">
                <div class="tarjeta-cabecera">
                    <h2 id="titulo-productos">Productos</h2>
                    <p id="resultado-filtro" class="resultado-filtro" aria-live="polite"></p>
                </div>

                <form class="filtros" role="search" aria-label="Buscar productos" id="form-filtros">
                    <div class="campo campo-busqueda">
                        <label for="buscar">Buscar por nombre</label>
                        <?= icono('buscar') ?>
                        <input type="search" id="buscar" name="q" autocomplete="off" placeholder="Ej.: capuchino">
                    </div>

                    <div class="campo">
                        <label for="filtro-categoria">Categoría</label>
                        <select id="filtro-categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="tabla-envoltura" role="region" aria-labelledby="titulo-productos" tabindex="0">
                    <table id="tabla-productos">
                        <thead>
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Categoría</th>
                                <th scope="col" class="numero">Precio</th>
                                <th scope="col">Estado</th>
                                <?php if (es_admin()): ?><th scope="col"><span class="visualmente-oculto">Acciones</span></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                                <tr data-nombre="<?= e($p['nombre']) ?>" data-categoria="<?= (int) $p['categoria_id'] ?>">
                                    <th scope="row"><?= e($p['nombre']) ?></th>
                                    <td class="texto-suave"><?= e($p['categoria']) ?></td>
                                    <td class="numero"><?= dinero($p['precio']) ?></td>
                                    <td>
                                        <?php if ($p['disponible']): ?>
                                            <span class="insignia insignia-ok">Disponible</span>
                                        <?php else: ?>
                                            <span class="insignia insignia-error">Agotado</span>
                                        <?php endif; ?>
                                        <?php if (es_admin() && $p['sin_receta']): ?>
                                            <span class="insignia insignia-aviso"><?= icono('alerta') ?> Sin receta</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (es_admin()): ?>
                                        <td>
                                            <div class="acciones-tabla">
                                                <a class="boton-fantasma" title="Receta"
                                                   href="<?= e(url('recetas.php?producto=' . $p['id'])) ?>"
                                                   aria-label="Receta de <?= e($p['nombre']) ?>">
                                                    <?= icono('receta') ?>
                                                </a>
                                                <button type="button" class="boton-fantasma" data-editar title="Editar"
                                                        data-id="<?= (int) $p['id'] ?>"
                                                        data-nombre="<?= e($p['nombre']) ?>"
                                                        data-categoria="<?= (int) $p['categoria_id'] ?>"
                                                        data-precio="<?= centavos_a_decimal($p['precio']) ?>"
                                                        data-disponible="<?= $p['disponible'] ? '1' : '0' ?>"
                                                        aria-label="Editar <?= e($p['nombre']) ?>"><?= icono('editar') ?></button>
                                                <form action="<?= e(url('php/menu/eliminar.php')) ?>" method="post"
                                                      data-confirmar="¿Eliminar «<?= e($p['nombre']) ?>» del menú?">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                    <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Eliminar"
                                                            aria-label="Eliminar <?= e($p['nombre']) ?>"><?= icono('eliminar') ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p id="sin-resultados" class="estado-vacio" hidden>Ningún producto coincide con la búsqueda.</p>
            </section>

            <?php if (es_admin()): ?>
                <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-form-producto">
                    <h2 id="titulo-form-producto">Agregar producto</h2>
                    <form id="form-producto" action="<?= e(url('php/menu/guardar.php')) ?>" method="post" novalidate data-validar>
                        <?= csrf_campo() ?>
                        <input type="hidden" id="producto-id" name="id" value="">

                        <div class="campo">
                            <label for="producto-nombre">Nombre</label>
                            <input type="text" id="producto-nombre" name="nombre" required maxlength="<?= NOMBRE_MAX_PRODUCTO ?>"
                                   aria-describedby="error-producto-nombre">
                            <p class="error-campo" id="error-producto-nombre" aria-live="polite"></p>
                        </div>

                        <div class="campo">
                            <label for="producto-categoria">Categoría</label>
                            <select id="producto-categoria" name="categoria_id" required
                                    aria-describedby="error-producto-categoria">
                                <option value="">Elige una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="error-campo" id="error-producto-categoria" aria-live="polite"></p>
                        </div>

                        <div class="campo">
                            <label for="producto-precio">Precio (USD)</label>
                            <input type="number" id="producto-precio" name="precio" required
                                   min="0.01" max="999.99" step="0.01" inputmode="decimal"
                                   aria-describedby="error-producto-precio">
                            <p class="error-campo" id="error-producto-precio" aria-live="polite"></p>
                        </div>

                        <div class="campo campo-check">
                            <input type="checkbox" id="producto-disponible" name="disponible" value="1" checked>
                            <label for="producto-disponible">Disponible para la venta</label>
                        </div>

                        <div class="acciones-form">
                            <button type="submit" class="boton-primario" id="boton-guardar">Guardar producto</button>
                            <button type="button" class="boton-secundario" id="cancelar-edicion" hidden>Cancelar</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </div>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
