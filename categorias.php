<?php

/**
 * Mantenimiento de categorías.
 *
 * Funciones:
 * - listar categorías activas e inactivas;
 * - crear categorías;
 * - editar categorías;
 * - eliminación lógica;
 * - reactivación mediante categoria_guardar.php.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/php/auth/sesion.php';
require_once __DIR__ . '/php/conexion.php';

requiere_rol(ROL_ADMIN);

// Consultar categorías

$categorias = consultar(
    'SELECT id, nombre, activo
     FROM categorias
     ORDER BY activo DESC, nombre ASC'
);

// Configuración de página

$tituloPagina = 'Categorías';
$scripts = ['js/comun.js', 'js/categorias.js'];

require __DIR__ . '/php/partials/cabecera.php';
?>

<h1>Categorías</h1>

<p class="texto-suave">
    Administra las categorías utilizadas por los productos del menú.
</p>

<?php if (es_admin()): ?>
    <a class="boton-secundario" href="<?= e(url('menu.php')) ?>">
    <?= icono('editar') ?> Menú </a>
<?php endif; ?>

<div class="disposicion">

    <!-- Listado de Categorias -->
    <section
        class="tarjeta"
        aria-labelledby="titulo-listado-categorias">

        <div class="tarjeta-cabecera">

            <div>
                <h2 id="titulo-listado-categorias">
                    Categorías registradas
                </h2>

                <p class="texto-suave">
                    Consulta, modifica o elimina categorías del menú.
                </p>
            </div>

        </div>

        <div class="barra-herramientas">

            <div class="campo campo-busqueda">

                <label for="buscar-categoria"> Buscar categoría </label>
                <?= icono('buscar') ?>
                <input type="search" id="buscar-categoria" placeholder="Ej. Bebidas calientes" autocomplete="off" >

            </div>

        </div>

        <div
            class="tabla-envoltura"
            role="region"
            aria-labelledby="titulo-listado-categorias"
            tabindex="0">

            <table>

                <thead>
                    <tr>
                        <th scope="col"> Categoría </th>
                        <th scope="col"> Estado </th>

                        <th scope="col"> 
                            <span class="visualmente-oculto"> Acciones </span>
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($categorias === []): ?>

                        <tr>
                            <td colspan="3">
                                No existen categorías registradas.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($categorias as $categoria): ?>

                            <!-- <tr> -->
                            <tr data-fila-categoria data-nombre="<?= e(mb_strtolower($categoria['nombre'])) ?>">

                                <!-- Nombre -->
                                <th scope="row">
                                    <?= e($categoria['nombre']) ?>
                                </th>

                                <!-- Estado -->
                                <td>

                                    <?php if ((int) $categoria['activo'] === 1): ?>

                                        <span class="insignia insignia-ok">
                                            Activa
                                        </span>

                                    <?php else: ?>

                                        <span class="insignia insignia-info">
                                            Eliminada
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Acciones -->
                                <td>

                                    <?php if ((int) $categoria['activo'] === 1): ?>

                                        <div class="acciones-tabla">

                                            <!-- Editar -->
                                            <button
                                                type="button"
                                                class="boton-icono boton-fantasma"
                                                data-editar-categoria
                                                data-id="<?= (int) $categoria['id'] ?>"
                                                data-nombre="<?= e($categoria['nombre']) ?>"
                                                title="Editar categoría"
                                                aria-label="Editar categoría <?= e($categoria['nombre']) ?>">
                                                <?= icono('editar') ?>
                                            </button>

                                            <!-- Eliminar lógico -->
                                            <form
                                                action="<?= e(url('php/menu/categoria_eliminar.php')) ?>"
                                                method="post"
                                                data-confirmar="¿Eliminar la categoría «<?= e($categoria['nombre']) ?>»?">

                                                <?= csrf_campo() ?>

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $categoria['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="boton-icono boton-fantasma-peligro"
                                                    title="Eliminar categoría"
                                                    aria-label="Eliminar categoría <?= e($categoria['nombre']) ?>">
                                                    <?= icono('eliminar') ?>
                                                </button>

                                            </form>

                                        </div>

                                    <?php else: ?>
                                        <!-- Reactivación de categoria -->
                                        <div class="acciones-tabla">

                                            <form action="<?= e(url('php/menu/categoria_reactivar.php')) ?>"
                                                method="post" 
                                                data-confirmar="¿Reactivar la categoría «<?= e($categoria['nombre']) ?>»?">
                                                <?= csrf_campo() ?>

                                                <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>" >

                                                <button
                                                    type="submit"
                                                    class="boton-icono boton-fantasma"
                                                    title="Reactivar categoría"
                                                    aria-label="Reactivar categoría <?= e($categoria['nombre']) ?>">
                                                    <?= icono('ok') ?>
                                                </button>

                                            </form>
                                        </div>

                                    <?php endif; ?>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

                <tr id="sin-resultados-categorias" hidden >
                    <td colspan="3">
                        No se encontraron categorías que coincidan con la búsqueda.
                    </td>
                </tr>

            </table>
        </div>
    </section>


    <!-- Formulario / Mantenimiento -->

    <section
        class="tarjeta tarjeta-lateral"
        aria-labelledby="titulo-form-categoria">

        <div class="tarjeta-cabecera">

            <div>
                <h2 id="titulo-form-categoria">
                    Agregar categoría
                </h2>

                <p class="texto-suave">
                    Registra una nueva categoría o modifica una existente.
                </p>
            </div>

        </div>

        <form id="form-categoria" action="<?= e(url('php/menu/categoria_guardar.php')) ?>"
            method="post" novalidate data-validar>

            <?= csrf_campo() ?>

            <!-- ID vacío = crear / con valor = editar -->
            <input type="hidden" id="categoria-id" name="id" value="">

            <div class="campo">

                <label for="categoria-nombre"> Nombre </label>

                <input type="text" id="categoria-nombre" name="nombre" maxlength="60"
                    required autocomplete="off" aria-describedby="error-categoria-nombre">

                <p class="error-campo" id="error-categoria-nombre" aria-live="polite"></p>

            </div>

            <div class="acciones-form">

                <button type="submit" class="boton-primario" id="boton-guardar-categoria">
                    Guardar categoría
                </button>

                <button type="button" class="boton-secundario" id="cancelar-edicion-categoria" hidden>
                    Cancelar
                </button>

            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/php/partials/pie.php'; ?>