# CoffeeDesk

## Contratos Frontend - Backend

Responsable:
Jeremy Arevalo

## Convención General
 
Todas las respuestas deberán devolverse en formato JSON.
 
### Respuesta Exitosa
 
```json
{
"estado": "ok",
"mensaje": "Operación realizada correctamente",
"datos": {}
}
```
 
### Respuesta de Error
 
```json
{
"estado": "error",
"mensaje": "Descripción del error"
}
```

## Módulo Autenticación

### Inicio de sesión

Archivo:

```text
php/auth/login.php
```

Método:

```text
POST
```

Parámetros:

| Campo | Tipo | Requerido |
|---------|---------|---------|
| usuario | string | Sí |
| clave | string | Sí |

Respuesta:

```json
{
  "estado":"ok",
  "mensaje":"Inicio de sesión correcto",
  "datos":{
      "id":1,
      "nombre":"Administrador General",
      "rol":"administrador"
  }
}
```

## Estándares de desarrollo

### Arquitectura

- Patrón DAO.
- Modelo por entidad.
- Consultas preparadas.
- Validación en servidor.
- Respuestas JSON estandarizadas.

### Seguridad

- Uso obligatorio de Prepared Statements.
- No concatenar variables en consultas SQL.
- Validación de datos recibidos por GET y POST.

## Módulo Inventario
 
### Operaciones previstas
 
- Crear insumo
- Listar insumos
- Consultar insumo por ID
- Actualizar insumo
- Eliminar insumo
- Buscar insumo
- Consultar alertas de stock bajo
- Descuento automático de stock

### Crear Insumo

Archivo:
php/inventario/crear.php

Método:
POST

Parámetros:
- nombre
- stock
- stock_minimo

Respuesta:
{
  "estado":"ok",
  "mensaje":"Insumo registrado"
}

# Convención de endpoints

GET
- Consultar información

POST
- Crear registro
- Actualizar registro
- Eliminar registro

## Crear Insumo

Archivo:
php/inventario/crear.php

Método:
POST

Recibe:

- nombre
- stock
- stock_minimo

Respuesta exitosa:

```json
{
  "estado":"ok",
  "mensaje":"Insumo registrado"
}
```

Respuesta error:

```json
{
  "estado":"error",
  "mensaje":"Datos inválidos"
}
```

## Actualizar Insumo

Archivo:
php/inventario/actualizar.php

Método:
POST

Recibe:
- id
- nombre
- stock
- stock_minimo

Validaciones:
- id obligatorio
- stock >= 0
- stock_minimo >= 0

Respuesta:

{
  "estado":"ok",
  "mensaje":"Insumo actualizado"
}

## Eliminar Insumo

Archivo:
php/inventario/eliminar.php

Método:
POST

Recibe:
- id

Validaciones:
- id obligatorio

Respuesta:

{
  "estado":"ok",
  "mensaje":"Insumo eliminado"
}

## Buscar Insumo

Archivo:
php/inventario/buscar.php

Método:
GET

Recibe:
- texto

Respuesta:

{
  "estado":"ok",
  "datos":[]
}

## Alertas de Stock Bajo

Archivo:
php/inventario/alertas.php

Método:
GET

Recibe:
ningún parámetro

Respuesta:

{
  "estado":"ok",
  "datos":[
      {
          "id":1,
          "nombre":"Cafe",
          "stock":5,
          "stock_minimo":10
      }
  ]
}

## Descuento de Stock

Archivo:
php/inventario/descontar_stock.php

Método:
POST

Recibe:
- producto_id
- cantidad

Respuesta:

{
  "estado":"ok",
  "mensaje":"Stock actualizado"
}

## Filtrar Productos por Categoría

Archivo:
php/inventario/filtrar.php

Método:
GET

Recibe:
- categoria_id

Respuesta:

{
  "estado":"ok",
  "datos":[]
}