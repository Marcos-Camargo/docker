<?php


class FileDir
{

    public static function handleWithDirSeparator(string $dirPath): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirPath);
    }

    public static function createDir($pathDir, $permissions = 0775): bool
    {
        if (!file_exists($pathDir)) {
            return @mkdir($pathDir, $permissions, true);
        }
        return true;
    }

    public static function copyDir($origin, $destination, $ignoreDir = false)
    {
        if (!file_exists($origin)) return false;
        $dir = opendir($origin);
        if ($dir === false) return false;
        FileDir::createDir($destination);
        foreach (scandir($origin) as $file) {
            if (!in_array($file, ['.', '..'])) {
                if (is_dir("{$origin}/{$file}")) {
                    if ($ignoreDir) continue;
                    self::copyDir("{$origin}/{$file}", "{$destination}/{$file}");
                } else {
                    copy("{$origin}/{$file}", "{$destination}/{$file}");
                }
            }
        }
        closedir($dir);
        return true;
    }

    public static function getFiles($path)
    {
        if (!file_exists($path)) {
            return [];
        }
        $dir = opendir($path);
        if ($dir === false) return [];
        $filesFullPath = [];
        foreach (scandir($path) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if (is_dir("{$path}/{$file}")) {
                $filesFullPath['dirs'][] = self::getFiles("{$path}/{$file}");
                continue;
            }
            $filesFullPath['files'][] = "{$path}/{$file}";
        }
        closedir($dir);
        return $filesFullPath;
    }

    public static function clearDir($path): ?bool
    {
        if (!file_exists($path)) {
            return true;
        }
        $dir = opendir($path);
        if ($dir === false) return true;

        $dir_verify = trim($path); 
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13); 						  
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die; 
        }

        foreach (scandir($path) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if (is_dir("{$path}/{$file}")) {
                self::clearDir("{$path}/{$file}");
                if (self::isDirEmpty("{$path}/{$file}")) {
                    rmdir("{$path}/{$file}");
                }
                continue;
            }
            // log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' unlink '."{$path}/{$file}");
            unlink("{$path}/{$file}");
        }
        closedir($dir);
        return self::isDirEmpty($path);
    }

    public static function isDirEmpty($path): ?bool
    {
        if (!is_readable($path)) {
            return null;
        }
        return (count(scandir($path)) == 2);
    }
}