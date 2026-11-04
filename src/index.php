<?php
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use models\Categoria;

try {
    // Prueba de Config (conexión PDO)
    $config = Config::getInstance();
    $total = $config->db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    echo "Conexión correcta. Productos en la base de datos: " . $total . "<br><br>";

    // Prueba de Categoria
    $cat = new Categoria('abc-123', 'DEPORTES', '2026-11-03', '2026-11-03', false);
    echo "Id: " . $cat->getId() . "<br>";
    echo "Nombre: " . $cat->nombre . "<br>";
    $cat->nombre = 'PÁDEL';
    echo "Nombre nuevo: " . $cat->nombre . "<br>";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}