# CoffeeDesk
## Contratos Frontend ↔ Backend

Responsables: Jeremy Arevalo (contratos e inventario) · Gabo (menú y pedidos)

Documento actualizado con los archivos que **existen** en el proyecto.

---

# Cómo responde el backend

Los formularios se envían por **POST** y el servidor responde con una **redirección (302)** a la página de origen
dejando un mensaje (`mensaje_flash`) que la cabecera muestra con `role="alert"`:

| Tipo | Ejemplo |
|---|---|
| `exito` | `Insumo registrado.` |
| `error` | `Ya existe un insumo con ese nombre.` |
| `aviso` | `El producto ya se encuentra eliminado.` |

Solo `php/auth/estado.php` responde JSON con el formato `{ "estado", "mensaje", "datos" }` (`responder_json()`).

**Todos los formularios POST** incluyen el campo oculto `csrf` (`<?= csrf_campo() ?>`).
Si falta o es inválido → mensaje de error y redirección, sin tocar la base de datos.

**Eliminar** es siempre una baja lógica (`activo = 0`). Si se crea de nuevo un registro con el mismo nombre
(el nombre es `UNIQUE`), se **reactiva** el existente en lugar de crear otro.

---

# INVENTARIO (solo administrador)

## Guardar insumo (crear, editar o reactivar)

| | |
|---|---|
| Archivo | `php/inventario/guardar.php` |
| Método | POST |
| Permisos | Administrador |
| Redirige a | `inventario.php` |

| Campo | Tipo | Validación |
|---|---|---|
| `csrf` | texto | obligatorio |
| `id` | entero | vacío = crear (o reactivar); con valor = editar un insumo **activo** |
| `nombre` | texto | 1 a 80 caracteres; único |
| `unidad` | texto | `unidades`, `kg`, `g`, `litros` o `ml` |
| `stock` | número | de 0 a 99999 |
| `stock_minimo` | número | de 0 a 99999 |

Mensajes: `Insumo registrado.` · `Insumo actualizado.` · `El insumo "X" volvió al inventario.` ·
`Ya existe un insumo con ese nombre.` · `Ese nombre pertenece a un insumo eliminado; créalo de nuevo para reactivarlo.` ·
`El insumo que intentas editar no existe o fue eliminado.` · errores de campo (`Unidad inválida`, `El stock debe ser…`).

## Eliminar insumo

| | |
|---|---|
| Archivo | `php/inventario/eliminar.php` |
| Método | POST |
| Permisos | Administrador |
| Campos | `csrf`, `id` (entero > 0) |

Se bloquea si el insumo está en la receta de un producto activo:
`No se puede eliminar "X": lo usan las recetas de …`.

## Alertas de stock bajo

No es un endpoint: `inventario.php` consulta `InventarioDAO::listar()` y marca como **stock bajo**
los insumos con `stock <= stock_minimo` (aviso, cifra de resumen, filas resaltadas y filtro «Solo stock bajo»).

---

# MENÚ (administrador; el mesero solo consulta)

| Operación | Archivo | Campos |
|---|---|---|
| Crear, editar o reactivar producto | `php/menu/guardar.php` | `csrf`, `id` (vacío = crear), `nombre`, `categoria_id`, `precio` (0.01 a 999.99), `disponible` (casilla) |
| Eliminar producto | `php/menu/eliminar.php` | `csrf`, `id` |
| Crear, editar o reactivar categoría | `php/menu/categoria_guardar.php` | `csrf`, `id` (vacío = crear), `nombre` (1 a 60) |
| Eliminar categoría | `php/menu/categoria_eliminar.php` | `csrf`, `id` |
| Reactivar categoría | `php/menu/categoria_reactivar.php` | `csrf`, `id` |

`disponible = 0` es **Agotado** (sigue en el menú, no se puede pedir). `activo = 0` es **Eliminado** (no aparece).

La **búsqueda por nombre** y el **filtro por categoría** del menú y del inventario se hacen en el navegador
(`js/menu.js`, `js/inventario.js`), sobre las filas ya cargadas: no necesitan endpoint.

---

# PEDIDOS (administrador y mesero)

## Registrar pedido

| | |
|---|---|
| Archivo | `php/pedidos/registrar.php` |
| Método | POST |
| Permisos | Administrador y mesero |
| Redirige a | `pedidos.php` |

| Campo | Tipo | Validación |
|---|---|---|
| `csrf` | texto | obligatorio |
| `mesa` | entero | 1 a 10 |
| `cliente` | texto | opcional, máximo 60 |
| `producto_id[]` | enteros | productos activos y disponibles |
| `cantidad[]` | enteros | 1 a 20 por producto (los repetidos se suman) |

El **total se recalcula en el servidor** con los precios de la base (el del navegador es solo informativo).
Todo ocurre en **una transacción**: se crean el pedido y su detalle y se **descuenta el stock** de los insumos de la receta
(`InventarioDAO::descontarStock()`). Si algún insumo no alcanza, se deshace todo y no se crea el pedido.

## Cambiar estado del pedido

| | |
|---|---|
| Archivo | `php/pedidos/estado.php` |
| Método | POST |
| Campos | `csrf`, `id`, `estado` (`entregado` o `anulado`) |
| Permisos | `entregado`: administrador y mesero · `anulado`: **solo administrador** |

Solo cambian los pedidos `pendiente`. Al **anular**, los insumos se devuelven al inventario
(`InventarioDAO::reponerStock()`) en la misma transacción.

---

# Capa de datos

| Archivo | Función |
|---|---|
| `php/conexion.php` | `consultar()`, `consultar_uno()`, `insertar()`, `ejecutar()` y `transaccion()`, todas con consultas preparadas |
| `php/dao/InventarioDAO.php` | Consultas del inventario: `listar`, `crear`, `actualizar`, `eliminar`, `descontarStock`, `reponerStock`, `productosQueLoUsan`… |
| `php/models/Insumo.php` | Modelo del insumo |

`php/dao/` y `php/models/` son librerías: el `.htaccess` las bloquea (403) desde el navegador.

---

# Reglas de seguridad

- `requiere_rol()` al inicio de cada archivo accesible desde el navegador.
- Solo POST y token CSRF en toda operación que modifica datos.
- Consultas preparadas y validación en el servidor.
- Salida HTML escapada con `e()`.
