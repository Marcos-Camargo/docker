<?php
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/TransformationException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/Validation/VariationIntegrationAnyMarket.php";
require_once APPPATH . "libraries/Helpers/ObjectSerializer.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Attributes\Custom\CustomApplicationAttributeService;
use \libraries\Helpers\ObjectSerializer;

/**
 * Class ParserProductAnymarketConectala
 * @property VariationIntegrationAnyMarket $variationIntegrationAnyMarket
 * @property CustomApplicationAttributeService $customAppAttrService
 */
class ParserProductAnymarketConectala
{
    const COLORS = [
        'cor', 'cores', 'color', 'colors'
    ];

    const SIZES = [
        'tamanho', 'tamanhos', 'size', 'sizes'
    ];

    const VOLTAGES = [
        'voltagem', 'voltagens', 'voltage', 'voltages'
    ];

    const FLAVORS = [
        'sabor', 'sabores', 'flavor', 'flavors'
    ];

    const DEGREES = [
        'grau', 'graus', 'degree', 'degrees'
    ];

    const SIDES = [
        'lado', 'lados', 'side', 'sides'
    ];

    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    private $CI;
    private $product = [];
    private $variants = [];
    private $varation_image = [];
    private $variationIntegrationAnyMarket;
    private $enabledFlavor = false;
    private $enabledDegree = false;
    private $enabledSide = false;

