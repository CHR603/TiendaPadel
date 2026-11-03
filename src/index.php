<?php
// Carga el autoload de Composer: permite usar las clases (Config) sin require_once
// __DIR__ es la carpeta de este archivo (src/), '/../' sube a la raíz donde está vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Importa la clase Config (namespace config) para usar su nombre corto
use config\Config;

// try/catch: si algo falla dentro del try, salta al catch en vez de romper la página
try {
    // Obtiene el único objeto Config (Singleton); aquí se abre la conexión PDO
    $config = Config::getInstance();

    // $config->db es la conexión (__get). query() ejecuta el SQL y fetchColumn()
    // devuelve el valor de la primera columna de la primera fila: el número de productos
    $total = $config->db->query("SELECT COUNT(*) FROM productos")->fetchColumn();

    // Muestra el resultado en el navegador
    echo "Conexión correcta. Productos en la base de datos: " . $total;
} catch (Exception $e) {
    // Si falla la conexión o la consulta, muestra el mensaje de error
    echo "Error: " . $e->getMessage();
}