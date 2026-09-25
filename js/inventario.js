/**
 * Inventario: búsqueda por nombre, filtro "solo stock bajo" y modo edición del formulario.
 * Requiere js/comun.js.
 */
const tablaInsumos = document.getElementById('tabla-insumos');
const campoBuscar = document.getElementById('buscar');
const soloBajo = document.getElementById('solo-bajo');

function aplicarFiltros() {
    const texto = normalizar(campoBuscar.value.trim());
    filtrarTabla(tablaInsumos, (fila) =>
        normalizar(fila.dataset.nombre).includes(texto)
        && (!soloBajo.checked || fila.dataset.bajo === '1'));
}

campoBuscar.addEventListener('input', aplicarFiltros);
soloBajo.addEventListener('change', aplicarFiltros);
document.getElementById('form-filtros').addEventListener('submit', (e) => e.preventDefault());
aplicarFiltros();

const formInsumo = document.getElementById('form-insumo');
modoEdicion({
    form: formInsumo,
    titulo: document.getElementById('titulo-form-insumo'),
    textoAgregar: 'Agregar insumo',
    textoEditar: 'Editar insumo',
    rellenar: (datos) => {
        formInsumo.elements.nombre.value = datos.nombre;
        formInsumo.elements.unidad.value = datos.unidad;
        formInsumo.elements.stock.value = datos.stock;
        formInsumo.elements.stock_minimo.value = datos.minimo;
    },
});
