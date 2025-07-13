<?php
// require_once "./ProductTransformation.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ParserProductAnymarketConectala.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationException.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/ValidateEanValue.php";
// require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/TransformationException.php";
require_once APPPATH . "controllers/BatchC/Integration/tools/RemoveAccentsAndCedilla.trait.php";
require_once APPPATH . "controllers/BatchC/Integration/tools/LoadSizeToDescriptionAndNameProduct.trait.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/RequestRestAnymarket.trait.php";

class ValidationProduct extends CI_Controller
{
    use RemoveAccentsAndCedilla;
    use RequestRestAnymarket;
    use LoadSizeToDescriptionAndNameProduct;
    use ValidateEanValue;
    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    private $product = [];
    private $variants = [];

    private $product_length_name;
    private $product_length_description;
    private $transformation;
    protected $url_anymarket;
    protected $appId;
    protected $token;

    public function __construct($CI)
    {
        $this->transformation = new ParserProductAnymarketConectala($CI);
        $this->CI = $CI;
        $this->CI->load->model('model_products');
        $this->CI->load->model('model_brands');
        $this->CI->load->model('model_stores');
        $this->CI->load->model('model_api_integrations');
        $this->CI->load->model('model_categories_anymaket_from_to');
        $this->CI->load->model('model_brand_anymaket_from_to');
        $this->CI->load->model('model_settings');
        $this->model_settings = $this->CI->model_settings;

        $this->url_anymarket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        $this->appId = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        $this->loadSizeToDescriptionAndNameProduct();
    }

    public function validateProduct($body, $integration, $type, $field = null, $is_variants = false)
    {
        $credentials = json_decode($integration["credentials"], true);
        $this->token = $credentials['token_anymarket'];

        if ($type == 'CanActive') {
            list($this->product, $this->variants) = $this->transformation->transformation($body, $integration, $is_variants);
            $this->printInTerminal(json_encode($this->product));
        } else if ($type == 'CanSave') {
            list($this->product, $this->variants) = $this->transformation->transformation($body, $integration, $is_variants);
            $this->printInTerminal(json_encode($this->product));
            if (isset($body['ean'])) {
                $this->validateEAN($body['ean'], $integration['store_id']);
                $this->product['ean'] = $field['EAN'] ?? '';
                $this->variants[0]['ean'] = $field['EAN'] ?? '';
            }
            if (isset($field['additionalTime'])) {
                $this->product['prazo_operacional_extra'] = $field['additionalTime'] ?? '0';
            }
            if (isset($body['discountPrice'])) {
                $this->product['price'] = $body['discountPrice'];
                $this->variants[0]['price'] = $body['discountPrice'];
            }
            $this->product['name'] = $body['title'];
            $this->validateNameAndDescription();
        } else if ($type == 'UpdateProducts') {
            list($this->product, $this->variants) = $this->transformation->transformation($body, $integration, $is_variants);

            $this->validateUpgradeableProduct([
                'sku' => $this->product['sku'],
                'store_id' => $integration['store_id'],
                'product_id_erp' => $this->product['product_id_erp']
            ]);
            $this->validateUpgradeableProduct([
                'sku' => $this->product['_original_sku'],
                'store_id' => $integration['store_id'],
                'product_id_erp' => $this->product['product_id_erp']
            ]);
        }

        $this->product = ValidationProduct::RemoveArrayKeyStartsWith($this->product);
        $this->variants = ValidationProduct::RemoveArrayKeyStartsWith($this->variants);

        // list($this->product, $this->variants) = $this->transformation->transformation($body, $integration);
        return array($this->product, $this->variants);
    }

    public static function stringStartsWith($string, $with, $case = false)
    {
        if (!$case)
            return strtolower(substr($string, 0, strlen($with))) === strtolower($with);

        return substr($string, 0, strlen($with)) === $with;
    }

    public static function removeArrayKeyStartsWith(array $array, $with = '_', $case_sensitive = false)
    {

        foreach ($array as $k => $v) {
            if (!ValidationProduct::stringStartsWith($k, $with, $case_sensitive) && is_array($v)) {
                $array[$k] = ValidationProduct::removeArrayKeyStartsWith($array[$k], $with, $case_sensitive);
            } elseif (ValidationProduct::stringStartsWith($k, $with, $case_sensitive)) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    public function validateNameAndDescription()
    {
        if (strlen($this->removeAccentsAndCedilla($this->product['name'])) > $this->product_length_name) {
            throw new ValidationException("Campo title não pode conter mais de {$this->product_length_name} caracteres.");
        }
        if (strlen($this->removeAccentsAndCedilla($this->product['description'])) > $this->product_length_description) {
            throw new ValidationException("Campo product.description não pode conter mais de {$this->product_length_description} caracteres.");
        }
    }

    private function printInTerminal($string)
    {
        file_put_contents('php://stdout', print_r($string, true));
    }

    public function validateUpgradeableProduct($params)
    {
        $product = $this->validateProductExists($params);
        if ($product && !empty($product['product_id_erp'])) {
            $resultCheck = $this->getProductFromExternalPlatform($product['product_id_erp']);
            $idProduct = $resultCheck['product']['id'] ?? 0;
            if (!empty($idProduct) && $idProduct != $params['product_id_erp']) {
                throw new Exception("Já existe um produto com sku {$params['sku']} igual no Seller Center");
            } else if (isset($params['product_id'])
                && $product['id'] != $params['product_id']) {
                throw new Exception(
                    "Não é possível atualizar o produto (ID: {$params['product_id']}) pois já existe um produto (ID: {$product['id']}) de mesmo sku {$params['product_id']}."
                );
            }
        }
    }

    protected function validateProductExists($params)
    {
        $product = $this->CI->model_products->getProductBySkuAndStore(
            $params['sku'],
            $params['store_id']
        );
        if (
            !empty($product['product_id_erp'])
            && $product['product_id_erp'] != $params['product_id_erp']
        ) {
            return $product;
        }
        return null;
    }

    protected function getProductFromExternalPlatform($productId)
    {
        $productAnymarket = $this->CI->db->select()
            ->from('anymarket_queue')->where(['idProduct' => $productId])
            ->order_by('id', 'DESC')->limit(1)
            ->get()->row_array();
        if (!$productAnymarket) {
            return [];
        }
        $skuId = $productAnymarket['idSku'];
        $result = $this->sendREST("{$this->url_anymarket}skus/id/{$skuId}");
        if ($result['httpcode'] != 200) {
            return [];
        }
        return $result['content'] ? json_decode($result['content'], true) : [];
    }
}
