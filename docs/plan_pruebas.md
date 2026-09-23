# Plan de pruebas funcionales — CoffeeDesk

Responsable: Bryan Gallegos · Participan: todos
Cada prueba se ejecuta en **local (XAMPP)** y en **hosting (InfinityFree)**; se toma una captura por caso para el informe (nombre sugerido: `P-01_local.png`, `P-01_hosting.png`).

Estado: ✔ pasa · ✖ falla · — pendiente

## Autenticación y roles (Bryan)
| ID | Caso | Pasos | Resultado esperado | Local | Hosting |
|---|---|---|---|:---:|:---:|
| P-01 | Acceso sin sesión | Abrir `/panel.php` sin haber ingresado | Redirige al login con aviso "Inicia sesión para continuar" | ✔ | — |
| P-02 | Campos vacíos (cliente) | Pulsar *Ingresar* con campos vacíos | Mensajes bajo cada campo, foco en el primero con error, no se envía | — | — |
| P-03 | Usuario con caracteres inválidos (servidor) | Enviar usuario `a'` | Mensaje de error de formato | ✔ | — |
| P-04 | Contraseña incorrecta | `admin` / `mala` | "Usuario o contraseña incorrectos" | ✔ | — |
| P-05 | Login administrador | `admin` / `Admin123*` | Panel de administración, ve Inventario | ✔ | — |
| P-06 | Login mesero | `mesero` / `Mesero123*` | Panel de atención, no ve Inventario | ✔ | — |
| P-07 | Mesero fuerza URL restringida | Como mesero abrir `/inventario.php` | Vuelve al panel con "No tienes permiso…" | ✔ | — |
| P-08 | Cerrar sesión | Botón *Cerrar sesión* | Login con "Cerraste sesión correctamente"; `/panel.php` ya no abre | ✔ | — |
| P-09 | Bloqueo por intentos | 5 contraseñas erróneas seguidas | "Demasiados intentos fallidos. Espera 5 minuto(s)" | ✔ | — |
| P-10 | Contraseña cifrada | Ver tabla `usuarios` en phpMyAdmin | `clave_hash` empieza con `$2y$`, no se ve la clave | ✔ | — |
| P-11 | Mostrar/ocultar contraseña | Botón *Mostrar* | Alterna texto visible, también con teclado (Tab + Enter) | — | — |
| P-12 | Navegación por teclado | Solo Tab/Shift+Tab/Enter en login y panel | Foco visible en cada control, orden lógico, "Saltar al contenido" aparece | — | — |

## Menú (Gabo)
| ID | Caso | Resultado esperado | Local | Hosting |
|---|---|---|:---:|:---:|
| M-01 | Registrar producto válido | Mensaje de éxito y aparece en la lista | — | — |
| M-02 | Registrar con precio 0, negativo o vacío | Error en cliente y en servidor | — | — |
| M-03 | Editar producto | Cambios guardados | — | — |
| M-04 | Marcar como agotado | Estado cambia; no se puede pedir | — | — |
| M-05 | Eliminar producto (con confirmación) | Desaparece de la lista | — | — |
| M-06 | Mesero intenta crear/editar/eliminar | Operación rechazada | — | — |

## Pedidos (Gabo + Jeremy)
| ID | Caso | Resultado esperado | Local | Hosting |
|---|---|---|:---:|:---:|
| O-01 | Pedido con varios productos | Total calculado correctamente | — | — |
| O-02 | Cantidad 0, negativa o no numérica | Error | — | — |
| O-03 | Pedido que supera el stock | Error y no se descuenta nada | — | — |
| O-04 | Pedido válido descuenta stock | Stock de insumos disminuye | — | — |
| O-05 | Pedido asociado a mesa/cliente | Se guarda la mesa y el usuario que lo registró | — | — |

## Inventario, búsqueda y filtros (Jeremy)
| ID | Caso | Resultado esperado | Local | Hosting |
|---|---|---|:---:|:---:|
| I-01 | CRUD de insumo | Registrar, editar y eliminar funcionan | — | — |
| I-02 | Stock bajo el mínimo | Alerta visible con texto/icono (no solo color) | — | — |
| B-01 | Buscar por nombre | Solo coinciden los productos buscados | — | — |
| B-02 | Filtrar por categoría | Solo esa categoría | — | — |
| B-03 | Búsqueda sin resultados | Mensaje "No se encontraron productos" | — | — |

## Responsivo y accesibilidad (Frederick)
| ID | Caso | Resultado esperado | Local | Hosting |
|---|---|---|:---:|:---:|
| R-01 | Vista en 375 px, 768 px y 1280 px | Sin scroll horizontal, todo usable | — | — |
| A-01 | WAVE | 0 errores (capturas antes/después) | — | — |
| A-02 | Lighthouse – Accesibilidad | ≥ 90 (capturas antes/después) | — | — |
| A-03 | Validador W3C | Sin errores de HTML | — | — |
