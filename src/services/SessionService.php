<?php

// Esta clase está en la carpeta src/services/ (el namespace debe coincidir con la carpeta)
namespace services;

use models\User; // Modelo del usuario que inicia sesión (lo recibe el método login)

/**
 * Class SessionService
 * @package services
 * Esta clase se encarga de gestionar la sesión de usuario
 */
// Patrón Singleton: solo existe UN objeto SessionService en cada petición.
// Así session_start() se ejecuta una sola vez y todos los archivos comparten la misma sesión.
class SessionService
{
    // Aquí se guarda la única instancia. Empieza en null porque todavía no se ha creado
    private static ?SessionService $instance = null;

    // Segundos de inactividad tras los cuales la sesión caduca (3600 segundos = 1 hora)
    private int $expireAfterSeconds = 3600;

    // Constructor PRIVADO: nadie puede hacer "new SessionService()" desde fuera.
    // Solo se ejecuta una vez, la primera vez que se llama a getInstance()
    private function __construct()
    {
        // session_status() dice si la sesión ya está iniciada.
        // PHP_SESSION_NONE significa "todavía no hay sesión": solo entonces la arrancamos.
        // session_start() crea la sesión (o recupera la que ya existe gracias a la cookie del navegador)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // CADUCIDAD: comprobamos si ya había una "última actividad" guardada
        // y si han pasado más segundos de los permitidos desde entonces.
        // time() devuelve la hora actual en segundos, así que time() - lastActivity
        // son los segundos que lleva el usuario sin hacer nada.
        if (isset($_SESSION['lastActivity'])
            && time() - $_SESSION['lastActivity'] > $this->expireAfterSeconds) {
            $this->logout();   // Cierra la sesión caducada (vacía los datos y la destruye)
            session_start();   // Arranca una sesión nueva y vacía
        }

        // Guarda la hora actual como "última actividad".
        // Se actualiza en cada petición, por eso la caducidad cuenta desde la última vez que hizo algo
        $_SESSION['lastActivity'] = time();

        // CONTROL DE VISITAS: si el usuario ha iniciado sesión, suma una visita más.
        // ($_SESSION['visitas'] ?? 0) significa "el valor guardado, o 0 si todavía no existe"
        if ($this->isLoggedIn()) {
            $_SESSION['visitas'] = ($_SESSION['visitas'] ?? 0) + 1;
        }
    }

    // Método estático que da acceso al único objeto.
    // "??=" significa: si $instance es null, créalo; si ya existe, no hagas nada.
    // En los dos casos devuelve el mismo objeto. Se usa así: SessionService::getInstance()
    public static function getInstance(): SessionService
    {
        return self::$instance ??= new SessionService(); //Si es null, créalo.” Un Singleton en una línea.
    }

    // LOGIN: guarda los datos del usuario en la sesión.
    // Se llama después de que UsersService haya comprobado usuario y contraseña
    public function login(User $user): void
    {
        $_SESSION['loggedIn'] = true;                            // Marca que hay un usuario con sesión iniciada
        $_SESSION['username'] = $user->username;                 // Guarda su nombre de usuario
        $_SESSION['roles'] = $user->roles;                       // Guarda su lista de roles, por ejemplo ['USER', 'ADMIN']
        $_SESSION['isAdmin'] = in_array('ADMIN', $user->roles);  // in_array busca 'ADMIN' en el array: true o false
        $_SESSION['lastLogin'] = date('d/m/Y H:i:s');            // Fecha y hora del login, por ejemplo 05/11/2026 10:30:00
        $_SESSION['visitas'] = 1;                                // Es la primera visita de esta sesión
    }

    // ¿Ha iniciado sesión el usuario?
    // empty() devuelve true si la clave no existe, es false, 0 o está vacía.
    // Con "!" lo invertimos: devuelve true solo si loggedIn existe y vale true
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['loggedIn']);
    }

    // ¿Es administrador? Se usará en create.php, update.php y delete.php para proteger esas páginas
    public function isAdmin(): bool
    {
        return !empty($_SESSION['isAdmin']);
    }

    // Devuelve el nombre del usuario, o "Invitado" si no ha iniciado sesión.
    // "??" devuelve el valor de la izquierda si existe y no es null; si no, el de la derecha
    public function getUsername(): string
    {
        return $_SESSION['username'] ?? 'Invitado';
    }

    // Devuelve la fecha y hora del último login, o null si no ha iniciado sesión
    public function getLastLogin(): ?string
    {
        return $_SESSION['lastLogin'] ?? null;
    }

    // Devuelve el número de visitas durante esta sesión (0 si no hay ninguna)
    public function getVisitas(): int
    {
        return $_SESSION['visitas'] ?? 0;
    }

    // LOGOUT: cierra la sesión por completo
    public function logout(): void
    {
        $_SESSION = [];     // Vacía todos los datos guardados en la sesión
        session_destroy();  // Elimina la sesión en el servidor
    }
}

?>