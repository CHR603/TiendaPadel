# Tienda online de pádel (PHP puro)

> **Estado:** en desarrollo. Entorno y conexión a la base de datos terminados.

## Descripción

Aplicación web para gestionar una tienda online de productos de pádel, desarrollada en PHP sin frameworks. El objetivo final es un CRUD de productos con autenticación y roles (usuario y administrador).

De momento está montado el entorno de trabajo y la conexión con la base de datos.

## Tecnologías utilizadas

- PHP 8.2 con Apache
- PostgreSQL 12 (acceso con PDO)
- Docker y Docker Compose
- Composer (autoload PSR-4)
- `vlucas/phpdotenv` y `ramsey/uuid`
- Adminer (visor de la base de datos)

## Estructura del proyecto

```
tienda/
├── database/
│   └── init.sql        # Tablas y datos de prueba
├── src/                # Aplicación (document root de Apache)
│   ├── config/
│   │   └── Config.php  # Conexión a la base de datos (Singleton)
│   ├── models/
│   ├── services/
│   ├── uploads/
│   └── index.php       # Prueba temporal de conexión
├── .env        # Plantilla de configuración
├── composer.json
├── Dockerfile
├── docker-compose.yml
├── README.md
```

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Compose)
- [Git](https://git-scm.com/)


## Uso básico

- Aplicación: <http://localhost:8080>. Muestra por ahora un mensaje con el número de productos de la base de datos, para comprobar la conexión.
- Adminer: <http://localhost:8081>
  - Sistema: PostgreSQL · Servidor: `postgres-db` · Usuario: `admin` · Contraseña: `_____` · Base de datos: `tienda`

Usuarios de prueba (se usarán cuando esté el login):

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin1` | Administrador |
| `user` | `User1234` | Usuario |
| `test` | `test1234` | Usuario |

## Autor y créditos
Christian

Proyecto del módulo de Programación web en servidor, 2º DAW semipresencial, IES Juan de Garay.

## Licencia

Uso educativo.
