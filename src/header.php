<?php

// Carga el autoload de Composer (para poder usar SessionService)
require_once __DIR__ . '/../vendor/autoload.php';

use services\SessionService;

// Obtiene la sesión (Singleton). Debe ir ANTES de escribir nada de HTML
$session = SessionService::getInstance();

// Un heredoc no puede tener if dentro, así que las partes que cambian
// se preparan antes en variables y luego se insertan en el HTML

// Nombre del usuario. htmlspecialchars evita que se cuele HTML (XSS)
$nombre = htmlspecialchars($session->getUsername());

// El enlace "Crear producto" solo existe si el usuario es administrador
$enlaceCrear = '';
if ($session->isAdmin()) {
    $enlaceCrear = '<li class="nav-item"><a class="nav-link" href="create.php">Crear producto</a></li>';
}

// El botón es Logout si hay sesión iniciada y Login si no la hay
if ($session->isLoggedIn()) {
    $botonSesion = '<a class="btn btn-outline-light" href="logout.php">Logout</a>';
} else {
    $botonSesion = '<a class="btn btn-outline-light" href="login.php">Login</a>';
}

// echo <<<HTML ... HTML, escribe todo el bloque tal cual.
// Las variables se insertan con {$variable}
echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tienda de pádel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">Tienda de pádel</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menu" aria-controls="menu" aria-expanded="false"
                aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Productos</a></li>
                {$enlaceCrear}
            </ul>

            <span class="navbar-text me-3">{$nombre}</span>
            {$botonSesion}
        </div>
    </div>
</nav>

<main class="container mt-4">
HTML;

?>