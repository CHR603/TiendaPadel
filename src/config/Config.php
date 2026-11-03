<?php

// Conexión prueba temporal

namespace config; // Coincide con la carpeta src/config/ (autoload PSR-4)


use Dotenv\Dotenv; // Librería que lee el archivo .env
use PDO;           // Clase de PHP para conectar con la base de datos


// Singleton: solo existe un objeto Config en toda la aplicación
class Config
{
    private static $instance; // Guarda la única instancia
    private $postgresDb;
    private $postgresUser;
    private $postgresPassword;
    private $postgresHost;
    private $postgresPort;
    private $db;              // Aquí se guarda la conexión PDO

    // CAMBIO: antes '/var/www/html/public/'. Aquí está mi .env (raíz del proyecto, sin public/)
    private $rootPath = '/var/www/html/';

    // CAMBIO: antes '/var/www/html/public/uploads/'. Mis imágenes van en src/uploads/
    private $uploadPath = '/var/www/html/src/uploads/';

    private $uploadUrl = 'http://localhost:8080/uploads/'; // URL pública de las imágenes

    // Constructor privado: impide hacer new Config() desde fuera
    private function __construct()
    {

        // Carga el archivo .env que está en rootPath
        $dotenv = Dotenv::createImmutable($this->rootPath);
        $dotenv->load();

        // Cargar las variables de entorno y almacenarlas en las propiedades.
        $this->postgresDb = getenv('POSTGRES_DB') ?? 'default_db';
        $this->postgresUser = getenv('POSTGRES_USER') ?? 'default_user';
        $this->postgresPassword = getenv('POSTGRES_PASSWORD') ?? 'default_password';
        $this->postgresHost = getenv('POSTGRES_HOST') ?? 'localhost';
        $this->postgresPort = getenv('POSTGRES_PORT') ?? '5432';

        // Abre la conexión con PostgreSQL
        $this->db = new PDO("pgsql:host={$this->postgresHost};port={$this->postgresPort};dbname={$this->postgresDb}", $this->postgresUser, $this->postgresPassword);
    }

    // Devuelve siempre el mismo objeto: lo crea solo la primera vez
    public static function getInstance(): Config
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }


    // Magic methos for get and set
    // __get permite leer propiedades privadas: $config->db
    public function __get($name)
    {
        return $this->$name;
    }

    // __set permite modificar propiedades privadas: $config->uploadUrl = '...'
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

}