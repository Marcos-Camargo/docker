<?php
if (!defined('SaveImagem')) {
    define('SaveImagem', '');

    trait SaveImagem
    {

        public function saveImagem($imagens, $product_folder, $variant_folder = '')
        {
            $principal = "";
            $path = $this->PASTA_DE_IMAGEM . $product_folder . $variant_folder;
            if (!is_dir($path)) {
                @mkdir($path, 0775);
            } else {
                $this->clean_folder($path);
            }
            foreach ($imagens as $key => $imagen) {
                if (!$this->CI->uploadproducts->checkRemoteFile($imagen)) {
                    throw new TransformationException('Ao menos uma imagem nesta linha apresentou erro/At least one image in this line has an error');
                } else {
                    $callback_data = $this->CI->uploadproducts->sendImageForUrl($path, $imagen);
                    if (empty($principal) && $callback_data['success']) {
                        $principal = $path . $callback_data['path'];
                    }
                }
            }
            return $principal;
        }
        public function clean_folder($caminho)
        {
            $dir_verify = trim($caminho); 
            $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13); 						  
            if ($last_path_delete =='product_image') {
                log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
                die; 
            }

            $files = glob($caminho . '*');
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' unlink '.$file);
                    unlink($file); // delete file
                }
            }
        }
        private function createFolderIfNotExist($root_folder, &$item)
        {
            if (method_exists($this, 'getGUID')) {
                if (!isset($item['image'])) {
                    $item['image'] = $this->getGUID2(false);
                }
            }
            if (!is_dir($root_folder . "/" . $item['image'])) {
                @mkdir($root_folder . "/" . $item['image'], 0775);
            }
        }
        public function getGUID2($brackets = true)
        {
            if (function_exists('com_create_guid')) {
                return com_create_guid();
            } else {
                mt_srand((float) microtime() * 10000);
                $charid = strtoupper(md5(uniqid(rand(), true)));
                $hyphen = chr(45); // "-"
                $uuid = ($brackets ? chr(123) : "") // "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                    . ($brackets ? chr(125) : ""); // "}"
                return $uuid;
            }
        }
    }
}
