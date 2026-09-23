<?php
/**
 * Punto de entrada único de seguridad. Incluirlo al inicio de TODA página o
 * archivo PHP:
 *
 *   require_once __DIR__ . '/php/auth/sesion.php';
 *   requiere_login();                    // cualquier usuario autenticado
 *   requiere_rol(ROL_ADMIN);             // solo administradores
 *   requiere_rol(ROL_ADMIN, ROL_MESERO); // cualquiera de los dos
 *
 * EN ARCHIVOS QUE RESPONDEN JSON (contratos de Jeremy):
 *   requiere_login_api();
 *   requiere_rol_api(ROL_ADMIN);
 *
 * Este archivo solo reúne las piezas; cada función vive en:
 *   php/comun/html.php         e()
 *   php/comun/respuesta.php    redirigir(), responder_json()
 *   php/comun/flash.php        mensaje_flash(), mostrar_flash(), hay_flash()
 *   php/comun/validacion.php   post_texto(), get_texto(), post_entero(), get_entero()
 *   php/comun/cabeceras.php    enviar_cabeceras_seguridad(), es_https()
 *   php/auth/csrf.php          csrf_token(), csrf_campo(), csrf_valido()
 *   php/auth/autorizacion.php  usuario_actual(), tiene_rol(), es_admin(), requiere_*()
 *   php/auth/arranque_sesion.php  iniciar_sesion()
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../comun/html.php';
require_once __DIR__ . '/../comun/respuesta.php';
require_once __DIR__ . '/../comun/flash.php';
require_once __DIR__ . '/../comun/validacion.php';
require_once __DIR__ . '/../comun/cabeceras.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/autorizacion.php';
require_once __DIR__ . '/arranque_sesion.php';

enviar_cabeceras_seguridad();
iniciar_sesion();
