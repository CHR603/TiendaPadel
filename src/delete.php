<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- CONTROL DE ACCESO ----------
// Solo el administrador puede borrar productos. Si no lo es, se muestra un mensaje
// y a los 3 segundos el navegador va solo al índice.
// La comprobación se hace aquí, en la propia página: ocultar el botón Borrar no basta,
// porque alguien podría escribir /delete.php?id=3 en la barra del navegador
if (!$session->isAdmin()) {
    header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No tienes permisos para borrar productos. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit; // Corta la ejecución: el resto de la página no se ejecuta
}

// ---------- RECIBIR Y VALIDAR EL ID ----------
// El id llega por la URL (delete.php?id=3). filter_var devuelve el entero, o false si no lo es
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php'); // Redirige al listado
    exit;                          // y corta la ejecución
}

// ---------- LEER EL PRODUCTO ----------
// Hace falta leerlo antes de borrarlo por dos motivos: comprobar que existe
// y saber qué imagen tiene, para borrar también ese archivo
try {
    $productosService = new ProductosService(Config::getInstance()->db);
    $producto = $productosService->findById($id); // El producto, o null si no existe
} catch (Throwable $e) {
    header('Refresh: 3; url=index.php');
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No se ha podido cargar el producto. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit;
}

// Si no existe ningún producto con ese id, no hay nada que borrar: se vuelve al listado
if ($producto === null) {
    header('Location: index.php');
    exit;
}

// ---------- BORRAR LA IMAGEN ASOCIADA ----------
// Config es Singleton: getInstance() devuelve siempre el mismo objeto (la misma configuración)
$config = Config::getInstance();
$imagen = (string)$producto->imagen;

// Una imagen es "personalizada" (subida por el administrador) si su dirección empieza por
// la de la carpeta uploads. La imagen por defecto es externa (placeholder) y no hay nada que borrar.
// str_starts_with comprueba si un texto empieza por otro
if (str_starts_with($imagen, $config->uploadUrl)) {
    // basename() se queda solo con el nombre del archivo (quita cualquier ruta),
    // así nadie puede apuntar a otra carpeta con algo como ../../archivo
    $archivo = $config->uploadPath . basename($imagen);

    // is_file comprueba que el archivo existe. unlink lo borra del disco
    if (is_file($archivo)) {
        unlink($archivo);
    }
}

// ---------- BORRAR EL PRODUCTO ----------
// deleteById elimina la fila de la base de datos (borrado físico)
try {
    $productosService->deleteById($id);
    $mensaje = '<div class="alert alert-success">Producto eliminado correctamente. Volviendo al listado...</div>';
} catch (Throwable $e) {
    $mensaje = '<div class="alert alert-danger">No se ha podido eliminar el producto. Volviendo al listado...</div>';
}

// ---------- MENSAJE Y REDIRECCIÓN ----------
// Muestra el resultado 3 segundos y el navegador va solo al índice
header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
include_once __DIR__ . '/header.php';
echo $mensaje;
include_once __DIR__ . '/footer.php';

?>