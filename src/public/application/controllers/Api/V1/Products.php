<?php

require APPPATH . "controllers/Api/V1/API.php";
require APPPATH . "libraries/Traits/LengthValidationProduct.trait.php";
require APPPATH . "libraries/Traits/ValidationSkuSpace.trait.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Microservices\v1\Integration\Price;
use Microservices\v1\Integration\Stock;
use App\Libraries\FeatureFlag\FeatureManager;

require_once APPPATH . "controllers/Api/V1/Helpers/VariationTypeHelper.php";

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property model_log_products $model_log_products
 * @property Model_products $model_products
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_providers $model_providers
 * @property Model_category $model_category
 * @property Model_groups $model_groups
 * @property Model_attributes $model_attributes
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_products_modified    $model_products_modified
 * @property Model_api_integrations $model_api_integrations
 * @property Model_products_catalog $model_products_catalog
 * @property Model_catalogs $model_catalogs
 *
 * @property Price $ms_price
 * @property Stock $ms_stock
 * @property DeleteProduct $deleteProduct
 * @property UploadProducts $uploadproducts
 * @property blacklistofwords $blacklistofwords
 * @property Bucket $bucket
 */

class Products extends API
{
    use LengthValidationProduct;
    use ValidationSkuSpace;
    use VariationTypeHelper {
        VariationTypeHelper::__construct as private __varTypeHelperConstruct;
    }

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';

    public const MAP_STATUS = [
        self::STATUS_ENABLED => 1,
        self::STATUS_DISABLED => 2,
    ];

