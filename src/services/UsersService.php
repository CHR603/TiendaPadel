<?php

// Esta clase está en la carpeta src/services/ (el namespace debe coincidir)
namespace services;

use Exception;      // Clase de excepciones de PHP (para lanzar el error de login)
use models\User;    // Modelo que representa un usuario con sus roles
use PDO;            // Se usa para el tipo PDO y para las constantes PDO::FETCH_ASSOC...

// Carga el modelo User a mano (con el autoload de Composer no haría falta, pero no molesta)
require_once __DIR__ . '/../models/User.php';

// Servicio: autenticación y consulta de usuarios
class UsersService
{
    private PDO $db; // Conexión a la base de datos

    // Recibe la conexión PDO (se la pasamos desde Config: $config->db)
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Comprueba usuario y contraseña. Devuelve el User si son correctos, si no, lanza una excepción
    public function authenticate(string $username, string $password): User
    {
        // Busca al usuario (con sus roles) por su nombre de usuario
        $user = $this->findUserByUsername($username);

        // password_verify compara la contraseña escrita con el HASH guardado en la base de datos
        // Si el usuario no existe o la contraseña no coincide: mismo mensaje en ambos casos
        if ($user === null || !password_verify($password, $user->password)) {
            throw new Exception('Usuario o contraseña incorrectos');
        }
        return $user;
    }

    // Busca un usuario por su username y carga también sus roles. Devuelve null si no existe
    public function findUserByUsername(string $username): ?User
    {
        // :username es un marcador: PDO lo sustituye de forma segura (evita inyección SQL)
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = :username AND is_deleted = false");
        // bindParam: asocia el marcador a una variable y le indica el tipo (texto)
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        // fetch: devuelve la fila como array asociativo, o false si no hay
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null; // No hay ningún usuario con ese username
        }

        // Los roles están en otra tabla (user_roles): se piden aparte
        $userId = (int)$row['id'];
        $stmt = $this->db->prepare("SELECT roles FROM user_roles WHERE user_id = :id");
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // fetchAll con FETCH_COLUMN: devuelve todas las filas, pero solo la primera columna, en un array simple
        // Resultado: ['USER', 'ADMIN']
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Convierte la fila en un objeto User, en el orden del constructor del modelo
        return new User(
            $row['id'],
            $row['username'],
            $row['password'],
            $row['nombre'],
            $row['apellidos'],
            $row['email'],
            $row['created_at'],
            $row['updated_at'],
            $row['is_deleted'],
            $roles
        );
    }
}

?>