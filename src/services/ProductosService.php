<?php

// Esta clase está en la carpeta src/services/ (el namespace debe coincidir)
namespace services;

use models\Producto;   // Modelo que representa una fila de "productos"
use PDO;               // Se usa para el tipo PDO y para PDO::FETCH_ASSOC
use Ramsey\Uuid\Uuid;  // Se usará en save() para generar el uuid del producto nuevo

// Carga el modelo Producto a mano (con el autoload de Composer no haría falta, pero no molesta)
require_once __DIR__ . '/../models/Producto.php';

// Servicio: contendrá las consultas SQL de los productos
class ProductosService
{
    private PDO $pdo; // Conexión a la base de datos

    // Recibe la conexión PDO (se la pasamos desde Config: $config->db)
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Métodos pendientes (según el PDF, punto 7.1):
    // FASE 2: findAllWithCategoryName($searchTerm)  lista productos con su categoría (JOIN) y búsqueda
    // FASE 2: findById($id)                         busca un producto por id (null si no existe)
    // FASE 2: save(Producto $producto)              inserta un producto nuevo (genera el uuid)
    // FASE 3: update(Producto $producto)            actualiza un producto existente
    // FASE 3: deleteById($id)                       elimina un producto (true si va bien)
}

?>