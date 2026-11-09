<?php

// Autoload de Composer: obligatorio, sin él no funciona ninguna clase
require_once __DIR__ . '/../vendor/autoload.php';

use config\Config;
use services\SessionService;
use services\UsersService;

// La sesión debe obtenerse antes de escribir nada de HTML
$session = SessionService::getInstance();

// ---------- SI YA HA INICIADO SESIÓN ----------
// No tiene sentido enseñar el login a quien ya ha entrado: se le lleva al listado.
// header('Location: ...') redirige y exit corta la ejecución. Debe ir antes de cualquier HTML
if ($session->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// ---------- VALORES INICIALES ----------
$username = ''; // Lo escrito en el campo usuario (para no perderlo si hay un error)
$error = '';    // Mensaje de error, si lo hay

// ---------- PROCESAR EL FORMULARIO ----------
// Solo se ejecuta cuando se pulsa "Entrar" (el formulario usa POST).
// Al abrir la página normalmente (GET) se salta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lee los dos campos. trim() quita espacios sobrantes del usuario.
    // La contraseña NO se recorta: los espacios podrían formar parte de ella
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // VALIDACIÓN: los dos campos son obligatorios
    if ($username === '' || $password === '') {
        $error = 'Rellena todos los campos.';
    } else {
        // try/catch: authenticate lanza una excepción si el usuario o la contraseña no son correctos
        try {
            $usersService = new UsersService(Config::getInstance()->db);

            // Comprueba usuario y contraseña (password_verify). Devuelve el User con sus roles
            $user = $usersService->authenticate($username, $password);

            // Guarda en la sesión el usuario, sus roles y si es administrador
            $session->login($user);

            // Login correcto: va al listado. exit evita que se siga ejecutando código
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            // Fallo de base de datos: mensaje genérico, para no enseñar datos internos.
            // Este catch debe ir ANTES del de Exception, porque PDOException es una Exception
            $error = 'No se ha podido comprobar el usuario. Inténtalo más tarde.';
        } catch (Exception $e) {
            // Credenciales incorrectas: el mensaje lo escribe UsersService
            // ("Usuario o contraseña incorrectos"), el mismo si falla el usuario o la contraseña
            $error = $e->getMessage();
        }
    }
}

// ---------- PREPARAR LO QUE CAMBIA ----------
// Un heredoc no admite if ni foreach: todo lo variable se prepara antes en variables.
// htmlspecialchars convierte < y > en texto inofensivo (evita XSS)

// El usuario escrito se vuelve a mostrar en el campo. La contraseña nunca se vuelve a escribir
$vUsername = htmlspecialchars($username);

// Aviso de error: solo existe si hay un error
$alerta = '';
if ($error !== '') {
    $alerta = '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}

// ---------- ESCRIBIR LA PÁGINA ----------
include_once __DIR__ . '/header.php';

// Heredoc: todo hasta "HTML;" se escribe tal cual y {$variable} se sustituye por su valor.
// Escribe una tarjeta centrada con el título, el aviso de error y el formulario.
// method="POST" envía los datos en el cuerpo de la petición y no por la URL
// (importante: la contraseña no debe viajar en la URL).
// type="password" oculta lo que se escribe. autofocus pone el cursor en el campo usuario.
// El cierre "HTML;" va solo en su línea, sin espacios delante ni nada detrás
echo <<<HTML
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 text-success text-center mb-4">Iniciar sesión</h1>

                {$alerta}

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label" for="username">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" value="{$vUsername}" autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>

                    <button type="submit" class="btn btn-success w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
HTML;

include_once __DIR__ . '/footer.php';

?>