-- =========================================================
-- CoffeeDesk — tablas de autenticación (roles y usuarios)
-- Responsable: Bryan Gallegos · Integrar en el script general de Gabo
--
-- Local:   selecciona la base "coffeedesk" en phpMyAdmin e importa.
-- Hosting: selecciona la base if0_..._coffeedesk en phpMyAdmin e importa.
-- (Este archivo no usa CREATE DATABASE ni USE, así sirve en ambos.)
-- Probado en MySQL 8.0 (modo estricto). INSERT IGNORE permite re-importarlo sin duplicar.
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(20)  NOT NULL,
    descripcion VARCHAR(100) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(80)      NOT NULL,
    usuario       VARCHAR(30)      NOT NULL,
    clave_hash    VARCHAR(255)     NOT NULL,      -- resultado de password_hash()
    rol_id        TINYINT UNSIGNED NOT NULL,
    activo        TINYINT UNSIGNED NOT NULL DEFAULT 1, -- 1 = activo, 0 = inactivo
    ultimo_acceso DATETIME         NULL,
    creado_en     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Datos iniciales ----------------------------------------------------
INSERT IGNORE INTO roles (id, nombre, descripcion) VALUES
    (1, 'administrador', 'Acceso total: menú, inventario, pedidos'),
    (2, 'mesero',        'Registra pedidos y consulta el menú');

-- Usuarios de prueba (CAMBIAR contraseñas antes de la defensa si se desea)
--   admin  / Admin123*
--   mesero / Mesero123*
-- Hashes generados con: php herramientas/generar_hash.php "Admin123*"
INSERT IGNORE INTO usuarios (nombre, usuario, clave_hash, rol_id) VALUES
    ('Administrador General', 'admin',
     '$2y$12$DKjU805WxhgDnmIVt7Fb7.TKI3NewpJ4DfA5sEifHZfOBdOdqSUu.', 1),
    ('Mesero de Turno', 'mesero',
     '$2y$12$YPhKy1mm4YekSlCEAlXj.ed2QibyDM.vxX9GwMNZgUfhXnscm8hs6', 2);
