<?php
/**
 * Inventario: insumos con alerta de stock bajo, búsqueda y alta, edición y baja (solo administrador).
 *
 * Vista: Frederick · Lógica y datos: Jeremy.
 * Los nombres de los campos y los archivos de destino son PROVISIONALES
 * hasta que Jeremy entregue los contratos.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_rol(ROL_ADMIN);

$unidades = ['unidades', 'kg', 'g', 'litros', 'ml'];

// PROVISIONAL (Jeremy): consultar('SELECT id, nombre, unidad, stock, stock_minimo FROM insumos ORDER BY nombre')
$insumos = [
    ['id' => 1, 'nombre' => 'Café en grano',  'unidad' => 'kg',       'stock' => 8,  'stock_minimo' => 3],
    ['id' => 2, 'nombre' => 'Leche entera',   'unidad' => 'litros',   'stock' => 4,  'stock_minimo' => 10],
    ['id' => 3, 'nombre' => 'Azúcar',         'unidad' => 'kg',       'stock' => 12, 'stock_minimo' => 2],
    ['id' => 4, 'nombre' => 'Vasos de 12 oz', 'unidad' => 'unidades', 'stock' => 40, 'stock_minimo' => 50],
    ['id' => 5, 'nombre' => 'Limones',        'unidad' => 'unidades', 'stock' => 60, 'stock_minimo' => 20],
];

$stockBajo = array_filter($insumos, fn ($i) => $i['stock'] <= $i['stock_minimo']);

$tituloPagina = 'Inventario';
$scripts = ['js/comun.js', 'js/inventario.js'];
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <h1>Inventario</h1>
                <p class="descripcion">Stock de insumos de la cocina y alertas cuando bajan del mínimo.</p>
            </div>
            <a class="boton-primario" href="#form-insumo"><?= icono('mas') ?> Nuevo insumo</a>
        </div>

        <ul class="resumen-cifras" aria-label="Resumen del inventario">
            <li class="cifra">
                <span class="cifra-valor"><?= count($insumos) ?></span>
                <span class="cifra-etiqueta">Insumos registrados</span>
            </li>
            <li class="cifra <?= $stockBajo !== [] ? 'cifra-aviso' : '' ?>">
                <span class="cifra-valor"><?= count($stockBajo) ?></span>
                <span class="cifra-etiqueta">Con stock bajo</span>
            </li>
        </ul>

        <?php if ($stockBajo !== []): ?>
            <div class="alerta alerta-aviso" role="status">
                <?= icono('alerta') ?>
                <p><strong>Stock bajo:</strong> <?= e(implode(', ', array_column($stockBajo, 'nombre'))) ?>. Conviene reponerlos pronto.</p>
            </div>
        <?php endif; ?>

        <div class="disposicion">
            <section class="tarjeta" aria-labelledby="titulo-insumos">
                <div class="tarjeta-cabecera">
                    <h2 id="titulo-insumos">Insumos</h2>
                    <p id="resultado-filtro" class="resultado-filtro" aria-live="polite"></p>
                </div>

                <form class="filtros" role="search" aria-label="Buscar insumos" id="form-filtros">
                    <div class="campo campo-busqueda">
                        <label for="buscar">Buscar por nombre</label>
                        <?= icono('buscar') ?>
                        <input type="search" id="buscar" name="q" autocomplete="off" placeholder="Ej.: leche">
                    </div>
                    <div class="campo campo-check">
                        <input type="checkbox" id="solo-bajo" name="solo_bajo" value="1">
                        <label for="solo-bajo">Solo stock bajo</label>
                    </div>
                </form>

                <div class="tabla-envoltura" role="region" aria-labelledby="titulo-insumos" tabindex="0">
                    <table id="tabla-insumos">
                        <thead>
                            <tr>
                                <th scope="col">Insumo</th>
                                <th scope="col" class="numero">Stock</th>
                                <th scope="col" class="numero">Mínimo</th>
                                <th scope="col">Estado</th>
                                <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos as $i): ?>
                                <?php $bajo = $i['stock'] <= $i['stock_minimo']; ?>
                                <tr data-nombre="<?= e($i['nombre']) ?>" data-bajo="<?= $bajo ? '1' : '0' ?>"
                                    <?= $bajo ? 'class="fila-alerta"' : '' ?>>
                                    <th scope="row"><?= e($i['nombre']) ?></th>
                                    <td class="numero"><?= e((string) $i['stock']) ?> <span class="texto-suave"><?= e($i['unidad']) ?></span></td>
                                    <td class="numero"><?= e((string) $i['stock_minimo']) ?> <span class="texto-suave"><?= e($i['unidad']) ?></span></td>
                                    <td>
                                        <?php if ($bajo): ?>
                                            <span class="insignia insignia-aviso"><?= icono('alerta') ?> Stock bajo</span>
                                        <?php else: ?>
                                            <span class="insignia insignia-ok"><?= icono('ok') ?> Suficiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="acciones-tabla">
                                            <button type="button" class="boton-fantasma" data-editar title="Editar"
                                                    data-id="<?= (int) $i['id'] ?>"
                                                    data-nombre="<?= e($i['nombre']) ?>"
                                                    data-unidad="<?= e($i['unidad']) ?>"
                                                    data-stock="<?= e((string) $i['stock']) ?>"
                                                    data-minimo="<?= e((string) $i['stock_minimo']) ?>"
                                                    aria-label="Editar <?= e($i['nombre']) ?>"><?= icono('editar') ?></button>
                                            <form action="<?= e(url('php/inventario/eliminar.php')) ?>" method="post"
                                                  data-confirmar="¿Eliminar «<?= e($i['nombre']) ?>» del inventario?">
                                                <?= csrf_campo() ?>
                                                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                                <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Eliminar"
                                                        aria-label="Eliminar <?= e($i['nombre']) ?>"><?= icono('eliminar') ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p id="sin-resultados" class="estado-vacio" hidden>Ningún insumo coincide con la búsqueda.</p>
            </section>

            <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-form-insumo">
                <h2 id="titulo-form-insumo">Agregar insumo</h2>
                <form id="form-insumo" action="<?= e(url('php/inventario/guardar.php')) ?>" method="post" novalidate data-validar>
                    <?= csrf_campo() ?>
                    <input type="hidden" id="insumo-id" name="id" value="">

                    <div class="campo">
                        <label for="insumo-nombre">Nombre</label>
                        <input type="text" id="insumo-nombre" name="nombre" required maxlength="80"
                               aria-describedby="error-insumo-nombre">
                        <p class="error-campo" id="error-insumo-nombre" aria-live="polite"></p>
                    </div>
                    <div class="campo">
                        <label for="insumo-unidad">Unidad</label>
                        <select id="insumo-unidad" name="unidad" required aria-describedby="error-insumo-unidad">
                            <option value="">Elige una unidad</option>
                            <?php foreach ($unidades as $u): ?>
                                <option value="<?= e($u) ?>"><?= e($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="error-campo" id="error-insumo-unidad" aria-live="polite"></p>
                    </div>
                    <div class="rejilla-2">
                        <div class="campo">
                            <label for="insumo-stock">Stock actual</label>
                            <input type="number" id="insumo-stock" name="stock" required
                                   min="0" max="99999" step="0.01" inputmode="decimal"
                                   aria-describedby="error-insumo-stock">
                            <p class="error-campo" id="error-insumo-stock" aria-live="polite"></p>
                        </div>
                        <div class="campo">
                            <label for="insumo-minimo">Stock mínimo</label>
                            <input type="number" id="insumo-minimo" name="stock_minimo" required
                                   min="0" max="99999" step="0.01" inputmode="decimal"
                                   aria-describedby="error-insumo-minimo">
                            <p class="error-campo" id="error-insumo-minimo" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="acciones-form">
                        <button type="submit" class="boton-primario" id="boton-guardar">Guardar insumo</button>
                        <button type="button" class="boton-secundario" id="cancelar-edicion" hidden>Cancelar</button>
                    </div>
                </form>
            </section>
        </div>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
