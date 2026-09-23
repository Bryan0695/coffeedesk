<?php
/**
 * Cierre común de las páginas internas.
 * Para cargar JS propio de la página, definir antes de incluirlo:
 *   $scripts = ['js/pedidos.js'];
 * (La CSP no permite <script> en línea: todo el JS va en archivos.)
 */
?>
    </main>

<?php require __DIR__ . '/pie_pagina.php'; ?>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e(url($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
