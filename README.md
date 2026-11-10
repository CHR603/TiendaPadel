# Tienda online de pádel

Aplicación web para gestionar una tienda online de productos de pádel, desarrollada en **PHP puro (sin frameworks)** con una arquitectura modular: modelos, servicios y vistas. Proyecto del módulo de Programación web en servidor, 2º DAW.

## Descripción

Permite gestionar el catálogo de una tienda: productos, categorías y usuarios con roles. Hay que iniciar sesión para acceder a la web.

- **Autenticación:** login y logout con contraseñas cifradas (bcrypt). La sesión caduca tras una hora de inactividad.
- **Roles:** el usuario (`USER`) consulta el catálogo, el administrador (`ADMIN`) además lo gestiona.
- **Consulta:** listado de productos con buscador por marca o modelo, y página de detalle.
- **Gestión (solo administrador):** crear, editar, cambiar la imagen (JPG o PNG) y eliminar productos, con validación de los datos.
- **Categorías:** cada producto pertenece a una categoría cargada desde la base de datos.

## Tecnologías utilizadas

| Tecnología | Uso |
|---|---|
| PHP 8.2 con Apache | Lenguaje y servidor web |
| PostgreSQL 12 | Base de datos |
| PDO (`pdo_pgsql`) | Acceso a la base de datos con consultas preparadas |
| Bootstrap 5 | Diseño de las páginas (se carga desde internet) |
| Docker y Docker Compose | Entorno de ejecución |
| Composer | Dependencias y carga automática de clases (PSR-4) |
| `vlucas/phpdotenv` | Lectura de las variables del archivo `.env` |
| `ramsey/uuid` | Generación de identificadores únicos (UUID) |
| Adminer | Visor web de la base de datos |

## Estructura del proyecto

```
tienda/
├── database/
│   └── init.sql          # Tablas y datos de prueba
├── src/                  # Aplicación (carpeta que publica Apache)
│   ├── config/           # Config.php: conexión a la base de datos (Singleton)
│   ├── models/           # Producto, Categoria y User
│   ├── services/         # Consultas y lógica: Productos, Categorias, Users y Session
│   ├── uploads/          # Imágenes subidas (se crea al instalar, no está en Git)
│   ├── index.php         # Listado y buscador
│   ├── details.php       # Detalle de un producto
│   ├── create.php        # Crear producto
│   ├── update.php        # Editar producto
│   ├── update-image.php  # Formulario para cambiar la imagen
│   ├── update_image_file.php  # Procesa la subida de la imagen
│   ├── delete.php        # Eliminar producto
│   ├── login.php         # Iniciar sesión
│   ├── logout.php        # Cerrar sesión
│   ├── header.php        # Cabecera y menú comunes
│   ├── footer.php        # Pie común
│   └── favicon.ico       # Icono de la pestaña
├── .env.example          # Plantilla de configuración
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

- **Modelos:** clases que representan los datos (una fila de una tabla).
- **Servicios:** contienen las consultas SQL y la lógica. `Config` y `SessionService` son Singleton.
- **Páginas:** reciben la petición, validan los datos, llaman a los servicios y muestran el resultado.
- No se ha creado la carpeta `test` ya que se han realizado pruebas manuales.

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Compose)
- [Git](https://git-scm.com/)
- Puertos **8080**, **8081** y **5432** libres
- Conexión a internet (Bootstrap y las imágenes por defecto se cargan desde internet)

PHP, PostgreSQL y Composer ya van dentro de los contenedores: no hace falta instalarlos.

## Instalación

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/CHR603/TiendaPadel.git
   cd TiendaPadel
   ```
2. Crear el archivo de configuración a partir de la plantilla:
   ```bash
   cp .env.example .env
   ```
   (En Windows PowerShell: `copy .env.example .env`)
3. Crear la carpeta donde se guardan las imágenes de los productos (no viene en el repositorio):
   ```bash
   mkdir src/uploads
   ```
4. Levantar los contenedores. La base de datos se importa sola desde `database/init.sql` la primera vez:
   ```bash
   docker compose up -d --build
   ```
5. Instalar las dependencias con Composer, dentro del contenedor:
   ```bash
   docker exec -it php_app composer install
   ```

Notas:

- Mantén `APP_PORT=8080` en el `.env`: la dirección de las imágenes subidas está fijada a ese puerto.
- Si en Linux la subida de imágenes da error de permisos: `docker exec -it php_app chown -R www-data:www-data /var/www/html/src/uploads`
- Para parar la aplicación: `docker compose down`. Para borrar la base de datos y volver a importarla: `docker compose down -v`.


## Uso básico

- **Aplicación:** <http://localhost:8080> (lleva al login si no se ha iniciado sesión)
- **Adminer** (ver la base de datos): <http://localhost:8081>
  - Sistema: PostgreSQL · Servidor: `postgres-db` · Usuario: `admin` · Contraseña: `adminPassword123` · Base de datos: `tienda`

### Usuarios de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `Admin1` | Administrador |
| `user` | `User1234` | Usuario |
| `test` | `test1234` | Usuario |

### Navegación

1. Inicia sesión con uno de los usuarios de prueba.
2. En el listado puedes buscar por marca o modelo y pulsar **Ver** para abrir el detalle de un producto.
3. Como **administrador** aparecen además **Crear producto** y, en cada fila, **Editar**, **Imagen** y **Borrar**.
4. Un usuario normal no puede acceder a esas páginas aunque escriba su dirección: recibe un aviso y vuelve al listado.
5. Cierra sesión con el botón **Logout** de la barra superior.

## Autor

- **Christian Velasco** – [GitHub](https://github.com/CHR603/TiendaPadel)

Proyecto realizado en el IES Juan de Garay.

## Licencia

Uso educativo.
