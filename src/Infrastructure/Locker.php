<?php

declare(strict_types=1);

namespace Backend\Infrastructure;

class Locker
{
    private $lock_path;
    private $lock_file;

    public function __construct(string $path)
    {
        $this->lock_path = $path ?: $_ENV['LOCKER_PATH'];
        $this->lock_file = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.lock';
        if (!isset($this->lock_path)) {
            throw new \Exception('The path parameter or the LOCKER_PATH environment variable are not defined.');
        }
    }

    /**
     * Create a lock file to prevent the script from running more than once.
     * @return void
     */
    public function createLock()
    {
        $lock_file_full_path = $this->lock_path . $this->lock_file;
        file_put_contents($lock_file_full_path, "Locked at: " . date('Y-m-d H:i:s'));
    }

    /**
     * Method to wait until a lock file is deleted.
     * @param string $lock_file 
     * @param int $max_time 
     * @return void
     */
    public function awaitLock(string $lock_file, int $max_time = 1800)
    {
        $lock_file_full_path = $this->lock_path . $lock_file . '.lock';
        # Wait until the lock file is deleted or the maximum time is reached
        $transcurrido = 0;
        while (file_exists($lock_file_full_path) && $transcurrido < $max_time) {
            sleep(300); # Wait 5 minutes
            $transcurrido += 300;
        }
    }

    /**
     * Method to delete a lock file.
     * @return void
     */
    public function removeLock()
    {
        $lock_file_full_path = $this->lock_path . $this->lock_file;
        if (file_exists($lock_file_full_path)) {
            unlink($lock_file_full_path);
        }
    }

    /**
     * Destructor method to remove the lock file when the object is destroyed.
     */
    public function __destruct()
    {
        $this->removeLock();
    }
}
