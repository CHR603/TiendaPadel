<?php

// ARCHIVO TEMPORAL: sirve para entrar y salir mientras no existe login.php (fase 4).
// Se borra cuando se cree el login real
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\SessionService;
use services\UsersService;

// La sesión debe obtenerse antes de escribir nada
$session = SessionService::getInstance();

// Quién quiere entrar: viene por la URL (?quien=admin)
$quien = $_GET['quien'] ?? '';

try {
    $usuarios = new UsersService(Config::getInstance()->db);

    if ($quien === 'admin') {
        $session->login($usuarios->authenticate('admin', 'Admin1')); // Comprueba la contraseña y guarda la sesión
    } elseif ($quien === 'user') {
        $session->login($usuarios->authenticate('user', 'User1234'));
    } elseif ($quien === 'salir') {
        $session->logout();
    }
} catch (Throwable $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit; // Si falla, se queda aquí mostrando el error
}

// Vuelve al listado. exit evita que se siga ejecutando código después de la redirección
header('Location: index.php');
exit;

?>