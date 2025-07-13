<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('getFolderPath')) {
    function getFolderPath(string $folder, $endsWithSlash = false): string
    {
        $path = getcwd().DIRECTORY_SEPARATOR.$folder;
        if ($endsWithSlash){
            $path.= DIRECTORY_SEPARATOR;
        }
        return $path;
    }
}

if (!function_exists('readTempCsv')) {
    function readTempCsv(string $tempFile, int $limitation = 0, array $expectedHeaders = null, $separator = ";"): array
    {
        // Ler o conteúdo do arquivo
        $content = file_get_contents($tempFile);

        // Remover o BOM se presente
        if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Converter o conteúdo para UTF-8 se necessário
        $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));

        // Dividir o conteúdo em linhas, mantendo apenas linhas não vazias
        $lines = array_filter(array_map('trim', explode(PHP_EOL, $content)));

        // Processar as linhas para CSV
        $rows = array_map(function ($v) use ($separator) {
            return str_getcsv($v, $separator);
        }, $lines);

        // Obter o cabeçalho
        $header = array_shift($rows);
        $csv = [];

        // Combinar cada linha com o cabeçalho
        foreach ($rows as $row) {
            // Verificar se a linha tem o mesmo número de colunas que o cabeçalho
            if (count($row) === count($header)) {
                $csv[] = array_combine($header, $row);
            }
        }

        // Verificar o limite
        if ($limitation > 0 && count($csv) > $limitation) {
            throw new \Exception(lang('application_the_maximum_number_of_data_exceeded_limit_of') . ": $limitation");
        }

        // Verificar os cabeçalhos esperados
        if ($expectedHeaders) {
            foreach ($csv as $item) {
                foreach ($expectedHeaders as $expectedHeader) {
                    if (!isset($item[$expectedHeader])) {
                        throw new \Exception(lang('application_csv_file_not_correct_formated_please_download_sample'));
                    }
                }
            }
        }

        return $csv;
    }

}

if (!function_exists('readTempXls')) {
    function readTempXls(string $tempFile, array $expectedHeaders = null): array
    {

        //import lib excel
        require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
        $objPHPExcel = PHPExcel_IOFactory::load($tempFile);

        $sheet = $objPHPExcel->getActiveSheet();
        $headers = $sheet->toArray('A1');

        $header = array_shift($headers);
        $itens    = [];
        foreach($headers as $row) {
            $itens[] = array_combine($header, $row);
        }

        if ($expectedHeaders){
            foreach ($itens as $item){
                foreach ($expectedHeaders as $expectedHeader){
                    if (!isset($item[$expectedHeader])){
                        throw new \Exception(lang('application_csv_file_not_correct_formated_please_download_sample'));
                    }
                }
            }
        }
        return $itens;

    }
}

if (!function_exists('baseUrlPublic')) {
    function baseUrlPublic($url) {
        return str_replace(
            'http://',
            ENVIRONMENT === 'local' ? 'http://' : 'https://',
            str_replace('conectala.tec.br','conectala.com.br',
            base_url($url)));
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile(string $upload_path, string $allowed_types = 'csv|txt', $file_name = null)
    {
        if (is_null($file_name)) {
            $file_name = uniqid();
        }

        $config['upload_path']   = $upload_path;
        $config['file_name']     = $file_name;
        $config['allowed_types'] = $allowed_types;

        get_instance()->load->library('upload', $config);

        if (!get_instance()->upload->do_upload('product_upload')) {
            $error = get_instance()->upload->display_errors();
            get_instance()->data['upload_msg'] = $error;
            return false;
        } else {
            $data = array(
                'upload_data' => get_instance()->upload->data()
            );

            $pathinfo   = pathinfo($_FILES['product_upload']['name']);
            $type       = $pathinfo['extension'];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return $data ? $path : false;
        }
    }
}

if (!function_exists('getSourcePath')) {
    function getSourcePath(string $path = null)
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $source_path = substr($serverpath, 0, $pos);

        if (!is_null($path)) {
            return $source_path.$path;
        }

        return $source_path;
    }
}

if (!function_exists('pathCopy')) {
    function pathCopy($dir, $destination)
    {
        if ($destination[strlen($destination) - 1] == '/') {
            $destination = substr($destination, 0, -1);
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755);
        }

        $folder = opendir($dir);

        while ($item = readdir($folder)) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir("{$dir}/{$item}")) {
                pathCopy("{$dir}/{$item}", "{$destination}/{$item}");
            } else {
                copy("{$dir}/{$item}", "{$destination}/{$item}");
            }
        }
    }
}

if (!function_exists('checkIfDirExist')) {
    /**
     * @param string $dir
     */
    function checkIfDirExist(string $dir)
    {
        $dir_check = '';
        $arr_dir = explode('/', $dir);
        foreach ($arr_dir as $_dir) {
            // Por regra, sem tem '.' é um arquivo então nao tentar criar o repositório.
            if (strpos($_dir, '.') !== false || empty($_dir)) {
                continue;
            }

            $dir_check .= empty($dir_check) ? ($_dir == 'var' ? "/$_dir" : $_dir) : "/$_dir";
            if (!is_dir($dir_check)) {
                mkdir($dir_check);
            }
        }
    }
}

if (!function_exists('copyFileByUrl')) {
    /**
     * @param string $dir_to_save_with_file_name
     * @param string $url_to_copy
     * @return false|int
     */
    function copyFileByUrl(string $dir_to_save_with_file_name, string $url_to_copy)
    {
        checkIfDirExist($dir_to_save_with_file_name);
        return file_put_contents(getFolderPath($dir_to_save_with_file_name), file_get_contents($url_to_copy));
    }
}

if (!function_exists('copyFileByContentFile')) {
    /**
     * @param string $dir_to_save_with_file_name
     * @param string $content_to_copy
     * @param bool $return_public_link
     * @return false|int|string
     */
    function copyFileByContentFile(string $dir_to_save_with_file_name, string $content_to_copy, bool $return_public_link = false)
    {
        checkIfDirExist($dir_to_save_with_file_name);

        $result_save = file_put_contents(getFolderPath($dir_to_save_with_file_name), $content_to_copy);

        if ($return_public_link) {
            return publicUrl($dir_to_save_with_file_name);
        }

        return $result_save;
    }
}

if ( ! function_exists('write_file'))
{
	/**
	 * Write File
	 *
	 * Writes data to the file specified in the path.
	 * Creates a new file if non-existent.
	 *
	 * @param	string	$path	File path
	 * @param	string	$data	Data to write
	 * @param	string	$mode	fopen() mode (default: 'wb')
	 * @return	bool
	 */
	function write_file($path, $data, $mode = 'wb')
	{
		if ( ! $fp = @fopen($path, $mode))
		{
			return FALSE;
		}

        $stat = fstat($fp);
        $isNew  = ($stat['size'] === 0 && $stat['ctime'] === $stat['mtime']);

		flock($fp, LOCK_EX);

		for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result)
		{
			if (($result = fwrite($fp, substr($data, $written))) === FALSE)
			{
				break;
			}
		}

		flock($fp, LOCK_UN);
		fclose($fp);

        //Dando permissão para poder escrever pelo grupo
        if ($isNew) {
            chmod($path, 0664);
        }

		return is_int($result);
	}
}