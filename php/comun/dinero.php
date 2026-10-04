<?php
/**
 * Conversión de importes. Dentro de PHP el dinero se maneja en centavos
 * enteros (sin errores de redondeo de float); MySQL lo guarda como DECIMAL(10,2).
 *
 *   precio_a_centavos('2.50')       → 250   (valor DECIMAL leído de MySQL)
 *   texto_a_centavos('2,5')         → 250   (lo que escribió el usuario; null si no es válido)
 *   centavos_a_decimal(250)         → '2.50' (para guardarlo en MySQL)
 *   dinero(250)                     → '$2.50' (para mostrarlo; está en html.php)
 */

/** Valor DECIMAL de MySQL ("2.50", "12.5", "3") a centavos. */
function precio_a_centavos(string $precio): int
{
    [$entero, $decimales] = array_pad(explode('.', trim($precio), 2), 2, '');
    return (int) $entero * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0');
}

/**
 * Precio escrito por el usuario a centavos: de 1 a 3 cifras enteras y hasta
 * 2 decimales, con punto o coma ("2", "2.5", "2,50"). Devuelve null si el texto
 * no tiene ese formato.
 */
function texto_a_centavos(string $texto): ?int
{
    $texto = str_replace(',', '.', trim($texto));
    if (preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $texto) !== 1) {
        return null;
    }
    return precio_a_centavos($texto);
}

/** Centavos a texto DECIMAL para MySQL: 250 → "2.50", -5 → "-0.05". Solo enteros, sin float. */
function centavos_a_decimal(int $centavos): string
{
    $signo = $centavos < 0 ? '-' : '';
    $centavos = abs($centavos);
    return sprintf('%s%d.%02d', $signo, intdiv($centavos, 100), $centavos % 100);
}
