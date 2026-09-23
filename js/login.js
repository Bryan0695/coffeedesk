/**
 * Validación del formulario de login en el cliente.
 * (El servidor vuelve a validar todo en php/auth/login.php.)
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-login');
    const campoUsuario = document.getElementById('usuario');
    const campoClave = document.getElementById('clave');
    const botonVer = document.getElementById('ver-clave');

    // La regla viene del atributo pattern (definida una sola vez en config/constantes.php)
    const PATRON_USUARIO = new RegExp('^(?:' + campoUsuario.getAttribute('pattern') + ')$');

    function marcarError(campo, texto) {
        const error = document.getElementById('error-' + campo.id);
        error.textContent = texto;
        campo.setAttribute('aria-invalid', texto ? 'true' : 'false');
    }

    function validarUsuario() {
        const valor = campoUsuario.value.trim();
        if (valor === '') {
            marcarError(campoUsuario, 'Escribe tu usuario.');
            return false;
        }
        if (!PATRON_USUARIO.test(valor)) {
            marcarError(campoUsuario, 'Usa de 3 a 30 letras, números, punto, guion o guion bajo.');
            return false;
        }
        marcarError(campoUsuario, '');
        return true;
    }

    function validarClave() {
        if (campoClave.value === '') {
            marcarError(campoClave, 'Escribe tu contraseña.');
            return false;
        }
        marcarError(campoClave, '');
        return true;
    }

    campoUsuario.addEventListener('change', validarUsuario);
    campoClave.addEventListener('change', validarClave);

    form.addEventListener('submit', (evento) => {
        const usuarioOk = validarUsuario();
        const claveOk = validarClave();
        if (!usuarioOk || !claveOk) {
            evento.preventDefault();
            // Lleva el foco al primer campo con error
            (usuarioOk ? campoClave : campoUsuario).focus();
        }
    });

    // Mostrar / ocultar contraseña
    botonVer.addEventListener('click', () => {
        const visible = campoClave.type === 'text';
        campoClave.type = visible ? 'password' : 'text';
        botonVer.textContent = visible ? 'Mostrar' : 'Ocultar';
        botonVer.setAttribute('aria-pressed', String(!visible));
    });
});
