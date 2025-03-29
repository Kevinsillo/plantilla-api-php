<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

use Exception;

class Logger
{
    private float $time_start;
    private float $time_end;
    private string $file_path;
    private string $file_path_detalle;
    private $stdout;
    private bool $is_detail;
    private static $instances = [];

    /**
     * Logger constructor
     * @param array $options 
     * @throws Exception 
     */
    public function __construct(array $options = [])
    {
        # Validate options
        if (empty($options["folder_path"])) {
            throw new Exception("The Logger path is not defined. Provide the 'path' parameter or define the 'LOGGER_PATH' environment variable.");
        }

        # Set default values
        $base_path = $options["folder_path"] . "/" . $options["subfolder"] . "/";
        $this->is_detail = $options["is_detail"];
        $this->file_path = $base_path . $options["name_log"] . '.log';
        $this->file_path_detalle = $base_path . $options["name_log"] . '_detalle.log';
        $this->time_start = microtime(true);

        # Create the log directory if it doesn't exist
        if (!is_dir($base_path)) {
            if (!mkdir($base_path, 0777, true)) {
                throw new Exception("No se pudo crear el directorio de logs.");
            }
        }

        # Create the log files if they don't exist
        $this->stdout = fopen('php://stdout', 'w');
        if ($this->stdout === false) {
            throw new Exception("No se pudo abrir el flujo 'stdout'.");
        }

        $this->lineBreak();
    }

    /**
     * Get an instance of the Logger
     *
     * @param array $options Options for the logger
     * @return Logger Instance of Logger
     */
    public static function instance(array $options = []): Logger
    {
        $is_detail = self::getOption($options, "is_detail", false);
        $name_log = self::getOption($options, "name_log", basename($_SERVER['SCRIPT_FILENAME'], '.php'));
        $folder_path = self::getOption($options, "folder_path", $_ENV['LOGGER_PATH']);
        $subfolder_name = self::getOption($options, "subfolder", 'LARGO');
        $use_timestamp = self::getOption($options, "use_timestamp", true);

        $key = md5($is_detail . $name_log . $folder_path . $subfolder_name, $use_timestamp); # Generar clave única basada en la configuración
        if (!isset(self::$instances[$key])) {
            $options = [
                "is_detail" => $is_detail,
                "name_log" => $name_log,
                "folder_path" => $folder_path,
                "subfolder" => $subfolder_name,
                "use_timestamp" => $use_timestamp
            ];
            self::$instances[$key] = new self($options);
        }
        return self::$instances[$key];
    }

    /**
     * Write a message to the log.
     * @param string $message message to write
     * @param int $level tab level (0, 1, 2)
     * @return self
     */
    public function write(string $message, int $level = 0, string $tipo = "")
    {
        $file_path = $this->is_detail ? $this->file_path_detalle : $this->file_path;
        $message_colored = $this->color($tipo, $message);
        $tab = str_repeat("    ", min(2, max(0, $level)));
        if (!$this->is_detail) {
            fwrite($this->stdout, "{$tab}{$message_colored}\n");
        }
        file_put_contents($file_path, "{$tab}{$message_colored}\n", FILE_APPEND);
        return $this;
    }

    /**
     * Write a message with timestamp to the log.
     * @param string $message message to write
     * @param int $level tab level (0, 1, 2)
     * @return self
     */
    public function write_ts(string $message, int $level = 0, string $tipo = "")
    {
        $file_path = $this->is_detail ? $this->file_path_detalle : $this->file_path;
        $message_colored = $this->color($tipo, $message);
        $tab = str_repeat("    ", min(2, max(0, $level)));
        $timestamp = date('c');
        if (!$this->is_detail) {
            fwrite($this->stdout, "{$timestamp} {$tab}{$message_colored}\n");
        }
        file_put_contents($file_path, "{$timestamp} {$tab}{$message_colored}\n", FILE_APPEND);
        return $this;
    }

    /**
     * Write an array to the log.
     * @param array $array array to write
     * @param int $level tab level (0, 1, 2)
     * @return self
     */
    public function write_array(array $array, int $level = 0, string $tipo = "")
    {
        $file_path = $this->is_detail ? $this->file_path_detalle : $this->file_path;
        $message_colored = $this->color($tipo, json_encode($array, JSON_PRETTY_PRINT));
        $tab = str_repeat("    ", min(2, max(0, $level)));
        if (!$this->is_detail) {
            fwrite($this->stdout, "{$tab}{$message_colored}\n");
        }
        file_put_contents($file_path, "{$tab}{$message_colored}\n", FILE_APPEND);
        return $this;
    }

    /**
     * Add a line break to the log.
     * @return self 
     */
    public function lineBreak()
    {
        $this->write("");
        return $this;
    }

    /**
     * Add a double line break to the log.
     * @return self 
     */
    public function doubleLineBreak()
    {
        $this->write("\n");
        return $this;
    }

    /**
     * Add a colored message to the log.
     * @param string $type 
     * @param string $message 
     */
    private function color(string $type, string $message)
    {
        switch ($type) {
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
        $this->time_end = microtime(true);
        $tiempo_transcurrido = $this->time_end - $this->time_start;
        $minutos = floor($tiempo_transcurrido / 60);
        $segundos = $tiempo_transcurrido % 60;
        $this->lineBreak()
            ->write("Tiempo total de ejecución: $minutos min. y $segundos seg.", 0, "success")
            ->write("Logs guardados en:", 0, "success")
            ->write($this->file_path, 0, "success")
            ->write($this->file_path_detalle, 0, "success")
            ->doubleLineBreak();
        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }

    /**
     * Obtener una opcion de un array
     * @param array $options
     * @param mixed $key 
     * @param mixed $default 
     * @return mixed 
     */
    private static function getOption(array $options, mixed $key, mixed $default)
    {
        return isset($options[$key]) ? $options[$key] : $default;
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
