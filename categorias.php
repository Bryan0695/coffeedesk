<?php
/**
 * Categorías del menú (solo administrador): búsqueda, alta, edición,
 * eliminación lógica y reactivación.
 *
 * Vista y lógica: Gabo (php/menu/categoria_*.php, php/dao/CategoriaDAO.php).
 */
require_once __DIR__ . '/php/auth/sesion.php';
require_once __DIR__ . '/php/dao/CategoriaDAO.php';

requiere_rol(ROL_ADMIN);

$categorias = (new CategoriaDAO())->listarTodas();

$tituloPagina = 'Categorías';
$scripts = ['js/comun.js', 'js/categorias.js'];
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <h1>Categorías</h1>
                <p class="descripcion">Organiza las categorías en las que se agrupan los productos del menú.</p>
            </div>
            <div class="acciones-encabezado">
                <a class="boton-secundario" href="<?= e(url('menu.php')) ?>"><?= icono('menu') ?> Volver al menú</a>
                <a class="boton-primario" href="#form-categoria"><?= icono('mas') ?> Nueva categoría</a>
            </div>
        </div>

        <div class="disposicion">
            <section class="tarjeta" aria-labelledby="titulo-categorias">
                <div class="tarjeta-cabecera">
                    <h2 id="titulo-categorias">Categorías registradas</h2>
                    <p id="resultado-filtro" class="resultado-filtro" aria-live="polite"></p>
                </div>

                <?php if ($categorias === []): ?>
                    <p class="estado-vacio">Todavía no hay categorías registradas.</p>
                <?php else: ?>
                    <form class="filtros" role="search" aria-label="Buscar categorías" id="form-filtros">
                        <div class="campo campo-busqueda">
                            <label for="buscar">Buscar por nombre</label>
                            <?= icono('buscar') ?>
                            <input type="search" id="buscar" name="q" autocomplete="off" placeholder="Ej.: bebidas">
                        </div>
                    </form>

                    <div class="tabla-envoltura" role="region" aria-labelledby="titulo-categorias" tabindex="0">
                        <table id="tabla-categorias">
                            <thead>
                                <tr>
                                    <th scope="col">Categoría</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $c): ?>
                                    <?php $activa = (int) $c['activo'] === 1; ?>
                                    <tr data-nombre="<?= e($c['nombre']) ?>">
                                        <th scope="row"><?= e($c['nombre']) ?></th>
                                        <td>
                                            <?php if ($activa): ?>
                                                <span class="insignia insignia-ok">Activa</span>
                                            <?php else: ?>
                                                <span class="insignia insignia-info">Eliminada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="acciones-tabla">
                                                <?php if ($activa): ?>
                                                    <button type="button" class="boton-fantasma" data-editar title="Editar"
                                                            data-id="<?= (int) $c['id'] ?>"
                                                            data-nombre="<?= e($c['nombre']) ?>"
                                                            aria-label="Editar <?= e($c['nombre']) ?>"><?= icono('editar') ?></button>
                                                    <form action="<?= e(url('php/menu/categoria_eliminar.php')) ?>" method="post"
                                                          data-confirmar="¿Eliminar la categoría «<?= e($c['nombre']) ?>»?">
                                                        <?= csrf_campo() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                        <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Eliminar"
                                                                aria-label="Eliminar <?= e($c['nombre']) ?>"><?= icono('eliminar') ?></button>
                                                    </form>
                                                <?php else: ?>
                                                    <form action="<?= e(url('php/menu/categoria_reactivar.php')) ?>" method="post"
                                                          data-confirmar="¿Reactivar la categoría «<?= e($c['nombre']) ?>»?">
                                                        <?= csrf_campo() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                        <button type="submit" class="boton-fantasma" title="Reactivar"
                                                                aria-label="Reactivar <?= e($c['nombre']) ?>"><?= icono('ok') ?></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p id="sin-resultados" class="estado-vacio" hidden>Ninguna categoría coincide con la búsqueda.</p>
                <?php endif; ?>
            </section>

            <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-form-categoria">
                <h2 id="titulo-form-categoria">Agregar categoría</h2>
                <form id="form-categoria" action="<?= e(url('php/menu/categoria_guardar.php')) ?>" method="post" novalidate data-validar>
                    <?= csrf_campo() ?>
                    <input type="hidden" id="categoria-id" name="id" value="">

                    <div class="campo">
                        <label for="categoria-nombre">Nombre</label>
                        <input type="text" id="categoria-nombre" name="nombre" required maxlength="<?= NOMBRE_MAX_CATEGORIA ?>"
                               autocomplete="off" aria-describedby="error-categoria-nombre">
                        <p class="error-campo" id="error-categoria-nombre" aria-live="polite"></p>
                    </div>

                    <div class="acciones-form">
                        <button type="submit" class="boton-primario" id="boton-guardar">Guardar categoría</button>
                        <button type="button" class="boton-secundario" id="cancelar-edicion" hidden>Cancelar</button>
                    </div>
                </form>
            </section>
        </div>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
