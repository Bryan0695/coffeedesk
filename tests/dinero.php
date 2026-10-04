<?php
/**
 * Prueba de consola de php/comun/dinero.php: conversión de importes sin float.
 *
 * Uso:  php tests/dinero.php     (sale con código 1 si algo falla)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../php/comun/dinero.php';

$fallos = 0;
function comprobar(string $caso, $obtenido, $esperado): void
{
    global $fallos;
    if ($obtenido === $esperado) {
        echo "  ✔ $caso" . PHP_EOL;
    } else {
        echo "  ✖ $caso → " . var_export($obtenido, true) . ', esperado ' . var_export($esperado, true) . PHP_EOL;
        $fallos++;
    }
}

// Valores DECIMAL(10,2) tal como los devuelve MySQL
comprobar("precio_a_centavos('2.50')", precio_a_centavos('2.50'), 250);
comprobar("precio_a_centavos('0.29')", precio_a_centavos('0.29'), 29);      // (int) (0.29 * 100) da 28
comprobar("precio_a_centavos('1.15')", precio_a_centavos('1.15'), 115);     // (int) (1.15 * 100) da 114
comprobar("precio_a_centavos('999.99')", precio_a_centavos('999.99'), 99999);
comprobar("precio_a_centavos('12345678.90')", precio_a_centavos('12345678.90'), 1234567890);
comprobar("precio_a_centavos('3')", precio_a_centavos('3'), 300);
comprobar("precio_a_centavos('3.5')", precio_a_centavos('3.5'), 350);

// Lo que escribe el usuario en el formulario
comprobar("texto_a_centavos('2,5')", texto_a_centavos('2,5'), 250);
comprobar("texto_a_centavos(' 2.05 ')", texto_a_centavos(' 2.05 '), 205);
comprobar("texto_a_centavos('0.01')", texto_a_centavos('0.01'), 1);
comprobar("texto_a_centavos('0')", texto_a_centavos('0'), 0);
foreach (['', 'abc', '-1', '1.234', '1000', '1e2', '2.', '.5', '1,2,3'] as $invalido) {
    comprobar("texto_a_centavos('$invalido') es null", texto_a_centavos($invalido), null);
}

// Hacia MySQL
comprobar('centavos_a_decimal(250)', centavos_a_decimal(250), '2.50');
comprobar('centavos_a_decimal(5)', centavos_a_decimal(5), '0.05');
comprobar('centavos_a_decimal(0)', centavos_a_decimal(0), '0.00');
comprobar('centavos_a_decimal(99999)', centavos_a_decimal(99999), '999.99');
comprobar('centavos_a_decimal(1999980)', centavos_a_decimal(1999980), '19999.80');
comprobar('centavos_a_decimal(-5)', centavos_a_decimal(-5), '-0.05');
comprobar('centavos_a_decimal(-250)', centavos_a_decimal(-250), '-2.50');

// Ida y vuelta: ningún precio de 0.01 a 999.99 cambia al convertirlo
$distintos = 0;
for ($c = 1; $c <= 99999; $c++) {
    if (precio_a_centavos(centavos_a_decimal($c)) !== $c) {
        $distintos++;
    }
}
comprobar('ida y vuelta de 0.01 a 999.99 sin diferencias', $distintos, 0);

echo PHP_EOL . ($fallos === 0 ? 'Todo correcto.' : "Fallos: $fallos") . PHP_EOL;
exit($fallos === 0 ? 0 : 1);
