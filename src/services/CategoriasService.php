<?php

// Esta clase está en la carpeta src/services/ (el namespace debe coincidir)
namespace services;

use models\Categoria; // Modelo que representa una fila de "categorias"
use PDO;              // Se usa para el tipo PDO y para PDO::FETCH_ASSOC

// Carga el modelo Categoria a mano (con el autoload de Composer no haría falta, pero no molesta)
require_once __DIR__ . '/../models/Categoria.php';

// Servicio: contiene las consultas SQL de las categorías
class CategoriasService
{
    private PDO $pdo; // Conexión a la base de datos

    // Recibe la conexión PDO (se la pasamos desde Config: $config->db)
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Devuelve todas las categorías como un array de objetos Categoria
    public function findAll(): array
    {
        // prepare() prepara la consulta; execute() la ejecuta
        $stmt = $this->pdo->prepare("SELECT * FROM categorias ORDER BY id ASC");
        $stmt->execute();

        $categorias = [];
        // fetch() devuelve una fila cada vez (como array asociativo) y false cuando no quedan
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convierte la fila en un objeto Categoria
            $categoria = new Categoria(
                $row['id'],
                $row['nombre'],
                $row['created_at'],
                $row['updated_at'],
                $row['is_deleted']
            );
            $categorias[] = $categoria; // Lo añade al array
        }
        return $categorias;
    }

    // Busca una categoría por su nombre exacto. Devuelve la Categoria o false si no existe
    public function findByName(string $name): Categoria|false
    {
        // :nombre es un marcador: PDO lo sustituye de forma segura (evita inyección SQL)
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $name]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false; // No hay ninguna fila con ese nombre
        }
        $categoria = new Categoria(
            $row['id'],
            $row['nombre'],
            $row['created_at'],
            $row['updated_at'],
            $row['is_deleted']
        );
        return $categoria;
    }
}

?>