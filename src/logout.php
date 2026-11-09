<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use services\SessionService;

// Obtiene la sesión (Singleton). Debe ir antes de escribir nada de HTML
$session = SessionService::getInstance();

// Cierra la sesión: logout() vacía $_SESSION y llama a session_destroy().
// Se borran todos los datos del usuario: nombre, roles, si es admin, visitas...
$session->logout();

// Redirige al listado, que ahora se muestra como "Invitado".
// exit corta la ejecución para que no se ejecute nada más
header('Location: index.php');
exit;

?>