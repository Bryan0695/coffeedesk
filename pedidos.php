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
require_once __DIR__ . '/php/conexion.php';

requiere_rol(ROL_ADMIN, ROL_MESERO);

$mesas = 10;          // PROVISIONAL (Gabo): o una tabla `mesas`
$maxCantidad = 20;

// Productos disponibles para registrar pedidos

// Solo mostramos productos:
// - disponibles para la venta
// - pertenecientes a categorías activas

$productosDb = consultar(
    'SELECT
        p.id,
        p.nombre,
        p.precio,
        p.categoria_id,
        c.nombre AS categoria
     FROM productos AS p
     INNER JOIN categorias AS c
        ON c.id = p.categoria_id
     WHERE p.disponible = 1
        AND p.activo = 1
        AND c.activo = 1
     ORDER BY c.nombre ASC, p.nombre ASC'
);

$productos = array_map(
    static function (array $producto): array {
        return [
            'id'            => (int) $producto['id'],
            'nombre'        => $producto['nombre'],
            'categoria_id'  => (int) $producto['categoria_id'],
            'categoria'     => $producto['categoria'],
            'precio'        => (int) round(((float) $producto['precio']) * 100), // 2.50 en MySQL -> 250 centavos en PHP
        ];
    },
    $productosDb
);

// -----------------------------------------------------------------------------
// Pedidos registrados hoy
// -----------------------------------------------------------------------------
// CURDATE() utiliza la zona horaria configurada por php/conexion.php.
// -----------------------------------------------------------------------------

$pedidosDb = consultar(
    'SELECT
        p.id,
        p.mesa,
        p.cliente,
        p.total,
        p.estado,
        p.creado_en
     FROM pedidos AS p
     WHERE p.creado_en >= CURDATE()
       AND p.creado_en < CURDATE() + INTERVAL 1 DAY
     ORDER BY p.creado_en DESC, p.id DESC'
);

// Adaptamos los tipos y nombres al formato que ya utiliza la vista.
$pedidos = array_map(
    static function (array $pedido): array {

        $fecha = new DateTime($pedido['creado_en']);

        return [
            'id' => (int) $pedido['id'],
            'mesa' => (int) $pedido['mesa'],
            'cliente' => $pedido['cliente'] ?? '',

            // DECIMAL de MySQL -> centavos para dinero()
            'total' => (int) round(
                ((float) $pedido['total']) * 100
            ),

            // Guardamos internamente el estado normalizado.
            'estado' => $pedido['estado'],

            // Solo necesitamos hora y minuto en esta vista.
            'hora' => $fecha->format('H:i'),
        ];
    },
    $pedidosDb
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
                                    <td>
                                      
                                        <?php
                                            $claseEstado = match ($p['estado']) {
                                                'entregado' => 'insignia-ok',
                                                'anulado'   => 'insignia-error',
                                                default     => 'insignia-info',
                                            };

                                            $textoEstado = match ($p['estado']) {
                                                'entregado' => 'Entregado',
                                                'anulado'   => 'Anulado',
                                                default     => 'Pendiente',
                                            };
                                        ?>

                                        <span class="insignia <?= e($claseEstado) ?>">
                                            <?= e($textoEstado) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php if ($p['estado'] === 'pendiente'): ?>

                                            <div class="acciones-tabla">

                                                <form action="<?= e(url('php/pedidos/estado.php')) ?>" method="post"
                                                    data-confirmar="¿Marcar el pedido #<?= (int) $p['id'] ?> como entregado?">
                                                    <?= csrf_campo() ?>

                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                    <input type="hidden" name="estado" value="entregado">

                                                    <button type="submit" class="boton-fantasma" title="Entregar Pedido" aria-label="Marcar pedido #<?= (int) $p['id'] ?> como entregado">
                                                        <?= icono('ok') ?>
                                                    </button>

                                                </form>

                                             <!-- Validación de Admin para anular pedidos -->
                                                <?php if (es_admin()): ?>
                                                    <form action="<?= e(url('php/pedidos/estado.php')) ?>" method="post"
                                                        data-confirmar="¿Anular el pedido #<?= (int) $p['id'] ?>?">

                                                        <?= csrf_campo() ?>

                                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                        <input type="hidden" name="estado" value="anulado">

                                                        <button type="submit" class="boton-fantasma boton-fantasma-peligro" title="Anular Pedido"
                                                            aria-label="Anular pedido #<?= (int) $p['id'] ?>">
                                                            <?= icono('alerta') ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="texto-secundario">
                                                
                                            </span>

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
