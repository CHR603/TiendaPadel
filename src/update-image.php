<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- CONTROL DE ACCESO ----------
// Solo el administrador puede cambiar imágenes. Si no lo es, se muestra un mensaje
// y a los 3 segundos el navegador va solo al índice.
// La comprobación se hace aquí, en la propia página: ocultar el botón Imagen no basta,
// porque alguien podría escribir /update-image.php?id=1 en la barra
if (!$session->isAdmin()) {
    header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No tienes permisos para cambiar imágenes. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit; // Corta la ejecución: el resto de la página no se ejecuta
}

// ---------- RECIBIR EL ID Y LEER EL PRODUCTO ----------
// El id llega por la URL (update-image.php?id=3). filter_var devuelve el entero, o false si no lo es
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$producto = null; // Si el id no vale, el producto se queda en null

// Solo se consulta la base de datos si el id es un entero válido.
// try/catch: si la base de datos falla, se avisa en vez de romper la página
if ($id !== false && $id >= 1) {
    try {
        $producto = (new ProductosService(Config::getInstance()->db))->findById($id);
    } catch (Throwable $e) {
        header('Refresh: 3; url=index.php');
        include_once __DIR__ . '/header.php';
        echo '<div class="alert alert-danger">No se ha podido cargar el producto. Volviendo al listado...</div>';
        include_once __DIR__ . '/footer.php';
        exit;
    }
}

// Si el id no es válido o no existe ese producto, se avisa y se vuelve al listado.
// (El PDF dice "te avisa y te redirige")
if ($producto === null) {
    header('Refresh: 3; url=index.php');
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-warning">El producto no existe. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit;
}

// ---------- MENSAJES DE RESULTADO ----------
// update_image_file.php vuelve a esta página con ?ok=1 (todo bien) o ?error=1 (algo falló).
// Se leen de la URL con $_GET y se comparan con ===
$alerta = '';
if (($_GET['ok'] ?? '') === '1') {
    $alerta = '<div class="alert alert-success">Imagen actualizada correctamente.</div>';
} elseif (($_GET['error'] ?? '') === '1') {
    $alerta = '<div class="alert alert-danger">No se ha podido subir la imagen. Usa un archivo JPG o PNG.</div>';
}

// ---------- PREPARAR LO QUE CAMBIA ----------
// Un heredoc no admite if ni foreach: los datos se preparan antes en variables.
// htmlspecialchars convierte < y > en texto inofensivo (evita XSS).
// (string) evita errores si algún dato es null. No se usa ?? con las propiedades del modelo
// porque son privadas y ?? las daría siempre por vacías
$vId = (int)$producto->id;
$vMarca = htmlspecialchars((string)$producto->marca);
$vModelo = htmlspecialchars((string)$producto->modelo);
$vImagen = htmlspecialchars((string)$producto->imagen);

// ---------- ESCRIBIR LA PÁGINA ----------
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta "HTML;" se escribe tal cual y {$variable} se sustituye por su valor.
// Escribe: el título, el aviso, los datos del producto con su imagen actual y el formulario.
// Detalles del formulario:
//  - method="POST" y enctype="multipart/form-data": sin este enctype el navegador NO envía el archivo.
//  - action="update_image_file.php": esa página recibirá el id y el archivo (paso siguiente).
//  - El campo oculto (hidden) lleva el id del producto, porque la otra página lo necesita.
//  - type="file" permite elegir un archivo. name="imagen" es el nombre con el que llegará en $_FILES.
//  - accept="image/jpeg,image/png" hace que el navegador solo ofrezca JPG y PNG. Es solo una ayuda:
//    el servidor debe volver a comprobarlo, porque el navegador se puede saltar.
// El cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
<h1 class="text-success">Cambiar imagen del producto</h1>

{$alerta}

<dl class="row mt-4">
    <dt class="col-sm-3">ID</dt>
    <dd class="col-sm-9">{$vId}</dd>

    <dt class="col-sm-3">Marca</dt>
    <dd class="col-sm-9">{$vMarca}</dd>

    <dt class="col-sm-3">Modelo</dt>
    <dd class="col-sm-9">{$vModelo}</dd>

    <dt class="col-sm-3">Imagen actual</dt>
    <dd class="col-sm-9"><img src="{$vImagen}" alt="{$vMarca} {$vModelo}" class="img-thumbnail" width="200"></dd>
</dl>

<form method="POST" action="update_image_file.php" enctype="multipart/form-data" class="mt-3">
    <input type="hidden" name="id" value="{$vId}">

    <div class="mb-3">
        <label class="form-label" for="imagen">Nueva imagen (JPG o PNG)</label>
        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/jpeg,image/png" required>
    </div>

    <button type="submit" class="btn btn-success">Actualizar imagen</button>
    <a href="index.php" class="btn btn-outline-secondary">Volver al listado</a>
</form>
HTML;

include_once __DIR__ . '/footer.php';

?>