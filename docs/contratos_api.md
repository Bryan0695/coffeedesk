# CoffeeDesk
## Contratos Frontend ↔ Backend

Responsable:
Jeremy Arevalo

---

# Formato general de respuesta

## Éxito

```json
{
    "estado":"exito",
    "mensaje":"Operación realizada correctamente",
    "datos": {}
}
```

## Error

```json
{
    "estado":"error",
    "mensaje":"Descripción del error",
    "datos": null
}
```

---

# INVENTARIO

## Listar insumos

Archivo:

php/inventario/listar.php

Método:

GET

Permisos:

Administrador

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Inventario obtenido",
  "datos":[]
}
```

---

## Crear insumo

Archivo:

php/inventario/guardar.php

Método:

POST

Permisos:

Administrador

Recibe:

```json
{
  "nombre":"",
  "unidad":"",
  "stock":0,
  "stock_minimo":0
}
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Insumo registrado",
  "datos":{
      "id":1
  }
}
```

---

## Actualizar insumo

Archivo:

php/inventario/guardar.php

Método:

POST

Permisos:

Administrador

Recibe:

```json
{
  "id":1,
  "nombre":"",
  "unidad":"",
  "stock":0,
  "stock_minimo":0
}
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Insumo actualizado"
}
```

---

## Eliminar insumo

Archivo:

php/inventario/eliminar.php

Método:

POST

Permisos:

Administrador

Recibe:

```json
{
  "id":1
}
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Insumo eliminado"
}
```

---

## Alertas de stock

Archivo:

php/inventario/alertas.php

Método:

GET

Permisos:

Administrador

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Alertas obtenidas",
  "datos":[]
}
```

---

# BÚSQUEDA DE PRODUCTOS

## Buscar productos

Archivo:

php/inventario/buscar.php

Método:

GET

Permisos:

Administrador

Parámetros:

```text
?q=cafe
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Búsqueda realizada",
  "datos":[]
}
```

---

## Filtrar por categoría

Archivo:

php/inventario/filtrar.php

Método:

GET

Permisos:

Administrador

Parámetros:

```text
?categoria_id=2
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Productos filtrados",
  "datos":[]
}
```

---

# DESCUENTO DE STOCK

## Descontar stock

Archivo:

php/inventario/descontar_stock.php

Método:

POST

Permisos:

Administrador

Uso:

Consumido internamente por el módulo de pedidos.

Recibe:

```json
{
  "producto_id":1,
  "cantidad":2
}
```

Respuesta:

```json
{
  "estado":"exito",
  "mensaje":"Stock actualizado correctamente"
}
```

---

# Reglas de seguridad

Todos los endpoints implementan:

- requiere_rol_api(ROL_ADMIN)
- consultas preparadas
- validación servidor
- protección CSRF
- prevención SQL Injection
- respuestas JSON estandarizadas