<?php
/**
 * Comprueba que cada archivo PHP accesible por el navegador llama a
 * requiere_login(), requiere_rol(), requiere_login_api() o requiere_rol_api() (F-017).
 * Detecta el fallo más probable al agregar módulos: un endpoint sin control de acceso.
 *
 * Uso:  php tests/verificar_endpoints.php     (sale con código 1 si algo falla)
 *
 * Si agregas un archivo que de verdad debe ser público o que solo se incluye
 * (una librería), añádelo a $excepciones con el motivo.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__);

// Carpetas que no son endpoints (solo se incluyen o no se publican)
$carpetasExcluidas = ['.git', '.github', 'config', 'herramientas', 'tests', 'logs', 'php/comun', 'php/partials'];

// Archivos sin requiere_*() a propósito: ruta => motivo
$excepciones = [
    'index.php'                      => 'página de login (pública)',
    '404.php'                        => 'página "no encontrada" (pública)',
    'php/auth/login.php'             => 'procesa el login (público por definición)',
    'php/auth/logout.php'            => 'cierra la sesión; protegido con POST + CSRF',
    'php/auth/sesion.php'            => 'librería: fachada de seguridad',
    'php/auth/csrf.php'              => 'librería',
    'php/auth/autorizacion.php'      => 'librería (define requiere_*)',
    'php/auth/arranque_sesion.php'   => 'librería',
    'php/auth/limite_intentos.php'   => 'librería',
    'php/conexion.php'               => 'librería: conexión y helpers de BD',
];

/** ¿El código llama (no solo menciona en un comentario) a alguna requiere_*()? */
function llama_a_requiere(string $codigo): bool
{
    $tokens = token_get_all($codigo);
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if (!is_array($t) || $t[0] !== T_STRING
            || !preg_match('/^requiere_(login|rol)(_api)?$/', $t[1])) {
            continue;
        }
        // Anterior significativo: no debe ser "function" (definición)
        $j = $i - 1;
        while ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j--;
        }
        if ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_FUNCTION) {
            continue;
        }
        // Siguiente significativo: debe ser "(" (llamada)
        $k = $i + 1;
        while ($k < $n && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
            $k++;
        }
        if ($k < $n && $tokens[$k] === '(') {
            return true;
        }
    }
    return false;
}

$iterador = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)
);

$revisados = 0;
$fallos = [];
foreach ($iterador as $archivo) {
    if (strtolower($archivo->getExtension()) !== 'php') {
        continue;
    }
    $relativa = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz) + 1));

    foreach ($carpetasExcluidas as $carpeta) {
        if (strpos($relativa, $carpeta . '/') === 0) {
            continue 2;
        }
    }
    if (isset($excepciones[$relativa])) {
        continue;
    }

    $revisados++;
    if (llama_a_requiere((string) file_get_contents($archivo->getPathname()))) {
        echo "  ✔ $relativa" . PHP_EOL;
    } else {
        echo "  ✖ $relativa  → no llama a requiere_login/requiere_rol(_api)" . PHP_EOL;
        $fallos[] = $relativa;
    }
}

// Excepciones que ya no existen: la lista debe mantenerse al día
foreach (array_keys($excepciones) as $relativa) {
    if (!is_file($raiz . '/' . $relativa)) {
        echo "  ✖ $relativa  → está en \$excepciones pero no existe" . PHP_EOL;
        $fallos[] = $relativa;
    }
}

echo PHP_EOL . "Revisados: $revisados · Excepciones: " . count($excepciones) . " · Fallos: " . count($fallos) . PHP_EOL;
exit($fallos ? 1 : 0);
