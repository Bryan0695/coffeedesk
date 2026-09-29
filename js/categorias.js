'use strict';

// Mantenimiento de categorías

const formCategoria = document.getElementById('form-categoria');

if (formCategoria) {

    const categoriaId       = document.getElementById('categoria-id');
    const categoriaNombre   = document.getElementById('categoria-nombre');
    const tituloFormulario  = document.getElementById('titulo-form-categoria');
    const botonGuardar      = document.getElementById('boton-guardar-categoria');
    const botonCancelar     = document.getElementById('cancelar-edicion-categoria');

    
    // Editar

    document.querySelectorAll('[data-editar-categoria]').forEach((boton) => {

            boton.addEventListener('click', () => {
                categoriaId.value               = boton.dataset.id;
                categoriaNombre.value           = boton.dataset.nombre;
                tituloFormulario.textContent    = 'Editar categoría';
                botonGuardar.textContent        = 'Guardar cambios';
                botonCancelar.hidden            = false;
                categoriaNombre.focus();
            });

        });

    
    // Cancelar edición
    botonCancelar.addEventListener('click', () => {

        formCategoria.reset();

        categoriaId.value            = '';
        tituloFormulario.textContent = 'Agregar categoría';
        botonGuardar.textContent     = 'Guardar categoría';
        botonCancelar.hidden         = true;

        categoriaNombre.focus();
    });
}

// -----------------------------------------------------------------------------
// Buscador de categorías
// -----------------------------------------------------------------------------

const buscarCategoria           = document.getElementById('buscar-categoria');
const filasCategorias           = document.querySelectorAll('[data-fila-categoria]');
const sinResultadosCategorias   = document.getElementById('sin-resultados-categorias');

if (buscarCategoria) {

    buscarCategoria.addEventListener('input', () => {

        const textoBusqueda = buscarCategoria.value.trim().toLocaleLowerCase('es');

        let visibles = 0;

        filasCategorias.forEach((fila) => {

            const nombre = fila.dataset.nombre ?? '';

            const coincide = nombre.includes(textoBusqueda);

            fila.hidden = !coincide;

            if (coincide) {
                visibles++;
            }
        });

        if (sinResultadosCategorias) {
            sinResultadosCategorias.hidden = 
            visibles !== 0;
        }
    });
}