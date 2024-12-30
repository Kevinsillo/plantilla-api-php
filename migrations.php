<?php

require(__DIR__ . '/vendor/autoload.php');

session_start();

// Cargar el fichero de variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

$hostname = $_ENV['DATABASE_HOST'];
$database = $_ENV['DATABASE_NAME'];
$user = $_ENV['DATABASE_USER'];
$pass = $_ENV['DATABASE_PASS'];
$migrationsfolder = __DIR__ . '/migrations/';
$logs = $_SESSION["logs"];
$tables = [];

try {
    // Conexión y configuración
    $pdo = new PDO("mysql:host=$hostname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $logs[] = 'Conexión con la base de datos realizada correctamente';

    // Crear usuario y base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database;");
    $logs[] = "Se crea la base de datos '$database'";

    $pdo->exec("USE $database");

    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name varchar(255))');
    $logs[] = "Se crea la tabla 'migrations'";

    $logs[] = 'Buscando migraciones...';
    $stmt = $pdo->query('SELECT * FROM migrations');
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = $result;

    if (count($tables) > 0) {
        $logs[] = "Listo\n";
    } else {
        $logs[] = "No existen migraciones\n";
    }
} catch (PDOException $e) {
    $logs[] = "DB ERROR: " . $e->getMessage();
}

if (isset($_POST['clean_logs'])) {
    $logs = [];
    $_SESSION["logs"] = $logs;
    header("Refresh:0");
}

if (isset($_POST['delete_migrations'])) {
    $logs[] = "Eliminando las migraciones:";
    $stmt = $pdo->prepare("SHOW TABLES;");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tables as $ntable) {
        foreach ($ntable as $key => $value) {
            $pdo->exec("DROP TABLE $value;");
            $logs[] = "- Tabla $value eliminada";
        }
    }
    $logs[] = "Se han eliminado las migraciones\n";

    $_SESSION["logs"] = $logs;
    header("Refresh:0");
}

if (isset($_POST['run_migrations'])) {

    $logs[] = "Comenzando migraciones";

    foreach (new DirectoryIterator($migrationsfolder) as $file) {
        if ($file->getFilename() === '.' || $file->getFilename() === '..') {
            continue;
        }
        $migrations[] = $file->getFilename();
    }

    sort($migrations);

    foreach ($migrations as $migration) {
        $stmt = $pdo->prepare('SELECT * FROM migrations WHERE name=:name');
        $stmt->execute(['name' => $migration]);
        if (empty($stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $logs[] = "- $migration";
            try {
                $pdo->query(file_get_contents($migrationsfolder . $migration));
                $pdo->prepare('INSERT INTO migrations VALUES (:name)')
                    ->execute(['name' => $migration]);
            } catch (PDOException $e) {
                $logs[] = "DB ERROR: " . $e->getMessage();
                break;
            }
        }
    }

    $logs[] = "Migraciones terminadas\n";

    $_SESSION["logs"] = $logs;
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migraciones</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            line-height: 150%;
        }

        h1 {
            margin-bottom: 10px;
            padding: 0;
        }

        p {
            color: #777;
        }

        .titles {
            font-weight: bolder;
        }

        .seccion {
            margin-bottom: 20px;
        }

        .select {
            padding: 5px;
            font-size: 110%;
        }

        .primary {
            background-color: #378de5;
            border: 0;
            color: #ffffff;
            font-size: 15px;
            padding: 10px 24px;
            margin: 10px 0 10px 0;
            text-decoration: none;
        }

        .danger {
            background-color: #e64d43;
            border: 0;
            color: #ffffff;
            font-size: 15px;
            padding: 10px 24px;
            margin: 10px 0 10px 0;
            text-decoration: none;
        }

        .submit:hover {
            background-color: #2c6db2;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <form action="" method="POST">
        <h1>Migraciones</h1>
        <p class="parrafo">Herramienta para lanzar las migraciones que se encuentren en la carpeta "database/migrations" del proyecto.</p>
        <div class="seccion">
            <div class="titles">Acciones:</div>
            <input type="submit" name="run_migrations" class="primary" value="Lanzar migraciones" />
            <input type="submit" name="delete_migrations" class="danger" value="Eliminar migraciones" />
        </div>
        <div class="seccion">
            <div class="titles">Migraciones detectadas:</div>
            <?php
            if (isset($tables)) {
                if (count($tables) > 0) {
                    foreach ($tables as $nTable) {
                        echo '<span>- ' . $nTable['name'] . '</span><br>';
                    }
                } else {
                    echo '<span>Ninguna</span>';
                }
            }
            ?>
        </div>
        <div class="seccion">
            <input type="submit" name="clean_logs" value="Limpiar log" />
            <br>
            <textarea cols="100" rows="30"><?php
                                            if (count($logs)) {
                                                foreach ($logs as $line) echo $line . "\n";
                                            }
                                            ?></textarea>
        </div>
    </form>
</body>

</html>