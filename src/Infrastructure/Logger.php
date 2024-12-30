<?php

declare(strict_types=1);

namespace DiscrepanciasBackend\Infrastructure;

use Exception;

class Logger
{
    private $time_start;
    private $time_end;
    private $file_path;
    private $file_path_detalle;
    private $stdout;
    private $es_detalle;
    private static $instances = [];

    /**
     * Constructor de la clase Logger
     *
     * @param string $name Nombre base del archivo de log.
     * @param string $folder Carpeta a usar dentro de la ruta.
     * @param string $path Ruta opcional del archivo de log.
     */
    public function __construct(bool $es_detalle = false, string $name = "", string $folder = 'LARGO', string $path = null)
    {
        $this->time_start = microtime(true);
        $path = $path ?? ($_ENV['LOGGER_PATH'] ?? null);

        # Validar que la ruta esté definida y no esté vacía
        if (empty($path)) {
            throw new Exception("No se ha definido la ruta del Logger. Proporciona el parámetro 'path' o define la variable de entorno 'LOGGER_PATH'.");
        }

        # Construir la ruta base del archivo de log
        $basePath = rtrim($path, '/') . '/' . $folder . '/';

        # Crear directorios si no existen
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        # Asignar rutas de archivos de log
        $this->file_path = $basePath . $name . '.log';
        $this->file_path_detalle = $basePath . $name . '_detalle.log';

        # Abrir el flujo estándar de salida
        $this->stdout = fopen('php://stdout', 'w');
        if ($this->stdout === false) {
            throw new Exception("No se pudo abrir el flujo 'stdout'.");
        }

        $this->es_detalle = $es_detalle;
    }

    /**
     * Obtener una instancia de Logger única por nombre de archivo.
     *
     * @param string $name Nombre base del archivo de log.
     * @param string $folder Carpeta a usar dentro de la ruta.
     * @param string $path Ruta opcional del archivo de log.
     * @return Logger
     */
    public static function instance(bool $es_detalle = false, string $name = "", string $folder = 'LARGO', string $path = null)
    {
        if (empty($name)) {
            $name = basename($_SERVER['SCRIPT_FILENAME'], '.php');
        }
        $key = md5($es_detalle . $name . $folder . $path); # Generar clave única basada en la configuración
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($es_detalle, $name, $folder, $path);
        }
        return self::$instances[$key];
    }

    /**
     * Escribir un mensaje en el log.
     * @param string $message mensaje a escribir
     * @param int $level nivel de tabulación (0, 1, 2)
     * @return $this 
     */
    public function write(string $message, int $level = 0, string $tipo = "")
    {
        # Seleccionar la ruta del archivo según $es_detalle
        $file_path = $this->es_detalle ? $this->file_path_detalle : $this->file_path;

        # Aplicar color al mensaje
        $message_colored = $this->color($tipo, $message);

        # Control de niveles de tabulación (0, 1, 2)
        $tab = str_repeat("    ", min(2, max(0, $level)));

        # Mostrar por consola (no afecta a la respuesta de la api)
        if (!$this->es_detalle) {
            fwrite($this->stdout, "{$tab}{$message_colored}\n");
        }

        # Escribir el mensaje en el archivo de log
        file_put_contents($file_path, "{$tab}{$message_colored}\n", FILE_APPEND);
        return $this;
    }

    /**
     * Escribir un mensaje en el log.
     * @param string $message mensaje a escribir
     * @param int $level nivel de tabulación (0, 1, 2)
     * @return $this 
     */
    public function write_ts(string $message, int $level = 0, string $tipo = "")
    {
        # Seleccionar la ruta del archivo según $es_detalle
        $file_path = $this->es_detalle ? $this->file_path_detalle : $this->file_path;

        # Aplicar color al mensaje
        $message_colored = $this->color($tipo, $message);

        # Control de niveles de tabulación (0, 1, 2)
        $tab = str_repeat("    ", min(2, max(0, $level)));

        # Formato del mensaje con o sin timestamp
        $timestamp = date('d-m-Y H-i-s');

        # Mostrar por consola (no afecta a la respuesta de la api)
        if (!$this->es_detalle) {
            fwrite($this->stdout, "{$timestamp} {$tab}{$message_colored}\n");
        }

        # Escribir el mensaje en el archivo de log
        file_put_contents($file_path, "{$timestamp} {$tab}{$message_colored}\n", FILE_APPEND);
        return $this;
    }

    /**
     * Agregar salto de línea al log.
     * @return $this 
     */
    public function saltoLinea()
    {
        $this->write("");
        return $this;
    }

    /**
     * Agregar doble salto de línea al log.
     * @return $this 
     */
    public function dobleSaltoLinea()
    {
        $this->write("\n");
        return $this;
    }

    /**
     * Aplicar color al mensaje.
     * @param string $tipo 
     * @param string $message 
     */
    private function color(string $tipo, string $message)
    {
        switch ($tipo) {
            case "info":
                return "\033[34m{$message}\033[0m";
            case "warning":
                return "\033[33m{$message}\033[0m";
            case "error":
                return "\033[31m{$message}\033[0m";
            case "success":
                return "\033[32m{$message}\033[0m";
            default:
                return $message;
        }
    }

    /**
     * Destructor para cerrar recursos abiertos.
     */
    public function __destruct()
    {
        if (!$this->es_detalle) {
            $this->time_end = microtime(true);
            $tiempo_transcurrido = $this->time_end - $this->time_start;
            $minutos = floor($tiempo_transcurrido / 60);
            $segundos = $tiempo_transcurrido % 60;
            $this->saltoLinea()
                ->write("Tiempo de ejecución: $minutos min. y $segundos seg.", 0, "success")
                ->write("Logs guardados en:", 0, "success")
                ->write($this->file_path, 0, "success")
                ->write($this->file_path_detalle, 0, "success");
        }
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }
}
