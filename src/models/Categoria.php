<?php

// Esta clase está en la carpeta src/models/ (el namespace debe coincidir)
namespace models;

// Importa la librería que genera identificadores únicos (UUID)
use Ramsey\Uuid\Uuid;

// Modelo: un objeto Categoria representa una fila de la tabla "categorias"
class Categoria
{
    // Una propiedad por cada columna de la tabla. Son privadas: no se tocan directamente desde fuera
    // Todos llevan ? porque el constructor permite crear un producto vacío (null).
    private ?string $id;
    private ?string $nombre;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?bool $isDeleted;

    // Constructor: se ejecuta al hacer new Categoria(...) y guarda los datos en el objeto
    // Todos los parámetros son opcionales (= null), así se puede crear una categoría vacía
    public function __construct(?string $id = null, ?string $nombre = null, ?string $createdAt = null, ?string $updatedAt = null, ?bool $isDeleted = null)
    {
        // $this es el objeto actual: guarda cada valor recibido en su propiedad
        $this->id = $id;
        $this->nombre = $nombre;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isDeleted = $isDeleted;
    }

    // Método normal: devuelve el id
    public function getId(): ?string
    {
        return $this->id;
    }

    // Magic method for get and set
    // __get se ejecuta solo al leer una propiedad privada desde fuera: $categoria->nombre
    public function __get(string $name): mixed
    {
        return $this->$name;
    }

    // __set se ejecuta solo al modificar una propiedad privada desde fuera: $categoria->nombre = 'X'
    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }

    // Devuelve un UUID aleatorio. Es privado: solo se puede usar dentro de esta clase
    private function generateUUID(): string
    {
        return Uuid::uuid4()->toString();
    }
}

?>