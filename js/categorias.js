/**
 * Categorías: búsqueda por nombre (sin distinguir tildes) y modo edición del formulario.
 * Requiere js/comun.js. Va dentro de una función para no crear variables globales.
 */
(() => {
    'use strict';

    const tablaCategorias = document.getElementById('tabla-categorias');
    const campoBuscar = document.getElementById('buscar');
    const formCategoria = document.getElementById('form-categoria');

    // Sin categorías la página no muestra la tabla ni el buscador
    if (tablaCategorias && campoBuscar) {
        const aplicarFiltros = () => {
            const texto = normalizar(campoBuscar.value.trim());
            filtrarTabla(tablaCategorias, (fila) => normalizar(fila.dataset.nombre).includes(texto));
        };
        campoBuscar.addEventListener('input', aplicarFiltros);
        document.getElementById('form-filtros').addEventListener('submit', (e) => e.preventDefault());
        aplicarFiltros();
    }

    if (formCategoria) {
        modoEdicion({
            form: formCategoria,
            titulo: document.getElementById('titulo-form-categoria'),
            textoAgregar: 'Agregar categoría',
            textoEditar: 'Editar categoría',
            rellenar: (datos) => {
                formCategoria.elements.nombre.value = datos.nombre;
            },
        });
    }
})();
