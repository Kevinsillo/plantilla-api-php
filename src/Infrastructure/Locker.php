<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

class Locker
{
    private $lock_path;
    private $lock_file;

    public function __construct()
    {
        // Configurar ruta y nombre del archivo
        $this->lock_path = $_ENV['LOCKER_PATH'];
        $this->lock_file = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.lock';
    }

    /**
     * Crear un archivo de bloqueo para evitar que se ejecute el script más de una vez.
     * @return void
     */
    public function crearBloqueo()
    {
        $lock_file_full_path = $this->lock_path . $this->lock_file;

        // Crear el archivo de bloqueo
        file_put_contents($lock_file_full_path, "Bloqueo activo: " . date('Y-m-d H:i:s'));
    }

    /**
     * Método para esperar hasta que un archivo de bloqueo sea eliminado.
     * @param string $lock_file 
     * @param int $max_time 
     * @return void
     */
    public function esperarBloqueo(string $lock_file, int $max_time = 1800)
    {
        $lock_file_full_path = $this->lock_path . $lock_file . '.lock';
        
        // Esperar hasta que el archivo de bloqueo sea eliminado o se alcance el tiempo máximo
        $transcurrido = 0;
        while (file_exists($lock_file_full_path) && $transcurrido < $max_time) {
            sleep(300); // Esperar 5 minutos
            $transcurrido += 300;
        }
    }

    /**
     * Eliminar el fichero de bloqueo.
     * @return void
     */
    public function eliminarBloqueo()
    {
        $lock_file_full_path = $this->lock_path . $this->lock_file;

        if (file_exists($lock_file_full_path)) {
            unlink($lock_file_full_path);
        }
    }

    /**
     * Destructor para asegurarse de que el bloqueo se elimina automáticamente.
     */
    public function __destruct()
    {
        $this->eliminarBloqueo();
    }
}