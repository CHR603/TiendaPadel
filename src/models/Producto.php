<?php

// Esta clase está en la carpeta src/models/ (el namespace debe coincidir)
namespace models;

// Modelo: un objeto Producto representa una fila de la tabla "productos"
class Producto
{
    // Imagen que se usa si el producto no tiene una propia (static: es de la clase, no de cada objeto)
    public static string $IMAGEN_DEFAULT = 'https://via.placeholder.com/150';

    // Una propiedad por cada columna de la tabla. Son privadas: no se tocan directamente desde fuera
    // Todos llevan ? porque el constructor permite crear un producto vacío (null).
    private ?int $id;
    private ?string $uuid;
    private ?string $descripcion;
    private ?string $imagen;
    private ?string $marca;
    private ?string $modelo;
    private ?float $precio;
    private ?int $stock;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $categoriaId;     // uuid de la categoría (clave foránea)
    private ?string $categoriaNombre; // nombre de la categoría (viene del JOIN, para mostrarlo)
    private ?bool $isDeleted;

    // Constructor: se ejecuta al hacer new Categoria(...) y guarda los datos en el objeto
    // Todos los parámetros son opcionales (= null), así se puede crear una categoría vacía
    public function __construct(
        ?int $id = null,
        ?string $uuid = null,
        ?string $descripcion = null,
        ?string $imagen = null,
        ?string $marca = null,
        ?string $modelo = null,
        ?float $precio = null,
        ?int $stock = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $categoriaId = null,
        ?string $categoriaNombre = null,
        ?bool $isDeleted = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->descripcion = $descripcion;
        // Si no llega imagen (null o vacía), usa la de por defecto
        $this->imagen = $imagen ?: self::$IMAGEN_DEFAULT;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->precio = $precio;
        $this->stock = $stock;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->categoriaId = $categoriaId;
        $this->categoriaNombre = $categoriaNombre;
        $this->isDeleted = $isDeleted;
    }

    // Magic method for get and set
    // __get se ejecuta solo al leer una propiedad privada desde fuera: $producto->marca
    public function __get(string $name): mixed
    {
        return $this->$name;
    }

    // __set se ejecuta solo al modificar una propiedad privada desde fuera: $producto->marca = 'X'
    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }
}