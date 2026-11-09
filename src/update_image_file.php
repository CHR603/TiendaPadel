<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- CONTROL DE ACCESO ----------
// Solo el administrador puede cambiar imágenes. Esta página no tiene botón en ningún sitio
// (recibe el formulario), pero alguien podría escribir su dirección: se comprueba igualmente
if (!$session->isAdmin()) {
    header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No tienes permisos para cambiar imágenes. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit;
}

// ---------- SOLO SE TRABAJA SI LLEGA UN POST CON ARCHIVO ----------
// Si alguien abre esta página escribiendo la dirección (GET), o el formulario no envió
// ningún archivo, no hay nada que procesar: se vuelve al listado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['imagen'])) {
    header('Location: index.php');
    exit;
}

// ---------- RECIBIR EL ID Y LEER EL PRODUCTO ----------
// El id llega en el campo oculto del formulario (por POST). filter_var devuelve el entero, o false si no lo es
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php');
    exit;
}

// try/catch: si la base de datos falla, se vuelve al listado en vez de romper la página
try {
    $productosService = new ProductosService(Config::getInstance()->db);
    $producto = $productosService->findById($id); // El producto, o null si no existe
} catch (Throwable $e) {
    header('Location: index.php');
    exit;
}

// Si no existe ningún producto con ese id, no hay nada que actualizar
if ($producto === null) {
    header('Location: index.php');
    exit;
}

// Config es Singleton: getInstance() devuelve siempre el mismo objeto (la misma configuración)
$config = Config::getInstance();

// A partir de aquí el producto existe. Si algo falla, se vuelve al formulario con un aviso
$volverConError = 'update-image.php?id=' . $id . '&error=1';

// ---------- COMPROBAR QUE EL ARCHIVO SE HA SUBIDO BIEN ----------
// $_FILES['imagen'] es un array con los datos del archivo: name (nombre original), type, tmp_name
// (ruta temporal donde PHP lo ha dejado), error y size.
// UPLOAD_ERR_OK significa "subida sin errores". Si el archivo supera el tamaño permitido
// por PHP (2 MB por defecto), llega con otro código de error.
// is_uploaded_file comprueba que el archivo temporal viene de una subida real por HTTP
$subido = $_FILES['imagen'];
if ($subido['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($subido['tmp_name'])) {
    header('Location: ' . $volverConError);
    exit;
}

// ---------- COMPROBAR QUE ES UNA IMAGEN JPG O PNG ----------
// El tipo que dice el navegador ($subido['type']) y la extensión del nombre los puede escribir
// cualquiera, así que no son fiables. finfo mira el CONTENIDO del archivo y devuelve su tipo real (MIME)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($subido['tmp_name']);

// Extensión del nombre original, en minúsculas: "Foto.JPG" -> "jpg"
$extension = strtolower(pathinfo($subido['name'], PATHINFO_EXTENSION));

// Se exige que coincidan el tipo real Y la extensión (como dice el PDF).
// $extensionFinal es la que se usará para guardar: siempre "jpg" o "png"
if ($mime === 'image/jpeg' && in_array($extension, ['jpg', 'jpeg'], true)) {
    $extensionFinal = 'jpg';
} elseif ($mime === 'image/png' && $extension === 'png') {
    $extensionFinal = 'png';
} else {
    header('Location: ' . $volverConError);
    exit;
}

// ---------- GUARDAR EL ARCHIVO ----------
// El nombre lo genera el servidor a partir del uuid del producto (por ejemplo
// d69cf3db-b77d-4181-b3cd-5ca8107fb6a9.jpg), nunca se usa el nombre que envía el usuario.
// Así nadie puede elegir el nombre ni la ruta, y cada producto tiene su propio archivo
$nombre = $producto->uuid . '.' . $extensionFinal;
$destino = $config->uploadPath . $nombre; // Carpeta uploads de la configuración + nombre

// Se guarda la imagen anterior para borrarla después si hace falta
$imagenAnterior = (string)$producto->imagen;

// move_uploaded_file mueve el archivo de la ruta temporal a su destino definitivo.
// Devuelve false si no puede (por ejemplo, si la carpeta no tiene permisos de escritura)
if (!move_uploaded_file($subido['tmp_name'], $destino)) {
    header('Location: ' . $volverConError);
    exit;
}

// ---------- ACTUALIZAR LA RUTA EN LA BASE DE DATOS ----------
// La imagen se guarda como la dirección pública: uploadUrl + nombre del archivo.
// delete.php reconoce las imágenes propias porque su dirección empieza por uploadUrl.
// update() guarda el producto entero con la imagen nueva (el resto de datos no cambia)
try {
    $producto->imagen = $config->uploadUrl . $nombre;
    $productosService->update($producto);
} catch (Throwable $e) {
    header('Location: ' . $volverConError);
    exit;
}

// ---------- BORRAR LA IMAGEN ANTERIOR, SI SOBRA ----------
// Si el producto ya tenía una imagen propia con OTRO nombre (por ejemplo la anterior era
// .jpg y la nueva es .png), el archivo viejo se quedaría huérfano en uploads: se borra.
// Si tenía el mismo nombre, move_uploaded_file ya lo ha sustituido.
// basename() deja solo el nombre del archivo, sin ninguna ruta (seguridad)
if (str_starts_with($imagenAnterior, $config->uploadUrl)) {
    $anterior = $config->uploadPath . basename($imagenAnterior);
    if (basename($anterior) !== $nombre && is_file($anterior)) {
        unlink($anterior);
    }
}

// ---------- VOLVER AL FORMULARIO CON EL AVISO DE ÉXITO ----------
// Redirigir tras un POST evita que al recargar (F5) se reenvíe el formulario y se suba otra vez
header('Location: update-image.php?id=' . $id . '&ok=1');
exit;

?>