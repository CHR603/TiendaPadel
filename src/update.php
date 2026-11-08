<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\CategoriasService;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- CONTROL DE ACCESO ----------
// Solo el administrador puede modificar productos. Si no lo es, se muestra un mensaje
// y a los 3 segundos el navegador va solo al índice.
// La comprobación se hace aquí, en la propia página: ocultar el botón Editar no basta,
// porque alguien podría escribir /update.php?id=1 en la barra
if (!$session->isAdmin()) {
    header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No tienes permisos para modificar productos. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit; // Corta la ejecución: el resto de la página no se ejecuta
}

// ---------- RECIBIR Y VALIDAR EL ID ----------
// El id llega por la URL (update.php?id=3). filter_var devuelve el entero, o false si no lo es
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: index.php'); // Redirige al listado
    exit;                          // y corta la ejecución
}

// ---------- LEER EL PRODUCTO Y LAS CATEGORÍAS ----------
// try/catch: si la base de datos falla, se muestra un aviso en vez de romper la página.
// Si falla, la página termina con exit, así que más abajo estas variables siempre existen
try {
    $db = Config::getInstance()->db;
    $productosService = new ProductosService($db);
    $producto = $productosService->findById($id);          // El producto a editar (o null)
    $categorias = (new CategoriasService($db))->findAll(); // Para el desplegable
} catch (Throwable $e) {
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No se ha podido cargar el producto.</div>';
    include_once __DIR__ . '/footer.php';
    exit;
}

// Si no existe ningún producto con ese id, se vuelve al listado
if ($producto === null) {
    header('Location: index.php');
    exit;
}

// ---------- VALORES INICIALES ----------
// Al abrir la página, el formulario muestra los datos actuales del producto.
// (string) convierte cada valor a texto. No se usa ?? con las propiedades del modelo
// porque son privadas y ?? las daría siempre por vacías
$marca = (string)$producto->marca;
$modelo = (string)$producto->modelo;
$descripcion = (string)$producto->descripcion;
$precio = (string)$producto->precio;
$stock = (string)$producto->stock;
$categoriaId = (string)$producto->categoriaId;

$errores = [];   // Errores de validación: ['marca' => 'texto del error', ...]
$mensaje = '';   // Mensaje de éxito
$aviso = '';     // Mensaje de error de base de datos

// ---------- PROCESAR EL FORMULARIO ----------
// Solo se ejecuta cuando se pulsa "Guardar cambios" (el formulario usa POST).
// El id sigue en la URL (action="update.php?id=3"), por eso $_GET['id'] sigue disponible
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Los valores enviados sustituyen a los iniciales. trim() quita espacios sobrantes
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $categoriaId = $_POST['categoria_id'] ?? '';

    // VALIDACIÓN: cada fallo se guarda en $errores con el nombre del campo.
    // mb_strlen cuenta caracteres (strlen contaría bytes y las tildes ocupan 2)
    if ($marca === '') {
        $errores['marca'] = 'La marca es obligatoria.';
    } elseif (mb_strlen($marca) > 255) {
        $errores['marca'] = 'La marca no puede superar los 255 caracteres.';
    }

    if ($modelo === '') {
        $errores['modelo'] = 'El modelo es obligatorio.';
    } elseif (mb_strlen($modelo) > 255) {
        $errores['modelo'] = 'El modelo no puede superar los 255 caracteres.';
    }

    if ($descripcion === '') {
        $errores['descripcion'] = 'La descripción es obligatoria.';
    } elseif (mb_strlen($descripcion) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    // filter_var con FILTER_VALIDATE_FLOAT devuelve el número, o false si no es un decimal válido
    $precioNum = filter_var($precio, FILTER_VALIDATE_FLOAT);
    if ($precioNum === false || $precioNum < 0) {
        $errores['precio'] = 'El precio debe ser un número igual o mayor que 0.';
    }

    // Igual con FILTER_VALIDATE_INT. Se compara con === porque el stock 0 es válido (y 0 == false en PHP)
    $stockNum = filter_var($stock, FILTER_VALIDATE_INT);
    if ($stockNum === false || $stockNum < 0) {
        $errores['stock'] = 'El stock debe ser un número entero igual o mayor que 0.';
    }

    // La categoría debe ser una de las que existen. Nunca se confía en lo que llega del navegador
    $categoriaValida = false;
    foreach ($categorias as $c) {
        if ($c->id === $categoriaId) {
            $categoriaValida = true;
        }
    }
    if (!$categoriaValida) {
        $errores['categoria'] = 'Elige una categoría de la lista.';
    }

    // Si no hay ningún error, se actualiza el producto
    if (count($errores) === 0) {
        try {
            // Se modifica el MISMO objeto que se leyó de la base de datos, así conserva su
            // id, su uuid y su imagen, que no se editan aquí. __set cambia cada propiedad
            $producto->marca = $marca;
            $producto->modelo = $modelo;
            $producto->descripcion = $descripcion;
            $producto->precio = $precioNum;
            $producto->stock = $stockNum;
            $producto->categoriaId = $categoriaId;

            // update() guarda los cambios en la fila con ese id
            $productosService->update($producto);

            $mensaje = '<div class="alert alert-success">Producto modificado correctamente.</div>';
        } catch (Throwable $e) {
            $aviso = '<div class="alert alert-danger">No se ha podido guardar el producto.</div>';
        }
    }
}

