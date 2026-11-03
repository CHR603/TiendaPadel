<?php
// Carga el autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use models\Categoria;

// Crea una categoría con id, nombre, fechas y isDeleted
$cat = new Categoria('abc-123', 'DEPORTES', '2026-11-03', '2026-11-03', false);

echo "Id: " . $cat->getId() . "<br>";        // método normal
echo "Nombre: " . $cat->nombre . "<br>";     // usa __get

$cat->nombre = 'PÁDEL';                      // usa __set
echo "Nombre nuevo: " . $cat->nombre . "<br>";

echo $cat->id;