    private $fields = [
        // ['anyField' => 'title', 'require' => true, 'innerField' => 'name', 'type' => 'string'],
        ['anyField' => ['sku', 'product', 'title'], 'require' => true, 'innerField' => 'name', 'type' => 'subarray'],
        ['anyField' => 'discountPrice', 'require' => true, 'innerField' => 'price', 'type' => 'string'],
        ['anyField' => 'ean', 'require' => false, 'innerField' => 'ean', 'type' => 'string', 'default' => ''],
        ['anyField' => ['sku', 'product', 'description'], 'require' => true, 'innerField' => 'description', 'type' => 'subarray'],
        ['anyField' => 'skuInMarketplace', 'require' => true, 'innerField' => 'sku', 'type' => 'string'],
        ['anyField' => ['sku', 'partnerId'], 'require' => true, 'innerField' => '_original_sku', 'type' => 'subarray'],
        ['anyField' => ['stock', 'additionalTime'], 'require' => false, 'innerField' => 'prazo_operacional_extra', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'brand', 'id'], 'require' => true, 'innerField' => 'brand_id', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'category', 'id'], 'require' => true, 'innerField' => 'category_id', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'warrantyTime'], 'require' => false, 'innerField' => 'garantia', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'height'], 'require' => true, 'innerField' => 'actual_height', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'height'], 'require' => true, 'innerField' => 'altura_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'height'], 'require' => true, 'innerField' => 'altura', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'width'], 'require' => true, 'innerField' => 'actual_width', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'width'], 'require' => true, 'innerField' => 'largura', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'width'], 'require' => true, 'innerField' => 'largura_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'weight'], 'require' => true, 'innerField' => 'peso_liquido', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'weight'], 'require' => true, 'innerField' => 'peso_bruto', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'weight'], 'require' => true, 'innerField' => 'peso_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'length'], 'require' => true, 'innerField' => 'profundidade', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'length'], 'require' => true, 'innerField' => 'profundidade_embalado', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'length'], 'require' => true, 'innerField' => 'actual_depth', 'type' => 'subarray', 'default' => 0],
        ['anyField' => ['sku', 'product', 'id'], 'require' => true, 'innerField' => 'product_id_erp', 'type' => 'subarray', 'default' => 0],
    ];

    public function __construct($CI)
    {
        $this->CI = $CI;
        $this->CI->load->library('UploadProducts');
        $this->CI->load->model('model_settings');

        $this->enabledFlavor = !empty($this->CI->model_settings->getFlavorActive());
        $this->enabledDegree = !empty($this->CI->model_settings->getDegreeActive());
        $this->enabledSide = !empty($this->CI->model_settings->getSideActive());
        $this->customAppAttrService = new CustomApplicationAttributeService();
    }

    public function transformation($body, $integration, $is_variants)
    {

        $this->variationIntegrationAnyMarket = new VariationIntegrationAnyMarket($integration);
        $this->product = [];
        $this->variants = [];
        $this->varation_image = [];

        foreach ($this->fields as $key => $field) {
            if ($field['require']) {
                $this->verifyIfExiste($body, $field);
            } else {
                try {
                    $this->verifyIfExiste($body, $field);
                } catch (Exception $e) {
                    $this->product[$field['innerField']] = $field['default'];
                }
            }
        }
        $this->product['store_id'] = $integration['store_id'];
        $store = $this->CI->model_stores->getStoresData($integration['store_id']);
        $this->product['company_id'] = $store['company_id'];
        $this->product['origin'] = $body['sku']['product']['origin']['id'];
        $this->product['qty'] = 0;
        if (isset($body['sku']['product']['hasVariations'])) {
            $this->validateVariants($body);
        }
        $this->validationCategory($body['sku']['product']['category'], $integration);
        $this->validationBrand($body['sku']['product']['brand'], $integration);
        // $this->validateImage($body, $is_variants);

        $categoryId = json_decode($this->product['category_id'])[0] ?? null;
        $this->product['attributes'] = $this->handleAttributesByCategory($body, $categoryId);
        return array($this->product, $this->variants);
    }

    protected function handleAttributesByCategory($body, $categoryId)
    {
        $attributes = [];
        foreach ($body['attributes'] ?? [] as $code => $value) {
            $customAttr = $this->customAppAttrService->getCustomAttributeByCriteria([
                'company_id' => $this->product['company_id'] ?? null,
                'store_id' => $this->product['store_id'] ?? null,
                'category_id' => $categoryId,
                'module' => CustomAttribute::PRODUCT_CATEGORY_ATTRIBUTE_MODULE,
                'code' => $code,
            ]);
            if (!$customAttr->exists()) continue;
            $attrName = $customAttr->getValueByColumn('name');
            $attributes[$attrName] = $value;
            $customAttrValue = $this->customAppAttrService->getCustomAttributeValueByCriteria([
                'company_id' => $this->product['company_id'] ?? null,
                'store_id' => $this->product['store_id'] ?? null,
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'code' => $value,
            ]);
            if (!$customAttrValue->exists()) continue;
            $attributes[$attrName] = $customAttrValue->getValueByColumn('value');
        }
        return $attributes;
    }

    private function validationCategory($category, $integration)
    {
        //$category['id'] = 761775;
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
        //$brand['id'] = 221412;
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
        $sabor = null;
        $has_variant = '';
        $variant_name = '';
        if (isset($body['sku']['variations'])) {
            foreach ($body['sku']['variations'] as $variant) {
                $variant = $this->variationIntegrationAnyMarket
                    ->overwriteVariationWithIntegrationCustomAttribute(ObjectSerializer::arrayToObject($variant));
                $variant = ObjectSerializer::objectToArray($variant);
                $variantName = strtolower($variant['type']['name']);
                $variant['type']['name'] = in_array($variantName, self::COLORS) ? 'Cor' : $variant['type']['name'];
                $variant['type']['name'] = in_array($variantName, self::VOLTAGES) ? 'Voltagem' : $variant['type']['name'];
                $variant['type']['name'] = in_array($variantName, self::SIZES) ? 'Tamanho' : $variant['type']['name'];
                if($this->enabledFlavor) {
                    $variant['type']['name'] = in_array($variantName, self::FLAVORS) ? 'Sabor' : $variant['type']['name'];
                }
                if($this->enabledDegree) {
                    $variant['type']['name'] = in_array($variantName, self::DEGREES) ? 'Grau' : $variant['type']['name'];
                }
                if($this->enabledSide) {
                    $variant['type']['name'] = in_array($variantName, self::SIDES) ? 'Lado' : $variant['type']['name'];
                }
                if (!in_array(strtolower($variant['type']['name']), array_merge(['cor', 'tamanho', 'voltagem'], ($this->enabledFlavor ? ['sabor'] : [])))) {
                    throw new Exception("O tipo de variação {$variant['type']['name']} não é válido, ou não está vinculado.");
                }
                if ($variant['type']['name'] == 'Tamanho') {
                    $tamanho = $variant;
                }
                if ($variant['type']['name'] == 'Cor') {
                    $cor = $variant;
                }
                if ($variant['type']['name'] == 'Voltagem') {
                    $voltagem = $variant;
                    if (!in_array(strtolower($variant['description']), ['110v', '220v', 'bivolt'])) {
                        throw new Exception("A opção {$variant['description']} não é válida, ou não está vinculada, para o tipo {$variant['type']['name']}.");
                    }
                }

                if ($this->enabledFlavor) {
                    if ($variant['type']['name'] == 'Sabor') {
                        $sabor = $variant;
                    }
                }

                if ($this->enabledDegree) {
                    if ($variant['type']['name'] == 'Grau') {
                        $sabor = $variant;
                    }
                }

                if ($this->enabledSide) {
                    if ($variant['type']['name'] == 'Lado') {
                        $sabor = $variant;
                    }
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
        if ($sabor) {
            $value = $sabor;
            $has_variant .= $has_variant == '' ? $value['type']['name'] : ';' . $value['type']['name'];
            $variant_name .= $variant_name == '' ? $value['description'] : ';' . $value['description'];
        }
        $this->variants[] = [
            'qty' => 0,
            'price' => 0,
            'status' => 1
        ];
        $this->product['has_variants'] = $has_variant;
        $this->variants[0]['variant_id_erp'] = $body['sku']['id'];
        $this->variants[0]['sku'] = $body['skuInMarketplace'] ?? $body['sku']['partnerId'];
        $this->variants[0]['_original_sku'] = $body['sku']['partnerId'];
        $this->variants[0]['name'] = $variant_name;
        $this->variants[0]['price'] = $body['discountPrice'];
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
            mt_srand((float)microtime() * 10000);
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
        $last_path_delete = (substr($dir_verify, -1) == '/') ? explode('/',substr($dir_verify,0,-1)): explode('/',$dir_verify);
        if (end($last_path_delete)=='product_image') {
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
