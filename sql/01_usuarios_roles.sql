-- =========================================================
-- CoffeeDesk — versión 1 del esquema: control de versiones, roles y usuarios
-- Responsable: Bryan Gallegos
--
-- Local:   selecciona la base "coffeedesk" en phpMyAdmin e importa.
-- Hosting: selecciona la base if0_..._coffeedesk en phpMyAdmin e importa.
-- (Este archivo no usa CREATE DATABASE ni USE, así sirve en ambos.)
-- Compatible con MySQL 8 y MariaDB (XAMPP).
--
-- REGLAS DE LOS SCRIPTS SQL (F-016)
--   * Nunca editar un script que ya se importó en alguna base: los cambios
--     van en un script NUEVO (03_, 04_, …) que use ALTER TABLE.
--   * Cada script registra su número en esquema_version justo al inicio.
--     Si se importa dos veces, falla en esa línea ("Duplicate entry") ANTES
--     de tocar nada más, y así se nota que ya estaba aplicado.
--   * Para ver qué tiene una base:  SELECT * FROM esquema_version;
--   * Los usuarios de prueba NO están aquí: van en 90_seed_solo_local.sql.
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS esquema_version (
    version     SMALLINT UNSIGNED NOT NULL,
    descripcion VARCHAR(150)      NOT NULL,
    aplicado_en DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO esquema_version (version, descripcion)
VALUES (1, 'Control de versiones, roles y usuarios');

CREATE TABLE roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(20)  NOT NULL,
    descripcion VARCHAR(100) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
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

-- ---- Datos base (necesarios también en el hosting) ----------------------
INSERT INTO roles (id, nombre, descripcion) VALUES
    (1, 'administrador', 'Acceso total: menú, inventario, pedidos'),
    (2, 'mesero',        'Registra pedidos y consulta el menú');

-- El primer administrador del hosting se crea con herramientas/crear_admin.php
