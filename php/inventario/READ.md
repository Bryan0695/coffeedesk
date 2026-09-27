# Módulo Inventario

Responsable:
Jeremy Arevalo

Funciones previstas:

- Registrar insumos
- Listar insumos
- Actualizar stock
- Eliminar insumos
- Alertas de stock bajo
- Búsqueda de productos
- Filtros por categoría
- Descuento automático de stock

## Listar Insumos

Archivo:
php/inventario/listar.php

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
          "nombre":"Café",
          "stock":100,
          "stock_minimo":20
      }
  ]
}
``

## Crear Insumo

Archivo:
php/inventario/crear.php

Método:
POST

Recibe:
- nombre
- stock
- stock_minimo

Respuesta:

{
  "estado":"ok",
  "mensaje":"Insumo registrado"
}