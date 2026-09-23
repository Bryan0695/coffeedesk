<?php
/**
 * Conexión PHP–MySQL (mysqli) y helpers de consultas preparadas (F-014).
 *
 * USO (todas las consultas son preparadas; NUNCA concatenar variables en el SQL):
 *
 *   $filas = consultar('SELECT id, nombre FROM productos WHERE categoria_id = ?', [$idCategoria]);
 *   $fila  = consultar_uno('SELECT * FROM productos WHERE id = ?', [$id]);          // array o null
 *   $n     = ejecutar('UPDATE productos SET agotado = 1 WHERE id = ?', [$id]);       // filas afectadas
 *   $id    = insertar('INSERT INTO categorias (nombre) VALUES (?)', [$nombre]);      // id nuevo
 *
 *   $idPedido = transaccion(function () use ($mesa, $lineas) {
 *       $id = insertar('INSERT INTO pedidos (mesa) VALUES (?)', [$mesa]);
 *       // … más consultas: si alguna lanza una excepción, se deshace todo
 *       return $id;
 *   });
 *
 * El tipo de cada parámetro se deduce del valor PHP: int/bool → i, float → d,
 * el resto → s. Por eso hay que pasar enteros como int (post_entero(), (int) $x):
 * LIMIT ? o INTERVAL ? MINUTE con un texto fallan o se comportan mal.
 *
 * Responsable: Bryan Gallegos
 */

require_once __DIR__ . '/../config/config.php';

// Hace que mysqli lance excepciones en lugar de fallar en silencio
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function conectar(): mysqli
{
    static $conexion = null; // se reutiliza la misma conexión en cada petición

    if ($conexion instanceof mysqli) {
        return $conexion;
    }

    try {
        $nueva = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $nueva->set_charset('utf8mb4'); // tildes y ñ correctas
        // F-012: NOW() y CURRENT_TIMESTAMP con la misma hora que date() de PHP.
        // Se usa el desfase (-05:00) porque el hosting puede no tener cargadas
        // las tablas de zonas horarias de MySQL.
        $nueva->query("SET time_zone = '" . (new DateTime())->format('P') . "'");
    } catch (mysqli_sql_exception $e) {
        // El manejador global (php/comun/errores.php) la registra y responde 500.
        throw new RuntimeException('No se pudo conectar a la base de datos.', 0, $e);
    }

    $conexion = $nueva;
    return $conexion;
}

/** Tipos para bind_param deducidos de los valores: int/bool → i, float → d, resto → s. */
function tipos_parametros(array $params): string
{
    $tipos = '';
    foreach ($params as $valor) {
        if (is_int($valor) || is_bool($valor)) {
            $tipos .= 'i';
        } elseif (is_float($valor)) {
            $tipos .= 'd';
        } else {
            $tipos .= 's';
        }
    }
    return $tipos;
}

/** Prepara y ejecuta una sentencia. Uso interno de los helpers. */
function preparar_y_ejecutar(string $sql, array $params): mysqli_stmt
{
    $stmt = conectar()->prepare($sql);
    if ($params) {
        $valores = array_values($params);
        $stmt->bind_param(tipos_parametros($valores), ...$valores);
    }
    $stmt->execute();
    return $stmt;
}

/** SELECT preparado. Devuelve todas las filas como arrays asociativos. */
function consultar(string $sql, array $params = []): array
{
    $stmt = preparar_y_ejecutar($sql, $params);
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $filas;
}

/** SELECT preparado que devuelve la primera fila, o null si no hay resultados. */
function consultar_uno(string $sql, array $params = []): ?array
{
    $stmt = preparar_y_ejecutar($sql, $params);
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

/** UPDATE / DELETE preparado. Devuelve el número de filas afectadas. */
function ejecutar(string $sql, array $params = []): int
{
    $stmt = preparar_y_ejecutar($sql, $params);
    $afectadas = $stmt->affected_rows;
    $stmt->close();
    return (int) $afectadas;
}

/** INSERT preparado. Devuelve el id AUTO_INCREMENT generado. */
function insertar(string $sql, array $params = []): int
{
    $stmt = preparar_y_ejecutar($sql, $params);
    $id = $stmt->insert_id;
    $stmt->close();
    return (int) $id;
}

/**
 * Ejecuta $trabajo dentro de una transacción. Si lanza una excepción se hace
 * rollback y la excepción continúa; si termina bien, commit. Devuelve lo que
 * devuelva $trabajo.
 */
function transaccion(callable $trabajo)
{
    $db = conectar();
    $db->begin_transaction();
    try {
        $resultado = $trabajo($db);
        $db->commit();
        return $resultado;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
