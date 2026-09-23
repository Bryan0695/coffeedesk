-- #########################################################################
-- ##                                                                     ##
-- ##   ⚠  NO IMPORTAR EN INFINITYFREE NI EN NINGÚN SERVIDOR PÚBLICO  ⚠   ##
-- ##                                                                     ##
-- ##   Crea usuarios con contraseñas PÚBLICAS (están en el README).      ##
-- ##   Solo para XAMPP y para las pruebas automáticas.                   ##
-- ##   En el hosting el administrador se crea con                        ##
-- ##   herramientas/crear_admin.php (ver docs/despliegue_infinityfree.md)##
-- ##                                                                     ##
-- #########################################################################
--
-- Requiere 01_usuarios_roles.sql. Importar al final (después de los 0X_).
--
--   admin  / Admin123*   (administrador)
--   mesero / Mesero123*  (mesero)
-- Hashes generados con: php herramientas/generar_hash.php "Admin123*"

SET NAMES utf8mb4;

INSERT INTO usuarios (nombre, usuario, clave_hash, rol_id) VALUES
    ('Administrador General', 'admin',
     '$2y$12$DKjU805WxhgDnmIVt7Fb7.TKI3NewpJ4DfA5sEifHZfOBdOdqSUu.', 1),
    ('Mesero de Turno', 'mesero',
     '$2y$12$YPhKy1mm4YekSlCEAlXj.ed2QibyDM.vxX9GwMNZgUfhXnscm8hs6', 2);
