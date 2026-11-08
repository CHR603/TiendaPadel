<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use models\Producto;
use services\CategoriasService;
use services\ProductosService;
use services\SessionService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- CONTROL DE ACCESO ----------
// Solo el administrador puede crear productos. Si no lo es, se muestra un mensaje
// y a los 3 segundos el navegador va solo al índice (header Refresh).
// Ocultar el enlace del menú no basta: alguien podría escribir /create.php en la barra.
// Por eso la comprobación se hace aquí, en la propia página
if (!$session->isAdmin()) {
    header('Refresh: 3; url=index.php'); // Debe ir antes de cualquier HTML
    include_once __DIR__ . '/header.php';
    echo '<div class="alert alert-danger">No tienes permisos para crear productos. Volviendo al listado...</div>';
    include_once __DIR__ . '/footer.php';
    exit; // Corta la ejecución: el resto de la página no se ejecuta
}

// ---------- VALORES INICIALES ----------
// Variables del formulario. Empiezan vacías y se rellenan si el formulario se envía
$marca = $modelo = $descripcion = $precio = $stock = $categoriaId = '';
$errores = [];   // Aquí se guardan los errores de validación: ['marca' => 'texto del error', ...]
$mensaje = '';   // Mensaje de éxito
$aviso = '';     // Mensaje de error de base de datos
$categorias = [];

// Carga las categorías para el desplegable (y para comprobar la elegida).
// try/catch: si falla la base de datos, se avisa en vez de romper la página
try {
    $config = Config::getInstance();
    $categorias = (new CategoriasService($config->db))->findAll();
} catch (Throwable $e) {
    $aviso = '<div class="alert alert-danger">No se han podido cargar las categorías.</div>';
}

// ---------- PROCESAR EL FORMULARIO ----------
// Esta parte solo se ejecuta cuando se pulsa "Guardar" (el formulario usa POST).
// Al abrir la página normalmente (GET) se salta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aviso === '') {
    // Lee cada campo. trim() quita espacios; "?? ''" evita un error si el campo no llega
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

    // Igual con FILTER_VALIDATE_INT: devuelve el entero, o false si no lo es.
    // Se compara con === porque el stock 0 es válido (y 0 == false en PHP)
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

    // Si no hay ningún error, se guarda el producto
    if (count($errores) === 0) {
        try {
            // Producto nuevo: sin id, uuid ni imagen, porque save() los asigna.
            // El orden de los argumentos es el del constructor del modelo Producto
            $producto = new Producto(null, null, $descripcion, null, $marca, $modelo, $precioNum, $stockNum, null, null, $categoriaId);
                        // Config es Singleton: getInstance() devuelve siempre el mismo objeto (la misma conexión)
            (new ProductosService(Config::getInstance()->db))->save($producto);

            $mensaje = '<div class="alert alert-success">Producto creado correctamente.</div>';
            // Vacía el formulario para poder crear otro producto
            $marca = $modelo = $descripcion = $precio = $stock = $categoriaId = '';
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

// Valores escritos, protegidos con htmlspecialchars (evita XSS), para que no se pierdan si hay errores
$vMarca = htmlspecialchars($marca);
$vModelo = htmlspecialchars($modelo);
$vDescripcion = htmlspecialchars($descripcion);
$vPrecio = htmlspecialchars($precio);
$vStock = htmlspecialchars($stock);

// Opciones del desplegable: una por categoría, marcando la que se había elegido
$opciones = '<option value="">-- Elige una categoría --</option>';
foreach ($categorias as $c) {
    $seleccionada = ($c->id === $categoriaId) ? ' selected' : '';
    $opciones .= '<option value="' . htmlspecialchars($c->id) . '"' . $seleccionada . '>'
        . htmlspecialchars($c->nombre) . '</option>';
}

// ---------- ESCRIBIR LA PÁGINA ----------
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta "HTML;" se escribe tal cual y {$variable} se sustituye por su valor.
// Escribe: el título, los mensajes, el formulario (method="POST" envía los datos en el
// cuerpo de la petición y no por la URL) y el botón para volver al listado.
// El cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
<h1 class="text-success">Crear producto</h1>

{$mensaje}
{$aviso}

<form method="POST" action="create.php" class="mt-3">
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

    <button type="submit" class="btn btn-success">Guardar</button>
    <a href="index.php" class="btn btn-outline-secondary">Volver al listado</a>
</form>
HTML;

include_once __DIR__ . '/footer.php';

?>