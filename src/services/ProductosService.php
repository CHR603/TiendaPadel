<?php

// Indica en qué carpeta está la clase: src/services/.
// El namespace debe coincidir con la carpeta para que el autoload de Composer la encuentre
namespace services;

use models\Producto;   // Modelo Producto: un objeto de esta clase representa una fila de la tabla productos
use PDO;               // Clase de PHP para trabajar con la base de datos (la conexión y sus constantes)
use Ramsey\Uuid\Uuid;  // Librería que genera identificadores únicos. Todavía no se usa: la necesitará save()

// Carga el archivo del modelo Producto a mano, por si el autoload no lo hubiera cargado ya
require_once __DIR__ . '/../models/Producto.php';

// SERVICIO: clase que contiene las consultas SQL de los productos.
// Las páginas (index.php, create.php...) no escriben SQL, llaman a los métodos de esta clase
class ProductosService
{
    // Aquí se guarda la conexión a la base de datos para usarla en todos los métodos.
    // Es private: solo se puede usar dentro de esta clase
    private PDO $pdo;




    // CONSTRUCTOR: se ejecuta al crear el objeto con new ProductosService(...).
    // La conexión no se crea aquí: se recibe desde fuera (desde Config, con $config->db)
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo; // Guarda la conexión recibida en la propiedad de la clase
    }




    // LISTADO: devuelve un array con todos los productos y el nombre de su categoría.
    // Si se le pasa un texto, devuelve solo los que lo tengan en la marca o en el modelo.
    // Si no se le pasa nada, el texto vale '' (vacío) y devuelve todos
    public function findAllWithCategoryName(string $searchTerm = ''): array
    {
        // prepare() deja la consulta lista pero NO la ejecuta todavía.
        // :marca y :modelo son marcadores: se rellenan después con los valores reales,
        //   así los datos nunca se mezclan con el SQL (esto evita la inyección SQL).
        // p y c son nombres cortos (alias) de las tablas productos y categorias.
        // p.* trae todas las columnas del producto, y c.nombre AS categoria_nombre trae
        //   además el nombre de la categoría, guardado con ese nombre para leerlo luego.
        // LEFT JOIN une cada producto con su categoría usando categoria_id. Se usa LEFT
        //   para que un producto sin categoría también salga en el listado.
        // ILIKE compara sin distinguir mayúsculas de minúsculas (solo existe en PostgreSQL).
        // ORDER BY p.id ASC los ordena por id, para que salgan siempre en el mismo orden
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.marca ILIKE :marca OR p.modelo ILIKE :modelo
             ORDER BY p.id ASC"
        );

        // Prepara el texto a buscar. El símbolo % significa "cualquier texto",
        // así que '%bull%' encuentra cualquier marca o modelo que CONTENGA "bull".
        // trim() quita los espacios del principio y del final.
        // Si no se busca nada queda '%%', que coincide con todo y por eso salen todos los productos
        $like = '%' . trim($searchTerm) . '%';

        // bindParam asigna un valor a cada marcador y le dice de qué tipo es.
        // PARAM_STR significa que es un texto
        $stmt->bindParam(':marca', $like, PDO::PARAM_STR);
        $stmt->bindParam(':modelo', $like, PDO::PARAM_STR);

        // execute() lanza la consulta a la base de datos
        $stmt->execute();

        $productos = []; // Array vacío donde se irán guardando los objetos Producto

        // fetch() lee UNA fila del resultado cada vez que se llama.
        // PDO::FETCH_ASSOC la devuelve como array con el nombre de cada columna (ej. $row['marca']).
        // Cuando ya no quedan filas devuelve false y el while termina
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // mapRow convierte la fila en un objeto Producto, [] = añade al final del array
            $productos[] = $this->mapRow($row);
        }
        return $productos; // Devuelve el array (vacío si no se encontró ningún producto)
    }





    // DETALLE: devuelve el producto con ese id, o null si no existe.
    // El ? delante de Producto en el tipo de retorno significa "Producto o null"
    public function findById(int $id): ?Producto
    {
        // Misma consulta con JOIN que antes, pero filtrando por un solo producto (WHERE p.id = :id)
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE p.id = :id"
        );

        // PARAM_INT indica que el id es un número entero
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Aquí solo se espera una fila, así que se llama a fetch() una vez.
        // Si no hay ningún producto con ese id, $row vale false
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null; // No existe: la página que llame a este método debe comprobarlo
        }
        return $this->mapRow($row); // Existe: devuelve el objeto Producto
    }



    
        // INSERTAR: guarda un producto nuevo en la base de datos.
    // Devuelve true si se ha guardado y lanza una excepción si la base de datos da error
    public function save(Producto $producto): bool
    {
        // El uuid lo genera la librería Ramsey: uuid4() crea uno aleatorio y toString() lo pasa a texto
        $uuid = Uuid::uuid4()->toString();

        // Si el producto no tiene imagen, usa la imagen por defecto que define el modelo Producto
        $imagen = $producto->imagen ?: Producto::$IMAGEN_DEFAULT;

        // No se indican id, created_at, updated_at ni is_deleted porque la tabla los rellena sola:
        //   el id con su secuencia y las fechas con la hora actual (CURRENT_TIMESTAMP)
        $stmt = $this->pdo->prepare(
            "INSERT INTO productos (uuid, marca, modelo, descripcion, precio, stock, imagen, categoria_id)
             VALUES (:uuid, :marca, :modelo, :descripcion, :precio, :stock, :imagen, :categoria_id)"
        );

        // bindValue asigna el valor a cada marcador y le dice de qué tipo es.
        // Se usa bindValue y no bindParam porque los valores salen de $producto->..., que no son variables
        $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $stmt->bindValue(':marca', $producto->marca, PDO::PARAM_STR);
        $stmt->bindValue(':modelo', $producto->modelo, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $producto->descripcion, PDO::PARAM_STR);
        $stmt->bindValue(':precio', $producto->precio, PDO::PARAM_STR); // PDO no tiene tipo decimal: va como texto y PostgreSQL lo convierte
        $stmt->bindValue(':stock', $producto->stock, PDO::PARAM_INT);
        $stmt->bindValue(':imagen', $imagen, PDO::PARAM_STR);
        $stmt->bindValue(':categoria_id', $producto->categoriaId, PDO::PARAM_STR); // El id de categoría es un uuid, es decir, texto

        // execute() lanza el INSERT y devuelve true si ha ido bien
        return $stmt->execute();
    }






        // ACTUALIZAR: modifica un producto que ya existe. Se identifica por su id.
    // Cambia los campos editables y la fecha de modificación (updated_at).
    // Devuelve true si la consulta se ha ejecutado bien
    public function update(Producto $producto): bool
    {
        // Si el producto no tiene imagen, usa la imagen por defecto (igual que en save)
        $imagen = $producto->imagen ?: Producto::$IMAGEN_DEFAULT;

        // UPDATE cambia las columnas indicadas en las filas que cumplan el WHERE.
        // IMPORTANTE: WHERE id = :id hace que solo se modifique ESTE producto.
        //   Sin WHERE se modificarían TODOS los productos de la tabla.
        // No se tocan id, uuid ni created_at: identifican al producto y no cambian.
        // updated_at = CURRENT_TIMESTAMP guarda la fecha y hora actuales
        $stmt = $this->pdo->prepare(
            "UPDATE productos
             SET marca = :marca, modelo = :modelo, descripcion = :descripcion, precio = :precio,
                 stock = :stock, imagen = :imagen, categoria_id = :categoria_id,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        // bindValue asigna el valor a cada marcador y le dice de qué tipo es.
        // Se usa bindValue y no bindParam porque los valores salen de $producto->..., que no son variables
        $stmt->bindValue(':marca', $producto->marca, PDO::PARAM_STR);
        $stmt->bindValue(':modelo', $producto->modelo, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $producto->descripcion, PDO::PARAM_STR);
        $stmt->bindValue(':precio', $producto->precio, PDO::PARAM_STR); // PDO no tiene tipo decimal: va como texto
        $stmt->bindValue(':stock', $producto->stock, PDO::PARAM_INT);
        $stmt->bindValue(':imagen', $imagen, PDO::PARAM_STR);
        $stmt->bindValue(':categoria_id', $producto->categoriaId, PDO::PARAM_STR); // Es un uuid, es decir, texto
        $stmt->bindValue(':id', $producto->id, PDO::PARAM_INT);                    // El id es un número entero

        // execute() lanza el UPDATE y devuelve true si ha ido bien
        return $stmt->execute();
    }







    // BORRAR: elimina físicamente el producto con ese id (la fila desaparece de la tabla).
    // Devuelve true si la consulta se ha ejecutado bien
    public function deleteById(int $id): bool
    {
        // Igual que en update: sin WHERE se borrarían TODOS los productos
        $stmt = $this->pdo->prepare("DELETE FROM productos WHERE id = :id");

        // Aquí sí se puede usar bindParam, porque $id es una variable
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // execute() lanza el DELETE y devuelve true si ha ido bien
        return $stmt->execute();
    }





    
    // Convierte UNA fila de la base de datos (un array) en un objeto Producto.
    // Se usa en los dos métodos de arriba para no repetir las mismas líneas.
    // Es private: solo se puede llamar desde dentro de esta clase
    private function mapRow(array $row): Producto
    {
        // Cada valor se pasa al constructor del modelo Producto.
        // El orden es el mismo que el de los parámetros de ese constructor
        return new Producto(
            $row['id'],
            $row['uuid'],
            $row['descripcion'],
            $row['imagen'],
            $row['marca'],
            $row['modelo'],
            $row['precio'],
            $row['stock'],
            $row['created_at'],
            $row['updated_at'],
            $row['categoria_id'],
            $row['categoria_nombre'], // Viene del JOIN (el alias del SELECT)
            $row['is_deleted']
        );
    }
}

?>