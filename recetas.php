<?php
/**
 * Receta de un producto: qué insumos (y cuánto de cada uno) se descuentan del
 * inventario por cada unidad vendida. Solo administrador.
 *
 * Uso: recetas.php?producto=ID  (se llega desde el botón "Receta" del menú)
 *
 * Responsable: Bryan Gallegos
 */
require_once __DIR__ . '/php/auth/sesion.php';
require_once __DIR__ . '/php/dao/ProductoDAO.php';
require_once __DIR__ . '/php/dao/RecetaDAO.php';
require_once __DIR__ . '/php/dao/InventarioDAO.php';

requiere_rol(ROL_ADMIN);

$productoId = get_id('producto');
$producto   = $productoId !== null ? (new ProductoDAO())->obtenerActivo($productoId) : null;
if ($producto === null) {
    fallar('El producto no existe o fue eliminado.', 'menu.php');
}

$receta  = (new RecetaDAO())->insumosDeProducto($productoId); // insumos que ya forman parte de la receta
$insumos = (new InventarioDAO())->listar();                  // insumos activos para el selector

$tituloPagina = 'Receta de ' . $producto['nombre'];
$scripts = ['js/comun.js', 'js/recetas.js'];
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <p class="sobretitulo"><?= e($producto['categoria']) ?></p>
                <h1>Receta de <?= e($producto['nombre']) ?></h1>
                <p class="descripcion">Insumos que se descuentan del inventario por cada unidad vendida.</p>
            </div>
            <a class="boton-secundario" href="<?= e(url('menu.php')) ?>">Volver al menú</a>
        </div>

        <?php if ($receta === []): ?>
            <div class="alerta alerta-aviso" role="status">
                <?= icono('alerta') ?>
                <p><strong>Sin receta:</strong> mientras no agregues insumos, las ventas de este producto no descontarán stock.</p>
            </div>
        <?php endif; ?>

        <div class="disposicion">
            <section class="tarjeta" aria-labelledby="titulo-receta">
                <div class="tarjeta-cabecera">
                    <h2 id="titulo-receta">Insumos por unidad</h2>
                    <p class="resultado-filtro"><?= count($receta) ?> insumo(s)</p>
                </div>

                <?php if ($receta === []): ?>
                    <p class="estado-vacio">Esta receta todavía no tiene insumos.</p>
                <?php else: ?>
                    <div class="tabla-envoltura" role="region" aria-label="Tabla de insumos de la receta" tabindex="0">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Insumo</th>
                                    <th scope="col" class="numero">Cantidad por unidad</th>
                                    <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receta as $r): ?>
                                    <tr>
                                        <th scope="row">
                                            <?= e($r['nombre']) ?>
                                            <?php if ((int) $r['activo'] !== 1): ?>
                                                <span class="insignia insignia-aviso"><?= icono('alerta') ?> Eliminado del inventario</span>
                                            <?php endif; ?>
                                        </th>
                                        <td class="numero"><?= e(cantidad((string) $r['cantidad'])) ?> <span class="texto-suave"><?= e($r['unidad']) ?></span></td>
                                        <td>
                                            <div class="acciones-tabla">
                                                <button type="button" class="boton-fantasma" data-editar-cantidad title="Editar cantidad"
                                                        data-insumo="<?= (int) $r['id'] ?>"
                                                        data-cantidad="<?= e(cantidad((string) $r['cantidad'])) ?>"
                                                        aria-label="Editar cantidad de <?= e($r['nombre']) ?>"><?= icono('editar') ?></button>
                                                <form action="<?= e(url('php/menu/receta_quitar.php')) ?>" method="post"
                                                      data-confirmar="¿Quitar «<?= e($r['nombre']) ?>» de la receta?">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                                                    <input type="hidden" name="insumo_id" value="<?= (int) $r['id'] ?>">
                                                    <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Quitar"
                                                            aria-label="Quitar <?= e($r['nombre']) ?> de la receta"><?= icono('eliminar') ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-form-receta">
                <h2 id="titulo-form-receta">Agregar insumo</h2>
                <?php if ($insumos === []): ?>
                    <p class="estado-vacio">No hay insumos en el inventario. <a href="<?= e(url('inventario.php')) ?>">Registra uno primero</a>.</p>
                <?php else: ?>
                    <form id="form-receta" action="<?= e(url('php/menu/receta_guardar.php')) ?>" method="post" novalidate data-validar>
                        <?= csrf_campo() ?>
                        <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">

                        <div class="campo">
                            <label for="receta-insumo">Insumo</label>
                            <select id="receta-insumo" name="insumo_id" required aria-describedby="error-receta-insumo">
                                <option value="">Elige un insumo</option>
                                <?php foreach ($insumos as $i): ?>
                                    <option value="<?= (int) $i['id'] ?>"><?= e($i['nombre']) ?> (<?= e($i['unidad']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <p class="error-campo" id="error-receta-insumo" aria-live="polite"></p>
                        </div>

                        <div class="campo">
                            <label for="receta-cantidad">Cantidad por unidad vendida</label>
                            <input type="number" id="receta-cantidad" name="cantidad" required
                                   min="0.001" max="99999" step="0.001" inputmode="decimal"
                                   aria-describedby="ayuda-receta-cantidad error-receta-cantidad">
                            <p class="texto-suave" id="ayuda-receta-cantidad">En la unidad del insumo. Si el insumo ya está en la receta, se reemplaza su cantidad.</p>
                            <p class="error-campo" id="error-receta-cantidad" aria-live="polite"></p>
                        </div>

                        <div class="acciones-form">
                            <button type="submit" class="boton-primario">Guardar en la receta</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </div>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
