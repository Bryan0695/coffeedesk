/**
 * Utilidades de las páginas internas. Se carga antes del JS de cada módulo.
 *
 * - Valida en el cliente los formularios con data-validar usando las reglas del
 *   propio HTML (required, min, max, step, maxlength). El servidor vuelve a validar.
 *   El mensaje de cada campo va en el elemento con id "error-<id del campo>".
 * - Pide confirmación antes de enviar formularios con data-confirmar (eliminar).
 * - filtrarTabla() y modoEdicion(): los usan menu.js, inventario.js y categorias.js.
 */

function mensajeError(campo) {
    const v = campo.validity;
    if (campo.required && campo.type === 'text' && campo.value.trim() === '') {
        return 'Este campo es obligatorio.';
    }
    if (v.valid) return '';
    if (v.valueMissing) return campo.tagName === 'SELECT' ? 'Elige una opción.' : 'Este campo es obligatorio.';
    if (v.badInput) return 'Escribe un número válido.';
    if (v.rangeUnderflow) return 'El valor mínimo es ' + campo.min + '.';
    if (v.rangeOverflow) return 'El valor máximo es ' + campo.max + '.';
    if (v.stepMismatch) {
        const decimales = (campo.step.split('.')[1] || '').length;
        return decimales === 0 ? 'Escribe un número entero.' : `Usa como máximo ${decimales} decimales.`;
    }
    return campo.validationMessage;
}

function esCampo(elemento) {
    return elemento.matches('input:not([type="hidden"]), select, textarea');
}

function validarCampo(campo) {
    const texto = mensajeError(campo);
    const error = document.getElementById('error-' + campo.id);
    if (error) error.textContent = texto;
    campo.setAttribute('aria-invalid', texto ? 'true' : 'false');
    return texto === '';
}

function limpiarErrores(form) {
    form.querySelectorAll('.error-campo').forEach((p) => { p.textContent = ''; });
    form.querySelectorAll('[aria-invalid]').forEach((c) => c.removeAttribute('aria-invalid'));
}

document.addEventListener('change', (evento) => {
    const campo = evento.target;
    if (campo.form && campo.form.hasAttribute('data-validar') && esCampo(campo)) {
        validarCampo(campo);
    }
});

document.addEventListener('submit', (evento) => {
    const form = evento.target;
    if (evento.defaultPrevented) return;

    if (form.hasAttribute('data-validar')) {
        const invalidos = [...form.elements].filter((c) => esCampo(c) && !validarCampo(c));
        if (invalidos.length > 0) {
            evento.preventDefault();
            invalidos[0].focus(); // lleva el foco al primer campo con error
            return;
        }
    }
    if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) {
        evento.preventDefault();
    }
});

/** Minúsculas y sin tildes: "Café" y "cafe" coinciden al buscar. */
function normalizar(texto) {
    return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

/**
 * Oculta las filas de la tabla que no cumplen la condición y anuncia cuántas quedan
 * en #resultado-filtro (aria-live). Muestra #sin-resultados si no queda ninguna.
 */
function filtrarTabla(tabla, cumple) {
    const filas = [...tabla.tBodies[0].rows];
    let visibles = 0;
    for (const fila of filas) {
        fila.hidden = !cumple(fila);
        if (!fila.hidden) visibles++;
    }
    document.getElementById('resultado-filtro').textContent = `Mostrando ${visibles} de ${filas.length}.`;
    document.getElementById('sin-resultados').hidden = visibles > 0;
}

/**
 * Los botones [data-editar] cargan su fila en el formulario (rellenar recibe su dataset).
 * "Cancelar edición" vuelve al modo agregar y devuelve el foco al botón que se pulsó.
 * Los enlaces "Nuevo …" (href="#id-del-form") también vuelven al modo agregar; si no,
 * guardarían sobre el registro que se estaba editando.
 */
function modoEdicion({ form, titulo, textoAgregar, textoEditar, rellenar }) {
    const cancelar = form.querySelector('#cancelar-edicion');
    const campoId = form.elements.id;
    let botonOrigen = null;

    function modoAgregar() {
        form.reset();
        limpiarErrores(form);
        // reset() no vacía los hidden (id, stock_original…): se limpian a mano, salvo el token
        form.querySelectorAll('input[type="hidden"]:not([name="csrf"])').forEach((c) => { c.value = ''; });
        titulo.textContent = textoAgregar;
        cancelar.hidden = true;
    }

    // getAttribute: form.id devolvería el <input name="id"> del propio formulario
    document.querySelectorAll(`a[href="#${form.getAttribute('id')}"]`).forEach((enlace) => {
        enlace.addEventListener('click', (evento) => {
            // Sin el salto al ancla, que quitaría el foco; focus() ya desplaza hasta el campo
            evento.preventDefault();
            modoAgregar();
            form.querySelector('input:not([type="hidden"])').focus();
        });
    });

    document.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-editar]');
        if (!boton) return;
        botonOrigen = boton;
        form.reset();
        limpiarErrores(form);
        rellenar(boton.dataset);
        campoId.value = boton.dataset.id;
        titulo.textContent = `${textoEditar}: ${boton.dataset.nombre}`;
        cancelar.hidden = false;
        form.querySelector('input:not([type="hidden"])').focus();
    });

    cancelar.addEventListener('click', () => {
        modoAgregar();
        if (botonOrigen) botonOrigen.focus();
    });
}
