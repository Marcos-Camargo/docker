<?php
// require_once "./ProductTransformation.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/CanSave/ParserProductAnymarketConectalaCanSave.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationException.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationProduct.php";
require_once APPPATH . "controllers/Api/Integration/AnyMarket/traits/ValidateEanValue.php";
// require_once APPPATH . "controllers/Api/Integration/AnyMarket/Validations/TransformationException.php";
require_once APPPATH . "controllers/BatchC/Integration/tools/RemoveAccentsAndCedilla.trait.php";
require_once APPPATH . "controllers/BatchC/Integration/tools/LoadSizeToDescriptionAndNameProduct.trait.php";
class ValidationProductCanSave extends ValidationProduct
{
    use RemoveAccentsAndCedilla;
    use LoadSizeToDescriptionAndNameProduct;
    use ValidateEanValue;
    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    private $product = [];
    private $variants = [];

    private $product_length_name;
    private $product_length_description;
    private $transformation;

    public function __construct($CI)
    {
        $this->transformation = new ParserProductAnymarketConectalaCanSave($CI);
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

        list($this->product, $this->variants) = $this->transformation->transformation($body, $integration, $is_variants);
        $this->printInTerminal(json_encode($this->product));
        $this->validateUpgradeableProduct([
            'sku' => $this->product['sku'],
            'store_id' => $integration['store_id'],
            'product_id_erp' => $this->product['product_id_erp']
        ]);
        if (isset($field['EAN'])) {
            $this->validateEAN($field['EAN'], $integration['store_id']);
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
        $this->product['name'] = $body['fields']['title'];
        $this->validateNameAndDescription();
        return array($this->product, $this->variants);
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

}