    private $sku;
    private $type_variation = array(
        "Cor"       => "color",
        "TAMANHO"   => "size",
        "VOLTAGEM"  => "voltage",
        "SABOR"     =>  "flavor",
        "GRAU"     =>  "degree",
        "LADO"     =>  "side"
    );
    private $mktAttributesIgnore = array(
        'VIA'   => array(
            'SELECIONE',
            'GARANTIA'
        ),
        'ML'    => array(
            'BRAND',
            'EAN',
            'GTIN',
            'SELLER_SKU',
            'EXCLUSIVE_CHANNEL',
            'ITEM_CONDITION'
        )
    );
    private $arrColumnsUpdate = array(
        'images'                => array('columnDatabase' => 'image','type' => 'A', 'required' => false, 'can_update_after_publish' => false),
        'name'                  => array('columnDatabase' => 'name','type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'sku'                   => array('columnDatabase' => 'sku', 'type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'active'                => array('columnDatabase' => 'status','type' => 'S', 'required' => true, 'can_update_after_publish' => true),
        'description'           => array('columnDatabase' => 'description','type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'price'                 => array('columnDatabase' => 'price','type' => 'F', 'required' => true, 'can_update_after_publish' => true),
        'list_price'            => array('columnDatabase' => 'list_price','type' => 'F', 'required' => false, 'can_update_after_publish' => true),
        'qty'                   => array('columnDatabase' => 'qty','type' => 'F', 'required' => true, 'can_update_after_publish' => true),
        'ean'                   => array('columnDatabase' => 'EAN','type' => 'S', 'required' => false, 'can_update_after_publish' => false),
        'sku_manufacturer'      => array('columnDatabase' => 'codigo_do_fabricante','type' => 'S', 'required' => false, 'can_update_after_publish' => false),
        'net_weight'            => array('columnDatabase' => 'peso_liquido','type' => 'F', 'required' => true, 'can_update_after_publish' => false),
        'gross_weight'          => array('columnDatabase' => 'peso_bruto','type' => 'F', 'required' => true, 'can_update_after_publish' => false),
        'width'                 => array('columnDatabase' => 'largura','type' => 'F', 'required' => true, 'can_update_after_publish' => false),
        'height'                => array('columnDatabase' => 'altura','type' => 'F', 'required' => true, 'can_update_after_publish' => false),
        'depth'                 => array('columnDatabase' => 'profundidade','type' => 'F', 'required' => true, 'can_update_after_publish' => false),
        'items_per_package'     => array('columnDatabase' => 'products_package','type' => 'I', 'required' => false, 'can_update_after_publish' => false),
        'guarantee'             => array('columnDatabase' => 'garantia','type' => 'I', 'required' => true, 'can_update_after_publish' => false),
        'ncm'                   => array('columnDatabase' => 'NCM','type' => 'S', 'required' => false, 'can_update_after_publish' => false),
        'origin'                => array('columnDatabase' => 'origin','type' => 'I', 'required' => true, 'can_update_after_publish' => false),
        'unity'                 => array('columnDatabase' => 'attribute_value_id','type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'manufacturer'          => array('columnDatabase' => 'brand_id','type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'extra_operating_time'  => array('columnDatabase' => 'prazo_operacional_extra','type' => 'S', 'required' => false, 'can_update_after_publish' => true),
        'category'              => array('columnDatabase' => 'category_id', 'type' => 'S', 'required' => false, 'can_update_after_publish' => false),
        'product_width'         => array('columnDatabase' => 'actual_width','type' => 'F', 'required' => false, 'can_update_after_publish' => false),
        'product_height'        => array('columnDatabase' => 'actual_height','type' => 'F', 'required' => false, 'can_update_after_publish' => false),
        'product_depth'         => array('columnDatabase' => 'actual_depth','type' => 'F', 'required' => false, 'can_update_after_publish' => false),
        //'prazo_operacional'     => array('columnDatabase' => 'prazo_operacional_extra', 'type' => 'I', 'required' => false),
    );
    private $arrColumnsInsert = array(
        'product_variations'=> array('columnDatabase' => 'product_variations','type' => '', 'required' => true),
        'types_variations'  => array('columnDatabase' => 'has_variants', 'type' => 'A', 'required' => false)
    );
    private $arrColumnsCatalog = array(
        'sku'                   => array('columnDatabase' => 'sku', 'type' => 'S', 'required' => true, 'can_update_after_publish' => false),
        'active'                => array('columnDatabase' => 'status','type' => 'S', 'required' => true, 'can_update_after_publish' => true),
        'price'                 => array('columnDatabase' => 'price','type' => 'F', 'required' => true, 'can_update_after_publish' => true),
        'list_price'            => array('columnDatabase' => 'list_price','type' => 'F', 'required' => false, 'can_update_after_publish' => true),
        'qty'                   => array('columnDatabase' => 'qty','type' => 'F', 'required' => true, 'can_update_after_publish' => true),
        'ean'                   => array('columnDatabase' => 'EAN','type' => 'S', 'required' => false, 'can_update_after_publish' => false),
        'extra_operating_time'  => array('columnDatabase' => 'prazo_operacional_extra','type' => 'S', 'required' => false, 'can_update_after_publish' => true),
        'product_variations'    => array('columnDatabase' => 'product_variations','type' => '', 'required' => true),
        'types_variations'      => array('columnDatabase' => 'has_variants', 'type' => 'A', 'required' => false)
    );
    public $allowable_tags = null;
    private $usePriceVariation = false;
    private $arrEANsCheck = array();

    private $product_length_name ;
    private $product_length_description ;

    private $prd_id_update = null;
    private $store_has_catalog = false;

    /**
     * Campos opcionais, não serão validados com obrigatório.
     *
     * @var array $unverifiedFieldsBD
     */
    private $unverifiedFieldsBD = array(
        'list_price',
        'image',
        'product_variations',
        'has_variants',
        'products_package',
        'actual_width',
        'actual_height',
        'actual_depth'
    );

    /**
     * @var DeleteProduct
     */
    public $deleteProduct;

    protected $listFilters = [];

    protected $productFilters = [];

    protected $sellercenter = null;

    private $request_body;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_log_products');
        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_category');
        $this->load->model('model_integrations');
        $this->load->model('model_api_integrations');
        $this->load->model('model_queue_products_marketplace');
        $this->load->model('model_providers');
        $this->load->model('model_groups');
        $this->load->model('model_attributes');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_products_modified');
        $this->load->model('model_products_catalog');
        $this->load->model('model_catalogs');

        $this->load->library('UploadProducts');
        $this->load->library('Bucket');
        $this->load->library('BlacklistOfWords');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');
        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');

        $this->sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $settingPriceVariation = $this->model_settings->getSettingDatabyName('price_variation');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        if ($settingPriceVariation && $settingPriceVariation['status'] == 1) {
            $this->usePriceVariation = true;
        }

        $this->loadLengthSettings();

        $this->lang->load('application', 'english');
        $this->lang->load('messages', 'english');
        $this->load->library('DeleteProduct', [
            'productModel' => $this->model_products,
            'ordersModel' => $this->model_orders,
            'lang' => $this->lang
        ], 'deleteProduct');
        $this->__varTypeHelperConstruct();
    }

    protected function normalizeListFilters(array $queryParams = [])
    {
        $this->listFilters['page'] = (int)($queryParams['page'] ?? 0);
        $this->listFilters['page'] = $this->listFilters['page'] == 0 ? 1 : $this->listFilters['page'];
        $this->listFilters['per_page'] = (int)($queryParams['per_page'] ?? 100);
        $this->listFilters['per_page'] = $this->listFilters['per_page'] > 100 ? 100 : $this->listFilters['per_page'];
        $this->listFilters['stores'] = isset($queryParams['store']) ? [(int)$queryParams['store']] : [];
        $this->listFilters['cnpj'] = preg_replace('/[^0-9]/', '', $queryParams['cnpj'] ?? '');
        if (isset($queryParams['with_stock']) && in_array($queryParams['with_stock'], ['0','1','true','false'])) {
            $this->listFilters['with_stock'] = $queryParams['with_stock'] == 'true' ? 1 : $queryParams['with_stock'];
            $this->listFilters['with_stock'] = $this->listFilters['with_stock'] == 'false' ? 0 : $this->listFilters['with_stock'];
            $this->listFilters['with_stock'] = (int)$this->listFilters['with_stock'];
            $this->listFilters['with_stock'] = (bool)$this->listFilters['with_stock'];
        }
        if (array_key_exists($queryParams['status'] ?? null, self::MAP_STATUS)) {
            $this->listFilters['status'] = self::MAP_STATUS[$queryParams['status']];
        }
        if (array_key_exists('order_by', $queryParams)) {
            $this->listFilters['order_by'] = $queryParams['order_by'];
        }
        return $this->listFilters;
    }

    public function verifyInit($validateStoreProvider = true, $validateCnpjCompany = true)
    {
        $verifyResult = parent::verifyInit($validateStoreProvider, $validateCnpjCompany);
        $this->listFilters['company_id'] = $this->company_id;
        $this->listFilters['store_id'] = $this->store_id;
        if ((isset($this->company_id) && $this->company_id == 1)) {
            unset($this->listFilters['company_id']);
        }
        if ((isset($this->store_id) && $this->store_id == 0)) {
            unset($this->listFilters['store_id']);
        }

        return $verifyResult;
    }

    protected function list_get()
    {
        $this->endPointFunction = __FUNCTION__;
        list($status, $response) = $this->verifyInit();
        if (!$status) {
            return $this->response($response, REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->normalizeListFilters($this->cleanGet());
        $realPage = $this->listFilters['page'] - 1;
        $offset = $this->listFilters['per_page'] * $realPage;
        $filter_to_query = array();
        $order_by = array('id', 'DESC');
        $filterStockModifiedAfter = $this->input->get("stock_modified_after");
        $filterStockModifiedBefore = $this->input->get("stock_modified_before");

        if (!empty($this->listFilters['stores'])) {
            $filter_to_query['p.store_id'] = $this->listFilters['stores'][0] ?? null;
        }
        if (!empty($this->listFilters['cnpj'])) {
            $filter_to_query['s.CNPJ'] = onlyNumbers($this->listFilters['cnpj']);
        }
        if (!empty($this->listFilters['with_stock'])) {
            $filter_to_query['p.qty >'] = 0;
        }
        if (!empty($this->listFilters['status'])) {
            $filter_to_query['p.status'] = $this->listFilters['status'];
        }
        if (!empty($this->listFilters['order_by'])) {
            $exp_order_by = explode(':', $this->listFilters['order_by']);
            // ordem mal informada.
            if (count($exp_order_by) != 2) {
                return $this->response($this->returnError($this->lang->line('api_filter_order_by_bad_informed')), REST_Controller::HTTP_BAD_REQUEST);
            }

            // direção mal informada.
            if (!in_array(strtolower($exp_order_by[1]), array('desc', 'asc'))) {
                return $this->response($this->returnError($this->lang->line('api_filter_order_by_direction_bad_informed')), REST_Controller::HTTP_BAD_REQUEST);
            }

            $arrColumnsUpdate = [];
            // Adicionado os campos que não tem na api, mas que podem ser filtrados.
            $arrColumnsUpdate['id'] = ['columnDatabase' => 'id'];

            $columnFound = (array_filter($arrColumnsUpdate, function($columnUpdate) use ($exp_order_by) {
                return $columnUpdate['columnDatabase'] == $exp_order_by[0];
            }));

            // campo para filtro no existe. $arrColumnsUpdate
            if (empty($columnFound)) {
                $accept_fields = implode(',', array_map(function($columnUpdate) {
                    return $columnUpdate['columnDatabase'];
                }, $arrColumnsUpdate));
                return $this->response($this->returnError(sprintf($this->lang->line('api_filter_order_by_field_bad_informed'), $accept_fields)), REST_Controller::HTTP_BAD_REQUEST);
            }

            $order_by = $exp_order_by;
        }

        if (!empty($filterStockModifiedAfter)) {
            $dateAfter = DateTime::createFromFormat('d-m-Y', $filterStockModifiedAfter);

            if (!$dateAfter || $filterStockModifiedAfter != $dateAfter->format('d-m-Y')) {
                return $this->response($this->returnError("Formatos aceitos: 'dd-mm-YYYY'."), REST_Controller::HTTP_BAD_REQUEST);
            }

            $filter_to_query['DATE_FORMAT(p.stock_updated_at, "%Y-%m-%d") >='] = $dateAfter->format('Y-m-d');
        }

        if (!empty($filterStockModifiedBefore)) {
            $dateBefore = DateTime::createFromFormat('d-m-Y', $filterStockModifiedBefore);

            if (!$dateBefore || $filterStockModifiedBefore != $dateBefore->format('d-m-Y')) {
                return $this->response($this->returnError("Formatos aceitos: 'dd-mm-YYYY'."), REST_Controller::HTTP_BAD_REQUEST);
            }

            $filter_to_query['DATE_FORMAT(p.stock_updated_before, "%Y-%m-%d") <='] = $dateBefore->format('Y-m-d');
        }

        $products = $this->model_products->getProductsByStore(
            $this->store_id,
            $offset,
            $this->listFilters['per_page'],
            null,
            $filter_to_query,
            false,
            $order_by
        );

        $nroRegisters = $this->model_products->getProductsByStore(
            $this->store_id,
            0,
            null,
            null,
            $filter_to_query,
            true,
            $order_by
        );

        $data = [];
        foreach ($products as $product) {
            $product_response = $this->createArrayIten($product);
            if (isset($product_response['error'])) {
                continue;
            }
            $data[] = $product_response;
        }

        $response = [
            'success' => true,
            'offset' => $offset,
            'result' => [
                'error' => false,
                'registers_count' => (int)$nroRegisters,
                'pages_count' => ceil($nroRegisters / $this->listFilters['per_page']),
                'page' => $this->listFilters['page'],
                'data' => $data,
            ]
        ];
        return $this->response($response, REST_Controller::HTTP_OK);
    }

    /**
     * Consulta lista de produtos modificados.
     */
    public function modified_get()
    {

        $this->endPointFunction = __FUNCTION__;
        list($status, $response) = $this->verifyInit();
        if (!$status) {
            return $this->response($response, REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->normalizeListFilters($this->cleanGet());
        $realPage = $this->listFilters['page'] - 1;
        $offset = $this->listFilters['per_page'] * $realPage;
        $filter_to_query = array();
        $order_by = array('id', 'DESC');
        $filterStockModifiedAfter = $this->input->get("stock_modified_after");
        $filterStockModifiedBefore = $this->input->get("stock_modified_before");

        if (!empty($this->listFilters['stores'])) {
            $filter_to_query['p.store_id'] = $this->listFilters['stores'][0] ?? null;
        }

        if (!empty($this->listFilters['order_by'])) {
            $exp_order_by = explode(':', $this->listFilters['order_by']);
            // ordem mal informada.
            if (count($exp_order_by) != 2) {
                return $this->response($this->returnError($this->lang->line('api_filter_order_by_bad_informed')), REST_Controller::HTTP_BAD_REQUEST);
            }

            // direção mal informada.
            if (!in_array(strtolower($exp_order_by[1]), array('desc', 'asc'))) {
                return $this->response($this->returnError($this->lang->line('api_filter_order_by_direction_bad_informed')), REST_Controller::HTTP_BAD_REQUEST);
            }

            $arrColumnsUpdate = [];
            // Adicionado os campos que não tem na api, mas que podem ser filtrados.
            $arrColumnsUpdate['id'] = ['columnDatabase' => 'id'];

            $columnFound = (array_filter($arrColumnsUpdate, function($columnUpdate) use ($exp_order_by) {
                return $columnUpdate['columnDatabase'] == $exp_order_by[0];
            }));

            // campo para filtro no existe. $arrColumnsUpdate
            if (empty($columnFound)) {
                $accept_fields = implode(',', array_map(function($columnUpdate) {
                    return $columnUpdate['columnDatabase'];
                }, $arrColumnsUpdate));
                return $this->response($this->returnError(sprintf($this->lang->line('api_filter_order_by_field_bad_informed'), $accept_fields)), REST_Controller::HTTP_BAD_REQUEST);
            }

            $order_by = $exp_order_by;
        }

        if (!empty($filterStockModifiedAfter)) {
            $dateAfter = DateTime::createFromFormat('d-m-Y', $filterStockModifiedAfter);

            if (!$dateAfter || $filterStockModifiedAfter != $dateAfter->format('d-m-Y')) {
                return $this->response($this->returnError("Formatos aceitos: 'dd-mm-YYYY'."), REST_Controller::HTTP_BAD_REQUEST);
            }

            $filter_to_query['DATE_FORMAT(p.stock_updated_at, "%Y-%m-%d") >='] = $dateAfter->format('Y-m-d');
        }

        if (!empty($filterStockModifiedBefore)) {
            $dateBefore = DateTime::createFromFormat('d-m-Y', $filterStockModifiedBefore);

            if (!$dateBefore || $filterStockModifiedBefore != $dateBefore->format('d-m-Y')) {
                return $this->response($this->returnError("Formatos aceitos: 'dd-mm-YYYY'."), REST_Controller::HTTP_BAD_REQUEST);
            }

            $filter_to_query['DATE_FORMAT(p.stock_updated_before, "%Y-%m-%d") <='] = $dateBefore->format('Y-m-d');
        }

        $products = $this->model_products_modified->getProductsModifiedByStore(
            $this->store_id,
            $offset,
            $this->listFilters['per_page'],
            null,
            $filter_to_query,
            false,
            $order_by
        );

        $nroRegisters = $this->model_products_modified->getProductsModifiedByStore(
            $this->store_id,
            0,
            null,
            null,
            $filter_to_query,
            true,
            $order_by
        );

        $data = [];
        foreach ($products as $product) {
            $product_response = $this->createArrayIten($product,true);
            if (isset($product_response['error'])) {
                continue;
            }
            $data[] = $product_response;
        }

        $response = [
            'success' => true,
            'offset' => $offset,
            'result' => [
                'error' => false,
                'registers_count' => (int)$nroRegisters,
                'pages_count' => ceil($nroRegisters / $this->listFilters['per_page']),
                'page' => $this->listFilters['page'],
                'data' => $data,
            ]
        ];
        return $this->response($response, REST_Controller::HTTP_OK);
    }

    /**
     * Deleta produtos da lista de modificados.
     */
    public function modified_delete($sku = null)
    {
        $this->endPointFunction = __FUNCTION__;

        // Verifica se o prd_id foi fornecido
        if (!$sku) {
            return $this->response($this->returnError($this->lang->line('api_field_sku_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }

        $sku = xssClean($sku);

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $deleteResult = $this->model_products_modified->deleteProductsModified($sku, $this->store_id);

        if (!$deleteResult) {
            return $this->response($this->returnError($this->lang->line('api_field_sku_not_found') . " " . 'SKU: ' .  $sku), REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, "[PRODUCT_ID={$sku}] removido da model products modified");

        // Retorna a resposta de sucesso
        return $this->response(array('success' => true, "message" => $this->lang->line('api_product_removed') . " " . 'SKU: ' . $sku), REST_Controller::HTTP_OK);
    }



    public function index_get($sku = null, $skuAttr = null)
    {
        if (!$sku)
            return $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_NOT_FOUND);

        $skuAttr = xssClean($skuAttr);
        $sku = xssClean($sku);

        $this->sku = xssClean($sku);

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        //        if($this->store_id){
        //            $catalog = $this->getDataCatalogByStore($this->store_id);
        //            if ($catalog)
        //                return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);
        //        }


        if ($sku == 'attributes') {

            $this->sku = $skuAttr;
            $result = $this->getAttributeProduct();

        } else {

            // Verifica se o SKU informado está em branco
            if (empty($this->sku)) $result = false;

            // Verifica se foi informado um SKU
            if (!empty($this->sku)) $result = $this->createArrayIten();
        }
        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error'])
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    public function index_put($sku = null, $skuAttr = null)
    {
        // Recupera dados enviado pelo body
        $data = file_get_contents('php://input');
        $data = $data_decode = preg_replace('/\s/',' ',$data);
        $data = json_decode(json_encode($this->cleanGet(json_decode($data,true),false)));

        $skuAttr = xssClean($skuAttr);
        $sku = xssClean($sku);

        if (!$sku)
            return $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));

        $this->sku = $sku;

        // Verificação inicial
        $verifyInit = $this->verifyInit();

        if (!$verifyInit[0]){
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
        }

        if (strtolower($sku) == 'attributes') {
            if (!$skuAttr)
                return $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));

            $this->sku = $skuAttr;

            $dataProduct = $this->getDataProduct()->row_array();
            if (!$dataProduct) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_product_not_found'). " ( {$this->sku} )","W");
                return $this->response($this->returnError($this->lang->line('api_product_not_found')), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            }

            if (in_array($dataProduct['product_status'], [Model_products::DELETED_PRODUCT])) {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_trash_product_cannot_updated'). " ( {$this->sku} )", "W");
                return $this->response(
                    $this->returnError($this->lang->line('api_trash_product_cannot_updated')),
                    REST_Controller::HTTP_BAD_REQUEST,
                    $this->createButtonLogRequestIntegration($data)
                );
            }

            //            if (empty($dataProduct['category_id']))
            //                return $this->response($this->returnError('Produto ainda não contém uma categoria, para adicionar/alterar atributo ao produtos é necessário relacionar o produto a uma categoria!'), REST_Controller::HTTP_NOT_FOUND);

            if ($data == null) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_json_invalid_format')."\n\n " . file_get_contents('php://input'),"W");
                return $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            }
            $result = $this->updateAttribute($data, $dataProduct);
        } else {
            $dataProduct = $this->getDataProduct();
            if ($dataProduct->num_rows() === 0) {
                $check_variation = $this->model_products->getProductGroupedVariantsBySkuAndStore($this->sku, $this->store_id);

                // Condição para atualizar uma variação após um produto simples ser transformado em variação na tarefa OEP-1575.
                if ($check_variation) {
                    $client = new Client([
                        'verify' => false, // no verify ssl
                        'allow_redirects' => true
                    ]);

                    $json_option_variation = json_decode($data_decode, true);

                    $options = [
                        'json' => [
                            'variation' => $json_option_variation['product'] ?? []
                        ],
                        'headers' => getallheaders()
                    ];

                    try {
                        $request = $client->request(
                            $this->input->method(),
                            $this->getInternalUrl() . "/Api/V1/Variations/sku/$check_variation[sku]/$this->sku",
                            $options
                        );
                        json_decode($request->getBody()->getContents());
                    } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
                        $error_message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                        $error_message = json_decode($error_message, JSON_UNESCAPED_UNICODE)['message'] ?? $this->lang->line('api_no_data_update');
                        return $this->response($this->returnError($error_message), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                    }

                    $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data),"I");
                    return $this->response(array('success' => true, "message" => $this->lang->line('api_product_updated')), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
                }
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_code_not_found'). " ( {$this->sku} )","W");
                return $this->response($this->returnError($this->lang->line('api_sku_code_not_found')), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            }

            $dataProduct = $dataProduct->row_array();
            if (in_array($dataProduct['product_status'], [Model_products::DELETED_PRODUCT])) {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_trash_product_cannot_updated'). " ( {$this->sku} )", "W");
                return $this->response(
                    $this->returnError($this->lang->line('api_trash_product_cannot_updated')),
                    REST_Controller::HTTP_BAD_REQUEST,
                    $this->createButtonLogRequestIntegration($data)
                );
            }

            // Verifica se o SKU informado está em branco
            if (empty($this->sku)) $result = false;

            // Recupera dados enviado pelo body
            $data = file_get_contents('php://input');
            $data = preg_replace('/\s/',' ',$data);
            $data = json_decode(json_encode($this->cleanGet(json_decode($data,true),false)));

            if ($data == null) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_json_invalid_format'). "\n\n " . file_get_contents('php://input'),"W");
                return $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            }

            foreach ($data->product as $key => $value) {
                // verica se alteracao e de preco
                if($key == "price"){
                    // verica se usa catalogo
                    $catalog = $this->getDataCatalogByStore($this->store_id);
                    if ($catalog){
                        // verica se usa paramtro => catalog_products_dont_modify_price
                        $catalog_products_dont_modify_price = $this->model_settings->getSettingDatabyName('catalog_products_dont_modify_price');
                        if($catalog_products_dont_modify_price){
                            if($catalog_products_dont_modify_price['status'] == 1)
                                return $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                        }
                    }
                }elseif($key == "extra_operating_time"){
                    // verifica se vai atualizar prazo operacional e nao usa catalogo
                    $catalog = $this->getDataCatalogByStore($this->store_id);
                    if ($catalog){
                        $permission = $this->user_permissions;
                        if($permission){
                            if(!in_array('changeCrossdocking', $permission)) {
                                return $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                            }
                        }
                    }
                }
            }


            // Verifica se foi informado um SKU
            if (!empty($this->sku)) $result = $this->update($data);
        }
        ob_clean();
        if (isset($data->product->user_id)) unset($data->product->user_id);
        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error']) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            // se não tiver autorização para atualizar o produto alem do estoque e preço, sera retornado um status 401
            return $this->response($this->returnError($result['data']), $result['data'] === 'Recurso indisponível.' ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }
        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data),"I");
        /*if (isset($data->product)) {
            $data->product = (array)$data->product;
            if (!empty($dataProduct['has_variants'])) unset($data->product['qty']); // remover a quantidade ser for variação
            $this->model_log_products->create_log_products($data->product, $dataProduct['code_p'], 'API');
        }*/

        $message = $sku == 'attributes' ? $this->lang->line('api_attribute_updated') : $this->lang->line('api_product_updated');

        if (isset($result) && array_key_exists('data', $result)) {
            $message .= $result['data'];
        }

        $this->response(array('success' => true, "message" => $message), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
    }

    public function index_post($type = null)
    {
        // Recupera dados enviado pelo body
        $data = file_get_contents('php://input');
        $data = $this->request_body = preg_replace('/\s/',' ',$data);
        $data = json_decode(json_encode($this->cleanGet(json_decode($data,true),false)));

        $this->arrColumnsInsert = array_merge($this->arrColumnsUpdate, $this->arrColumnsInsert);

        // Verificação inicial
        $verifyInit = $this->verifyInit();

        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));        if($this->store_id){
            $catalog = $this->getDataCatalogByStore($this->store_id);
            if ($catalog)
                return $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
        }

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            return $this->response(array('success' => false, "message" => utf8_decode($disable_message)), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }

        if ($this->store_id) {
            $catalog = $this->getDataCatalogByStore($this->store_id);
            if ($catalog) {
                $match_produto_por_ean = $this->model_settings->getValueIfAtiveByName('match_produto_por_EAN');
                $this->store_has_catalog = true;
                if (!$match_produto_por_ean) {
                    return $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                }
            }
        }

        // MUDAR PARA HTTP_BAD_REQUEST
        if ($data == null) {
            return $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }

        if ($type == 'attributes') {
            // Verifica se foi informado um SKU
            $result = $this->insertAttribute($data);
        } else {
            // Verifica se foi informado um SKU
            try {
                $result = $this->insert($data);
            } catch (Exception $exception) {
                $exception_decode = json_decode($exception->getMessage(), true);
                $this->response(array_merge($exception_decode, array('is_variation_grouped' => true)), $exception->getCode(), $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            // MUDAR PARA HTTP_BAD_REQUEST
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }
        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data) . "\n\nRETURN=".json_encode($result),"I");

        $this->response(array('success' => true, "message" => $type == 'attributes' ? $this->lang->line('api_attribute_inserted') : $this->lang->line('api_product_inserted')), REST_Controller::HTTP_CREATED, $this->createButtonLogRequestIntegration($data));
    }

    public function publish_post()
    {
        // Recupera dados enviado pelo body
        $data = $this->inputClean(); //raw_input_stream

        if ($this->model_settings->getValueIfAtiveByName('accept_publish_product_by_api') === false) {
            return $this->response('Feature unavailable', REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit(false, false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
        }

        // Verifica se foi informado um SKU
        try {
            $this->publishProduct($data);
        } catch (Exception $exception) {
            $error = $exception->getMessage();
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, "$error\npayload=" . json_encode($data),"E");
            return $this->response($this->returnError($error), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data));

        return $this->response(null, REST_Controller::HTTP_NO_CONTENT, $this->createButtonLogRequestIntegration($data));
    }

    private function trash($sku)
    {
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $this->deleteProduct->setModelsData([
            'usercomp' => $this->company_id,
            'userstore' => $this->store_id,
        ]);
        $sku = xssClean($sku);
        $this->sku = $sku;

        $dataProduct = $this->getDataProduct();
        if ($dataProduct->num_rows() === 0) {
            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_code_not_found'). " ( {$this->sku} )", "W");
            return $this->response($this->returnError($this->lang->line('api_sku_code_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }
        $dataProduct = $dataProduct->row_array();
        if(!$dataProduct['code_p']) {
            return $this->response($this->returnError($this->lang->line('api_sku_code_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }
        if (in_array($dataProduct['product_status'], [Model_products::DELETED_PRODUCT])) {
            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_trash_product_already'). " ( {$this->sku} )", "W");
            return $this->response(
                $this->returnError($this->lang->line('api_trash_product_already')),
                REST_Controller::HTTP_BAD_REQUEST
            );
        }
        $prod = ['id' => $dataProduct['code_p']];
        $response = $this->deleteProduct->moveToTrash([$prod]);
        if (isset($response['errors'])) {
            $err = current($response['errors']) ?? '';
            return $this->response($this->returnError($err, REST_Controller::HTTP_UNPROCESSABLE_ENTITY));
        }
        return $this->response([
            'success' => true,
            'message' => $response['message']
        ]);
    }

    public function index_delete($routeSku = null, $sku = null)
    {

        $sku = xssClean($sku);
        $routeSku = xssClean($routeSku);
        if (strcasecmp($routeSku, 'trash') === 0) {
            return $this->trash($sku);
        }

        if (!$routeSku)
            return $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_NOT_FOUND);

        $this->sku = $routeSku;

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        // Verifica se foi informado um SKU
        $result = $this->remove();

        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error'])
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST);

        $this->response(array('success' => true, "message" => $this->lang->line('api_product_removed')), REST_Controller::HTTP_OK);
    }

    private function remove()
    {
        return array('error' => true, 'data' => $this->lang->line('api_deprecated_functionality'));

        $codsVariants = $this->getIdVariantProduct();
        // Consulta diretório das imagens
        $sql    = "SELECT image FROM products WHERE sku = '{$this->sku}' AND store_id = {$this->store_id}";
        $image  = $this->db->query($sql);

        if ($image->num_rows() == 0)
            return array('error' => true, 'data' => "Product not found.");

        $dirImg = $image->first_row();

        $this->db->trans_begin();

        $this->db->where(array('sku' => $this->sku, 'store_id' => $this->store_id));
        $this->db->delete('products');

        if (count($codsVariants) > 0) {
            $this->db->where_in('id', $codsVariants);
            $this->db->delete('prd_variants');
        }

        // Excluir diretório das imagens
        $filename   = "assets/images/product_image/{$dirImg->image}";
        $complete   = true;

        if (file_exists($filename))
            $complete = $this->deleteDirectory($filename);

        // Realiza diretamente a exclusão no bucket.
        $this->bucket->deleteDirectory($filename);

        if ($this->db->trans_status() === FALSE || !$complete) {
            $this->db->trans_rollback();
            if ($this->db->trans_status() === FALSE) return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
            if (!$complete) return array('error' => true, 'data' => $this->lang->line('api_not_remove_directory_img'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir) && !$this->bucket->objectExists($dir)['success']) {
            return true;
        }

        $dir_verify = trim($dir);
        $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
        if ($last_path_delete =='product_image') {
            log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
            die;
        }

        // Remove do bucket. Caso tenha tido sucesso, significa que existia lá.
        if($this->bucket->deleteDirectory($dir)['success']){
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    private function getIdVariantProduct()
    {
        $arrVariant = array();
        $sql = "SELECT v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND v.sku = ? AND p.store_id = ?";
        foreach ($this->db->query($sql,array($this->sku, $this->sku, $this->store_id))->result_array() as $variant) {
            array_push($arrVariant, $variant['id']);
        }
        return $arrVariant;
    }

    private function associateCatalogProduct(object $data): array
    {
        if (!$this->is_provider && !in_array('showcaseCatalog', $this->user_permissions)) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'));
        }

        $dataSql = array('store_id' => $this->store_id, 'company_id' => $this->company_id, 'category_id' => '[""]');
        $erroColumn = "";
        $product_variations = array();
        $arrVariations = array();

        if (!isset($data->product)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_product_key'));
        }
        if (count((array)$data->product) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_create'));
        }
        if (empty($data->product->list_price)) {
            $data->product->list_price = $data->product->price;
        }
        if ($data->product->list_price < $data->product->price) {
            return array('error' => true, 'data' => $this->lang->line('application_prices_error_bigger_then'));
        }

        // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
        if (isset($data->product->price) && isset($data->product->list_price)) {
            if (empty($data->product->price) && empty($data->product->list_price)) {
                return array('error' => true, 'data' => $this->lang->line('application_prices_error'));
            }

            if ((empty($data->product->price) && !empty($data->product->list_price))) {
                $data->product->price = $data->product->list_price;
            }

            if (empty($data->product->list_price) && !empty($data->product->price)) {
                $data->product->list_price = $data->product->price;
            }
        }

        if (isset($data->product->qty) && (int)$data->product->qty <= 0) {
            $data->product->qty = 0;
        }

        foreach ($data->product as $key => $value) {
            if (!array_key_exists($key, $this->arrColumnsCatalog)) {
                continue;
            }

            $value = $this->setValueVariableCorrect($key, $value, 'I');

            if ($value === "" && $erroColumn === "" && $this->arrColumnsInsert[$key]['required']) {
                $erroColumn = $this->lang->line('api_all_fields_informed') . $key;
            }

            $this->prd_id_update = null;
            $verify = $this->verifyFields($key, $value, "I");

            if (!$verify[0] && $erroColumn === "") {
                $erroColumn = $verify[1];
            }

            $value = $verify[1];

            if ($key == "product_variations" && $erroColumn == "") {
                $product_variations['product_variations'] = $value;
                continue;
            }
            if ($key == "types_variations" && $erroColumn == "") {
                if (empty($value)) {
                    return array('error' => true, 'data' => $this->lang->line('api_type_variation_found'));
                }
                $product_variations['types_variations'] = $value;
            }

            if (!isset($this->arrColumnsInsert[$key])) {
                continue;
            }

            $dataSql[$this->arrColumnsInsert[$key]['columnDatabase']] = $value;
        }

        if (empty($product_variations)) {
            $dataSqls = array(
                $dataSql
            );
        } else {
            $returnVariations = $this->createArrayProductsVariations($product_variations, $dataSql);
            if ($returnVariations['success'] === false) {
                return array('error' => true, 'data' => $returnVariations['data']);
            }
            $arrVariations = $returnVariations['data'];

            $dataSqls = array_map(function($variation) use ($dataSql) {
                return array(
                    'sku'                       => $variation['sku'],
                    'status'                    => $variation['status'] ?? 1,
                    'list_price'                => $variation['list_price'],
                    'price'                     => $variation['price'],
                    'qty'                       => $variation['qty'],
                    'EAN'                       => $variation['EAN'],
                    'prazo_operacional_extra'   => $dataSql['prazo_operacional_extra']
                );
            }, $arrVariations);
        }

        $this->db->trans_begin();
        // campos faltantes
        foreach ($dataSqls as $dataSql) {
            $checkRequiredFields = $this->checkRequiredFields($dataSql, $this->arrColumnsCatalog);
            if ($checkRequiredFields != "") {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_all_fields_filled') . $checkRequiredFields);
            }

            $product_catalogs = $this->model_products_catalog->getProductsByEAN($dataSql['EAN']);

            if (empty($product_catalogs)) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => "Não foi encontrado um produto {$dataSql['sku']} que contém o EAN {$dataSql['EAN']}");
            }

            if (count($product_catalogs) > 1) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => "Foram encontrados mais que um produto {$dataSql['sku']} que contém o EAN {$dataSql['EAN']}");
            }

            $product_catalog = $product_catalogs[0];
            $product_catalog_id = $product_catalog['id'];

            $catalogs = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id);

            if (empty($catalogs)) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => "Produto {$dataSql['sku']} não está associado a nenhum catálogo que contém o EAN {$dataSql['EAN']}");
            }

            if (count($catalogs) > 1) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => "Foram encontrados mais que um catálogo para o produto {$dataSql['sku']} que contém o EAN {$dataSql['EAN']}");
            }

            $catalog = $catalogs[0];
            $catalog_id = $catalog['catalog_id'];

            $priceRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');

            $prazo_operacional_extra = $dataSql['prazo_operacional_extra'];
            $qty = $dataSql['qty'];
            if ($priceRO) {
                $price = $product_catalog['price'];
            } else {
                $price = $dataSql['price'];
            }

            if (is_null($product_catalog['brand_code'])) {
                $product_catalog['brand_code'] = '';
            }

            $auto_publish = ($this->model_settings->getStatusbyName('catalog_products_auto_publish') == 1);
            $data_prod = array(
                'name' 						=> $product_catalog['name'],
                'sku' 						=> $dataSql['sku'],
                'price' 					=> $price,
                'qty' 						=> $qty,
                'image' 					=> 'catalog_'.$product_catalog_id,
                'principal_image' 			=> $product_catalog['principal_image'],
                'description' 				=> $product_catalog['description'],
                'attribute_value_id' 		=> $product_catalog['attribute_value_id'],
                'brand_id' 					=> json_encode(array($product_catalog['brand_id'])),
                'category_id' 				=> json_encode(array($product_catalog['category_id'])),
                'store_id' 					=> $this->store_id,
                'status' 					=> $dataSql['status'],
                'EAN' 						=> $product_catalog['EAN'],
                'codigo_do_fabricante' 		=> $product_catalog['brand_code'],
                'peso_liquido' 				=> $product_catalog['net_weight'],
                'peso_bruto' 				=> $product_catalog['gross_weight'],
                'largura' 					=> $product_catalog['width'],
                'altura' 					=> $product_catalog['height'],
                'profundidade' 				=> $product_catalog['length'],
                'garantia' 					=> $product_catalog['warranty'],
                'NCM' 						=> $product_catalog['NCM'],
                'origin' 					=> $product_catalog['origin'],
                'has_variants' 				=> $product_catalog['has_variants'],
                'company_id' 				=> $this->company_id,
                'situacao' 					=> 2,
                'prazo_operacional_extra' 	=> $prazo_operacional_extra,
                'product_catalog_id' 		=> $product_catalog_id,
                'maximum_discount_catalog' 	=> null,
                'dont_publish' 				=> false,
            );

            $create = $this->model_products->create($data_prod);

            if (!$create) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => "Não foi possível criar o produto {$dataSql['sku']}");
            }

            $this->model_catalogs->setCatalogsStoresDataByStoreId($this->store_id, array($catalog_id));

            $integrations= $this->model_integrations->getIntegrationsbyStoreId($this->store_id);
            // altero agora os preços e qty por marketplace criados automaticamente pelo model_products->create
            foreach($integrations as $integration) {
                if ($auto_publish) {
                    $prd_to_int = Array(
                        'int_id' 		=> $integration['id'],
                        'prd_id' 		=> $create,
                        'company_id' 	=> $this->company_id,
                        'store_id' 		=> $this->store_id,
                        'date_last_int' => '',
                        'status' 		=> 1,
                        'user_id' 		=> $this->user_id ?: "1",
                        'status_int' 	=> 1,
                        'int_type' 		=> 13,
                        'int_to' 		=> $integration['int_to'] ,
                        'skubling' 		=> null,
                        'skumkt' 		=> null,
                        'variant' 		=> null,
                        'approved' 		=> ($integration['auto_approve']) ? 1 : 3,
                    );
                    $this->model_integrations->setProductToMkt($prd_to_int);
                }

                $this->model_products_marketplace->createIfNotExist($integration['int_to'],$create, $integration['int_type']=='DIRECT');

                $products_marketplace=$this->model_products_marketplace->getAllDataByIntToProduct($integration['int_to'], $create);
                foreach ($products_marketplace as $product_marketplace) {
                    if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
                        $data = array(
                            'same_price' => false,
                            'price' => $dataSql['price'],
                        );
                        // gravo log e update
                        $log = [
                            'id' 	 	 => $product_marketplace['id'],
                            'int_to' 	 => $product_marketplace['int_to'],
                            'product_id' => $create,
                            'old_price'    => 'NOVO',
                            'new_price'    => $data['price']
                        ];
                        $this->log_data('ProductsMarketPlace', 'Update_Price', json_encode($log), 'I');
                        $this->model_products_marketplace->updateAllVariants($data,$product_marketplace['int_to'], $product_marketplace['prd_id']);
                    }
                    if ($product_marketplace['hub']) {
                        if ($product_marketplace['variant'] == '') {
                            $data = array(
                                'same_qty' => false,
                                'qty' => $dataSql['qty']
                            );
                            // gravo log e update somente se alterou...
                            $log = [
                                'id' 	 	 => $product_marketplace['id'],
                                'int_to' 	 => $product_marketplace['int_to'],
                                'product_id' => $create,
                                'old_qty'    => 'NOVO',
                                'new_qty'    => $data['qty']
                            ];
                            $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
                            $this->model_products_marketplace->update($data,$product_marketplace['id']);
                        }
                    }
                }
            }
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => "Ocorreu um erro para salvar o produto.\n" . json_encode($this->db->error()));
        }
        $this->db->trans_commit();

        return array('error' => false);
    }

    private function insert($data)
    {
        if (!$this->is_provider && !in_array('createProduct', $this->user_permissions)) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'));
        }

        if ($this->store_has_catalog) {
            return $this->associateCatalogProduct($data);
        }

        $dataSql = array('store_id' => $this->store_id, 'company_id' => $this->company_id, 'category_id' => '[""]');
        $erroColumn = "";
        $imgsUpload = array();
        $product_variations = array();
        $arrVariations = array();
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if (!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) {
            $limite_imagens_aceitas_api = 6;
        }

        if (!isset($data->product)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_product_key'));
        }
        if (count((array)$data->product) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_create'));
        }
        if (empty($data->product->list_price)) {
            $data->product->list_price = $data->product->price;
        }
        if ($data->product->list_price < $data->product->price) {
            return array('error' => true, 'data' => $this->lang->line('application_prices_error_bigger_then'));
        }

        $limite_variacoes = $this->model_settings->getLimiteVariationActive();
        if (!empty($limite_variacoes)) {
            $limite_variacoes = 1;
        } else {
            $limite_variacoes = 0;
        }

        // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
        if (isset($data->product->price) && isset($data->product->list_price)) {
            if (empty($data->product->price) && empty($data->product->list_price)) {
                return array('error' => true, 'data' => $this->lang->line('application_prices_error'));
            }

            if ((empty($data->product->price) && !empty($data->product->list_price))) {
                $data->product->price = $data->product->list_price;
            }

            if (empty($data->product->list_price) && !empty($data->product->price)) {
                $data->product->list_price = $data->product->price;
            }
        }

        if (isset($data->product->qty) && (int)$data->product->qty <= 0) {
            $data->product->qty = 0;
        }

        foreach ($data->product as $key => $value) {
            if (!array_key_exists($key, $this->arrColumnsInsert) && $erroColumn === "") {
                $erroColumn = $this->lang->line('api_parameter_not_match_field_insert') . $key;
            }

            $value = $this->setValueVariableCorrect($key, $value, 'I');

            if ($value === "" && $erroColumn === "" && $this->arrColumnsInsert[$key]['required']) {
                $erroColumn = $this->lang->line('api_all_fields_informed') . $key;
            }

            // Upload file não adiciona no array de create
            if ($key === "images") {
                if (is_string($value)) {
                    $imgsUpload[] = $value;
                }
                if (is_array($value)) {
                    $imgsUpload = $value;
                }
                continue;
            }
            if ($key === "category") {
                $dataSql['category_imported'] = $value;
            }

            $this->prd_id_update = null;
            $verify = $this->verifyFields($key, $value, "I");

            if (!$verify[0] && $erroColumn === "") {
                $erroColumn = $verify[1];
            }

            $value = $verify[1];

            if ($key == "product_variations" && $erroColumn == "") {
                $product_variations['product_variations'] = $value;
                continue;
            }
            if ($key == "types_variations" && $erroColumn == "") {
                if (empty($value)) {
                    return array('error' => true, 'data' => $this->lang->line('api_type_variation_found'));
                }
                $product_variations['types_variations'] = $value;
            }

            if (!isset($this->arrColumnsInsert[$key])) {
                continue;
            }

            $dataSql[$this->arrColumnsInsert[$key]['columnDatabase']] = $value;
        }

        if ($limite_variacoes == 1) {
            $variacoes = explode(';',$product_variations['types_variations']);

            if (count($variacoes) > 2) {
                return array('error' => true, 'data' => $this->lang->line('api_variation_limit'));
            }
        }

        // campos faltantes
        $checkRequiredFields = $this->checkRequiredFields($dataSql);
        if ($checkRequiredFields != "") {
            return array('error' => true, 'data' => $this->lang->line('api_all_fields_filled'). $checkRequiredFields);
        }

        // variações, se a quantidade de types_variations bate com a quantidade de itens no product_variations
        if ((isset($product_variations['product_variations']) || isset($product_variations['types_variations'])) && (isset($product_variations['types_variations']) && $product_variations['types_variations'] != "")) {

            if (isset($product_variations['types_variations']) && !isset($product_variations['product_variations'])) {
                return array('error' => true, 'data' => $this->lang->line('api_type_variation_found'));
            }

            if (!isset($product_variations['types_variations']) && isset($product_variations['product_variations'])) {
                return array('error' => true, 'data' => $this->lang->line('api_variation_found'));
            }

            if (count($product_variations['product_variations']) > 0 || $product_variations['types_variations'] != "") {
                $returnVariations = $this->createArrayProductsVariations($product_variations, $dataSql);
                if ($returnVariations['success'] === false) {
                    return array('error' => true, 'data' => $returnVariations['data']);
                }
                $arrVariations = $returnVariations['data'];
                $dataSql[$this->arrColumnsInsert['types_variations']['columnDatabase']] = $returnVariations['types_variations'] ?? $product_variations['types_variations'];
                $dataSql['qty'] = 0;
                foreach ($product_variations['product_variations'] as $var){
                    if (isset($var->qty) && (int)$var->qty <= 0) {
                        $var->qty = 0;
                    }
                    $dataSql['qty'] += $var->qty;
                }
            }
        }

        // Erros gerado no laço dos itens
        if ($erroColumn !== "") {
            return array('error' => true, 'data' => $erroColumn);
        }

        // Bloqueia de prazo por categoria.
        if (!empty($dataSql['category_id']) && $dataSql['category_id'] != '[""]') {
            $category_id = is_numeric($dataSql['category_id']) ? $dataSql['category_id'] : (json_decode($dataSql['category_id'])[0] ?? 0);
            if ($category_id) {
                $data_category = $this->model_category->getCategoryData($category_id);

                if ($data_category['blocked_cross_docking']) {
                    $dataSql['prazo_operacional_extra'] = $data_category['days_cross_docking'];
                }
            }
        }

        // Não existem campos para o insert
        if (count($dataSql) == 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_create'));
        }
        if (count($imgsUpload) > $limite_imagens_aceitas_api) {
            return array('error' => true, 'data' => $this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images'));
        }
        // Inicia transação
        $this->db->trans_begin();

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
            $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
            if (($dataSql['category_id'] == '[""]' && !$publishWithoutCategory) || $dataSql['brand_id'] == '[""]' || count($imgsUpload) === 0) {
                $dataSql['situacao'] = 1;
            } else {
                $dataSql['situacao'] = 2;
            }
        } else {
            if ($dataSql['category_id'] == '[""]' || $dataSql['brand_id'] == '[""]' || count($imgsUpload) === 0) {
                $dataSql['situacao'] = 1;
            } else {
                $dataSql['situacao'] = 2;
            }
        }

        // Upload de imagens
        $uploadImg = $this->uploadImageProduct($imgsUpload, true);

        $dataSql['image'] = isset($uploadImg['new_path']) ? $uploadImg['new_path'] : $uploadImg['path'];
        $dataSql['principal_image'] = $uploadImg['primary_image'] ?? null;
        //$sqlImage = $this->db->update_string('products', $arrUpdateProducts, array('id' => $prd_id));
        //$this->db->query($sqlImage);
        // Erro em upload de imagem
        if ($uploadImg['error'] != 0) {
            $this->db->trans_rollback();
            if ($uploadImg['error'] == 1) {
                return array('error' => true, 'data' => $uploadImg['data']);
            }
            if ($uploadImg['error'] == 2) {
                return array('error' => true, 'data' => $uploadImg['data']);
            }
            $this->deleteImgError($imgsUpload, isset($uploadImg['new_path']) ? $uploadImg['new_path'] : $uploadImg['path']);

            return array('error' => true);
        }

        if ($dataSql['category_id'] != '[""]' && !empty($dataSql['category_id'])){
            $dataSql['categorized_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        }

        // Inserção produto
        $prd_id = $this->model_products->create($dataSql);

        // bloqueia produto se necessário
        $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($dataSql, $prd_id);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->deleteImgError($imgsUpload, isset($uploadImg['new_path']) ? $uploadImg['new_path'] : $uploadImg['path']);
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $ordem = 0;
        foreach ($imgsUpload as $imgUpload) {
            $dataSqlImg = array(
                'store_id'      => $this->store_id,
                'company_id'    => $this->company_id,
                'ordem'         => $ordem,
                'prd_id'        => $prd_id,
                'variant'       => null,
                'original_link' => $imgUpload,
                'pathProd'      => $dataSql['image'],
                'pathVariant'   => null,
                'status'        => 0,
                'error'         => null
            );
            $this->model_products->createImage($dataSqlImg);
            $ordem++;
        }

        $newPriceVariation = null;
        $newListPriceVariation = null;
        $exist_image_variation = false;
        $primary_image_variation = null;
        foreach($arrVariations as $variation) {

            if ($newPriceVariation === null || $newPriceVariation < $variation['price']) {
                $newPriceVariation = $variation['price'];
            }
            if ($newListPriceVariation === null || $newListPriceVariation < $variation['list_price']) {
                $newListPriceVariation = $variation['list_price'];
            }

            if(isset($newPriceVariation) || isset($newListPriceVariation)){
                if(empty($newPriceVariation) && empty($newListPriceVariation)){
                    return array('error' => true, 'data' => $this->lang->line('application_prices_error'));
                }

                if((empty($newPriceVariation) && !empty($newListPriceVariation))){
                    $newPriceVariation = $newListPriceVariation;
                }

                if(empty($newListPriceVariation) && !empty($newPriceVariation)){
                    $newListPriceVariation = $newPriceVariation;
                }
            }

            if(isset($variation['qty']) && (int)$variation['qty'] <=0){
                $variation['qty'] = 0;
            }

            $variation['prd_id']    = $prd_id;
            $variation['sku']       = $variation['sku'] == null ? "{$dataSql['sku']}-{$variation['variant']}" : $variation['sku'];
            $variation['status']    = 1;
            $arrImages              = $variation['arrImages'];
            // removo o arr de imagens para não enviar na query
            unset($variation['arrImages']);

            if ($variation['image']) {
                $exist_image_variation = true;
            }

            // Upload de imagens
            $uploadImgVar = $this->uploadImageVariation($arrImages, $dataSql['image'], $variation['image'], true);
            $imageVariation =  $variation['image'];
            // Erro em upload de imagem
            if ($uploadImgVar['error'] != 0) {
                $this->db->trans_rollback();
                if ($uploadImgVar['error'] == 1) {
                    return array('error' => true, 'data' => $uploadImgVar['data']);
                }
                if ($uploadImgVar['error'] == 2) {
                    return array('error' => true, 'data' => $uploadImgVar['data']);
                }
                return array('error' => true);
            }

            if (is_null($primary_image_variation) && isset($uploadImgVar['primary_image'])) {
                $primary_image_variation = $uploadImgVar['primary_image'];
            }

            // inserir na tabela prd_variants com o sku e id do produto inserido
            $this->model_products->createvar($variation);

            $ordem = 0;
            if (is_array($arrImages) || is_object($arrImages)) {
                foreach ($arrImages as $arrImagesVar) {
                    $LinkImageVar = is_array($arrImagesVar) ? current($arrImagesVar) : $arrImagesVar;
                    if ($LinkImageVar == "") {
                        continue;
                    }
                    $dataSqlImgVar = array(
                        'store_id'      => $this->store_id,
                        'company_id'    => $this->company_id,
                        'ordem'         => $ordem,
                        'prd_id'        => $prd_id,
                        'variant'       => $variation['variant'],
                        'original_link' => $LinkImageVar,
                        'pathProd'      => $dataSql['image'],
                        'pathVariant'   => $variation['image'],
                        'status'        => 0,
                        'error'         => null
                    );
                    $this->model_products->createImage($dataSqlImgVar);
                    $ordem++;
                }
            }
        }

        if (count($imgsUpload) === 0 && $primary_image_variation) {
            $this->model_products->update(array('principal_image' => $primary_image_variation), $prd_id);
        }

        if ( $dataSql['category_id'] != '[""]' &&     $dataSql['brand_id'] != '[""]' &&       count($imgsUpload) === 0 &&     $exist_image_variation  ) {
            $this->model_products->markComplete($prd_id);
        }

        if ($newPriceVariation !== null) {
            $this->model_products->update(array('price' => $newPriceVariation), $prd_id);
        }
        if ($newListPriceVariation !== null) {
            $this->model_products->update(array('list_price' => $newListPriceVariation), $prd_id);
        }

        $this->db->trans_commit();

        $prd_mkt = $this->model_products_marketplace->newProduct($prd_id); // cria os preços e estoque por marketplace se não existir

        $this->createPriceByMarketplace($prd_id, $dataSql['store_id']);

        return array('error' => false);
    }

    private function update($data)
    {
        if (!$this->is_provider && !in_array('updateProduct', $this->user_permissions)) {
            return array('error' => true, 'data' => $this->lang->line('api_unauthorized_request'));
        }

        $dataSql    = array();
        $dataSqlDb  = $this->model_products->getProductBySkuAndStore($this->sku, $this->store_id);
        $erroColumn = array();
        $imgsUpload = array();
        $additional_successfully_update = '';
        $dataSqlDb_qty_old = $dataSqlDb['qty'];

        if (!isset($data->product)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_product_key'));
        }
        if (count((array)$data->product) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }

        $prd_id = $dataSqlDb['id'];
        $store_id = $dataSqlDb['store_id'];
        $canUpdateProduct = true;
        $integrations = $this->model_integrations->getPrdIntegration($prd_id);
        $api_integrations = $this->model_api_integrations->getIntegrationByStoreId($store_id);

        // Campos aceitos após a publicação.
        $arrColumnsUpdateAfterPublish = array_keys(array_filter($this->arrColumnsUpdate, function ($column){
            return $column['can_update_after_publish'];
        }));

        if (isset($this->user_email)) {
            $user_id = $this->model_users->getUserByEmail($this->user_email);
            // recuperar permissão no grupo do usuário, se pode solicitar um update
            $groupUser = $this->model_groups->getUserGroupByUserId($user_id[0]["id"]);
            foreach ($integrations as $integration) {
                // Produto já tem código SKU no marketplace e usuario não é administrador, não pode ser mais alterado.
                if ($canUpdateProduct && $integration['skumkt']) {
                    if (!$groupUser['only_admin']) {
                        $canUpdateProduct = false;
                    } else {
                        if (!in_array('disabledCategoryPermission', $this->user_permissions) && !in_array('category', $arrColumnsUpdateAfterPublish)) {
                            $arrColumnsUpdateAfterPublish[] = "category";
                        }
                    }
                }
            }
        } else{
            foreach ($integrations as $integration) {
                // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
                if ($canUpdateProduct && $integration['skumkt']) {
                    $canUpdateProduct = false;
                }
            }
        }

        #Verifica se a loja possui integração com via varejo
        $store_integration = null;
        if (in_array(
            $api_integrations['integration'],
            array(
                'viavarejo_b2b_casasbahia',
                'viavarejo_b2b_pontofrio',
                'viavarejo_b2b_extra'
         ))
        ) {
            $store_integration = 'viavarejo_b2b';
        }

        if($store_integration == 'viavarejo_b2b'){
            $arrColumnsUpdateAfterPublish[] = "images";
        }

        foreach ($data->product as $key => $value) {
            if (!array_key_exists($key, $this->arrColumnsUpdate) && $key != "user_id") {
                $erroColumn[] = $this->lang->line('api_parameter_not_match_field_update') . $key;
                break;
            }
            // Produto já integrado com markerplace . Pode atualizar os campos que tem 'can_update_after_publish' no array de '$this->arrColumnsUpdate'.
            // ATENÇÂO NUNCA REMOVER ESTE TESTE !!!!!!!
            if (!$canUpdateProduct && !in_array($key, $arrColumnsUpdateAfterPublish)) {
                continue;
            }

            // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
            if ($key == "price" && empty($value)) {
                $erroColumn[] = $this->lang->line('application_prices_error_por');
                break;
            }

            if ($key == "list_price" && empty($value)) {
                $erroColumn[] = $this->lang->line('application_prices_error_de');
                break;
            }

            $value = $this->setValueVariableCorrect($key, $value, 'U');

            // Upload file não adiciona no array de update
            if ($key === "images") {
                if (is_string($value)) {
                    $imgsUpload[] = $value;
                }
                if (is_array($value)) {
                    $imgsUpload = $value;
                }
                continue;
            }

            $this->prd_id_update = $prd_id;
            if ($key !== 'user_id'){
                $verify = $this->verifyFields($key, $value, "U");

                if (!$verify[0]) {
                    $erroColumn[] = $verify[1];
                    break;
                }

                $value = $verify[1];

                $dataSql[$this->arrColumnsUpdate[$key]['columnDatabase']] = $value;
            }
        }

        // Adiciona uma mensagem quando o produto já está publicado.
        $product_request = (array)$data->product;
        if (!$canUpdateProduct && empty($erroColumn) &&
            (
                empty($imgsUpload) && count($dataSql) != count($product_request) ||
                // Quando tem imagem, não é salvo na variável '$dataSql'.
                !empty($imgsUpload) && count($dataSql) != (count($product_request) - 1)
            )
        ) {
            $additional_successfully_update = sprintf($this->lang->line('api_product_already_published_only_updates_some_fields'), implode(', ', array_keys($dataSql)));
        }

        if (empty($dataSql) && !empty($data->product) && !$canUpdateProduct) {
            if($store_integration != 'viavarejo_b2b' || empty($imgsUpload)){
                return array('error' => true, 'data' => $this->lang->line('api_product_already_integrated'));
            }
        }

        if(!empty($dataSql['category_id']) && $dataSql['category_id'] != '[""]' && $dataSqlDb['category_id'] == '[""]'){
            $dataSql['categorized_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        }

        if (isset($dataSql['list_price'])) {
            if ($dataSql['list_price'] == 0) {
                $dataSql['list_price'] = $dataSql['price'];
            }
        }
        if (isset($dataSql['list_price']) && isset($dataSql['price'])) {
            if ($dataSql['list_price'] < $dataSql['price']) {
                $erroColumn[] = $this->lang->line('api_list_price_price');
            }
        }

        // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
        if(isset($dataSql['price']) || isset($dataSql['list_price'])){
            if(empty($dataSql['price']) && empty($dataSql['list_price'])){
                $erroColumn[] = $this->lang->line('application_prices_error');
            }

            // price em branco ou zero, desconsiderar na atualização
            if (
                (isset($dataSql['price']) && empty($dataSql['price']))
            ) {
                unset($dataSql['price']);
            }

            // list_price em branco ou zero, desconsiderar na atualização
            if (
                (isset($dataSql['list_price']) && empty($dataSql['list_price']))
            ) {
                unset($dataSql['list_price']);
            }

            // Existe price e list_price, serã validado para saber se o price é maior que o list_price
            if (isset($dataSql['price']) && isset($dataSql['list_price'])) {
                if ($dataSql['price'] > $dataSql['list_price']) {
                    $erroColumn[] = $this->lang->line('api_list_price_price');
                }
            }

            // price não deve ser maior que o list_price
            if (isset($dataSql['price']) && !isset($dataSql['list_price'])) {
                if ($dataSql['price'] > $dataSqlDb['list_price']) {
                    $erroColumn[] = $this->lang->line('api_list_price_price');
                }
            }

            // price não deve ser maior que o list_price
            if (isset($dataSql['list_price']) && !isset($dataSql['price'])) {
                if ($dataSqlDb['price'] > $dataSql['list_price']) {
                    $erroColumn[] = $this->lang->line('api_list_price_price');
                }
            }
        }

        if (count($erroColumn) > 0) {
            return array('error' => true, 'data' => implode(". ",$erroColumn));
        }

        if (count($dataSql) === 0 && count($imgsUpload) === 0 ) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }

        if (isset($dataSql['category_id']) && $dataSql['category_id'] == '[""]') {
            unset($dataSql['category_id']);
        }

        // Bloqueia de prazo por categoria.
        if (
            (!empty($dataSql['category_id']) && $dataSql['category_id'] != '[""]') ||
            (!empty($dataSqlDb['category_id']) && $dataSqlDb['category_id'] != '[""]')
        ) {
            $category_id = $dataSql['category_id'] ?? $dataSqlDb['category_id'];
            $category_id = is_numeric($category_id) ? $category_id : (json_decode($category_id)[0] ?? 0);
            if ($category_id) {
                $data_category = $this->model_category->getCategoryData($category_id);

                if ($data_category['blocked_cross_docking']) {
                    $dataSql['prazo_operacional_extra'] = $data_category['days_cross_docking'];
                }
            }
        }

        /** Atualizar o que vem e manter o que já tem sem alteração */
        foreach ($dataSqlDb as $key => $productDb) {
            if ($key == "id") {
                unset($dataSqlDb[$key]);
                continue;
            }
            if (
                !isset($dataSql[$key]) ||
                $dataSql[$key] == "" ||
                $dataSql[$key] == '[""]' ||
                $dataSql[$key] == $productDb
            ) {
                continue;
            }

            $dataSqlDb[$key] = $dataSql[$key];
        }

        $categoryComplete   = ($dataSqlDb['category_id'] != '[""]' && !isset($dataSql['category_id'])) || (isset($dataSql['category_id']) && $dataSql['category_id'] != '[""]');
        $brandComplete      = ($dataSqlDb['brand_id'] != '[""]' && !isset($dataSql['brand_id'])) || (isset($dataSql['brand_id']) && $dataSql['brand_id'] != '[""]');

        $is_on_bucket = $this->isOnBucket();
        $imageCount = $is_on_bucket ?
            count($this->bucket->listObjects('assets/images/product_image/'.$dataSqlDb['image'])['contents'])
            : $this->uploadproducts->countImagesDir($this->getPathImage());

        $imageComplete      = count($imgsUpload) !== 0 || $imageCount;

        if ($dataSqlDb['situacao'] == 1 && $categoryComplete && $brandComplete && $imageComplete) {
            $dataSql['situacao'] = 2;
        }

        $stockVariation = $this->getStockVariation();
        if (count($dataSql) == 1 && isset($dataSql['qty']) && $stockVariation !== false) {
            return array('error' => true, 'data' => $this->lang->line('api_product_contains_variation'));
        }

        if (isset($dataSql['qty']) && $stockVariation !== false) {
            $dataSql['qty'] = $stockVariation;
        }

        if (isset($dataSql['qty']) && (int)$dataSql['qty'] <= 0) {
            $dataSql['qty'] = 0;
        }

        if (isset($dataSql['qty']) && $dataSqlDb_qty_old != $dataSql['qty']) {
            $dataSql['stock_updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->trans_begin();

        if (count($dataSql) > 0) {
            $this->model_products->updateProductBySkuAndStore($dataSql, $this->sku, $this->store_id, 'Alterar por API');
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        if (($canUpdateProduct || $store_integration == 'viavarejo_b2b') && count($imgsUpload)) {
            $dataSql = [];
            $pathprd = $this->getPathImage();
            if (trim($pathprd) == '') {
                $this->db->trans_rollback();
                log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro ao pegar o path do produto '.' sku '.$this->sku.' loja '.$this->store_id );
                return array('error' => true, 'data' => 'CONTACTE O SUPORTE IMEDITAMENTE.PROBLEMA NO PRODUTO');
            }
            //            $this->uploadproducts->deleteImagesDir($pathprd);

            if (!$is_on_bucket) {
                $old_images = $this->uploadproducts->getImagesFileByPath($pathprd);
                $uploadImg = $this->uploadImageProduct($imgsUpload);

                if (!empty($uploadImg['error'])) {
                    $this->uploadproducts->deleteImagesDir($pathprd, $old_images);
                    $this->db->trans_rollback();
                    if ($uploadImg['error'] == 1) {
                        return array('error' => true, 'data' => $uploadImg['data']);
                    }
                    if ($uploadImg['error'] == 2) {
                        return array('error' => true, 'data' => $uploadImg['data']);
                    }

                    return array('error' => true);
                }

                // Altera para ser produto no bucket.
                $this->db->update("products", array(
                    "is_on_bucket" => 1,
                ), array('id' => $prd_id));

                $this->uploadproducts->deleteImgError($old_images, $pathprd);

                // Adiciona o nome da pasta no produto
                $dataSql['principal_image'] = $uploadImg['primary_image'] ?? null;
            } else {
                if ($pathprd != '') {
                    $toDelete = "assets/images/product_image/" . $pathprd;

                    // Verifica se não está tentando deletar a pasta toda.
                    if (substr(str_replace("/", "", trim($toDelete)), -13) != 'product_image') {
                        $this->bucket->deleteDirectory("assets/images/product_image/" . $pathprd);
                    }
                }
                $uploadImg = $this->uploadImageProduct($imgsUpload);

                if (!empty($uploadImg['error'])) {
                    $this->db->trans_rollback();
                    if ($uploadImg['error'] == 1) {
                        return array('error' => true, 'data' => $uploadImg['data']);
                    }
                    if ($uploadImg['error'] == 2) {
                        return array('error' => true, 'data' => $uploadImg['data']);
                    }

                    return array('error' => true);
                }

                // Adiciona o nome da pasta no produto
                $dataSql['principal_image'] = $uploadImg['primary_image'] ?? null;
            }
            $this->model_products->updateProductBySkuAndStore($dataSql, $this->sku, $this->store_id);

            $this->model_products->deleteImage($pathprd);
            $ordem = 0;
            foreach ($imgsUpload as $arrImages) {
                $LinkImage = is_array($arrImages) ? current($arrImages) : $arrImages;
                if ($LinkImage == "") continue;
                $dataSqlImg = array(
                    'store_id'      => $this->store_id,
                    'company_id'    => $this->company_id,
                    'ordem'         => $ordem,
                    'prd_id'        => $prd_id,
                    'variant'       => null,
                    'original_link' => $LinkImage,
                    'pathProd'      => $pathprd,
                    'pathVariant'   => null,
                    'status'        => 0,
                    'error'         => null
                );
                $this->model_products->createImage($dataSqlImg);
                $ordem++;
            }
        }

        // bloqueia produto se necessário
        $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($this->model_products->getProductData(0, $prd_id), $prd_id);

        $this->db->trans_commit();

        $response = array('error' => false);

        // Mensagemm adicional quando o produto foi atualizado com sucesso.
        if (!empty($additional_successfully_update)) {
            $response['data'] = $additional_successfully_update;
        }

        return $response;
    }

    private function getStockVariation()
    {
        $countVar = 0;
        $prd_id = $this->getCodeForSku();

        $dataVariations = $this->model_products->getVariantsByProd_id($prd_id);
        if (empty($dataVariations)) {
            return false;
        }

        foreach($dataVariations as $var) {
            $countVar += $var['qty'];
        }

        return $countVar;
    }

    private function getCodeForSku()
    {
        $product = $this->model_products->getProductBySkuAndStore($this->sku, $this->store_id);
        return $product['id'];
    }

    private function checkRequiredFields($dataSql, $data = null)
    {
        $data = $data ?: $this->arrColumnsInsert;
        foreach ($data as $key => $prop) {
            if (!array_key_exists($prop['columnDatabase'], $dataSql) && !in_array($prop['columnDatabase'], $this->unverifiedFieldsBD)) {
                return $key;
            }
        }
        return "";
    }

    private function createArrayProductsVariations($product_variations, $dataProd)
    {
        $countVariant = 0;
        $arr = array();
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        $variantSku = array();
        $check_values_variations = array();

        foreach ($product_variations['product_variations'] as $variation) {
            $variation = (array)$variation;
            $variation = $this->cleanGet($variation);

            if (!isset($variation['qty']))
                return array('success' => false, 'data' => $this->lang->line('api_qty_not_informed_variation'));

            if (!isset($variation['sku']) || $variation['sku'] == '')
                return array('success' => false, 'data' => $this->lang->line('api_sku_not_informed_variation'));

            if ($variation['sku'] == $dataProd['sku'])
                return array('success' => false, 'data' => $this->lang->line('api_sku_variation_different'));

            if ($this->checkSkuAvailable($variation['sku']))
                return array('success' => false, 'data' => $this->lang->line('api_sku_in_use_other')." SKU: {$variation['sku']}");
            if(!$this->validateSkuSpace($variation['sku'])){
                return array('success' => false, 'data' => $this->getMessagemSkuFormatInvalid('variação'));
            }
            if(!$this->validateLengthSku($variation['sku'])){
                return array('success' => false, 'data' => $this->getMessageLenghtSkuInvalid('variação'));
            }

            if(in_array($variation['sku'], $variantSku)) {
                return array('success' => false, 'data' => $this->lang->line('api_sku_equal')." SKU: {$variation['sku']}");
            }

            $checkVariation = $variation;
            unset($checkVariation['sku']);
            unset($checkVariation['qty']);
            unset($checkVariation['price']);
            unset($checkVariation['list_price']);
            unset($checkVariation['EAN']);
            unset($checkVariation['ean']);
            unset($checkVariation['images']);
            $variationInSku = array_keys($checkVariation);

            $typesVariations = $product_variations['types_variations'];
            $valuesVariations = '';
            $typeVariationsList = [];
            $valuesVariationsList = [];

            $typeVariations = [];
            foreach (explode(";", $product_variations['types_variations']) as $type) {
                list($type, $variation) = $this->fetchCustomAttributesMapByCriteria($type, $variation);
                if (!key_exists($this->type_variation[$type], $variation))
                    return array('success' => false, 'data' => $this->lang->line('api_the') . $this->type_variation[$type] . $this->lang->line('api_type_not_declared_product') . " SKU: {$variation['sku']}. Types: (" . implode(',', $variationInSku) . ")");
                $typeVariations[] = $type;
            }
            $typeVariations = $this->sortVariationTypesByName($typeVariations);
            foreach($typeVariations as $type){
                switch (strtolower($type)) {
                    case "cor":
                        $valuesVariationsList[] = $variation["color"];
                        $typeVariationsList[] = 'Cor';
                        break;
                    case "voltagem":
                        if ($variation["voltage"] == 'bivolt') {
                            $variation["voltage"] = 'Bivolt';
                        }
                        // Valida os valores de voltagem
                        /*foreach (array('110','220') as $valueVoltageValidate) {
                            if (likeText("%$valueVoltageValidate%", strtolower($variation["voltage"]))) {
                                $variation["voltage"] = $valueVoltageValidate;
                            }
                        }
                        if (!in_array($variation["voltage"],array('110','220','110V','220V','Bivolt'))) {
                        	return array('success' => false, 'data' => $this->lang->line('api_voltage_variation'));
						}*/
                        //$existVolt = in_array($variation["voltage"], array('110', '220')) ? 'V' : '';
                        $valuesVariationsList[] = $variation["voltage"];
                        $typeVariationsList[] = 'VOLTAGEM';
                        break;
                    case "tamanho":
                        $valuesVariationsList[] = $variation["size"];
                        $typeVariationsList[] = 'TAMANHO';
                        break;
                    case "sabor":
                        $valuesVariationsList[] = $variation["flavor"];
                        $typeVariationsList[] = 'SABOR';
                        break;
                    case "grau":
                        $valuesVariationsList[] = $variation["degree"];
                        $typeVariationsList[] = 'GRAU';
                        break;
                    case "lado":
                        $valuesVariationsList[] = $variation["side"];
                        $typeVariationsList[] = 'LADO';
                        break;
                    /*default:
                        return array('success' => false, 'data' => $this->lang->line('api_type_variation'));*/
                }
            }
            $typesVariations = !empty($typeVariationsList) ? implode(';', $typeVariationsList) : $typesVariations;
            $valuesVariations = !empty($valuesVariationsList) ? implode(';', $valuesVariationsList) : $valuesVariationsList;

            if(in_array($valuesVariations, $check_values_variations)) {
                return array('success' => false, 'data' => $this->lang->line('api_variation_value_equal')." SKU: {$variation['sku']}");
            }

            $price = $dataProd['price'];
            if ($this->usePriceVariation && isset($variation['price'])) {
                $price = (float)number_format($variation['price'], 2, '.', '');
            }
            $list_price = $price;

            if (array_key_exists('list_price',$dataProd)) {
                if (!is_null($dataProd['list_price'])) {
                    $list_price = $dataProd['list_price'];
                }
            }

            if ($this->usePriceVariation && isset($variation['list_price'])) {
                $list_price = (float)number_format($variation['list_price'], 2, '.', '');
            }

            if(is_null($list_price) || $list_price == 0 || empty($list_price)) $list_price = $price;
            if (($list_price < $price))
                return array('success' => false, 'data' => $this->lang->line('api_list_price_price'). " SKU: {$variation['sku']}");

            $images = null;
            if (isset($variation['images']) && count((array)$variation['images']) > 0) {
                $images = (array)$variation['images'];
                if (count($images) > $limite_imagens_aceitas_api) {
                    return array('success' => false, 'data' => $this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images_variations') . "SKU: {$variation['sku']}");
                }
            }
            $ean = '';
            if (isset($variation['ean']) || isset($variation['EAN'])) {
                $eanCheck = $variation['ean'] ?? $variation['EAN'];
                if (!$this->model_products->ean_check($eanCheck)) {
                    return array('success' => false, 'data' => $this->lang->line('api_invalid_ean') . " SKU: {$variation['sku']}");
                }
                $ean = $eanCheck;
            }

            // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
            // PRECO POR
            if(isset($variation['price']) && empty($variation['price'])){
                if(isset($variation['list_price']) && !empty($variation['list_price'])){
                    $variation['price'] = $variation['list_price'];
                } else {
                    return array('success' => false, 'data' => $this->lang->line('application_prices_error_por') . " SKU: {$variation['sku']}");
                }
            }

            // PRECO DE
            if(isset($variation['list_price']) && empty($variation['list_price'])){
                if(isset($variation['price']) && !empty($variation['price'])){
                    $variation['list_price'] = $variation['price'];
                } else {
                    return array('success' => false, 'data' => $this->lang->line('application_prices_error_de') . " SKU: {$variation['sku']}");
                }
            }

            if (!empty($ean)) {
                if (in_array($ean, $this->arrEANsCheck)) {
                    return array('success' => false, 'data' => $this->lang->line('api_ean_same_variation') . "EAN:{$ean}. SKU: {$variation['sku']}");
                }

                $exist =$this->model_products->VerifyEanUnique($ean,$this->store_id ,null);
                if ($exist) {
                    return array('success' => false, 'data' => "O mesmo EAN não é permitido em mais de um produto ou variação. Este EAN {$ean} está sendo usado no produto de id ".$exist);
                }

                array_push($this->arrEANsCheck, $ean);
            }

            array_push($arr, array(
                'prd_id'    => null,
                'name'      => $valuesVariations ?? '',
                'sku'       => trim($variation['sku']),
                'qty'       => $variation['qty'],
                'variant'   => $countVariant++,
                'price'     => $price,
                'list_price'=> $list_price,
                'image'     => md5(round(microtime(true) * 100000).rand(11111111,99999999)),
                'EAN'       => $ean,
                'arrImages' => $images
            ));

            $variantSku[] = $variation['sku'];
            $check_values_variations[] = $valuesVariations;

        }
        return array('success' => true, 'data' => $arr, 'types_variations' => $typesVariations ?? '');
    }

    private function setValueVariableCorrect($key, $value, $type)
    {

        if (!isset($this->arrColumnsInsert[$key])) {return $value;}
        switch ($type == 'I' ? $this->arrColumnsInsert[$key]['type'] : $this->arrColumnsUpdate[$key]['type']) {
            case 'S': return (string)$value;
            case 'A': return (array)$value;
            case 'F': return (float)$value;
            case 'I': return (int)$value;
            default:  return $value;
        }
    }

    private function verifyFields($key, $value, $type)
    {
        $value = is_array($value) || is_object($value) ? $value : xssClean($value, false, $key !== 'description');
        $value_ok = array(true, $value);

        if ($key === 'status' && in_array($value, [Model_products::DELETED_PRODUCT])) {
            return array(false, $this->lang->line('api_not_move_product_trash'));
        }

        if ($key === 'price' || $key === 'list_price') {
            if ($value === "" || (float)$value <= 0) return array(false, $this->lang->line('api_price_zero'));
            $value_ok = array(true, (float)number_format($value, 2, '.', ''));
        }

        if ($key === 'sku' || $key === 'SKU') {
            if ($value === "") return array(false, $this->lang->line('api_sku_blank'));

            $skuAvailable = $this->verifySKUAvailable($value);
            if ($skuAvailable) {
                if ($skuAvailable['is_variation_grouped'] ?? false) {
                    $client = new Client([
                        'verify' => false, // no verify ssl
                        'allow_redirects' => true
                    ]);

                    $json_option_variation = json_decode($this->request_body, true);
                    $variation = array();

                    if (array_key_exists('active', $json_option_variation['product'])) {
                        $variation['status'] = $json_option_variation['product']['active'];
                    }
                    if (array_key_exists('price', $json_option_variation['product'])) {
                        $variation['price'] = $json_option_variation['product']['price'];
                    }
                    if (array_key_exists('list_price', $json_option_variation['product'])) {
                        $variation['list_price'] = $json_option_variation['product']['list_price'];
                    }
                    if (array_key_exists('qty', $json_option_variation['product'])) {
                        $variation['qty'] = $json_option_variation['product']['qty'];
                    }
                    if (array_key_exists('ean', $json_option_variation['product'])) {
                        $variation['ean'] = $json_option_variation['product']['ean'];
                    }

                    $options = [
                        'json' => [
                            'variation' => $variation
                        ],
                        'headers' => getallheaders()
                    ];

                    try {
                        $client->put(
                            $this->getInternalUrl() . "/Api/V1/Variations/sku/$skuAvailable[sku]/{$json_option_variation['product']['sku']}",
                            $options
                        );
                    } catch (InvalidArgumentException | GuzzleException | BadResponseException $exception) {
                        $error_message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
                        $error_message = json_decode($error_message, JSON_UNESCAPED_UNICODE)['message'] ?? $this->lang->line('api_no_data_update');
                        throw new Exception(json_encode($this->returnError($error_message)), REST_Controller::HTTP_BAD_REQUEST);
                    }

                    throw new Exception(json_encode(array('success' => true, "message" => $this->lang->line('api_product_updated'))), REST_Controller::HTTP_OK);
                }
                return array(false, $this->lang->line('api_sku_in_use'));
            }

            $value_ok = array(true, str_replace("/", "-", trim($value)));
        }
        if ($key === "active") {
            if ($value != "enabled" && $value != "disabled")
                return array(false, $this->lang->line('api_active_field'));

            $value_ok = array(true, $value == "enabled" ? self::MAP_STATUS[self::STATUS_ENABLED] : self::MAP_STATUS[self::STATUS_DISABLED]);
        }
        if ($key === "ean" || $key === "EAN") {
            $this->load->model('model_products');
            if (!$this->model_products->ean_check($value)){
                return array(false, $this->lang->line('api_ean_invalid'));
            }
            // rick            
            if ((!is_null( $this->prd_id_update)) && ($value != '' )) {
                $exist =$this->model_products->VerifyEanUnique($value, $this->store_id, $this->prd_id_update);
                if ($exist) {
                    return array(false, "O mesmo EAN não é permitido em mais de um produto ou variação. Este EAN {$value} está sendo usado no produto de id ".$exist);
                }
            }
        }

        if ($key === "ncm" || $key === "NCM") {
            $value = filter_var(preg_replace('~[.-]~', '', $value), FILTER_SANITIZE_NUMBER_INT);
            $value = $value == 0 ? null : $value;
            if (strlen($value) != 8 && $value != "")
                return array(false, $this->lang->line('api_invalid_ncm'));

            $value_ok = array(true, trim($value));
        }
        if ($key === "origin" && ($value < 0 || $value > 8)) {
            return array(false, $this->lang->line('api_origin_product_code'));
        }
        //        if ($key === "unity" || $key === "manufacturer" || $key === "category") {
        if ($key === "unity" || $key === "manufacturer" || $key === "category") {
            $value = trim($value);
            $codeInfoProduct = '';

            if ($key === "unity") {
                $codeInfoProduct = $this->getCodeInfo('attribute_value', 'value', $value);
            }
            elseif ($key === "manufacturer") {
                $codeInfoProduct = $this->getCodeInfo('brands', 'name', $value);
            }
            elseif ($key === "category") {
                if (!in_array('disabledCategoryPermission', $this->user_permissions)) {
                    $codeInfoProduct = $this->getCodeInfo('categories', 'name', trim($this->strReplaceName($value)), "AND active = 1");
                }
            }

            if ($codeInfoProduct) {
                $value_ok = array(true, "[\"$codeInfoProduct\"]");
            }
            else {
                $required = $type == "I" ? $this->arrColumnsInsert[$key]['required'] : $this->arrColumnsUpdate[$key]['required'];
                if ($key === "unity" && $required) {
                    return array(false, $this->lang->line('api_invalid_unit'));
                }
                elseif ($key === "manufacturer" && $required) {
                    return array(false, $this->lang->line('api_invalid_manufacturer'));
                }
                elseif ($key === "category" && $required && !empty(trim($value))) {
                    return array(false, $this->lang->line('api_invalid_category'));
                }
                else {
                    $value_ok = array(true, '[""]');
                }
            }
        }
        if ($key === "types_variations") {
            $varList = [];
            if (count($value) === 1 && $value[0] === "") {
                $value_ok = array(true, "");;
            }
            else {
                foreach ($value as $type_v) {
                    switch ($type_v) {
                        case "size":
                            $varList[] = "TAMANHO";
                            break;
                        case "color":
                            $varList[] = "Cor";
                            break;
                        case "voltage":
                            $varList[] = "VOLTAGEM";
                            break;
                        case "flavor":
                            $varList[] = "SABOR";
                            break;
                        case "degree":
                            $varList[] = "GRAU";
                            break;
                        case "side":
                            $varList[] = "LADO";
                            break;
                        default:
                            list($type, $variation) = $this->fetchCustomAttributesMapByCriteria($type_v, []);
                            if ($type == $type_v) {
                                break;
                                //                                return array(false, $this->lang->line('api_type_variation'));
                            }
                            $varList[] = $type_v;
                    }
                }
                $value_ok = array(true, !empty($varList) ? implode(';', $varList) : "");
            }
        }
        if ($key === 'net_weight' && $value <= 0) {
            return array(false, $this->lang->line('api_net_weight_zero'));
        }
        if ($key === 'gross_weight' && $value <= 0) {
            return array(false, $this->lang->line('api_gross_weight_zero'));
        }
        if ($key === 'width' && $value <= 0) {
            return array(false, $this->lang->line('api_width_zero'));
        }
        if ($key === 'height' && $value <= 0) {
            return array(false, $this->lang->line('api_height_zero'));
        }
        if ($key === 'depth' && $value <= 0) {
            return array(false, $this->lang->line('api_depth_zero'));
        }
        if ($key === 'items_per_package') {
            $value = (int)$value;
            if ($value <= 0) {
                $value = 1;
            }
            $value_ok = array(true, $value);
        }
        if ($key === 'name') {
            $value_ok = array(true, trim($value));
            $value_ok = $this->strReplaceName($value_ok);

            if (!$this->validateLengthName($value)){
                return array(false, $this->getMessageLenghtNameInvalid());
            }
        }
        if ($key === 'sku') {
            if(!$this->validateSkuSpace($value)){
                return array(false, $this->getMessagemSkuFormatInvalid());
            }
            if(!$this->validateLengthSku($value)){
                return array(false, $this->getMessageLenghtSkuInvalid());
            }
        }
        if ($key === 'description') {
            if ($value === "") return array(false, $this->lang->line('api_description_blank'));

            if (!$this->validateLengthDescription($value))
                return array(false, $this->getMessageLenghtDescriptionInvalid());

            $value_ok = array(true, strip_tags_products($value, $this->allowable_tags));
        }

        if ($key === 'extra_operating_time' && $value > 100) {
            return array(false, $this->lang->line('api_extra_operating_time'));
        }

        return $value_ok;
    }

    private function createArrayIten(array $product = null, $deletedItens = false): array
    {

        // Variaveis
        $arrVariations = array();
        $arrVariationsAttr = array();
        //$arrSpecifications = array();
        $arrPublishedMarketplace = array();
        $arrLinkOffer = array();

        if (!empty($this->sku) && empty($product)) {
            $product = $this->model_products->getProductsByStore($this->store_id, null, null, $this->sku);
        }

        if (empty($product)) {
            return array('error' => true, 'data' => $this->lang->line('api_product_not_found'));
        }

        if(!$deletedItens){
            if ($product['status'] == Model_products::DELETED_PRODUCT) {
                $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_trash_product'). " ( {$this->sku} )", "W");
                return array('error' => true, 'data' => $this->lang->line('api_trash_product_api'));
            }
        }

        /**
         * Imagens do produto
         */
        $arrImages = array();
        if (strpos(".." . $product['image'], "http") == 0) {
            if ($product['product_catalog_id']) {
                $imagesDir = "assets/images/catalog_product_image/".$product["product_catalog_id"];
            } else {
                $imagesDir = "assets/images/product_image/".$product["image"];
            }

            // Produto não está no bucket, busca no local.
            if (!$product['is_on_bucket']) {
                $imagesDir = FCPATH . $imagesDir;
                $images = array();
                if (is_dir($imagesDir)) {
                    $images = scandir($imagesDir);
                }

                foreach ($images as $image) {
                    if (($image != ".") && ($image != "..") && ($image != "") && (!is_dir("$imagesDir/$image"))) {
                        $arrImages[] = baseUrlPublic("assets/images/product_image/$product[image]/$image");
                    }
                }
            } else {
                // Busca as imagens do produto.
                $result = $this->bucket->getFinalObject($imagesDir);
                // Caso tenha dado certo, retorna na url.
                if ($result['success']) {
                    foreach ($result['contents'] as $key => $value) {
                        $arrImages[] = $value['url'];
                    }
                }
            }
        }

        /**
         * Variações de produto
         */
        if ($product['has_variants'] != "") {
            $variants = $this->model_products->getVariantsByProd_id($product['id']);
            foreach ($variants as $product_var) {
                $values_var = array();
                if ($product_var['sku'] === null) {
                    continue;
                }

                $specificationsVal = explode(";", $product_var['name']);
                $specificationsKey = explode(";", $product['has_variants']);
                foreach ($specificationsVal as $key => $value_var) {
                    $values_var[] = array($this->type_variation[$specificationsKey[$key]] => $value_var);
                }

                if ($product['product_catalog_id']) {
                    $imagesVarDir = "assets/images/catalog_product_image/$product[product_catalog_id]/$product_var[image]";
                } else {
                    $imagesVarDir = "assets/images/product_image/$product[image]/$product_var[image]";
                }

                $arrImagesVar = array();

                // Verifica se o produto está no bucket.
                if (!$product['is_on_bucket']) {
                    // Adiciona o path.
                    $imagesVarDir = FCPATH . $imagesVarDir;
                    $imagesVar = array();
                    if (is_dir($imagesVarDir)) {
                        $imagesVar = scandir($imagesVarDir);
                    }

                    // Percorre cada imagem da variante.
                    foreach ($imagesVar as $imageVar) {
                        if (($imageVar != ".") && ($imageVar != "..") && ($imageVar != "")) {
                            $arrImagesVar[] = baseUrlPublic('assets/images/product_image/' . $product['image'] . '/' . $product_var['image'] . '/' . $imageVar);
                        }
                    }
                } else {
                    // Busca as imagens das variantes do produto.
                    $result = $this->bucket->getFinalObject($imagesVarDir);

                    // Caso tenha dado certo, retorna na url.
                    if ($result['success']) {
                        foreach ($result['contents'] as $key => $value) {
                            $arrImagesVar[] = $value['url'];
                        }
                    }
                }

                $arrVariation = array(
                    "variant_id"    => $product_var['id'],
                    "sku"           => $catalog_variant['sku'] ?? $product_var['sku'],
                    "qty"           => $this->changeType($catalog_variant['quantity'] ?? $product_var['qty'], "float"),
                    "EAN"           => $this->changeType($catalog_variant['ean'] ?? $product_var['EAN'], "string"),
                    "price"         => $this->changeType($product_var['price'], "float"),
                    "list_price"    => $this->changeType($product_var['list_price'], "float"),
                    'images'        => $arrImagesVar,
                    "variant"       => $values_var,
                );

                $arrPublishedMarketplaceVariation = array();
                foreach ($this->getMarketplacesPublished($product['id'], $product_var['variant']) as $publishedMarketplace) {
                    $arrPublishedMarketplaceVariation[$publishedMarketplace['int_to']] = $publishedMarketplace['skumkt'];
                }

                if (count($arrPublishedMarketplaceVariation) > 0) {
                    $arrVariation["published_marketplace"] = $arrPublishedMarketplaceVariation;
                }

                $skuMktPublished = $this->model_integrations->getPrdIntegration($product['id'])[0];
                //? Necessaria a validação para Vertem Store #https://conectala.atlassian.net/browse/OEP-1931
                if (!empty($skuMktPublished) && $skuMktPublished['int_to'] == "VS") {
                    $arrVariation["published_marketplace"] = $skuMktPublished['skumkt'] . '-' . $product_var['variant'];
                }

                $arrVariations[] = $arrVariation;
            }
        }

        /**
         * Atributos do produto
         */
        if (!empty($product['attribute_value_id']) && $product['attribute_value_id'] != 'null') {
            if (is_numeric($product['attribute_value_id']))
                $product['attribute_value_id'] = '["'.$product['attribute_value_id'].'"]';

            foreach (json_decode($product['attribute_value_id']) as $cod_attribute) {
                if ($cod_attribute == "") {
                    continue;
                }
                $attribute = $this->model_attributes->getAttributeValueDataById($cod_attribute);

                if ($attribute !== null) {
                    $arrVariationsAttr[] = $attribute['value'];
                }
            }
        }

        /**
         * Produtos publicados
         */
        foreach ($this->getMarketplacesPublished($product['id']) as $publishedMarketplace) {
            $arrPublishedMarketplace[$publishedMarketplace['int_to']] = $publishedMarketplace['skumkt'];

            if (!is_null($publishedMarketplace['ad_link'])) {
                $ad_links = json_decode($publishedMarketplace['ad_link'], true);
                if (json_last_error() === 0) {
                    foreach ($ad_links as $link) {
                        $arrLinkOffer[] = array (
                            'name' => $link['name'],
                            'href' => $link['href']
                        );
                    }
                } else {
                    if (strpos($publishedMarketplace['ad_link'], 'http') !== false) {
                        $arrLinkOffer[] = array (
                            'name' => $publishedMarketplace['int_to'],
                            'href' => $publishedMarketplace['ad_link']
                        );
                    }
                }
            }
        }

        $originPrice = $this->model_products->getOriginPrice($product['EAN']);
        $suggestPrice = $this->model_products->getSuggestedPrice($product['EAN']);

        $product_array = array(
            "store_id"          => $product['store_id'],
            "product_id"        => $product['id'],
            "product_catalog_id"=> $product['product_catalog_id'],
            "sku"               => $product['sku'],
            "name"              => $product['name'],
            "description"       => $product['description'],
            "status"            => $product['status'] == 2 ? "disabled" : "enabled",
            "qty"               => $this->changeType($product['qty'], "float"),
            "price"             => $this->changeType($product['price'], "float"),
            "list_price"        => $this->changeType($product['list_price'], "float"),
            "weight_gross"      => $this->changeType($product['peso_bruto'], "float"),
            "weight_liquid"     => $this->changeType($product['peso_liquido'], "float"),
            "height"            => $this->changeType($product['altura'], "float"),
            "width"             => $this->changeType($product['largura'], "float"),
            "length"            => $this->changeType($product['profundidade'], "float"),
            "height_liquid"     => $this->changeType($product['actual_height'], "float"),
            "width_liquid "     => $this->changeType($product['actual_width'], "float"),
            "length_liquid"     => $this->changeType($product['actual_depth'], "float"),
            "items_per_package" => $this->changeType($product['products_package'], "int"),
            "brand"             => $product['brand_name'],
            "ean"               => $this->changeType($product['EAN'], "string"),
            "ncm"               => $this->changeType($product['NCM'], "number"),
            "values"            => array(
                array(
                    "discount"          => $originPrice > 0 ? (round((1-($suggestPrice/ $originPrice))*100, 2)."%") : "0%",
                    "max_discount"      => $product['maximum_discount_catalog'] ?? null,
                    "flag_inativate_prd"=> $product['maximum_discount_catalog'] != null,
                    "price_suggest"     => $suggestPrice,
                    "price_origin"      => $originPrice

                )
            ),
            "categories"        => array(
                array(
                    "code" => $this->changeType($product['category_id'], "int"),
                    "name" => $product['category_name']
                )
            )
        );

        $product_array["ean"] = $product['EAN'];
        $element = array(
            "vtex_ref_id"   => $this->model_products->getRefIdVtex($product['EAN']),
            "vtex_sku_id"   => $this->model_products->getSkuIdVtex($product['EAN']),
        );
        $product_array = array_slice($product_array, 0, 7) + $element + array_slice($product_array, 7);

        // CASO SEJA GRUPO SOMA, SERÃO INSERIDOS MAIS ITENS NO ARRAY DE PRODUTO
        if ($this->sellercenter && $this->sellercenter["value"] == "somaplace") {
            $product_array["ean"] = $this->changeType($product['EAN']);
            $element = array(
                "manufacturer_code" => $this->changeType($product['codigo_do_fabricante']),
                "unpacked_width" => $this->changeType($product['actual_width'], "float"),
                "unpacked_height" => $this->changeType($product['actual_height'], "float"),
                "unpacked_lenght" => $this->changeType($product['actual_height'], "float"),
                "depth" => $this->changeType($product['actual_depth'], "float"),
                "warranty" => $this->changeType($product['garantia'], "float"),
                "origin" => $this->changeType($product['origin'], "float"),
                "operating_term" => $this->changeType($product['prazo_operacional_extra'], "float")
            );
            $product_array = array_slice($product_array, 0, 7) + $element + array_slice($product_array, 7);
        }

        // Criar array
        $product_return = array(
            'product' => $product_array
        );
        if (count($arrImages) > 0) {
            $product_return['product']["images"] = $arrImages;
        }
        if (count($arrVariations) > 0) {
            $product_return['product']["variations"] = $arrVariations;
        }
        if (count($arrVariationsAttr) > 0) {
            $product_return['product']["variation_attributes"] = $arrVariationsAttr;
        }
        /*if (count($arrSpecifications) > 0) {
            $product_return['product']["specifications"] = $arrSpecifications;
        }*/
        if (count($arrPublishedMarketplace) > 0) {
            $product_return['product']["published_marketplace"] = $arrPublishedMarketplace;
        }
        if (count($arrLinkOffer) > 0) {
            $product_return['product']["marketplace_offer_links"] = $arrLinkOffer;
        }

        $product_return['product']["stock_updated_at"] = $product['stock_updated_at'];
        $product_return['product']["stock_updated_before"] = $product['stock_updated_before'];

        return $product_return;
    }

    private function getMarketplacesPublished(int $prd_id, int $variant = null)
    {
        $sql = "SELECT * FROM prd_to_integration where prd_id = ?";
        if (!is_null($variant) && $variant !== '') {
            $sql .= " AND variant = '$variant'";
        }
        return $this->db->query($sql, array($prd_id))->result_array();
    }

    private function getDataProduct()
    {
        $sql = "SELECT 
                p.id as code_p,
                p.has_variants,
                p.image as image_p,
                p.attribute_value_id,
                p_v.id as variant_id,
                p_v.name as variant_name,
                p_v.image as variant_images,
                p_v.sku as variant_sku,
                p_v.qty as variant_qty,
                p_v.price as variant_price,
                p_v.list_price as variant_list_price,
                p_v.EAN as variant_EAN,
                p_v.image as variant_image,
                p_v.image as variant,
                c.id as category_id,
                c.name as category_name,
                p.sku as product_sku,
                p.name as product_name,
                p.description as product_description,
                p.status as product_status,
                p.qty as product_qty,
                p.price as product_price,
                p.list_price as product_list_price,
                p.peso_bruto as product_peso_bruto,
                p.peso_liquido as product_peso_liquido,
                p.altura as product_altura,
                p.largura as product_largura,
                p.profundidade as product_profundidade,
                p.products_package,
                p.store_id as store_id,                
                p.NCM as product_ncm,
                p.EAN as product_ean,
                b.name as brand_name,
                p.product_catalog_id as catalog_id
                FROM products as p 
                LEFT JOIN categories as c ON c.id = left(substr(p.category_id,3),length(p.category_id)-4)
                LEFT JOIN brands as b ON b.id = left(substr(p.brand_id,3),length(p.brand_id)-4)
                LEFT JOIN prd_variants as p_v ON p.id = p_v.prd_id 
                WHERE ";

        if (!empty($this->productFilters)) {
            $this->productFilters['company_id'] = (int)$this->db->escape($this->productFilters['company_id']) ?? $this->company_id;
            $this->productFilters['store_id'] = (int)$this->db->escape($this->productFilters['store_id']) ?? $this->store_id;
            $sql .= "p.id = ". (int) $this->db->escape($this->productFilters['id']) ;
            $sql .= "AND p.company_id = {$this->productFilters['company_id']} ";
            $sql .= "AND p.store_id = {$this->productFilters['store_id']} ";
            if (!empty($this->productFilters['var_ids'])) {
                $varIds = implode(',', $this->productFilters['var_ids']);
                $sql .= "AND p_v.id IN ({$varIds}) ";
            }
        } else {
            $sql .= "p.sku = ".$this->db->escape($this->sku)." AND p.store_id = ".$this->db->escape($this->store_id);
        }

        return $this->db->query($sql);
    }

    private function getDataProductUnitario()
    {
        $sql = "SELECT * FROM products WHERE sku = ? AND store_id = ?";
        return $this->db->query($sql, array($this->sku, $this->store_id));
    }

    private function uploadImageProduct($arrImg, $newImages = false)
    {
        $erroImage = array('error' => 0);
        $dirImage = "";
        $targetDir = "";
        if ($newImages) {
            $targetDir = 'assets/images/product_image/';
            $dirImage = Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
            $targetDir .= $dirImage;
             if (!$this->bucket->isDirectory($targetDir)) {
                $erroImage = array('error' => 0, 'new_path' => $dirImage);
             }
        }
        foreach ($arrImg as $image) {
            $image = is_array($image) ? current($image) : $image;
            if ($image == "") continue;

            if ($targetDir == "") {
                // $serverpath = $_SERVER['SCRIPT_FILENAME'];
                // $pos = strpos($serverpath,'assets');
                // $serverpath = substr($serverpath,0,$pos);
                $targetDir = 'assets/images/product_image/';
                $targetDir .= $this->getPathImage();
            }

            //$upload = $this->bucket->sendImageToS3($image, $targetDir.'/' );
            $upload = $this->uploadproducts->sendImageForBucket($targetDir.'/', $image);
            if ($upload['success'] == false) {
                $erroImage = array('error' => 2, 'data' => $upload['data'], 'new_path' => $dirImage);
                break;
            }

            if (!array_key_exists('primary_image', $erroImage)) {
                $erroImage['primary_image'] = $upload['path'];
            }
        }

        return $erroImage;
    }

    private function uploadImageVariation($arrImg, $pathProducts, $pathVariation, $newImage)
    {

        $errorImage = array('error' => 0);
        $targetDir = "assets/images/product_image/{$pathProducts}/{$pathVariation}";

        if ($arrImg === null) return $errorImage;

        $primary_image = null;
        foreach ($arrImg as $image) {
            $image = is_array($image) ? current($image) : $image;
            if ($image == "") {
                continue;
            }

            $upload = $this->uploadproducts->sendImageForBucket("{$targetDir}/", $image);
            if ($upload['success'] == false) {
                $errorImage = array('error' => 2, 'data' => $upload['data']);
                break;
            }

            if (is_null($primary_image)) {
                $primary_image = $upload['path'];
            }
        }

        $errorImage['primary_image'] = $primary_image;

        return $errorImage;
    }

    private function deleteImgError($arrImg, $dirImage = "")
    {
        foreach ($arrImg as $image) {

            $expImg = explode("/", $image);
            $nameImg =  $expImg[count($expImg) - 1];
            $pathImage = $dirImage == "" ? $this->getPathImage() : $dirImage;

            $filename = "assets/images/product_image/{$pathImage}/{$nameImg}";

            $dir_verify = trim($filename);
            $last_path_delete = substr(str_replace("/","",trim($dir_verify)),-13);
            if ($last_path_delete =='product_image') {
                log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro no caminho '.$dir_verify);
                die;
            }

            if (file_exists($filename) && strpos( $nameImg, "." )) {
                // log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' unlink '.$filename);
                unlink($filename);
            }
        }
    }

    private function getPathImage()
    {
        $sql = "SELECT image FROM products WHERE sku = ? AND store_id = ?";
        $query = $this->db->query($sql, array($this->sku, $this->store_id));
        return $query->first_row()->image;
    }

    private function isOnBucket(){
        $sql = "SELECT is_on_bucket FROM products WHERE sku = ? AND store_id = ?";
        $query = $this->db->query($sql, array($this->sku, $this->store_id));
        return $query->first_row()->is_on_bucket;
    }

    private function verifySKUAvailable($sku)
    {
        if (is_null($this->sku)) {
            return $this->checkSkuAvailable($sku);
        }
        else {
            $sql = "SELECT * FROM products WHERE sku = ? AND store_id = ? AND sku <> ?";
            $query = $this->db->query($sql, array($sku, $this->store_id, $this->sku));
            return $query->row_array();
        }
    }

    private function getAttributeProduct()
    {
        if (!$this->sku)
            return array('error' => true, 'data' => $this->lang->line('api_sku_code_not_informed'));

        $dataProduct = $this->getDataProduct()->row_array();
        if (!$dataProduct)
            return array('error' => true, 'data' => $this->lang->line('api_product_not_found'));

        if (in_array($dataProduct['product_status'], [Model_products::DELETED_PRODUCT])) {
            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_trash_product'). " ( {$this->sku} )", "W");
            return array('error' => true, 'data' => $this->lang->line('api_trash_product_api'));
        }

        //        if (empty($dataProduct['category_id']))
        //            return array('error' => true, 'data' => 'Produto ainda não contém uma categoria, para visualizar os atributos do produto é necessário relacionar o produto a uma categoria!');

        $arrAttributes = array();

        foreach ($this->getMarketplacesIntegration() as $mkt) {

            foreach($this->getFieldsMktCategory($dataProduct['category_id'], $mkt['int_to']) as $attr) {

                $arrValueAttr = array();

                // por enquanto ignoro variacao
                if ($attr['variacao'] != 0) continue;
                // ignorar atributo
                if (array_key_exists($mkt['int_to'], $this->mktAttributesIgnore) &&  in_array(strtoupper($attr['id_atributo']), $this->mktAttributesIgnore[$mkt['int_to']])) continue;

                if ($attr['valor'] != '')
                    foreach (json_decode($attr['valor']) as $valueAttr) array_push($arrValueAttr, $valueAttr->Value ?? $valueAttr->name);

                $attrValue = $this->getAttributesProduct($dataProduct['code_p'], $attr['id_atributo'], $mkt['int_to']);

                $originalValue = $attrValue['valor'] ?? null;
                if ($attr['multi_valor'] == 1 && $originalValue) {
                    $originalValue = explode(',', $originalValue);
                    if (count($originalValue) == 1) $originalValue = $originalValue[0];
                }
                if($attr['tipo'] == "list" && $attr['valor'] != null){
                    foreach (json_decode($attr['valor']) as $value) {
                        if ($originalValue == $value->FieldValueId) {
                            $originalValue = $value->Value;
                            break;
                        }
                    }
                }

                array_push($arrAttributes, array(
                    'code'              => $mkt['int_to'].'-'.$attr['id_atributo'],
                    'attribute'         => $attr['nome'],
                    'original_value'    => $originalValue,
                    'required'          => $attr['obrigatorio'] == 1 ? true : false,
                    'multiple_values'   => $attr['multi_valor'] == 0 ? false : true,
                    'allowed_value'     => count($arrValueAttr) ? $arrValueAttr : false,
                    'note'              => $attr['tooltip']
                ));

            }
        }

        // ler atributos customizados
        foreach ($this->model_products->getAttributesCustomProduct($dataProduct['code_p']) as $attr) {
            array_push($arrAttributes, array(
                'code'              => 'CUSTOM-'.$attr['id_attr'],
                'attribute'         => $attr['name_attr'],
                'original_value'    => $attr['value_attr'],
                'required'          => false,
                'multiple_values'   => false,
                'allowed_value'     => false,
                'note'              => null
            ));
        }

        if (!count($arrAttributes))
            return array('error' => true, 'data' => $this->lang->line('api_no_attributes_product'));

        return $arrAttributes;
    }

    private function checkSkuAvailable($sku)
    {
        $sql = "SELECT p.id,p.is_variation_grouped,p.sku FROM products p WHERE p.store_id = ? AND p.sku = ? limit 1";
        $query = $this->db->query($sql, array($this->store_id, $sku));
        $row =$query->row_array();
        // já tem no produto...
        if ($row) {
            return $row;
        }

        $sql = "SELECT v.prd_id FROM prd_variants v WHERE v.sku = ?  limit 1";
        $query = $this->db->query($sql, array($sku));
        $row = $query->row_array();
        // nenhuma variação com este sku....
        if (!$row) {
            return null;
        }

        $sql = "SELECT p.id,p.is_variation_grouped,p.sku FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.has_variants != '' AND p.store_id = ? AND v.sku = ? limit 1";
        $query = $this->db->query($sql, array($this->store_id, $sku));
        return $query->row_array();
        /* old
        $sql = "SELECT p.id,v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.store_id = ? AND (p.sku = ? OR v.sku = ?) limit 1";
        $query = $this->db->query($sql, array($this->store_id, $sku, $sku));
        return $query->row_array() ? false : true;
        */
    }

    private function getFieldsMktCategory($idcat,$int_to)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$idcat);
        if ($result) {
            $idCatML= $result['category_marketplace_id'];
            $result = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML,$int_to);
        }
        else {
            $result = array();
        }

        return $result;
    }

    private function getAttributesProduct($productId, $attrId, $intTo)
    {
        return $this->model_atributos_categorias_marketplaces->getProductAttributeByIdIntto($productId, $attrId, $intTo);
    }

    private function updateAttribute($data, $dataProduct)
    {
        if (!isset($data->attribute)) return array('error' => true, 'data' => $this->lang->line('api_not_attribute_key'));
        if (count((array)$data->attribute) === 0) return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));

        $attributes = $data->attribute;
        $productId  = $dataProduct['code_p'];
        $categoryId = $dataProduct['category_id'];
        $error      = "";

        $this->db->trans_begin();

        foreach ($attributes as $key => $attr) {

            $iten        = $key + 1;
            $codeOri     = $attr->code ?? null;
            $value       = $attr->value ?? '';

            if (!$codeOri) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_field_not_identify_code') . $iten . $this->lang->line('api_th_item') );
            }

            $exCode = explode('-', $codeOri);

            if (count($exCode) <= 1) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_attribute_code_not_match') . $codeOri . $this->lang->line('api_attribute_code_not_match_end'));
            }
            $marketplace = $exCode[0];
            $code = str_replace($exCode[0].'-', '', $codeOri);

            // recuperar o atributo
            $attributeMkt = $this->model_atributos_categorias_marketplaces->getAtributoByAttrIdMkt($code, $marketplace, $categoryId);
            // não encontrou o atributo pelo codigo e marketplace
            if (!$attributeMkt) {

                $attributeMkt = $this->model_products->getAttributesCustomByProductAndAttrId($productId, $code);
                if (!$attributeMkt) {
                    $this->db->trans_rollback();
                    return array('error' => true, 'data' => $this->lang->line('api_attribute_code_not_found') . $codeOri . $this->lang->line('api_attribute_code_not_found_end'));
                }

                if (!$value) {
                    $this->db->trans_rollback();
                    return array('error' => true, 'data' => $this->lang->line('api_field_not_found_attribute') . $codeOri);
                }

                // atualizo atributo customizado e vou para o proximo atributos,
                // sem precisar fazer a validação dos campo de atributos de marketpalce
                $this->model_products->updateAttributesCustomProduct($productId, $code, $value);
                $this->model_products->updateAttributesCount($productId);
                continue;
            }
            // não encontrou valor para o campo obrigatorio
            if (!$value && $attributeMkt['obrigatorio'] == 1) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_field_not_found_attribute') . $codeOri);
            }
            // se for multi_valor ver se foi passado um array ou string e remover as vírgulas
            if ($attributeMkt['multi_valor'] == 1) {
                if (is_array($value)) {

                    $arrValue = array();

                    // remove as vírgular
                    foreach ($value as $_value) {
                        $arrValue[] = str_replace(',', '', $_value);
                    }

                    $value = implode(',', $arrValue);

                } else {
                    $value = str_replace(',', '', $value);
                }
            }
            // se não for multi_valor deve ser informado uma string
            if ($attributeMkt['multi_valor'] == 0 && is_array($value)) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_attribute_multiple_values') . $codeOri . $this->lang->line('api_attribute_multiple_values_end'));
            }
            // verificar se existem valores pré-definidos
            $valueRequired = array();
            foreach (json_decode($attributeMkt['valor']) as $valueAttr) {
                $valueRequired[] = $valueAttr->Value ?? $valueAttr->name;
            }
            if (count($valueRequired) && ($value || $attributeMkt['obrigatorio'] == 1)) {
                if ($attributeMkt['multi_valor'] == 1) {
                    $valuesVerifyRequired = explode(',', $value);
                } else {
                    $valuesVerifyRequired = [$value];
                }

                foreach ($valuesVerifyRequired as $valueVerifyRequired) {
                    if (!in_array($valueVerifyRequired, $valueRequired)) {
                        $this->db->trans_rollback();
                        return array('error' => true, 'data' => $this->lang->line('api_value_attribute_not_allowed') . $valueVerifyRequired . $this->lang->line('api_value_attribute_not_allowed_half') . $codeOri . $this->lang->line('api_value_attribute_not_allowed_end'));
                    }
                }
            }
            if($attributeMkt['tipo'] == "list" && $attributeMkt['valor'] != null){
                //$value = null;
                foreach (json_decode($attributeMkt['valor']) as $valor) {
                    if ($value == $valor->Value) {
                        $value = $valor->FieldValueId;
                        break;
                    }
                }
            }

            // cria/altera atributo
            if (!is_null($value)) {
                $this->model_atributos_categorias_marketplaces->saveProdutosAtributos(array(
                    'id_product'    => $productId,
                    'id_atributo'   => $code,
                    'valor'         => $value,
                    'int_to'        => $marketplace
                ));
            }
        }
        // verifica se deu algum problema nas queries
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }
        // tudo ok
        $this->db->trans_commit();
        return array('error' => false);
    }

    private function getMarketplacesIntegration()
    {
        return $this->db->query("SELECT * FROM integrations WHERE store_id = ? AND company_id = ?", array(0,1))->result_array();
    }

    private function insertAttribute($data)
    {
        if (!isset($data->attribute)) return array('error' => true, 'data' => $this->lang->line('api_not_attribute_key'));
        if (count((array)$data->attribute) === 0) return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        $attributes = $data->attribute;
        $error = '';

        $this->db->trans_begin();

        foreach ($attributes as $key => $attr) {

            $iten = $key + 1;
            $sku    = $attr->sku ?? null;
            $name   = $attr->name ?? null;
            $value  = $attr->value ?? null;

            if (!trim($sku))
                $error = $this->lang->line('api_field_not_identify_sku') . $iten . $this->lang->line('api_th_item');
            elseif (!trim($name))
                $error = $this->lang->line('api_field_not_identify_name') . $iten . $this->lang->line('api_th_item');
            elseif (!trim($value))
                $error = $this->lang->line('api_field_not_identify_value') . $iten . $this->lang->line('api_th_item');

            if (!empty($error)) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $error);
            }

            $sku    = filter_var($attr->sku, FILTER_SANITIZE_STRING);
            $name   = filter_var($attr->name, FILTER_SANITIZE_STRING);
            $value  = filter_var($attr->value, FILTER_SANITIZE_STRING);

            $this->sku = $sku;

            $dataProduct = $this->getDataProduct()->row_array();
            if (!$dataProduct) {
                $this->db->trans_rollback();
                return array('error' => true, 'data' => $this->lang->line('api_product_item_not_found'). " {$iten}º ". $this->lang->line('api_item'));
            }

            $dataAttribute = $this->model_products->getAttributesCustomByName($name);

            if ($dataAttribute) {
                if ($this->model_products->getAttributesCustomByProductAndAttrId($dataProduct['code_p'], $dataAttribute['id'])) {
                    $this->db->trans_rollback();
                    return array('error' => true, 'data' => $iten . $this->lang->line('api_item_attribute_product'));
                }
            }
            $this->model_products->insertAttributesCustomProduct($dataProduct['code_p'], $name, $value, ['id' => $this->user_id]);
            $this->model_products->updateAttributesCount($dataProduct['code_p']);
        }
        // verifica se deu algum problema nas queries
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }
        // tudo ok
        $this->db->trans_commit();
        return array('error' => false);
    }

    /**
     * Publicar produtos.
     *
     * @param   object      $products   Dados do produto para publicar.
     * @throws  Exception
     */
    private function publishProduct(object $products)
    {
        // sem código sku.
        if (empty($products->sku)) {
            throw new Exception($this->lang->line('api_field_sku_not_found'));
        }

        // sem código do marketplace.
        if (empty($products->marketplace)) {
            throw new Exception($this->lang->line('api_field_marketplace_not_found'));
        }

        // sem código da loja.
        if (empty($products->store)) {
            throw new Exception($this->lang->line('api_field_store_not_found'));
        }

        $sku = $products->sku;
        $marketplace = $products->marketplace;
        $store = $products->store;

        // Produto não encontrado para a loja.
        if (!$product = $this->model_products->getProductBySkuAndStore($sku, $store)) {
            if (!$product = $this->model_products->getProductsBySkuVariantAndStore($sku, $store)) {
                throw new Exception($this->lang->line('api_product') . $sku . $this->lang->line('api_not_found_store'));
            }
        }

        // Se não existe o campo 'vantiant', cria o campo no vetor como nulo.
        if (!isset($product['variant'])) {
            $product['variant'] = null;
        }

        // Verificar se o produto tem variação, se estiver a tentar publicar o produto pai, deve mostrar um erro.
        $variants = $this->model_products->getVariantsByProd_id($product['id']);
        if (is_null($product['variant']) && !empty($variants)) {
            throw new Exception($this->lang->line('api_cannot_publish_parent_product'));
        }

        // produto deve está completo e ativo.
        if ($product['status'] != 1 || $product['situacao'] != 2) {
            throw new Exception($this->lang->line('api_product_must_complete'));
        }

        // produto já está publicado.
        if ($this->model_integrations->getPrdIntegrationByIntToProdId($marketplace, $product['id'], $product['variant'])) {
            $messageErrorPublish = $this->lang->line(is_null($product['variant']) ? 'api_product_already_marketplace' : 'api_variation_already_marketplace');
            throw new Exception($messageErrorPublish . $marketplace);
        }

        // marketplace não encontrado.
        if (!$integration = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $marketplace)) {
            throw new Exception($this->lang->line('api_marketplace') . $marketplace . $this->lang->line('api_marketplace_not_found'));
        }

        // fornecedor não encontrado.
        $providerId = array_change_key_case(getallheaders())['x-provider-key'];
        if (!$provider = $this->model_providers->getProviderData($providerId)) {
            throw new Exception("Fornecedor '$providerId' não localizado.");
        }

        // fornecedor não pode publicar no marketplace.
        if ($provider['marketplace'] != $integration['id']) {
            throw new Exception("Fornecedor '$providerId' sem permissão para gerenciar o marketplace '$marketplace'.");
        }

        $productIntegration = Array(
            'int_id'        => $integration['id'],
            'prd_id'        => $product['id'],
            'company_id'    => $product['company_id'],
            'store_id'      => $product['store_id'],
            'date_last_int' => '',
            'status'        => 1,
            'user_id'       => 1,
            'status_int'    => 2,
            'int_type'      => 13,
            'int_to'        => $marketplace,
            'skubling'      => $sku,
            'skumkt'        => $sku,
            'variant'       => $product['variant'],
            'approved'      => ($integration['auto_approve']) ? 1 : 3
        );
        $this->model_integrations->setProductToMkt($productIntegration);

        $queue = array (
            'id' 		=> 0,
            'status' 	=> 0,
            'prd_id' 	=> $product['id'],
            'int_to' 	=> $marketplace
        );
        $this->model_queue_products_marketplace->create($queue);
    }

    public function images_get($product_id = null)
    {
        $this->endPointFunction = __FUNCTION__;

        if ($product_id) {
            $records = $this->db
                ->select('products.id, products.image, prd_variants.variant, prd_variants.image as variant_image, products.is_on_bucket')
                ->from('products')
                ->join('prd_variants', 'prd_variants.prd_id = products.id', 'left')
                ->where(array('products.id' => $product_id))
                ->get()
                ->result_array();
        }

        if (!$records[0]['image']) {
            return null;
        }

        $dirImage = 'assets/images/product_image/' . $records[0]['image'];

        $images = [];
        // Caso não esteja no bucket ainda, realiza a alteração.
        if (!$records[0]['is_on_bucket']) {
            foreach (scandir($dirImage) as $image) {
                if ($image == '.' || $image == '..') {
                    continue;
                }
                $url_image = $dirImage . '/' . $image;
                array_push($images, baseUrlPublic($url_image));
            }
        } else {
            // Busca as imagens do produto.
            $contents = $this->bucket->getFinalObject($dirImage);

            // Percorre cada imagem retornada e adiciona ao array.
            foreach ($contents["contents"] as $key => $content) {
                array_push($images, $content["url"]);
            }
        }

        $variants = array();
        foreach ($records as $record) {
            if (is_null($record['variant'])) continue;

            $dirImage = 'assets/images/product_image/' . $record['image'] . '/' . $record['variant_image'];
            $images_variant = [];
            if (!$records[0]['is_on_bucket']) {
                foreach (scandir($dirImage) as $image) {
                    if ($image == '.' || $image == '..') continue;

                    $url_image = $dirImage . '/' . $image;
                    array_push($images_variant, baseUrlPublic($url_image));
                }
            } else {
                // Busca as imagens das variantes do produto.
                $contents = $this->bucket->getFinalObject($dirImage);

                // Percorre cada imagem retornada e adiciona ao array.
                foreach ($contents["contents"] as $key => $content) {
                    array_push($images_variant, $content["url"]);
                }
            }

            $variant = array(
                "variant" => $record['variant'],
                "images" => $images_variant
            );
            array_push($variants, $variant);
        }

        $result = array(
            "main" => $images,
            "variants" => $variants
        );
        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    private function createPriceByMarketplace($product_id, $store_id)
    {
        if (!$this->ms_price->use_ms_price && !$this->ms_stock->use_ms_stock) {
            return;
        }

        $integrations = $this->model_integrations->getIntegrationsbyStoreId($store_id);
        $prd = $this->model_products->getProductData(0,$product_id);

        // Kit não tem valor por marketplace por enquanto.
        if ($prd['is_kit'] == 1) {
            return;
        }

        foreach($integrations as $integration) {
            if ($prd['has_variants'] == '') {
                try {
                    if ($this->ms_price->use_ms_price) {
                        $this->ms_price->updateMarketplacePrice($product_id, null, $integration['int_to'], $prd['price']);
                    }
                    if ($this->ms_stock->use_ms_stock) {
                        $this->ms_stock->updateMarketplaceStock($product_id, null, $integration['int_to'], $prd['qty']);
                    }
                } catch (Exception $exception) {
                    // Se der erro, por enquanto, não faz nada
                }
            }
            else {
                $variants = $this->model_products->getVariants($product_id);
                foreach ($variants as $variant) {
                    try {
                        if ($this->ms_price->use_ms_price) {
                            $this->ms_price->updateMarketplacePrice($product_id, $variant['variant'], $integration['int_to'], $variant['price']);
                        }
                        if ($this->ms_stock->use_ms_stock) {
                            $this->ms_stock->updateMarketplaceStock($product_id, $variant['variant'], $integration['int_to'], $variant['qty']);
                        }
                    } catch (Exception $exception) {
                        // Se der erro, por enquanto, não faz nada
                    }
                }
            }
        }
    }

    public function strReplaceName($name) {
        return str_replace('&amp;', '&', str_replace('&#039', "'", $name));
    }
}
