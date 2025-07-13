<?php

use GuzzleHttp\Exception\GuzzleException;
use Intervention\Image\ImageManagerStatic as Image;
use GuzzleHttp\Client;

require 'system/libraries/Vendor/autoload.php';

/**
 * @property CI_Loader $load
 * @property Model_settings $model_settings
 * @property Bucket $bucket
 */

class UploadProducts
{
    /**
     * @var Client Cliente of GuzzleHttp
     */
    public $client;

    const PRODUCT_IMAGE_FOLDER = 'assets/images/product_image';

    /**
     * Instantiate a new UploadProducts instance.
     */
    public function __construct()
    {
        $this->client = new Client([
            'verify' => false // no verify ssl
        ]);
        $this->load->library('Bucket');

    }

    /**
     * Método mágico para utilização do CI_Controller
     *
     * @param   string  $var    Propriedade para consulta
     * @return  mixed           Objeto da propriedade
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    private function getDimenssionImage($width, $height, $width_orig, $height_orig): array
    {
        $resize = false;
        // Verifica limites de 800x800 a 1200x1200
        if ($width_orig < 800 || $height_orig < 800) {
            $resize = 'min';
        } elseif ($width_orig > 1200 || $height_orig > 1200) {
            $resize = 'max';
        }

        // Precisa redimensionar
        if ($resize !== false) {
            // largura maior que altura
            if ($width > $height) {
                if ($resize == "min") {
                    $width = (800 / $height) * $width;
                    $height = 800;
                }
                else if ($resize == "max") {
                    $height = (1200 / $width) * $height;
                    $width = 1200;
                }
            }
            // altura maior que largura
            elseif ($height > $width) {
                if ($resize == "min") {
                    $height = (800 / $width) * $height;
                    $width = 800;
                }
                else if ($resize == "max") {
                    $width = (1200 / $height) * $width;
                    $height = 1200;
                }
            } else {
                $width = $resize == "min" ? 800 : 1200;
                $height = $resize == "min" ? 800 : 1200;
            }

            //Caso não consiga redimensionar propocional entre 800x800 e 1200x1200, vai ser preciso distorcer a imagem
            if ($width < 800) {
                $width  = 800;
            }
            if ($height < 800) {
                $height = 800;
            }
            if ($width > 1200) {
                $width  = 1200;
            }
            if ($height > 1200) {
                $height = 1200;
            }
        }

        return [
            'width'         => $width,
            'height'        => $height,
            'width_orig'    => $width_orig,
            'height_orig'   => $height_orig
        ];
    }

    /**
     * @throws Exception
     */
    public function imageWebp(string $fileUrl, string $caminho): string
    {
        try {
            list($width_orig, $height_orig) = getimagesize($fileUrl);
            $fileSize = strlen(file_get_contents($fileUrl));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $width      = $width_orig;
        $height     = $height_orig;
        $nameImage  = microtime(true) * 10000; // Define novo nome para a imagem

        $dimenssion  = $this->getDimenssionImage($width, $height, $width_orig, $height_orig);
        $width       = $dimenssion['width'];
        $height      = $dimenssion['height'];
        $width_orig  = $dimenssion['width_orig'];
        $height_orig = $dimenssion['height_orig'];

        $quality = 100;
        if ($fileSize> 700000) { // se a imagem é grande, abaixo a qualidade dela que diminiu bastente o tamanho
            $quality = 75;
        }

        try {
            $novaimagem = imagecreatetruecolor($width, $height);

            $origem = imagecreatefromwebp ($fileUrl);
            imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
            imagejpeg($novaimagem, $caminho . $nameImage . '.jpg',$quality);

            imagedestroy($novaimagem);
            imagedestroy($origem);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $nameImage;
    }

    /**
     * @param string    $caminho    caminho completo da pasta onde salvará a imagem ( assets/images/product_image/ABCDEF-1234567890-FEDCBA/ )
     * @param string    $fileUrl    url da imagem para download
     * @return array
     */
    public function sendImageForUrl(string $caminho, string $fileUrl, bool $application = false): array
    {
        $checkWebp = true;
        // timeout de 3min.
        set_time_limit(180);
        
        $fileUrl = str_replace(' ', '%20', $fileUrl);

        $parseUrl = parse_url($fileUrl);
        $imageFile = $fileUrl;
        if (
            !$application &&
            (
                $parseUrl['host'] === 'imgs.via.com.br' ||
                in_array(str_replace('www.', '', $parseUrl['host']), [
                    'pontofrio-imagens.com.br', 'casasbahia-imagens.com.br', 'extra-imagens.com.br'
                ])
            )
        ) {
            try {
                $checkWebp = false;
                $imageFile = $this->getImageViaVarejo($fileUrl);
            } catch (Exception $exception) {
                return array('success' => false, 'data' => $exception->getMessage());
            }
        } elseif (!$application) {
            $fileUrlSalvo = $fileUrl;
            if (!array_key_exists('query', $parseUrl) || empty($parseUrl['query'])) {
                $fileUrl = str_replace("https://", "http://", $fileUrl);

                if (!$this->checkRemoteFile($fileUrl)) {
                    $fileUrl = $fileUrlSalvo;
                }

                $imageFile = $fileUrl;
            }

            if (!$this->checkRemoteFile($fileUrl)) {
                try {
                    $imageFile = $this->getImageViaVarejo($fileUrl);
                } catch (Exception $exception) {
                    return array('success' => false, 'data' => $exception->getMessage());
                }
            }

            if (array_key_exists('query', $parseUrl) && !empty($parseUrl['query'])) {
                try {
                    $fileUrl = mb_convert_encoding($fileUrl, 'HTML-ENTITIES', "UTF-8");
                    $imageFile = file_get_contents($fileUrl);
                } catch (Exception $exception) {
                    //return array('success' => false, 'data' => $exception->getMessage());
                }

                if (!$imageFile) {
                    try {
                        $imageFile = $this->getImageBling($fileUrl); // BUGS-2892
                    } catch (Exception $exception) {
                        return array('success' => false, 'data' => $exception->getMessage());
                    }
                }
            }
        }

        $mineImage = null;
        try {
            if ($checkWebp) {
                set_error_handler('customErrorUploadImage', E_ALL ^ E_NOTICE);
                $mineImage = getimagesize($fileUrl)['mime'];
            }
        } catch (Exception | Error $exception) {}

        if ($mineImage === 'image/webp') {
            try {
                $nameImage = $this->imageWebp($fileUrl, $caminho);
            } catch (Exception $exception) {
                return array('success' => false, 'data' => $exception->getMessage());
            }

            return array('success' => true, 'path' => "$nameImage.jpg");
        }

        // tentar baixar a imagem três vezes
        // https://stackoverflow.com/a/52076483
        $flag = true;
        $try = 1;
        $messageErrorMakeImage = '';
        while ($flag && $try <= 3) {
            try {
                $image = Image::make($imageFile);
                //Image migrated successfully
                $flag = false;
            } catch (Exception $e) {
                $messageErrorMakeImage = $e->getMessage();
                //not throwing  error when exception occurs
            }
            $try++;
        }

        // Falhou, retornar erro para quem chamou.
        if ($flag) {
            return array('success' => false, 'data' => "Não foi possível acessar a imagem ($fileUrl).$messageErrorMakeImage");
        }

        $width_orig     = $image->getWidth();
        $height_orig    = $image->getHeight();
        $width          = $width_orig;
        $height         = $height_orig;
        $nameImage      = microtime(true) * 10000; // Define novo nome para a imagem.

        try {
            $this->load->model('model_settings');
            $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
            if ($product_image_rules) {
                $exp_product_image_rules = explode(';', $product_image_rules);
                if (count($exp_product_image_rules) === 2) {
                    $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                    $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                    if (
                        $width_orig < $dimenssion_min_validate ||
                        $width_orig > $dimenssion_max_validate ||
                        $height_orig < $dimenssion_min_validate ||
                        $height_orig > $dimenssion_max_validate
                    ) {
                        return array('success' => false, 'data' => "A dimensão da imagem deve ser entre {$dimenssion_min_validate}px e {$dimenssion_max_validate}px."." Imagem: {$fileUrl}");
                    }
                }
            } else {
                $dimenssion  = $this->getDimenssionImage($width, $height, $width_orig, $height_orig);
                $width       = $dimenssion['width'];
                $height      = $dimenssion['height'];

                $image = $image->resize($width, $height);
            }

            Image::canvas($image->width(), $image->height(), 'fff')
                ->insert($image)
                ->save("$caminho/$nameImage.jpg", 100, 'jpg');

        } catch (Exception $e) {
            return array('success' => false, 'data' => $e->getMessage());
        }

        return array('success' => true, 'path' => "$nameImage.jpg");
    }

    public function sendImageForBucket(string $caminho, string $fileUrl, bool $application = false): array
    {
        $checkWebp = true;
        // timeout de 3min.
        set_time_limit(180);
        
        $fileUrl = str_replace(' ', '%20', $fileUrl);

        $parseUrl = parse_url($fileUrl);
        $imageFile = $fileUrl;
        if (
            !$application &&
            (
                $parseUrl['host'] === 'imgs.via.com.br' ||
                in_array(str_replace('www.', '', $parseUrl['host']), [
                    'pontofrio-imagens.com.br', 'casasbahia-imagens.com.br', 'extra-imagens.com.br'
                ])
            )
        ) {
            try {
                $checkWebp = false;
                $imageFile = $this->getImageViaVarejo($fileUrl);
            } catch (Exception $exception) {
                return array('success' => false, 'data' => $exception->getMessage());
            }
        } elseif (!$application) {
            $fileUrlSalvo = $fileUrl;
            if (!array_key_exists('query', $parseUrl) || empty($parseUrl['query'])) {
                $fileUrl = str_replace("https://", "http://", $fileUrl);

                if (!$this->checkRemoteFile($fileUrl)) {
                    $fileUrl = $fileUrlSalvo;
                }

                $imageFile = $fileUrl;
            }

            if (!$this->checkRemoteFile($fileUrl)) {
                try {
                    $imageFile = $this->getImageViaVarejo($fileUrl);
                } catch (Exception $exception) {
                    return array('success' => false, 'data' => $exception->getMessage());
                }
            }

            if (array_key_exists('query', $parseUrl) && !empty($parseUrl['query'])) {
                try {
                    $fileUrl = mb_convert_encoding($fileUrl, 'HTML-ENTITIES', "UTF-8");
                    $imageFile = file_get_contents($fileUrl);
                } catch (Exception $exception) {
                    //return array('success' => false, 'data' => $exception->getMessage());
                }

                if (!$imageFile) {
                    try {
                        $imageFile = $this->getImageBling($fileUrl); // BUGS-2892
                    } catch (Exception $exception) {
                        return array('success' => false, 'data' => $exception->getMessage());
                    }
                }
            }
        }

        $mineImage = null;
        try {
            if ($checkWebp) {
                $mineImage = getimagesize($fileUrl)['mime'];
            }
        } catch (Exception | Error $exception) {}

        if ($mineImage === 'image/webp') {
            try {
                $nameImage = $this->imageWebp($fileUrl, $caminho);
            } catch (Exception $exception) {
                return array('success' => false, 'data' => $exception->getMessage());
            }

            return array('success' => true, 'path' => "$nameImage.jpg");
        }

        // tentar baixar a imagem três vezes
        // https://stackoverflow.com/a/52076483
        $flag = true;
        $try = 1;
        $messageErrorMakeImage = '';
        while ($flag && $try <= 3) {
            try {
                $image = Image::make($imageFile);
                //Image migrated successfully
                $flag = false;
            } catch (Exception $e) {
                $messageErrorMakeImage = $e->getMessage();
                //not throwing  error when exception occurs
            }
            $try++;
        }

        // Falhou, retornar erro para quem chamou.
        if ($flag) {
            return array('success' => false, 'data' => "Não foi possível acessar a imagem ($fileUrl).$messageErrorMakeImage");
        }

        $width_orig     = $image->getWidth();
        $height_orig    = $image->getHeight();
        $width          = $width_orig;
        $height         = $height_orig;
        $nameImage      = microtime(true) * 10000; // Define novo nome para a imagem.

        try {
            $this->load->model('model_settings');
            $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
            if ($product_image_rules) {
                $exp_product_image_rules = explode(';', $product_image_rules);
                if (count($exp_product_image_rules) === 2) {
                    $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                    $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                    if (
                        $width_orig < $dimenssion_min_validate ||
                        $width_orig > $dimenssion_max_validate ||
                        $height_orig < $dimenssion_min_validate ||
                        $height_orig > $dimenssion_max_validate
                    ) {
                        return array('success' => false, 'data' => "A dimensão da imagem deve ser entre {$dimenssion_min_validate}px e {$dimenssion_max_validate}px."." Imagem: {$fileUrl}");
                    }
                }
            } else {
                $dimenssion  = $this->getDimenssionImage($width, $height, $width_orig, $height_orig);
                $width       = $dimenssion['width'];
                $height      = $dimenssion['height'];

                $image = $image->resize($width, $height);
            }

            // Converte a imagem para Stream e envia para o Bucket.
            $image = Image::canvas($image->width(), $image->height(), 'fff')
                ->insert($image)
                ->stream('jpg', 100);

            // Garante que o caminho termine em /.
            if(substr($caminho,-1)!='/'){
                $caminho.='/';
            }
            
            $bucketImage = $this->bucket->sendFileToObjectStorage($image, $caminho.$nameImage.".jpg");       
            
            // Verifica se a inserção foi sucedida.
            if(!$bucketImage['success']){
                throw new Exception($bucketImage['url']);
            }
        } catch (Exception $e) {
            return array('success' => false, 'data' => $e->getMessage());
        }

        return array('success' => true, 'path' => $bucketImage['url'], 'key' => $nameImage . ".jpg");
    }

    public function deleteImgError($arrImg, $dirImage)
    {
        if (trim($dirImage) == '' ) {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.self::PRODUCT_IMAGE_FOLDER . "/".$dirImage);
            die;
        }

        $dir_verify = trim(self::PRODUCT_IMAGE_FOLDER . "/$dirImage");
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        foreach ($arrImg as $image){
            $filename = self::PRODUCT_IMAGE_FOLDER . "/$dirImage/$image";
            if(file_exists($filename)) {
               // log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' unlink '.$filename);
                unlink($filename);
            }
        }
    }

    public function deleteImgErrorBucket($arrImg, $dirImage)
    {
        if (trim($dirImage) == '' ) {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.self::PRODUCT_IMAGE_FOLDER . "/".$dirImage);
            die;
        }

        $dir_verify = trim(self::PRODUCT_IMAGE_FOLDER . "/$dirImage");
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        foreach ($arrImg as $image){
            $filename = self::PRODUCT_IMAGE_FOLDER . "/$dirImage/$image";
            $this->bucket->deleteObject($filename);
        }
    }

