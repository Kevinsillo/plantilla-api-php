<?php
/**
 * Script para dar de alta usuarios en el fichero usuarios.json.
 * Solo válido cuando la autenticación se gestiona mediante JSON, no para base de datos.
 */

declare(strict_types=1);

$usuariosFile = __DIR__ . '/../files/usuarios.json';

// Leer el fichero usuarios.json
if (!file_exists($usuariosFile)) {
    die("El fichero usuarios.json no existe.\n");
}

$usuarios = json_decode(file_get_contents($usuariosFile), true);

// Solicitar datos del usuario
echo "Introduce el nombre de usuario: ";
$username = trim(fgets(STDIN));

if (isset($usuarios[$username])) {
    die("El usuario ya existe.\n");
}

echo "Introduce la contraseña: ";
$password = trim(fgets(STDIN));

echo "¿Es administrador? (s/n): ";
$isAdmin = strtolower(trim(fgets(STDIN))) === 's';

// Crear el nuevo usuario
$usuarios[$username] = [
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $isAdmin ? 'admin' : 'user',
];

// Guardar los cambios en usuarios.json
if (file_put_contents($usuariosFile, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "Usuario registrado con éxito.\n";
} else {
    echo "Error al guardar el usuario.\n";
}
