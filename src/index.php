<?php
require_once __DIR__ . '/../vendor/autoload.php';

use models\User;

try {
    // Usuario con dos roles
    $u = new User(1, 'admin', 'hash-de-prueba', 'Admin', 'Admin Admin', 'admin@prueba.net',
        '2026-11-04', '2026-11-04', false, ['USER', 'ADMIN']);

    echo "Usuario: " . $u->username . "<br>";
    echo "Email: " . $u->email . "<br>";
    echo "Roles: " . implode(', ', $u->roles) . "<br>";
    echo "¿Es admin? " . (in_array('ADMIN', $u->roles) ? 'Sí' : 'No') . "<br><br>";

    // Usuario sin roles: debe quedar con un array vacío
    $v = new User(2, 'user');
    echo "Roles de user: " . count($v->roles) . "<br><br>";

    // Modificar una propiedad (usa __set)
    $v->email = 'user@prueba.net';
    echo "Email nuevo: " . $v->email . "<br><br>";

    // Comprobar el hash real de 'admin' que está en el init.sql
    $hash = '$2y$10$6wgbcN4nV7YB1zTGeCwZL.uLkxaqX.zSFT2J.BRIwPOoBPFQRq2a2';
    echo "Admin1 correcta: " . (password_verify('Admin1', $hash) ? 'Sí' : 'No') . "<br>";
    echo "Otra contraseña: " . (password_verify('mala', $hash) ? 'Sí' : 'No');
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}