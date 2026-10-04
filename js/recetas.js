/**
 * Recetas: el botón Editar de cada insumo carga su cantidad en el formulario
 * (guardar un insumo que ya está en la receta reemplaza su cantidad).
 * Requiere js/comun.js. Va dentro de una función para no crear variables globales.
 */
(() => {
    'use strict';

    const formReceta = document.getElementById('form-receta');
    if (!formReceta) return;

    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-editar-cantidad]');
        if (!boton) return;
        limpiarErrores(formReceta);
        formReceta.elements.insumo_id.value = boton.dataset.insumo;
        formReceta.elements.cantidad.value = boton.dataset.cantidad;
        formReceta.elements.cantidad.focus();
        formReceta.elements.cantidad.select();
    });
})();