    public function deleteImgTemp($arrImg, $dirImage)
    {
        if (trim($dirImage) == '' ) {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.self::PRODUCT_IMAGE_FOLDER . "/".$dirImage);
            die;
        }

        $dir_verify = trim(self::PRODUCT_IMAGE_FOLDER . "/$dirImage");
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        foreach ($arrImg as $image){
            $filename = self::PRODUCT_IMAGE_FOLDER . "/$dirImage/$image";
            if(file_exists($filename)) {
               // log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' unlink '.$filename);
                unlink($filename);
            }
        }
    }

    public function deleteImagesDir($dirImage, $ignoreImages = array()): bool
    {

        if (trim($dirImage) == '' ) {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.self::PRODUCT_IMAGE_FOLDER . "/".$dirImage);
            die;
        }

        $dir_verify = trim(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage);
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        $images = scandir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage);
        foreach ($images as $image) {
            if ($image != "." && $image != ".." && $image != "") {
                if (in_array($image, $ignoreImages)) {
                    continue;
                }
                $filename = self::PRODUCT_IMAGE_FOLDER . "/$dirImage/$image";
                if (is_dir($filename)) {
                    $this->deleteImagesDir("$dirImage/$image", $ignoreImages);
                    continue;
                }
                if (file_exists($filename)) {
                    // log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' unlink '.$filename);
                    unlink($filename);
                }
            }
        }
        return true;
    }

    public function countImagesDir($dirImage): int
    {
        $count = 0;
        $path = FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage;
        if(!file_exists($path)){
            return $count;
        }
        $images = scandir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage);
        foreach($images as $image) { #argumento invalido para o foreach
            if ($image != "." && $image != ".." && $image != "" && !is_dir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage.'/'.$image)) {
                $count++;
            }
        }
        return $count;
    }

    public function getPrimaryImageDir($dirImage, $catalog = null): string
    {
        $imagemPrimaria = '';
        if ($catalog)
            $images = scandir(FCPATH . 'assets/images/catalog_product_image/' . str_replace('catalog_', '', $dirImage));
        else
            $images = scandir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage);

        foreach ($images as $image) {
            if ($image != "." && $image != ".." && $image != "") {
                $imagemPrimaria = baseUrlPublic(self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage) . '/' . $image;
                break;
            }
        }

        return $imagemPrimaria;
    }

    public function checkRemoteFile($url): bool
    {
        $timeout = 8; //timeout seconds

        $headers[] = 'Accept: image/*, image/gif, image/x-bitmap, image/jpeg, image/pjpeg, image/png, image/webp, image/apng';
        $headers[] = 'Connection: Keep-Alive';
        $headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
        $user_agent = 'php';
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $user_agent); //check here
        curl_setopt($process, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
        $return = curl_exec($process);
        $status = curl_getinfo($process, CURLINFO_HTTP_CODE);
        curl_close($process);

        return ($return!==FALSE) && ($status == 200 || $status == 403);
    }

    /**
     * @throws Exception
     */
    public function getImageViaVarejo($url)
    {
        // Remove o query param que seta o tamanho menor de imagem.
        $url = $this->removeQueryParam($url,'imwidth');

        try {
            $options['headers']['User-Agent'] = 'testing/1.0';
            $options['headers']['Connection'] = 'keep-alive';
            $options['headers']['Accept'] = 'image/*';
            $options['headers']['Accept-Encoding'] = null;
            $options['headers']['Accept-Language'] = null;

            $request = $this->client->request('GET', $url, $options);

            return $request->getBody()->getContents();
        } catch (GuzzleException $exception) {
            throw new Exception("A imagem tem que ser um URL de imagem válida. URL: $url");
        }
    }

    /**
     * @param    string         $url Url para remover o query param.
     * @param    string         $paramToRemove Remove o parâmetro da query string.
     */
    function removeQueryParam($url, $paramToRemove)
    {
        // Quebra a URL
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $query);

        unset($query[$paramToRemove]);

        $newQuery = http_build_query($query);

        // Reconstrói os parâmetros.
        $newUrl = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $newUrl .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $newUrl .= $parts['path'];
        }

        if ($newQuery) {
            $newUrl .= '?' . $newQuery;
        }

        if (isset($parts['fragment'])) {
            $newUrl .= '#' . $parts['fragment'];
        }

        return $newUrl;
    }

    public function getImageBling($url)
    {
        $timeout = 30; //timeout seconds
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,  htmlspecialchars_decode($url));
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($curl_handle);
        curl_close($curl_handle);
        return $response;
   }

    public static function getImagePath($base = ''): string
    {
        $pos = strrpos($_SERVER['PHP_SELF'], '/');
        $appPath = trim(substr($_SERVER['PHP_SELF'], 0, $pos), '/');
        $path = [
            $_SERVER['DOCUMENT_ROOT'],
            $appPath,
            self::PRODUCT_IMAGE_FOLDER,
            $base,
        ];
        $path = array_filter($path, function ($i) {
            return !empty($i);
        });
        return implode('/', $path);
    }

    public static function generateImagePath($base = '')
    {
        $dirImage = get_instance()->getGUID(false);
        $path = array_filter([
            $base,
            $dirImage,
        ], function ($i) {
            return !empty($i);
        });
        FileDir::createDir(
            UploadProducts::getImagePath(implode('/', $path))
        );
        return $dirImage;
    }

    public function deleteDir(string $dir): bool
    {
        if (trim($dir) == '' ) {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.self::PRODUCT_IMAGE_FOLDER . "/".$dir);
            die;
        }

        $dir_verify = trim(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dir);
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.get_instance()->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        $dir_old = $dir;
        $dir     = self::PRODUCT_IMAGE_FOLDER . '/' . $dir;

        if(is_dir($dir)) {
            $this->deleteImagesDir($dir_old);
            rmdir($dir);
        }

        return true;
    }

    public function getImagesFileByPath(string $dirImage): array
    {
        $images_file = array();
        $images = scandir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage);
        foreach($images as $image) {
            if ($image != "." && $image != ".." && $image != "" && !is_dir(FCPATH . self::PRODUCT_IMAGE_FOLDER . '/' . $dirImage.'/'.$image)) {
                $images_file[] = $image;
            }
        }
        return $images_file;
    }
}
