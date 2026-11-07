<?php
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\SessionService;
use services\UsersService;

$session = SessionService::getInstance();
$accion = $_GET['accion'] ?? '';
$error = '';

// Esto va ANTES de header.php: después de escribir HTML ya no se puede cambiar la sesión
try {
    $usuarios = new UsersService(Config::getInstance()->db);
    if ($accion === 'login-admin') {
        $session->login($usuarios->authenticate('admin', 'Admin1'));
    } elseif ($accion === 'login-user') {
        $session->login($usuarios->authenticate('user', 'User1234'));
    } elseif ($accion === 'logout') {
        $session->logout();
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

include_once __DIR__ . '/header.php';

echo '<h1>Prueba de header y footer</h1>';
if ($error !== '') {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}
echo '<p><a href="?accion=login-admin">Entrar como admin</a> | ';
echo '<a href="?accion=login-user">Entrar como user</a> | ';
echo '<a href="?accion=logout">Salir</a></p>';

include_once __DIR__ . '/footer.php';

?>