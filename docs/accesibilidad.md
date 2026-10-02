# Accesibilidad web (WCAG 2.2) — evidencias

Responsable: Frederick Torres · Herramientas: **WAVE** (extensión de Chrome), Lighthouse y Validador HTML W3C
(más una revisión manual de teclado y estructura).

## 1. WAVE — resultado inicial ("antes")

Fecha: 02/10/2026 · Entorno: XAMPP local · Capturas en `docs/evidencias/` (no se versiona).

| Página | Errors | Contrast Errors | Alerts | Features | Structure | ARIA | AIM Score |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Login | 0 | 0 | 1 | 3 | 6 | 9 | 10/10 |
| Panel (admin) | 0 | 0 | 1 | 1 | 8 | 18 | 10/10 |
| Menú (admin) | 0 | 0 | 1 | 7 | 22 | 49 | 10/10 |
| Categorías (admin) | 0 | 0 | 1 | 3 | 17 | 33 | 10/10 |
| Inventario (admin) | 0 | 0 | 1 | 7 | 27 | 79 | 10/10 |
| Menú (mesero) | 0 | 0 | 1 | 3 | 20 | 15 | 10/10 |

Las capturas del antes se tomaron con el logo original. La página de **Pedidos** se capturó ya con la corrección
(ver sección 2). La **404** no se evalúa con WAVE: es una página de error sin formularios ni datos, y se revisó
manualmente (sección 3).

**Hallazgo único (alerta repetida en todas las páginas):** *Possible heading* en el nombre "CoffeeDesk" de la cabecera.
Era un `<p>` grande y en negrita; WAVE lo tomaba por un título sin marcar.

**Corrección:** el nombre ya no es un `<p>` sino un `<span class="marca">` (`php/partials/cabecera.php`).

**Intento descartado:** convertirlo en un enlace al inicio hizo que WAVE avisara *Redundant link* en las páginas internas,
porque quedaba junto al enlace "Inicio" con el mismo destino. En el login (`index.php`) sí es un enlace, porque allí no hay otro.

## 2. WAVE — resultado final ("después")

| Página | Errors | Contrast Errors | Alerts | Features | Structure | ARIA | AIM Score |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Pedidos (mesero) | 0 | 0 | **0** | 6 | 21 | 31 | 10/10 |

Con la corrección ya no aparecen *Possible heading* ni *Redundant link*. Un intento intermedio (logo como enlace) generó
*Redundant link* en el panel con el rol mesero; se descartó y el resultado final queda sin alertas.

_Pendiente: repetir el resto de páginas con la versión final para completar la tabla._

## 3. Revisión manual (criterios de la rúbrica)

Comprobada con un script sobre las 9 vistas (login, panel, menú admin y mesero, pedidos admin y mesero, categorías,
inventario y 404), en escritorio (1366 px) y en móvil (375 px):

| Criterio | Resultado |
|---|---|
| Estructura semántica (`header`, `nav`, `main`, `footer`), `lang="es"` y `<title>` por página | ✔ |
| Un solo `h1` por página y sin saltos de nivel (h1 → h2) | ✔ |
| Cada campo con `<label for>` asociado; errores enlazados con `aria-describedby` | ✔ |
| Botones y enlaces con nombre accesible (los de solo icono llevan `aria-label` y `title`) | ✔ |
| Iconos decorativos con `aria-hidden="true"`; no hay imágenes informativas (no requieren `alt`) | ✔ |
| Sin `tabindex` positivo, sin `id` duplicados, sin referencias ARIA rotas | ✔ |
| Teclado: 1.er Tab = "Saltar al contenido", orden lógico y foco visible (anillo azul de 3 px) | ✔ |
| Estado no depende solo del color (insignias con texto; alertas con icono y texto) | ✔ |
| Contraste: WAVE reporta 0 errores de contraste (texto ≥ 4.5:1) | ✔ |
| Tamaño mínimo de los controles 24×24 px (WCAG 2.2 · 2.5.8); los botones miden 36–44 px | ✔ |
| Formularios responsivos, sin desplazamiento horizontal a 375 px | ✔ |
| Mensajes de éxito y error con `role="alert"` | ✔ |

## 4. Lighthouse y Validador W3C

_Pendiente._
