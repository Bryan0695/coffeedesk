<?php
/**
 * Constantes de la aplicación que NO dependen del entorno ni de la petición.
 * Se pueden incluir desde la consola (herramientas/, tests/) sin credenciales.
 *
 * Responsable: Bryan Gallegos
 */

// ---- Sesión --------------------------------------------------------------
define('SESION_NOMBRE', 'COFFEEDESK_SID');
define('SESION_MINUTOS_INACTIVIDAD', 30);
define('SESION_HORAS_MAXIMAS', 12);          // vida máxima aunque haya actividad (F-007)
define('SESION_SEGUNDOS_REVALIDACION', 60);  // cada cuánto se revisa activo/rol en la BD (F-007)

// ---- Límite de intentos de inicio de sesión (F-002) ----------------------
define('LOGIN_MAX_INTENTOS', 5);       // por usuario
define('LOGIN_MAX_INTENTOS_IP', 20);   // por IP (una IP puede ser la wifi del local)
define('LOGIN_MINUTOS_BLOQUEO', 5);    // ventana en la que se cuentan los intentos

// ---- Contraseñas (F-003, F-010) ------------------------------------------
// Coste fijo: sin esto, PHP 8.0 (XAMPP) y PHP >= 8.4 usan costes distintos
// y el login reescribe el hash con un coste menor.
define('HASH_OPCIONES', ['cost' => 12]);
// Hash de una clave aleatoria descartada, con el mismo coste: iguala el tiempo
// de respuesta cuando el usuario no existe (evita enumerar usuarios).
define('HASH_FICTICIO', '$2y$12$g1ZhadDyP9HwCOV6dAPgVuLmiCI3ribfJUJ1NLCbR/NUsu7SIq9wi');

// ---- Validación (F-015) ---------------------------------------------------
// Única fuente de la regla del nombre de usuario: login.php, index.php (pattern="")
// y js/login.js la leen de aquí. El guion va escapado porque Chrome compila el
// atributo pattern con la bandera "v", donde un "-" suelto en la clase es inválido.
define('PATRON_USUARIO', '[A-Za-z0-9._\-]{3,30}');

// ---- Cabeceras de seguridad (F-006) --------------------------------------
// 'enforce' aplica la CSP; 'report' solo avisa en la consola del navegador.
define('CSP_MODO', 'enforce');

// ---- Roles (deben coincidir con la tabla `roles`) ------------------------
define('ROL_ADMIN',  'administrador');
define('ROL_MESERO', 'mesero');
