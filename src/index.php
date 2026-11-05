<?php
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\SessionService;
use services\UsersService;

// IMPORTANTE: antes de cualquier echo
$session = SessionService::getInstance();

try {
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'login') {
        // Autentica con UsersService y guarda el resultado en la sesión
        $user = (new UsersService(Config::getInstance()->db))->authenticate('admin', 'Admin1');
        $session->login($user);
    } elseif ($accion === 'logout') {
        $session->logout();
    }

    echo "Usuario: " . $session->getUsername() . "<br>";
    echo "¿Ha iniciado sesión? " . ($session->isLoggedIn() ? 'Sí' : 'No') . "<br>";
    echo "¿Es admin? " . ($session->isAdmin() ? 'Sí' : 'No') . "<br>";
    echo "Último login: " . ($session->getLastLogin() ?? 'ninguno') . "<br>";
    echo "Visitas: " . $session->getVisitas() . "<br><br>";

    echo '<a href="?accion=login">Login</a> | <a href="?accion=logout">Logout</a> | <a href="/">Recargar</a>';
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}

?>