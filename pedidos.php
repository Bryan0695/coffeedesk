<?php
/**
 * Pedidos: registro de órdenes por mesa con cálculo automático del total y listado del día.
 * El servidor recalcula el total con los precios de la BD: el del navegador es solo informativo.
 *
 * Vista: Frederick · Lógica y datos: Gabo (php/pedidos/, php/dao/PedidoDAO.php).
 */
require_once __DIR__ . '/php/auth/sesion.php';
require_once __DIR__ . '/php/dao/ProductoDAO.php';
require_once __DIR__ . '/php/dao/PedidoDAO.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);

// Productos que se pueden pedir (activos, disponibles y de categorías activas)
$productos = array_map(
    static fn (array $p): array => [
        'id'        => (int) $p['id'],
        'nombre'    => $p['nombre'],
        'categoria' => $p['categoria'],
        'precio'    => precio_a_centavos((string) $p['precio']),
    ],
    (new ProductoDAO())->listarParaVenta()
);

// Pedidos de hoy y pendientes de días anteriores (estos muestran también la fecha)
$hoy = date('Y-m-d');
$pedidos = array_map(
    static function (array $p) use ($hoy): array {
        $fecha = new DateTime($p['creado_en']);
        return [
            'id'      => (int) $p['id'],
            'mesa'    => (int) $p['mesa'],
            'cliente' => $p['cliente'] ?? '',
            'total'   => precio_a_centavos((string) $p['total']),
            'estado'  => $p['estado'],
            'hora'    => $fecha->format($fecha->format('Y-m-d') === $hoy ? 'H:i' : 'd/m H:i'),
        ];
    },
    (new PedidoDAO())->listarDeHoyYPendientes()
);

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

        <?php if ($productos === []): ?>
            <div class="alerta alerta-aviso" role="status">
                <strong>Aviso:</strong>
                No hay productos disponibles para registrar pedidos.
            </div>
        <?php endif; ?>

        <form id="form-pedido" class="disposicion" action="<?= e(url('php/pedidos/registrar.php')) ?>"
              method="post" novalidate data-validar aria-labelledby="titulo-nuevo-pedido">
            <?= csrf_campo() ?>

            <section class="tarjeta" aria-labelledby="titulo-nuevo-pedido">
                <h2 id="titulo-nuevo-pedido">Nuevo pedido</h2>
                <fieldset class="lineas-pedido">
                    <legend>Productos del pedido</legend>
                    <ul id="lista-lineas" class="lista-lineas"></ul>
                    <p class="error-campo" id="error-lineas" aria-live="polite"></p>

                    <button type="button" class="boton-secundario" id="agregar-linea" <?= $productos === [] ? 'disabled' : '' ?>>
                        <?= icono('mas') ?> Agregar producto
                    </button>
                </fieldset>
            </section>

            <section class="tarjeta tarjeta-lateral" aria-labelledby="titulo-resumen">
                <h2 id="titulo-resumen">Resumen</h2>
                <div class="campo">
                    <label for="pedido-mesa">Mesa</label>
                    <select id="pedido-mesa" name="mesa" required aria-describedby="error-pedido-mesa">
                        <option value="">Elige una mesa</option>
                        <?php for ($m = 1; $m <= PEDIDO_MAX_MESAS; $m++): ?>
                            <option value="<?= $m ?>">Mesa <?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="error-campo" id="error-pedido-mesa" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label for="pedido-cliente">Cliente <span class="opcional">(opcional)</span></label>
                    <input type="text" id="pedido-cliente" name="cliente" maxlength="<?= PEDIDO_MAX_CLIENTE ?>" autocomplete="off">
                </div>

                <p class="total-pedido">
                    <span>Total</span>
                    <output id="total-pedido" for="lista-lineas" aria-live="polite">$0.00</output>
                </p>

                <button type="submit" class="boton-primario boton-bloque" <?= $productos === [] ? 'disabled' : '' ?>>
                    Registrar pedido
                </button>
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
                                <?= e($p['nombre']) ?> — <?= e($p['categoria']) ?> - <?= dinero($p['precio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-campo" data-error="producto" aria-live="polite"></p>
                </div>
                <div class="campo">
                    <label data-para="cantidad">Cantidad</label>
                    <input type="number" name="cantidad[]" data-campo="cantidad" value="1"
                           required min="1" max="<?= PEDIDO_MAX_CANTIDAD ?>" step="1" inputmode="numeric">
                    <p class="error-campo" data-error="cantidad" aria-live="polite"></p>
                </div>
                <p class="subtotal"><span class="texto-suave">Subtotal</span> <output data-subtotal>$0.00</output></p>
                <button type="button" class="boton-icono boton-fantasma-peligro" data-quitar><?= icono('eliminar') ?></button>
            </li>
        </template>

        <section class="tarjeta" aria-labelledby="titulo-pedidos-dia">
            <div class="tarjeta-cabecera">
                <h2 id="titulo-pedidos-dia">Pedidos de hoy y pendientes</h2>
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
                                <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
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
                                    <td><?= insignia_estado_pedido($p['estado']) ?></td>
                                    <td>
                                        <?php if ($p['estado'] === ESTADO_PENDIENTE): ?>
                                            <div class="acciones-tabla">
                                                <form action="<?= e(url('php/pedidos/estado.php')) ?>" method="post"
                                                      data-confirmar="¿Marcar el pedido #<?= (int) $p['id'] ?> como entregado?">
                                                    <?= csrf_campo() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                    <input type="hidden" name="estado" value="<?= ESTADO_ENTREGADO ?>">
                                                    <button type="submit" class="boton-fantasma" title="Entregar pedido"
                                                            aria-label="Marcar pedido #<?= (int) $p['id'] ?> como entregado"><?= icono('ok') ?></button>
                                                </form>
                                                <?php if (es_admin()): /* anular devuelve stock: solo administrador */ ?>
                                                    <form action="<?= e(url('php/pedidos/estado.php')) ?>" method="post"
                                                          data-confirmar="¿Anular el pedido #<?= (int) $p['id'] ?>?">
                                                        <?= csrf_campo() ?>
                                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                        <input type="hidden" name="estado" value="<?= ESTADO_ANULADO ?>">
                                                        <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Anular pedido"
                                                                aria-label="Anular pedido #<?= (int) $p['id'] ?>"><?= icono('alerta') ?></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
<?php require __DIR__ . '/php/partials/pie.php'; ?>
