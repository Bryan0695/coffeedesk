/**
 * Pedidos: agregar y quitar líneas de productos y calcular subtotales y total en vivo.
 * Los importes se manejan en centavos enteros (sin errores de redondeo de float).
 * Requiere js/comun.js (valida cada línea con las reglas del HTML).
 */
const formPedido = document.getElementById('form-pedido');
const listaLineas = document.getElementById('lista-lineas');
const plantillaLinea = document.getElementById('plantilla-linea');
const botonAgregar = document.getElementById('agregar-linea');
const salidaTotal = document.getElementById('total-pedido');
const errorLineas = document.getElementById('error-lineas');
let contadorLineas = 0;

const formatoDinero = (centavos) => '$' + (centavos / 100).toFixed(2);

function subtotalDe(linea) {
    const opcion = linea.querySelector('[data-campo="producto"]').selectedOptions[0];
    const cantidad = Number(linea.querySelector('[data-campo="cantidad"]').value);
    const precio = Number(opcion ? opcion.dataset.precio || 0 : 0);
    return Number.isInteger(cantidad) && cantidad > 0 ? precio * cantidad : 0;
}

function recalcular() {
    let total = 0;
    [...listaLineas.children].forEach((linea, i) => {
        const subtotal = subtotalDe(linea);
        linea.querySelector('[data-subtotal]').textContent = formatoDinero(subtotal);
        linea.querySelector('[data-quitar]').setAttribute('aria-label', `Quitar producto ${i + 1}`);
        total += subtotal;
    });
    salidaTotal.textContent = formatoDinero(total);
}

function agregarLinea(enfocar) {
    contadorLineas++;
    const linea = plantillaLinea.content.firstElementChild.cloneNode(true);
    // ids únicos para que cada <label> y mensaje de error quede asociado a su campo
    linea.querySelectorAll('[data-campo]').forEach((campo) => {
        const nombre = campo.dataset.campo;
        campo.id = `linea-${contadorLineas}-${nombre}`;
        campo.setAttribute('aria-describedby', 'error-' + campo.id);
        linea.querySelector(`[data-para="${nombre}"]`).htmlFor = campo.id;
        linea.querySelector(`[data-error="${nombre}"]`).id = 'error-' + campo.id;
    });
    listaLineas.append(linea);
    errorLineas.textContent = '';
    recalcular();
    if (enfocar) linea.querySelector('select').focus();
}

botonAgregar.addEventListener('click', () => agregarLinea(true));
listaLineas.addEventListener('input', recalcular);

listaLineas.addEventListener('click', (evento) => {
    const boton = evento.target.closest('[data-quitar]');
    if (!boton) return;
    boton.closest('.linea-pedido').remove();
    recalcular();
    botonAgregar.focus(); // el foco no se pierde al borrar el botón pulsado
});

formPedido.addEventListener('submit', (evento) => {
    if (listaLineas.children.length === 0) {
        evento.preventDefault();
        errorLineas.textContent = 'Agrega al menos un producto al pedido.';
        botonAgregar.focus();
    }
});

agregarLinea(false);
