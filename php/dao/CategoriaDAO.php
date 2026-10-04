<?php
/**
 * Acceso a datos de las categorías del menú.
 *
 * Responsable: Gabo
 */

require_once __DIR__ . '/../conexion.php';

class CategoriaDAO
{
    /** Categorías activas (filtros y formulario de productos). */
    public function listarActivas(): array
    {
        return consultar(
            'SELECT id, nombre
             FROM categorias
             WHERE activo = 1
             ORDER BY nombre ASC'
        );
    }

    /** Todas las categorías: primero las activas. */
    public function listarTodas(): array
    {
        return consultar(
            'SELECT id, nombre, activo
             FROM categorias
             ORDER BY activo DESC, nombre ASC'
        );
    }

    public function obtenerPorId(int $id): ?array
    {
        return consultar_uno(
            'SELECT id, nombre, activo
             FROM categorias
             WHERE id = ?',
            [$id]
        );
    }

    /** Categoría activa o null. */
    public function obtenerActiva(int $id): ?array
    {
        $categoria = $this->obtenerPorId($id);
        return $categoria !== null && (int) $categoria['activo'] === 1 ? $categoria : null;
    }

    /** Otra categoría (activa o eliminada) con ese nombre, o null. */
    public function buscarPorNombre(string $nombre, int $excluirId = 0): ?array
    {
        return consultar_uno(
            'SELECT id, nombre, activo
             FROM categorias
             WHERE nombre = ?
               AND id <> ?
             LIMIT 1',
            [$nombre, $excluirId]
        );
    }

    public function crear(string $nombre): int
    {
        return insertar('INSERT INTO categorias (nombre, activo) VALUES (?, 1)', [$nombre]);
    }

    public function renombrar(int $id, string $nombre): void
    {
        ejecutar(
            'UPDATE categorias
             SET nombre = ?
             WHERE id = ?
               AND activo = 1',
            [$nombre, $id]
        );
    }

    /** Vuelve a activar una categoría eliminada; con $nombre, también la renombra. */
    public function reactivar(int $id, ?string $nombre = null): void
    {
        ejecutar(
            'UPDATE categorias
             SET activo = 1, nombre = COALESCE(?, nombre)
             WHERE id = ?',
            [$nombre, $id]
        );
    }

    /**
     * Baja lógica, solo si no tiene productos activos. El NOT EXISTS repite la
     * comprobación en la misma sentencia por si alguien agregó un producto entre
     * la consulta previa y el UPDATE. Devuelve false si no se eliminó.
     */
    public function eliminarSiNoTieneProductos(int $id): bool
    {
        return ejecutar(
            'UPDATE categorias
             SET activo = 0
             WHERE id = ?
               AND activo = 1
               AND NOT EXISTS (
                   SELECT 1 FROM productos
                   WHERE categoria_id = ?
                     AND activo = 1
               )',
            [$id, $id]
        ) === 1;
    }
}
