<?php

namespace App\Libraries\Queue;

class JobFileWatcher
{
    private static $instance = null;
    private $hashes = []; // chave = expectedHash, valor = currentHash

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new JobFileWatcher();
        }
        return self::$instance;
    }

    public function check($filePath)
    {
        $currentHash = md5_file($filePath);

        // Primeira verificação desse hash esperado
        if (!isset($this->hashes[$filePath])) {
            $this->hashes[$filePath] = $currentHash;
            return true;
        }

        // Demais verificações: compara o hash salvo anteriormente com o atual
        if ($this->hashes[$filePath] !== $currentHash) {

            if (getenv('MODE_DEBUG')){
                echo '[' . date('Y-m-d H:i:s') . "] AVISO: Hash do arquivo {$filePath} foi alterado após a validação inicial." . PHP_EOL;
            }
            return false;
        }

        return true;
    }
}
