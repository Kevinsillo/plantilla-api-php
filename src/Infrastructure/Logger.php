<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Exception;

class Logger
{
    private $time_start;
    private $time_end;
    private $file_path;
    private $file_path_detalle;
    private $stdout;
    private $es_detalle;
    private $timestamp;
    private static $instances = [];

    /**
     * Constructor de la clase.
     * @param array $opciones 
     * @throws Exception 
     */
    public function __construct(array $opciones = [])
    {
        # Validar que la ruta esté definida y no esté vacía
        if (empty($opciones["folder_path"])) {
            throw new Exception("No se ha definido la ruta del Logger. Proporciona el parámetro 'path' o define la variable de entorno 'LOGGER_PATH'.");
        }

        # Asignar valores por defecto
        $base_path = $opciones["folder_path"] . "/" . $opciones["subfolder"] . "/";
        $this->es_detalle = $opciones["es_detalle"];
        $this->file_path = $base_path . $opciones["name_log"] . '.log';
        $this->file_path_detalle = $base_path . $opciones["name_log"] . '_detalle.log';
        $this->time_start = microtime(true);
        $this->timestamp = $opciones["timestamp"];

        # Crear directorios si no existen
        if (!is_dir($base_path)) {
            if (!mkdir($base_path, 0777, true)) {
                throw new Exception("No se pudo crear el directorio de logs.");
            }
        }

        # Abrir el flujo estándar de salida
        $this->stdout = fopen('php://stdout', 'w');
        if ($this->stdout === false) {
            throw new Exception("No se pudo abrir el flujo 'stdout'.");
        }

        $this->saltoLinea();
    }

    /**
     * Obtener una instancia de Logger.
     *
     * @param array $opciones Opciones de configuración.
     * @return Logger Instancia de Logger configurada.
     */
    public static function instance(array $opciones = []): Logger
    {
        $es_detalle = self::obtenerOpcion($opciones, "es_detalle", false);
        $name_log = self::obtenerOpcion($opciones, "name_log", basename($_SERVER['SCRIPT_FILENAME'], '.php'));
        $folder_path = self::obtenerOpcion($opciones, "folder_path", $_ENV['LOGGER_PATH']);
        $subfolder_name = self::obtenerOpcion($opciones, "subfolder", 'LARGO');
        $timestamp = self::obtenerOpcion($opciones, "timestamp", true);

        $key = md5($es_detalle . $name_log . $folder_path . $subfolder_name, $timestamp); # Generar clave única basada en la configuración
        if (!isset(self::$instances[$key])) {
            $opciones = [
                "es_detalle" => $es_detalle,
                "name_log" => $name_log,
                "folder_path" => $folder_path,
                "subfolder" => $subfolder_name,
                "timestamp" => $timestamp
            ];
            self::$instances[$key] = new self($opciones);
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
     * Mostrar un mensaje en la misma línea.
     * @param string $message mensaje a escribir
     * @param int $level nivel de tabulación (0, 1, 2)
     * @return $this 
     */
    public function write_r(string $message, int $level = 0, string $tipo = "")
    {
        # Aplicar color al mensaje
        $message_colored = $this->color($tipo, $message);

        # Control de niveles de tabulación (0, 1, 2)
        $tab = str_repeat("    ", min(2, max(0, $level)));

        # Mostrar por consola (no afecta a la respuesta de la api)
        fwrite($this->stdout, "\r{$tab}{$message_colored}");

        return $this;
    }

    /**
     * Mostrar un array en el log.
     * @param array $array array a mostrar
     * @param int $level nivel de tabulación (0, 1, 2)
     * @return $this 
     */
    public function write_array(array $array, int $level = 0, string $tipo = "")
    {
        # Seleccionar la ruta del archivo según $es_detalle
        $file_path = $this->es_detalle ? $this->file_path_detalle : $this->file_path;

        # Aplicar color al mensaje
        $message_colored = $this->color($tipo, json_encode($array, JSON_PRETTY_PRINT));

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
            case "debug":
                return "\033[95m{$message}\033[0m";
            default:
                return $message;
        }
    }

    /**
     * Destructor para cerrar recursos abiertos.
     */
    public function __destruct()
    {
        if ($this->timestamp) {
            $this->time_end = microtime(true);
            $tiempo_transcurrido = $this->time_end - $this->time_start;
            $minutos = floor($tiempo_transcurrido / 60);
            $segundos = $tiempo_transcurrido % 60;
            $this->saltoLinea()
                ->write("Tiempo total de ejecución: $minutos min. y $segundos seg.", 0, "success")
                ->write("Logs guardados en:", 0, "success")
                ->write($this->file_path, 0, "success")
                ->write($this->file_path_detalle, 0, "success")
                ->dobleSaltoLinea();
        }
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }

    /**
     * Obtener una opcion de un array
     * @param array $opciones
     * @param mixed $clave 
     * @param mixed $default 
     * @return mixed 
     */
    private static function obtenerOpcion(array $opciones, mixed $clave, mixed $default)
    {
        return isset($opciones[$clave]) ? $opciones[$clave] : $default;
    }

    /**
     * Obtener el file_path
     * @return string 
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * Obtener el file_path_detalle
     * @return string 
     */
    public function getFilePathDetalle()
    {
        return $this->file_path_detalle;
    }
}
