/**
 * Menú: búsqueda por nombre, filtro por categoría y modo edición del formulario (admin).
 * Requiere js/comun.js.
 */
const tablaProductos = document.getElementById('tabla-productos');
const campoBuscar = document.getElementById('buscar');
const filtroCategoria = document.getElementById('filtro-categoria');

function aplicarFiltros() {
    const texto = normalizar(campoBuscar.value.trim());
    const categoria = filtroCategoria.value;
    filtrarTabla(tablaProductos, (fila) =>
        normalizar(fila.dataset.nombre).includes(texto)
        && (categoria === '' || fila.dataset.categoria === categoria));
}

campoBuscar.addEventListener('input', aplicarFiltros);
filtroCategoria.addEventListener('change', aplicarFiltros);
// Enter en la búsqueda no recarga la página: el filtro ya es instantáneo
document.getElementById('form-filtros').addEventListener('submit', (e) => e.preventDefault());
aplicarFiltros();

// El formulario solo existe para el administrador
const formProducto = document.getElementById('form-producto');
if (formProducto) {
    modoEdicion({
        form: formProducto,
        titulo: document.getElementById('titulo-form-producto'),
        textoAgregar: 'Agregar producto',
        textoEditar: 'Editar producto',
        rellenar: (datos) => {
            formProducto.elements.nombre.value = datos.nombre;
            formProducto.elements.categoria_id.value = datos.categoria;
            formProducto.elements.precio.value = datos.precio;
            formProducto.elements.disponible.checked = datos.disponible === '1';
        },
    });
}
