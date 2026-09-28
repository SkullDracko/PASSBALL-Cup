# Administración de torneos

Referencia de las operaciones disponibles para administrar torneos y rondas.

## Torneos

### Crear un torneo

Enviar los datos del torneo:

```json
{
    "nombre": "Copa piston",
    "fecha_inicio": "2026-10-01",
    "fecha_fin": "2026-10-31"
}
```

### Consultar los torneos existentes

Realizar una solicitud `GET` a la ruta correspondiente. No se requieren datos en la solicitud.

Respuesta:

```json
{
    "exito": true,
    "data": {
        "torneo": {
            "id": 1,
            "nombre": "Copa piston",
            "tipo": "eliminacion_directa",
            "fecha_inicio": "2026-10-01",
            "fecha_fin": "2026-10-31",
            "estado": "programado",
            "fecha_creacion": "2026-09-25 12:00:20"
        }
    },
    "errores": []
}
```

### Actualizar un torneo

Realizar una solicitud `PUT` a la ruta correspondiente con los datos actualizados:

```json
{
    "nombre": "Copa piston",
    "tipo": "eliminacion_directa",
    "fecha_inicio": "2026-10-02",
    "fecha_fin": "2026-10-31",
    "estado": "cancelado"
}
```

### Eliminar un torneo

Realizar una solicitud `DELETE` a la ruta correspondiente:

```json
{
    "id": 1
}
```

## Rondas

### Crear una ronda

Realizar una solicitud `POST` a la ruta correspondiente:

```json
{
    "torneo_id": 1,
    "nombre": "final",
    "orden": 3
}
```

Respuesta:

```json
{
    "exito": true,
    "data": {
        "mensaje": "Ronda creada correctamente",
        "id": 4
    },
    "errores": []
}
```

### Eliminar una ronda

Realizar una solicitud `DELETE` a la ruta correspondiente:

```json
{
    "id": 4,
    "torneo_id": 1
}
```
## Equipos en el torneo 


