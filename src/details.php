<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML (header.php la necesita)
$session = SessionService::getInstance();

// ---------- RECIBIR Y VALIDAR EL ID ----------
// El id llega por la URL (details.php?id=3), así que se lee de $_GET.
// "?? ''" evita un error si no llega. filter_var devuelve el número entero,
// o false si lo que llegó no es un entero válido (por ejemplo "abc" o "3.5")
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

// Si no es un entero o es menor que 1, no puede ser un producto real: se vuelve al listado.
// header('Location: ...') redirige y exit corta la ejecución para que no siga el resto del código.
// Esto debe ir ANTES de escribir cualquier HTML
if ($id === false || $id < 1) {
    header('Location: index.php');
    exit;
}

// ---------- LEER EL PRODUCTO ----------
// try/catch: si la base de datos falla, se muestra un aviso en vez de romper la página
try {
    $producto = (new ProductosService(Config::getInstance()->db))->findById($id);
} catch (Throwable $e) {
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No se ha podido cargar el producto.</div>';
    include_once __DIR__ . '/footer.php';
    exit;
}

// findById devuelve null si no existe ningún producto con ese id: se vuelve al listado
if ($producto === null) {
    header('Location: index.php');
    exit;
}

// ---------- PREPARAR LO QUE CAMBIA ----------
// Un heredoc no admite if ni foreach: los datos se preparan antes en variables.
// htmlspecialchars se usa con todo lo que viene de la base de datos (evita XSS).
// (string) evita errores si algún dato es null. No se usa ?? con las propiedades del modelo
// porque son privadas y ?? las daría siempre por vacías
$vId = (int)$producto->id;
$vMarca = htmlspecialchars((string)$producto->marca);
$vModelo = htmlspecialchars((string)$producto->modelo);
$vDescripcion = htmlspecialchars((string)$producto->descripcion);
$vImagen = htmlspecialchars((string)$producto->imagen);
$vStock = (int)$producto->stock;
$vCategoria = htmlspecialchars($producto->categoriaNombre ?: 'Sin categoría'); // ?: sí funciona con propiedades
$vPrecio = number_format((float)$producto->precio, 2, ',', '.') . ' €';         // 249.95 -> 249,95 €

// ---------- ESCRIBIR LA PÁGINA ----------
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta "HTML;" se escribe tal cual y {$variable} se sustituye por su valor.
// Escribe: el título, una lista de definición (<dl>) con los datos del producto y el botón de volver.
// En la lista, cada <dt> es el nombre del dato y el <dd> que le sigue es su valor.
// En Bootstrap, la clase "row" con col-sm-3 y col-sm-9 pone los nombres a la izquierda y los valores a la derecha.
// El cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
<h1 class="text-success">Detalle del producto</h1>

<dl class="row mt-4">
    <dt class="col-sm-3">ID</dt>
    <dd class="col-sm-9">{$vId}</dd>

    <dt class="col-sm-3">Marca</dt>
    <dd class="col-sm-9">{$vMarca}</dd>

    <dt class="col-sm-3">Modelo</dt>
    <dd class="col-sm-9">{$vModelo}</dd>

    <dt class="col-sm-3">Descripción</dt>
    <dd class="col-sm-9">{$vDescripcion}</dd>

    <dt class="col-sm-3">Precio</dt>
    <dd class="col-sm-9">{$vPrecio}</dd>

    <dt class="col-sm-3">Stock</dt>
    <dd class="col-sm-9">{$vStock}</dd>

    <dt class="col-sm-3">Categoría</dt>
    <dd class="col-sm-9">{$vCategoria}</dd>

    <dt class="col-sm-3">Imagen</dt>
    <dd class="col-sm-9"><img src="{$vImagen}" alt="{$vMarca} {$vModelo}" class="img-thumbnail" width="200"></dd>
</dl>

<a href="index.php" class="btn btn-outline-secondary">Volver al listado</a>
HTML;

include_once __DIR__ . '/footer.php';

?>