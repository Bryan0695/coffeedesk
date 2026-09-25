<?php
/**
 * Pedidos: registro de órdenes por mesa con cálculo automático del total y listado del día.
 *
 * Vista: Frederick · Lógica y datos: Gabo.
 * Los nombres de los campos y los archivos de destino son PROVISIONALES
 * hasta que Jeremy entregue los contratos. El servidor debe recalcular el
 * total con los precios de la BD: el total del navegador es solo informativo.
 */
require_once __DIR__ . '/php/auth/sesion.php';
requiere_rol(ROL_ADMIN, ROL_MESERO);

$mesas = 10;          // PROVISIONAL (Gabo): o una tabla `mesas`
$maxCantidad = 20;

// PROVISIONAL (Gabo): consultar('SELECT id, nombre, precio FROM productos WHERE disponible = 1 ORDER BY nombre'). Precio en centavos.
$productos = [
    ['id' => 1, 'nombre' => 'Café americano',     'precio' => 150],
    ['id' => 2, 'nombre' => 'Capuchino',          'precio' => 250],
    ['id' => 5, 'nombre' => 'Cheesecake de mora', 'precio' => 325],
    ['id' => 4, 'nombre' => 'Limonada',           'precio' => 200],
    ['id' => 6, 'nombre' => 'Sánduche de jamón',  'precio' => 300],
];
// PROVISIONAL (Gabo): pedidos del día
$pedidos = [
    ['id' => 12, 'mesa' => 3, 'cliente' => 'Ana', 'total' => 700, 'estado' => 'Pendiente', 'hora' => '09:15'],
    ['id' => 11, 'mesa' => 1, 'cliente' => '',    'total' => 400, 'estado' => 'Entregado', 'hora' => '08:50'],
];

$tituloPagina = 'Pedidos';
$scripts = ['js/comun.js', 'js/pedidos.js'];
require __DIR__ . '/php/partials/cabecera.php';
?>
        <div class="encabezado-pagina">
            <div>
                <h1>Pedidos</h1>
                <p class="descripcion">Registra la orden de una mesa; el total se calcula mientras agregas productos.</p>
            </div>
        </div>

        <form id="form-pedido" class="disposicion" action="<?= e(url('php/pedidos/registrar.php')) ?>"
              method="post" novalidate data-validar aria-labelledby="titulo-nuevo-pedido">
            <?= csrf_campo() ?>

            <section class="tarjeta" aria-labelledby="titulo-nuevo-pedido">
                <h2 id="titulo-nuevo-pedido">Nuevo pedido</h2>
                <fieldset class="lineas-pedido">
                    <legend>Productos del pedido</legend>
                    <ul id="lista-lineas" class="lista-lineas"></ul>
                    <p class="error-campo" id="error-lineas" aria-live="polite"></p>
                    <button type="button" class="boton-secundario" id="agregar-linea"><?= icono('mas') ?> Agregar producto</button>
                </fieldset>
            </section>

            <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-resumen">
                <h2 id="titulo-resumen">Resumen</h2>
                <div class="campo">
                    <label for="pedido-mesa">Mesa</label>
                    <select id="pedido-mesa" name="mesa" required aria-describedby="error-pedido-mesa">
                        <option value="">Elige una mesa</option>
                        <?php for ($m = 1; $m <= $mesas; $m++): ?>
                            <option value="<?= $m ?>">Mesa <?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="error-campo" id="error-pedido-mesa" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label for="pedido-cliente">Cliente <span class="opcional">(opcional)</span></label>
                    <input type="text" id="pedido-cliente" name="cliente" maxlength="60" autocomplete="off">
                </div>

                <p class="total-pedido">
                    <span>Total</span>
                    <output id="total-pedido" for="lista-lineas" aria-live="polite">$0.00</output>
                </p>

                <button type="submit" class="boton-primario boton-bloque">Registrar pedido</button>
            </section>
        </form>

        <template id="plantilla-linea">
            <li class="linea-pedido">
                <div class="campo">
                    <label data-para="producto">Producto</label>
                    <select name="producto_id[]" data-campo="producto" required>
                        <option value="">Elige un producto</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" data-precio="<?= (int) $p['precio'] ?>">
                                <?= e($p['nombre']) ?> — <?= dinero($p['precio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-campo" data-error="producto" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label data-para="cantidad">Cantidad</label>
                    <input type="number" name="cantidad[]" data-campo="cantidad" value="1"
                           required min="1" max="<?= $maxCantidad ?>" step="1" inputmode="numeric">
                    <p class="error-campo" data-error="cantidad" aria-live="polite"></p>
                </div>
                <p class="subtotal"><span class="texto-suave">Subtotal</span> <output data-subtotal>$0.00</output></p>
                <button type="button" class="boton-icono boton-fantasma-peligro" data-quitar><?= icono('eliminar') ?></button>
            </li>
        </template>

        <section class="tarjeta" aria-labelledby="titulo-pedidos-dia">
            <div class="tarjeta-cabecera">
                <h2 id="titulo-pedidos-dia">Pedidos de hoy</h2>
                <p class="resultado-filtro"><?= count($pedidos) ?> registrados</p>
            </div>
            <?php if ($pedidos === []): ?>
                <p class="estado-vacio">Todavía no hay pedidos registrados hoy.</p>
            <?php else: ?>
                <div class="tabla-envoltura" role="region" aria-labelledby="titulo-pedidos-dia" tabindex="0">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">N.º</th>
                                <th scope="col">Hora</th>
                                <th scope="col">Mesa</th>
                                <th scope="col">Cliente</th>
                                <th scope="col" class="numero">Total</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr>
                                    <th scope="row">#<?= (int) $p['id'] ?></th>
                                    <td class="texto-suave"><?= e($p['hora']) ?></td>
                                    <td>Mesa <?= (int) $p['mesa'] ?></td>
                                    <td><?= $p['cliente'] !== '' ? e($p['cliente']) : '<span class="texto-suave">—</span>' ?></td>
                                    <td class="numero"><?= dinero($p['total']) ?></td>
                                    <td>
                                        <span class="insignia <?= $p['estado'] === 'Entregado' ? 'insignia-ok' : 'insignia-info' ?>"><?= e($p['estado']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
