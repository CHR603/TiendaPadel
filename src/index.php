<?php

// Carga el autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\ProductosService;
use services\SessionService;

// Obtiene la sesión y la configuración (los dos son Singleton).
// La sesión debe ir antes de cualquier HTML: header.php escribe la página
$session = SessionService::getInstance();

// Para ver la web hay que haber iniciado sesión: si no, se va al login.
// Debe ir antes de cualquier HTML, y exit corta la ejecución
if (!$session->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// ---------- LÓGICA: leer los productos ----------
// Texto del buscador. Viene por la URL (?search=...) porque el formulario usa GET.
// "?? ''" evita un error si todavía no se ha buscado nada. trim() quita espacios sobrantes
$search = trim($_GET['search'] ?? '');

$aviso = '';      // Mensaje de error, si lo hay
$productos = [];  // Productos a mostrar

// try/catch: si la base de datos falla, se muestra un mensaje en vez de romper la página.
// Es importante: un error de conexión sin capturar enseñaría usuario y contraseña
try {
    $config = Config::getInstance();
    $productosService = new ProductosService($config->db);
    $productos = $productosService->findAllWithCategoryName($search);
} catch (Throwable $e) {
    $aviso = '<div class="alert alert-danger">No se han podido cargar los productos.</div>';
}

// ---------- PREPARAR LO QUE CAMBIA ----------
// Un heredoc no admite if ni foreach, así que todo lo variable se prepara antes en variables.
// htmlspecialchars se usa con todo lo que viene del usuario o de la base de datos (evita XSS)

// Mensaje de bienvenida con el nombre de la sesión ("Invitado" si no hay sesión)
$bienvenida = 'Bienvenido, ' . htmlspecialchars($session->getUsername());

// Texto del buscador ya protegido, para volver a mostrarlo dentro del campo
$busqueda = htmlspecialchars($search);

// Botón "Crear producto": solo lo ve el administrador
$botonCrear = '';
if ($session->isAdmin()) {
    $botonCrear = '<a class="btn btn-dark" href="create.php">Crear producto</a>';
}

// Filas de la tabla: se construyen con un bucle, una por producto
$filas = '';
foreach ($productos as $p) {
    // Los datos del producto, protegidos. NO se usa ?? con las propiedades de los modelos:
    // como son privadas, ?? las daría siempre por vacías. Se convierten con (string) o (int)
    $id = (int)$p->id;
    $marca = htmlspecialchars((string)$p->marca);
    $modelo = htmlspecialchars((string)$p->modelo);
    $imagen = htmlspecialchars((string)$p->imagen);
    $categoria = htmlspecialchars($p->categoriaNombre ?: 'Sin categoría'); // ?: sí funciona con propiedades
    $stock = (int)$p->stock;
    $precio = number_format((float)$p->precio, 2, ',', '.') . ' €'; // 249.95 -> 249,95 €

    // Botones de cada fila. "Ver" lo ve todo el mundo
    $botones = '<a class="btn btn-sm btn-info" href="details.php?id=' . $id . '">Ver</a>';

    // Editar, cambiar imagen y borrar: solo para el administrador.
    // El id va en la URL (?id=3) y la otra página lo leerá con $_GET
    if ($session->isAdmin()) {
        $confirmar = "return confirm('¿Seguro que quieres borrar este producto?')"; // Pide confirmación
        $botones .= ' <a class="btn btn-sm btn-warning" href="update.php?id=' . $id . '">Editar</a>';
        $botones .= ' <a class="btn btn-sm btn-secondary" href="update-image.php?id=' . $id . '">Imagen</a>';
        $botones .= ' <a class="btn btn-sm btn-danger" href="delete.php?id=' . $id . '" onclick="' . $confirmar . '">Borrar</a>';
    }

    // Añade la fila con todas sus celdas. El punto (.) une textos
    $filas .= '<tr>'
        . '<td>' . $id . '</td>'
        . '<td><img src="' . $imagen . '" alt="' . $marca . ' ' . $modelo . '" width="60"></td>'
        . '<td>' . $marca . '</td>'
        . '<td>' . $modelo . '</td>'
        . '<td>' . $precio . '</td>'
        . '<td>' . $stock . '</td>'
        . '<td>' . $categoria . '</td>'
        . '<td>' . $botones . '</td>'
        . '</tr>';
}

// Si no hay ningún producto (búsqueda sin resultados), una sola fila con un aviso
if ($filas === '') {
    $filas = '<tr><td colspan="8" class="text-center">No se han encontrado productos</td></tr>';
}

// Pie con visitas y último login: solo si el usuario ha iniciado sesión
$pieSesion = '';
if ($session->isLoggedIn()) {
    $pieSesion = '<p class="text-muted mt-4">Visitas en esta sesión: ' . $session->getVisitas()
        . '. Último login: ' . htmlspecialchars((string)$session->getLastLogin()) . '</p>';
}

// ---------- ESCRIBIR LA PÁGINA ----------
// Cabecera: abre el HTML, escribe el menú y abre <main>. include_once la carga una sola vez
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta la línea "HTML;" se escribe tal cual, y {$variable} se sustituye por su valor.
// Este bloque escribe: el título con la bienvenida, el formulario de búsqueda, el botón de crear,
// la tabla (con las filas preparadas arriba) y el pie de la sesión.
// Cuidado: el cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
{$aviso}
<h1 class="text-success">{$bienvenida}</h1>

<!-- Formulario de búsqueda: method="GET" envía el texto por la URL (index.php?search=...) -->
<form method="GET" action="index.php" class="row g-2 my-3">
    <div class="col-auto">
        <input type="text" name="search" value="{$busqueda}" class="form-control" placeholder="Marca o modelo">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-success">Buscar</button>
        <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="mb-3">{$botonCrear}</div>

<table class="table table-striped align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th><th>Imagen</th><th>Marca</th><th>Modelo</th>
            <th>Precio</th><th>Stock</th><th>Categoría</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        {$filas}
    </tbody>
</table>

{$pieSesion}
HTML;

// Pie: cierra <main>, escribe el footer y cierra el HTML
include_once __DIR__ . '/footer.php';

?>