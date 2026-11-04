<?php

// Esta clase está en la carpeta src/models/ (el namespace debe coincidir)
namespace models;

// Modelo: un objeto User representa una fila de la tabla "usuarios" junto con sus roles
class User
{
    // Una propiedad por cada columna de la tabla. Son privadas: no se tocan directamente desde fuera
     // Todos llevan ? porque el constructor permite crear un producto vacío (null).
    private ?int $id;
    private ?string $username;
    private ?string $password;   // Aquí está el HASH bcrypt, nunca la contraseña en claro
    private ?string $nombre;
    private ?string $apellidos;
    private ?string $email;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?bool $isDeleted;
    private array $roles;        // Roles del usuario, por ejemplo ['USER', 'ADMIN']

    // Constructor: se ejecuta al hacer new Categoria(...) y guarda los datos en el objeto
    // Todos los parámetros son opcionales (= null), así se puede crear una categoría vacía
    public function __construct(
        ?int $id = null,
        ?string $username = null,
        ?string $password = null,
        ?string $nombre = null,
        ?string $apellidos = null,
        ?string $email = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?bool $isDeleted = null,
        array $roles = []
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->nombre = $nombre;
        $this->apellidos = $apellidos;
        $this->email = $email;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isDeleted = $isDeleted;
        $this->roles = $roles;
    }

    // Magic method for get and set
    // __get se ejecuta solo al leer una propiedad privada desde fuera: $user->username
    public function __get(string $name): mixed
    {
        return $this->$name;
    }

    // __set se ejecuta solo al modificar una propiedad privada desde fuera: $user->email = 'X'
    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }
}