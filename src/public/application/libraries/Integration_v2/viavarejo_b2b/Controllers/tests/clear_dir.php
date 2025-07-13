<?php

require_once "/var/www/html/conectala/application/libraries/FileDir.php";

$yesterday = time() - 60 * 60 *24;
$lastMonth = time() - 60 * 60 * 24 * 31;

$clear = new ClearDirectory();
$clear->removerDiretorios("/var/www/html/conectala/assets/files/products_via/tmp", $yesterday);
$clear->removerDiretorios("/var/www/html/conectala/assets/files/products_via/*/", $lastMonth);


class ClearDirectory
{
    public function removerDiretorios($path, $date)
    {
        if (strpos($path, '/*/') !== false) {
            $path = substr($path, 0, strrpos($path, '/*/'));
            $dir = opendir($path);
            if ($dir === false) return true;

            foreach (scandir($path) as $file) {
                if (in_array($file, ['.', '..'])) continue;
                if (is_dir("{$path}/{$file}")) {
                    $this->removerDiretorios("{$path}/{$file}", $date);
                }
            }
            return true;
            closedir($dir);
        }
        if (!file_exists($path)) {
            return true;
        }
        $dir = opendir($path);
        if ($dir === false) return true;
        foreach (scandir($path) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if (is_dir("{$path}/{$file}")) {
                $this->removerDiretorios("{$path}/{$file}", $date);
                if (FileDir::isDirEmpty("{$path}/{$file}")) {
                    rmdir("{$path}/{$file}");
                }
                continue;
            }
            if (filemtime("{$path}/{$file}") < $date) {
                unlink("{$path}/{$file}");
            }
        }
        closedir($dir);
        return FileDir::isDirEmpty($path);
    }
}