// ---------- PREPARAR LO QUE CAMBIA ----------
// Un heredoc no admite if ni foreach: todo lo variable se prepara antes en variables

// Función pequeña: devuelve el mensaje de error de un campo, o '' si ese campo no tiene error
function mensajeError(array $errores, string $campo): string
{
    if (!isset($errores[$campo])) {
        return '';
    }
    return '<div class="text-danger small mt-1">' . htmlspecialchars($errores[$campo]) . '</div>';
}

// Errores de cada campo, para escribirlos junto a él en el formulario
$errMarca = mensajeError($errores, 'marca');
$errModelo = mensajeError($errores, 'modelo');
$errDescripcion = mensajeError($errores, 'descripcion');
$errPrecio = mensajeError($errores, 'precio');
$errStock = mensajeError($errores, 'stock');
$errCategoria = mensajeError($errores, 'categoria');

// Valores del formulario, protegidos con htmlspecialchars (evita XSS)
$vId = (int)$id;
$vMarca = htmlspecialchars($marca);
$vModelo = htmlspecialchars($modelo);
$vDescripcion = htmlspecialchars($descripcion);
$vPrecio = htmlspecialchars($precio);
$vStock = htmlspecialchars($stock);
$vImagen = htmlspecialchars((string)$producto->imagen);

// Opciones del desplegable: una por categoría, marcando la que corresponde
$opciones = '<option value="">-- Elige una categoría --</option>';
foreach ($categorias as $c) {
    $seleccionada = ($c->id === $categoriaId) ? ' selected' : '';
    $opciones .= '<option value="' . htmlspecialchars($c->id) . '"' . $seleccionada . '>'
        . htmlspecialchars($c->nombre) . '</option>';
}

// ---------- ESCRIBIR LA PÁGINA ----------
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta "HTML;" se escribe tal cual y {$variable} se sustituye por su valor.
// Escribe: el título, los mensajes, el formulario y los botones.
// action="update.php?id={$vId}" mantiene el id en la URL al enviar el formulario (POST).
// El campo imagen es readonly: se ve pero no se puede editar (la imagen se cambia en otra página).
// El cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
<h1 class="text-success">Editar producto #{$vId}</h1>

{$mensaje}
{$aviso}

<form method="POST" action="update.php?id={$vId}" class="mt-3">
    <div class="mb-3">
        <label class="form-label" for="marca">Marca</label>
        <input type="text" class="form-control" id="marca" name="marca" value="{$vMarca}">
        {$errMarca}
    </div>

    <div class="mb-3">
        <label class="form-label" for="modelo">Modelo</label>
        <input type="text" class="form-control" id="modelo" name="modelo" value="{$vModelo}">
        {$errModelo}
    </div>

    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción</label>
        <input type="text" class="form-control" id="descripcion" name="descripcion" value="{$vDescripcion}">
        {$errDescripcion}
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="precio">Precio (€)</label>
            <input type="number" step="0.01" class="form-control" id="precio" name="precio" value="{$vPrecio}">
            {$errPrecio}
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label" for="stock">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" value="{$vStock}">
            {$errStock}
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="categoria_id">Categoría</label>
        <select class="form-select" id="categoria_id" name="categoria_id">
            {$opciones}
        </select>
        {$errCategoria}
    </div>

    <div class="mb-3">
        <label class="form-label" for="imagen">Imagen (solo lectura)</label>
        <input type="text" class="form-control" id="imagen" value="{$vImagen}" readonly>
        <div class="form-text">La imagen se cambia desde el botón Imagen del listado.</div>
    </div>

    <button type="submit" class="btn btn-success">Guardar cambios</button>
    <a href="index.php" class="btn btn-outline-secondary">Volver al listado</a>
</form>
HTML;

include_once __DIR__ . '/footer.php';

?>