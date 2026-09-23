-- =========================================================
-- CoffeeDesk — versión 2 del esquema: límite de intentos de inicio de sesión (F-002)
-- Responsable: Bryan Gallegos
--
-- Requiere 01_usuarios_roles.sql. Se importa en local y en el hosting.
-- Ver las reglas de los scripts SQL en la cabecera de 01_usuarios_roles.sql.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO esquema_version (version, descripcion)
VALUES (2, 'Tabla intentos_login (límite de intentos de inicio de sesión)');

-- Un registro por cada intento fallido. Los de más de 1 día se borran en cada
-- inicio de sesión correcto (php/auth/limite_intentos.php).
CREATE TABLE intentos_login (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario   VARCHAR(30)  NOT NULL,
    ip        VARCHAR(45)  NOT NULL,   -- 45 = longitud máxima de una IPv6 en texto
    creado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_intentos_usuario (usuario, creado_en),
    KEY idx_intentos_ip (ip, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
