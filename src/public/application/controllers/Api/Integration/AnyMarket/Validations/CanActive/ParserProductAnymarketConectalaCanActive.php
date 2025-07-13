<?php
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/TransformationException.php";

class ParserProductAnymarketConectala
{
    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    private $CI;
    private $product = [];
    private $variants = [];
    private $varation_image = [];

    private $fields = [
        ['anyField' => ['product', 'title'], 'require' => true, 'innerField' => 'name', 'type' => 'subarray'],
        ['anyField' => ['product', 'description'], 'require' => true, 'innerField' => 'description', 'type' => 'subarray'],
        ['anyField' => ['partnerId'], 'require' => true, 'innerField' => 'sku', 'type' => 'subarray'],
        // ['anyField' => ['stock','additionalTime'], 'require' => false, 'innerField' => 'prazo_operacional_extra', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'brand', 'id'], 'require' => true, 'innerField' => 'brand_id', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'category', 'id'], 'require' => true, 'innerField' => 'category_id', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'warrantyTime'], 'require' => false, 'innerField' => 'garantia', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'height'], 'require' => true, 'innerField' => 'actual_height', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'height'], 'require' => true, 'innerField' => 'altura_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'height'], 'require' => true, 'innerField' => 'altura', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'width'], 'require' => true, 'innerField' => 'actual_width', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'width'], 'require' => true, 'innerField' => 'largura', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'width'], 'require' => true, 'innerField' => 'largura_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'weight'], 'require' => true, 'innerField' => 'peso_liquido', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'weight'], 'require' => true, 'innerField' => 'peso_bruto', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'weight'], 'require' => true, 'innerField' => 'peso_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'length'], 'require' => true, 'innerField' => 'profundidade', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'length'], 'require' => true, 'innerField' => 'profundidade_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'length'], 'require' => true, 'innerField' => 'actual_depth', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['product', 'id'], 'require' => true, 'innerField' => 'product_id_erp', 'type' => 'subarray', 'default' => 0],
    ];
    public function __construct($CI)
    {
        $this->CI = $CI;
        $this->CI->load->library('UploadProducts');
    }
    public function transformation($body, $integration, $is_variants)
    {
        $this->product = [];
        $this->variants = [];
        $this->varation_image = [];

        foreach ($this->fields as $key => $field) {
            if ($field['require']) {
                $this->verifyIfExiste($body, $field);
            }else{
                try{
                    $this->verifyIfExiste($body, $field);
                }catch(Exception $e){
                    $this->product[$field['innerField']]=$field['default'];
                }
            }
        }
        $this->product['store_id'] = $integration['store_id'];
        $store = $this->CI->model_stores->getStoresData($integration['store_id']);
        $this->product['company_id'] = $store['company_id'];
        $this->product['origin'] = $body['product']['origin']['id'];
        $this->product['qty'] = 0;
        if (isset($body['product']['hasVariations'])) {
            $this->validateVariants($body);
        }
        $this->validationCategory($body['product']['category'], $integration);
        $this->validationBrand($body['product']['brand'], $integration);
        // $this->validateImage($body, $is_variants);
        return array($this->product, $this->variants);
    }
    private function validationCategory($category, $integration)
    {
        $whereData = ['api_integration_id' => $integration['id'], 'idCategoryAnymarket' => $category['id']];
        $categ = $this->CI->model_categories_anymaket_from_to->getData($whereData);

        if ($categ != null) {
            $cat = [];
            array_push($cat, $categ['categories_id']);
            $this->product['category_id'] = json_encode($cat);
        } else {
            $this->printInTerminal("Categoria invalida.\n");
            throw new TransformationException("Categoria não sincronizada com o seller center");
        }
    }
    private function validationBrand($brand, $integration)
    {
        $whereData = ['api_integration_id' => $integration['id'], 'idBrandAnymarket' => $brand['id']];
        $brand = $this->CI->model_brand_anymaket_from_to->getData($whereData);
        if ($brand != null) {
            $cat = [];
            array_push($cat, $brand['brand_id']);
            $this->product['brand_id'] = json_encode($cat);
        } else {
            $this->printInTerminal("Marca invalida.\n");
            throw new TransformationException("Marca não sincronizada com o seller center");
        }
    }
    private function validateVariants($body)
    {
        $tamanho = null;
        $cor = null;
        $voltagem = null;
        $has_variant = '';
        $variant_name = '';
        if (isset($body['variations'])) {
            foreach ($body['variations'] as $variant) {
                $variant['type']['name'] = $variant['type']['name'] == 'cor' ? 'Cor' : $variant['type']['name'];
                $variant['type']['name'] = $variant['type']['name'] == 'voltagem' ? 'Voltagem' : $variant['type']['name'];
                $variant['type']['name'] = $variant['type']['name'] == 'tamanho' ? 'Tamanho' : $variant['type']['name'];
                if ($variant['type']['name'] == 'Tamanho') {
                    $tamanho = $variant;
                }
                if ($variant['type']['name'] == 'Cor') {
                    $cor = $variant;
                }
                if ($variant['type']['name'] == 'Voltagem') {
                    $voltagem = $variant;
                }
                if ($variant['type']['visualVariation']) {
                    if (isset($body['product']['images'])) {
                        foreach ($body['product']['images'] as $image) {
                            if ($image['variation'] == $variant['description']) {
                                $this->varation_image[] = $image;
                            }
                        }
                    }
                }
            }
        }
        if ($tamanho) {
            $value = $tamanho;
            $has_variant .= $has_variant == '' ? strtoupper($value['type']['name']) : ';' . strtoupper($value['type']['name']);
            $variant_name .= $variant_name == '' ? $value['description'] : ';' . $value['description'];
        }
        if ($cor) {
            $value = $cor;
            $has_variant .= $has_variant == '' ? $value['type']['name'] : ';' . $value['type']['name'];
            $variant_name .= $variant_name == '' ? $value['description'] : ';' . $value['description'];
        }
        if ($voltagem) {
            $value = $voltagem;
            $has_variant .= $has_variant == '' ? strtoupper($value['type']['name']) : ';' . strtoupper($value['type']['name']);
            $variant_name .= $variant_name == '' ? $value['description'] : ';' . $value['description'];
        }
        $this->variants[] = [
            'qty' => 0,
            'price' => 0,
            'status' => 1,
            'variant_id_erp' => 2,
        ];
        $this->product['has_variants'] = $has_variant;
        $this->variants[0]['variant_id_erp'] = $body['id'];
        $this->variants[0]['sku'] = $body['partnerId'];
        $this->variants[0]['name'] = $variant_name;
    }
    private function verifyIfExiste($body, $field)
    {
        if ($field['type'] == 'string') {
            if (!isset($body[$field['anyField']])) {
                throw new Exception("Campo " . $field['anyField'] . " Não foi devidamente configurado pela anymarket.\n");
            } else {
                $this->printInTerminal('Validado com sucesso campo na anymarket:' . json_encode($field['anyField']) . " dentro da conecta:" . json_encode($field['innerField']) . " " . $body[$field['anyField']] . "\n");
                $this->product[$field['innerField']] = $body[$field['anyField']];
            }
        } else if ($field['type'] == 'subarray') {
            $bodyToTeste = $body;
            foreach ($field['anyField'] as $key => $anyKey) {
                if (!isset($bodyToTeste[$anyKey])) {
                    throw new Exception("Campo " . json_encode($field['anyField']) . " Não foi devidamente configurado pela anymarket.\nNão encontrado: " . $anyKey . "\n");
                } else {
                    $bodyToTeste = $bodyToTeste[$anyKey];
                }
            }
            // if($bodyToTeste)
            $this->product[$field['innerField']] = $bodyToTeste;
            $this->printInTerminal('Validado com sucesso campo na anymarket:' . json_encode($field['anyField']) . " dentro da conecta:" . json_encode($field['innerField']) . "\n");
        }
    }
    private function printInTerminal($string)
    {
        file_put_contents('php://stdout', print_r($string, true));
    }
    private function getGUID($brackets = true)
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
    private function validateImage($body, $is_variants)
    {
        $saved_image = [];
        $imagens_product = array();
        $imagens_variant = array();
        if (isset($body['variations'])) {
            foreach ($body['product']['imagesWithoutVariationValue'] as $img) {
                if (!in_array($img['id'], $saved_image)) {
                    array_push($imagens_product, $img);
                    array_push($saved_image, $img['id']);
                }
            }
            if (empty($body['product']['imagesWithoutVariationValue'])) {
                foreach ($body['product']['images'] as $img) {
                    if (!in_array($img['id'], $saved_image)) {
                        array_push($imagens_product, $img);
                        array_push($saved_image, $img['id']);
                    }
                }
            }
        }
        if (count($body['product']['images']) == 0) {
            $this->product['situacao'] = 1;
        }
        foreach ($body['product']['images'] as $img) {
            array_push($imagens_variant, $img);
        }
        list($folder, $principal_image) = $this->validate_image($imagens_product);
        $this->product['image'] = $folder;
        $this->product['principal_image'] = base_url($principal_image);
        list($folder_var, $principal_image) = $this->validate_image($this->varation_image, $folder, true);
        $this->variants[0]['image'] = $folder_var;
    }
    private function validateImageOnlyProduct($body)
    {
        $body = $body['product']["images"];
        $imagens_product = array();
        $imagens_variant = array();
        if (count($body) == 0) {
            $this->product['situacao'] = 1;
        }

        foreach ($body as $img) {
            array_push($imagens_product, $img);
        }
        list($folder, $principal_image) = $this->validate_image($imagens_product);
        $this->product['image'] = $folder;
        $this->product['principal_image'] = base_url($principal_image);
        list($folder_var, $principal_image) = $this->validate_image($this->varation_image, $folder, true);
        $this->variants[0]['image'] = $folder_var;
    }
    public function validate_image($imagens, $product_folder = null, $is_variant = false, $variant_folder = null)
    {
        $printipal_image = '';
        if (!$product_folder || $product_folder == '') {
            $product_folder = $this->getGUID(false);
            $caminho = SELF::PASTA_DE_IMAGEM . '/' . $product_folder . '/';
            if (!is_dir($caminho)) {
                @mkdir($caminho, 0775);
            }
        } else {
            $caminho = SELF::PASTA_DE_IMAGEM . '/' . $product_folder . '/';
            if ($is_variant) {
                $variant_folderTemp = $this->getGUID(false);
                if (!$variant_folder || $variant_folder == '') {
                    $variant_folder = $variant_folderTemp;
                    $caminho .= $variant_folder . '/';
                    if (!is_dir($caminho)) {
                        @mkdir($caminho, 0775);
                    }
                } else {
                    $variant_folder = $variant_folder;
                    $caminho .= $variant_folder . '/';
                    if (!is_dir($caminho)) {
                        @mkdir($caminho, 0775);
                    }
                }
            } else {
                $this->clean_folder($caminho);
            }
        }
        if (empty($imagens)) {
            if ($is_variant) {
                return array($variant_folder, $printipal_image);
            } else {
                return array($product_folder, $printipal_image);
            }
        }
        foreach ($imagens as $key => $imagen) {
            if (!$this->CI->uploadproducts->checkRemoteFile($imagen['standardUrl'])) {
                throw new TransformationException('Ao menos uma imagem nesta linha apresentou erro/At least one image in this line has an error');
            }
        }

        if (($product_folder && $product_folder != '') || (($variant_folder && $variant_folder != '') && (!$product_folder || $product_folder == ''))) {
            $this->clean_folder($caminho);
        }
        foreach ($imagens as $key => $imagen) {
            $callback_data = $this->CI->uploadproducts->sendImageForUrl($caminho, $imagen['standardUrl']);
            if ($callback_data["success"]) {
                if ($printipal_image == '') {
                    $printipal_image = $caminho . $callback_data['path'];
                }
            }
        }
        if ($is_variant) {
            return array($variant_folder, $printipal_image);
        } else {
            return array($product_folder, $printipal_image);
        }
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
        foreach ($files as $file) {
            if (is_file($file)) {
                log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' unlink '.$file);
                unlink($file);
            }
        }
    }
}
