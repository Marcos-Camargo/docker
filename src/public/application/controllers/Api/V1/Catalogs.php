<?php

require APPPATH . "controllers/Api/V1/API.php";
require APPPATH . "libraries/Traits/LengthValidationProduct.trait.php";
require APPPATH . "libraries/Traits/ValidationSkuSpace.trait.php";

/**
 * @property UploadProducts $uploadproducts
 * @property BlacklistOfWords $blacklistofwords
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_log_products $model_log_products
 * @property Model_products $model_products
 * @property Model_products_catalog $model_products_catalog
 * @property Model_catalogs $model_catalogs
 * @property Model_settings $model_settings
 * @property Model_brands $model_brands
 * @property Model_integrations $model_integrations
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_products_catalog_associated $model_products_catalog_associated
 * @property Model_catalogs_associated $model_catalogs_associated
 */

class Catalogs extends API
{
    use LengthValidationProduct;
    use ValidationSkuSpace;
    private $type_variation = array(
        "Cor"       => "color",
        "TAMANHO"   => "size",
        "VOLTAGEM"  => "voltage"
    );

    private $arrColumnsUpdate = array(
        //'images'                => array('columnDatabase' => 'image','type' => 'A', 'required' => true),
        'name'                  => array('columnDatabase' => 'name','type' => 'S', 'required' => true),
        'active'                => array('columnDatabase' => 'status','type' => 'S', 'required' => true),
        'description'           => array('columnDatabase' => 'description','type' => 'S', 'required' => true),
        'price'                 => array('columnDatabase' => 'price','type' => 'F', 'required' => true),
        'original_price'        => array('columnDatabase' => 'original_price','type' => 'F', 'required' => true),
        'ean'                   => array('columnDatabase' => 'EAN','type' => 'S', 'required' => false),
        'sku_manufacturer'      => array('columnDatabase' => 'brand_code','type' => 'S', 'required' => false),
        'net_weight'            => array('columnDatabase' => 'net_weight','type' => 'F', 'required' => true),
        'gross_weight'          => array('columnDatabase' => 'gross_weight','type' => 'F', 'required' => true),
        'width'                 => array('columnDatabase' => 'width','type' => 'F', 'required' => true),
        'height'                => array('columnDatabase' => 'height','type' => 'F', 'required' => true),
        'depth'                 => array('columnDatabase' => 'length','type' => 'F', 'required' => true),
        'guarantee'             => array('columnDatabase' => 'warranty','type' => 'I', 'required' => true),
        'ncm'                   => array('columnDatabase' => 'NCM','type' => 'S', 'required' => false),
        'origin'                => array('columnDatabase' => 'origin','type' => 'I', 'required' => true),
        'unity'                 => array('columnDatabase' => 'attribute_value_id','type' => 'S', 'required' => true),
        'manufacturer'          => array('columnDatabase' => 'brand_id','type' => 'S', 'required' => true),
        'category'              => array('columnDatabase' => 'category_id', 'type' => 'S', 'required' => true)
    );
    private $arrColumnsInsert = array(
        'product_variations'=> array('columnDatabase' => 'product_variations','type' => '', 'required' => true),
        'types_variations'  => array('columnDatabase' => 'has_variants', 'type' => 'A', 'required' => false)
    );
    public $allowable_tags = null;
    private $usePriceVariation = false;
    private $product_length_name ;
    private $product_length_description ;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_log_products');
        $this->load->model('model_products');
        $this->load->model('model_products_catalog');
        $this->load->model('model_settings');
        $this->load->model('model_catalogs');
        $this->load->model('model_brands');
        $this->load->model('model_integrations');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_products_catalog_associated');
        $this->load->model('model_catalogs_associated');
        $this->load->library('UploadProducts');
        $this->load->library('BlacklistOfWords');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('catalogs_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $settingPriceVariation = $this->model_settings->getSettingDatabyName('price_variation');

        if ($settingPriceVariation && $settingPriceVariation['status'] == 1)
            $this->usePriceVariation = true;

        $this->loadLengthSettings();

        if ($this->model_settings->getValueIfAtiveByName('catalog_products_require_ean')) {
            $this->arrColumnsUpdate['ean']['required'] = true;
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function index_get($sku = null)
    {
        return $this->response(array('success' => false, "message" => $this->lang->line('api_resource_unavailable')), REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function index_put($sku = null)
    {
        return $this->response(array('success' => false, "message" => $this->lang->line('api_resource_unavailable')), REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function index_post()
    {
        return $this->response(array('success' => false, "message" => $this->lang->line('api_resource_unavailable')), REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function associate_post($type = null)
    {
        // Recupera dados enviado pelo body
        $data = $this->cleanGet(json_decode($this->security->xss_clean($this->input->raw_input_stream), true));

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
            return;
        }

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            $this->response(array('success' => false, "message" => utf8_decode($disable_message)), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        // MUDAR PARA HTTP_BAD_REQUEST
        if ($data == null) {
            $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        try {
            $this->associate_skus($data);
        } catch (Exception $exception) {
            $error_message = $exception->getMessage();
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $error_message . " - payload: " . json_encode($data),"W");
            $this->response($this->returnError($error_message), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,json_encode($data));

        $this->response(array('success' => true, "message" => $this->lang->line('api_product_inserted')), REST_Controller::HTTP_CREATED, $this->createButtonLogRequestIntegration($data));
    }

    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function associate_skus(array $data)
    {
        if (!array_key_exists('catalog_associate', $data)) {
            throw new Exception("Chave catalog_associate não informado.");
        }

        $catalog_associate = $data['catalog_associate'];

        $sku            = $catalog_associate["sku"] ?? null;
        $catalog_id     = $catalog_associate["catalog_id"] ?? null;
        $product_exist  = $this->model_products->getProductBySkuAndStore($sku, $this->store_id);

        if (!$catalog_id) {
            throw new Exception("catalog_id não informado.");
        }

        $catalog = $this->model_catalogs->getCatalogData($catalog_id);
        $catalogs_store = array_map(function($catalog) {
            return $catalog['catalog_id'];
        },$this->model_catalogs->getCatalogsStoresDataByStoreId($this->store_id));

        if (!in_array($catalog_id, $catalogs_store)) {
            throw new Exception("catalog_id informado não utilizado pela loja.");
        }

        if (!$catalog) {
            throw new Exception("catalog_id informado não encontrado.");
        }

        // Catálogos disponíveis para determinado produto.
        $catalog_products_catalog = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_exist['product_catalog_id']);

        // Pega apenas os ids.
        $catalog_products_catalog = array_column($catalog_products_catalog,'catalog_id');

        // Filtra quais catálogos pode utilizar.
        $valid_catalogs = array_values(
            array_intersect($catalog_products_catalog, $catalogs_store)
        );

        // Caso não haja nenhum catálogo válido, joga
        if (empty($valid_catalogs)) {
            throw new Exception("Loja sem nenhum catálogo válido para este produto.");
        }

        // O primeiro catálogo permitido para a loja será tratado como catálogo base.
        $base_product_catalog_id = $valid_catalogs[0];

        // Produto não existe, então cria o produto.
        if (!$product_exist) {
            $this->createCatalogProduct($catalog, $catalog_associate);
        } else if ($base_product_catalog_id != $catalog_id) {
            // Produto existe, cria associação.
            $this->associateCatalogProduct($product_exist, $catalog_associate);
        } else {
            // Produto existe, mas o id do catálogo a ser associado é o do primeiro produto.
            $this->updateBaseProduct($product_exist, $catalog_associate);
        }
        
    }

    /**
     * @param array $product_catalog_exist
     * @param array $catalog_associate
     * @return void
     * @throws Exception
     */
    private function associateCatalogProduct(array $product_catalog_exist, array $catalog_associate)
    {
        $catalog_id_associate       = $catalog_associate["catalog_id"] ?? null;
        $brand                      = $catalog_associate["manufacturer"] ?? null;
        $ean                        = $catalog_associate["EAN"] ?? null;
        $status                     = $catalog_associate["status"] ?? null;
        $maximum_discount_catalog   = $catalog_associate["maximum_discount_catalog"] ?? null;

        if ($status != 1) {
            $status = 2;
        }

        $product_catalog_id = $product_catalog_exist['product_catalog_id'];
        $catalog_products_catalog = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id);
        $catalog_id_product = $catalog_products_catalog[0]['catalog_id'] ?? null;
        if (!$catalog_id_product) {
            throw new Exception("catalog_id informado não encontrado.");
        }
        $catalog_product = $this->model_catalogs->getCatalogData($catalog_id_product);

        $associate_skus_between_catalogs_db = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($catalog_product['id']);

        $fields_to_link_catalogs = explode(',', $catalog_product['fields_to_link_catalogs']);

        if (empty($associate_skus_between_catalogs_db) || empty($fields_to_link_catalogs)) {
            throw new Exception("Catálogo informado não configurado para usar associação.");
        }

        if (!in_array($catalog_id_associate, $associate_skus_between_catalogs_db)) {
            throw new Exception("Catálogo informado não parametrizado para o produto enviado.");
        }

        $product_catalog = null;
        $brand_data = null;
        if (!is_null($brand)) {
            $brand_data = $this->model_brands->getBrandData($brand);
            if (!$brand_data) {
                $brand_data = $this->model_brands->getBrandDatabyName($brand);
            }
        }

        if (likeTextNew('%brand%', $catalog_product['fields_to_link_catalogs'])) {
            if (!$brand_data) {
                throw new Exception("Marca informada não encontrada.");
            }
        }

        $brand_id = $brand_data['id'] ?? null;

        foreach ($associate_skus_between_catalogs_db as $associate_sku_catalog) {
            if ($catalog_id_associate != $associate_sku_catalog) {
                continue;
            }

            $product_catalog = $this->getMatchCatalogProductToAssociate($catalog_product['fields_to_link_catalogs'], $associate_sku_catalog, $brand_id, $ean);
            if (is_null($product_catalog)) {
                continue;
            }

            break;
        }

        if (empty($product_catalog)) {
            throw new Exception("Não foi encontrado nenhum produto para os dados informados.");
        }

        $products_catalog_associated = $this->model_products_catalog_associated->getByCatalogProductIdAndCatalogId($product_catalog['id'], $product_catalog['catalog_id']);
        if ($products_catalog_associated) {
            $update = array();

            if ($status == 1 || $status == 2) {
                $update['status'] = $status;
            }

            if (!empty($maximum_discount_catalog)) {
                $update['maximum_discount_catalog'] = $maximum_discount_catalog;
            }

            if (!empty($update)) {
                $this->model_products_catalog_associated->update($update, $products_catalog_associated['id']);
            }
        } else {
            $this->model_products_catalog_associated->create(array(
                'catalog_id'                    => $product_catalog['catalog_id'],
                'original_catalog_product_id'   => $product_catalog_id,
                'catalog_product_id'            => $product_catalog['id'],
                'product_id'                    => $product_catalog_exist['id'],
                'maximum_discount_catalog'      => empty($maximum_discount_catalog) ? null : $maximum_discount_catalog,
                'store_id'                      => $this->store_id,
                'company_id'                    => $this->company_id,
                'status'                        => $status ?? 1,
            ));
        }

        $this->model_products->update(array('date_update' => dateNow()->format(DATETIME_INTERNATIONAL)), $product_catalog_exist['id']);
    }

    /**
     * Realiza o update do limite de desconto do produto base.
     * Caso seja passado o id do produto como id para ser associado, então precisa alterar o produto base também.
     * 
     * @param    array   $product_catalog_exist Produto base a ser realizado o update.
     * @param    array   $catalog_update Array com o desconto máximo e novo status.
     */
    private function updateBaseProduct($product_catalog_exist, $catalog_update)
    {
        $update = [];

        // Pega o desconto e o status.
        $maximum_discount_catalog = $catalog_update['maximum_discount_catalog'];
        $status = $catalog_update['status'];
        // Caso tenha passado o desconto, adiciono ao update.
        if (!empty($maximum_discount_catalog)) {
            $update["maximum_discount_catalog"] = $maximum_discount_catalog;
        }

        // Caso tenha passado um status valido, adiciono ao update.
        if ($status == 1 || $status == 2) {
            $update['status'] = $status;
        }

        // Caso os valores sejam válidos, realiza o update do produto base.
        if (!empty($update)) {
            $this->db->update("products", $update, ['id' => $product_catalog_exist['id']]);
        }
    }

    /**
     * @param array $catalog
     * @param array $catalog_associate
     * @return void
     * @throws Exception
     */
    private function createCatalogProduct(array $catalog, array $catalog_associate)
    {
        $sku                        = $catalog_associate["sku"] ?? null;
        $qty                        = $catalog_associate["qty"] ?? null;
        $ean                        = $catalog_associate["EAN"] ?? null;
        $status                     = $catalog_associate["status"] ?? 1;
        $brand                      = $catalog_associate["manufacturer"] ?? null;
        $extra_operating_time       = $catalog_associate["extra_operating_time"] ?? 0;
        $catalog_id                 = $catalog_associate["catalog_id"] ?? null;
        $maximum_discount_catalog   = $catalog_associate["maximum_discount_catalog"] ?? null;

        $associate_skus_between_catalogs_db = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($catalog['id']);

        $fields_to_link_catalogs = explode(',', $catalog['fields_to_link_catalogs']);

        if (empty($associate_skus_between_catalogs_db) || empty($fields_to_link_catalogs)) {
            throw new Exception("Catálogo informado não configurado para usar associação.");
        }

        $brand_data = null;
        if (!is_null($brand)) {
            $brand_data = $this->model_brands->getBrandData($brand);
            if (!$brand_data) {
                $brand_data = $this->model_brands->getBrandDatabyName($brand);
            }
        }

        if (likeTextNew('%brand%', $catalog['fields_to_link_catalogs'])) {
            if (!$brand_data) {
                throw new Exception("Marca informada não encontrada.");
            }
        }

        $brand_id = $brand_data['id'] ?? null;

        $product_catalog = $this->getMatchCatalogProductToAssociate($catalog['fields_to_link_catalogs'], $catalog_id, $brand_id, $ean);

        if (is_null($product_catalog)) {
            throw new Exception("Não foi encontrado nenhum produto para os dados informados.");
        }

        $data_prod = array(
            'name' 						=> $product_catalog['name'],
            'sku' 						=> str_replace("/", "-", $sku),
            'price' 					=> $product_catalog['price'],
            'qty' 						=> $qty,
            'image' 					=> 'catalog_'.$product_catalog['id'],
            'principal_image' 			=> $product_catalog['principal_image'],
            'description' 				=> $product_catalog['description'],
            'attribute_value_id' 		=> $product_catalog['attribute_value_id'],
            'brand_id' 					=> json_encode(array($product_catalog['brand_id'])),
            'category_id' 				=> json_encode(array($product_catalog['category_id'])),
            'store_id' 					=> $this->store_id,
            'status' 					=> $status,
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
            'prazo_operacional_extra' 	=> $extra_operating_time,
            'product_catalog_id' 		=> $product_catalog['id'],
            'maximum_discount_catalog' 	=> empty($maximum_discount_catalog) ? null : $maximum_discount_catalog,
            'dont_publish' 				=> false,
        );

        if (!empty($maximum_discount_catalog) && ($product_catalog['original_price']>0)) {
            if ((float)$maximum_discount_catalog < round((1-$product_catalog['price'] / $product_catalog['original_price'])*100,2)) {
                $data_prod['status'] = 2; // já entra inativo
            }
        }

        if ($product_catalog['has_variants'] !== "") {
            throw new Exception("Produto contém variação, a configuração dev ser feito por tela, ainda não disponível.");
        }

        $create = $this->model_products->create($data_prod);

        if (!$create) {
            throw new Exception("Ocorreu um erro para criar o produto." . json_encode($this->db->error()));
        }

        $log_var = array('id'=> $create);
        $this->log_data('Products','create',json_encode(array_merge($log_var,$data_prod)),"I");

        $integrations= $this->model_integrations->getIntegrationsbyStoreId($this->store_id);
        $auto_publish = $this->model_settings->getStatusbyName('catalog_products_auto_publish') == 1;

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
                    'user_id' 		=> $this->user_id ?? 1,
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

            $this->model_products_marketplace->createIfNotExist($integration['int_to'],$create, $integration['int_type'] == 'DIRECT');
        }
    }

    /**
     * @param string $fields_to_link_catalogs
     * @param int $catalog_id
     * @param int|null $brand_id
     * @param string|null $ean
     * @return array|null
     * @throws Exception
     */
    private function getMatchCatalogProductToAssociate(string $fields_to_link_catalogs, int $catalog_id, ?int $brand_id, ?string $ean): ?array
    {
        switch ($fields_to_link_catalogs) {
            case 'brand,ean':
            case 'ean,brand':
                $product_catalog = $this->model_products_catalog->getProductProductDataByBrandAndEan($catalog_id, $brand_id, $ean);
                break;
            case 'brand':
                $product_catalog = $this->model_products_catalog->getProductProductDataByBrand($catalog_id, $brand_id);
                break;
            case 'ean':
                if (is_null($ean)) {
                    throw new Exception("EAN deve ser informado.");
                }
                $product_catalog = $this->model_products_catalog->getProductProductDataByEan($catalog_id, $ean);
                break;
            default:
                throw new Exception("Campos para vincular catálogos não parametrizado.");
        }

        if (count($product_catalog) == 0) {
            return null;
        }

        if (count($product_catalog) > 1) {
            throw new Exception("Foi encontrado mais que um produto para os dados informados.");
        }

        return $product_catalog[0];
    }

    public function index_delete($sku = null)
    {
        return $this->response(array('success' => false, "message" => $this->lang->line('api_resource_unavailable')), REST_Controller::HTTP_UNAUTHORIZED);
    }

    public function skumanufacturer_patch($sku = null)
    {
        ob_start();

        if (!$sku || empty($sku)) {
            return $this->response($this->returnError($this->lang->line('api_sku_provider')), REST_Controller::HTTP_NOT_FOUND);
        }

        $product = $this->model_products_catalog->getAllDataBySkuManufacturer($sku);
        if (count($product) === 0) {
            return $this->response($this->returnError($this->lang->line('api_sku_code_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }

        if (count($product) !== 1) {
            return $this->response($this->returnError($this->lang->line('api_sku_manufacture_unique')), REST_Controller::HTTP_NOT_FOUND);
        }

        // Recupera dados enviado pelo body
        // $data = file_get_contents('php://input');
        $data = $this->inputClean();
        $data = preg_replace('/\s/',' ',$data);
        $data = json_decode($data);
        if ($data == null) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_json_invalid_format') . "\n\n " . $this->inputClean(),"W");
            return $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_NOT_FOUND);
        }

        $result = $this->updateBySkuManufacturer($data, $sku);

        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error']) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - payload: " . json_encode($data),"W");
            return $this->response($this->returnError($result['data']),REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, "message" => $this->lang->line('api_product_updated')), REST_Controller::HTTP_OK);
    }

    private function setValueVariableCorrect($key, $value, $type)
    {
        if (!isset($this->arrColumnsInsert[$key])) return $value;
        switch ($type == 'I' ? $this->arrColumnsInsert[$key]['type'] : $this->arrColumnsUpdate[$key]['type']) {
            case 'S': return (string)$value;
            case 'A': return (array)$value;
            case 'F': return (float)$value;
            case 'I': return (int)$value;
            default:  return $value;
        }
    }

    private function verifyFields($key, $value, $type, $prdCatalogId)
    {
        $value = is_array($value) || is_object($value) ? $value : xssClean($value, false, $key !== 'description');
        $value_ok = array(true, $value);

        if ($key === 'price') {
            if ($value === "" || (float)$value <= 0) return array(false, $this->lang->line('api_price_zero'));
            $value_ok = array(true, (float)number_format($value, 2, '.', ''));
        }

        if ($key === "active") {
            if ($value != "enabled" && $value != "disabled")
                return array(false, $this->lang->line('api_active_field'));

            $value_ok = array(true, $value == "enabled" ? 1 : 0);
        }
        if ($key === "ean" || $key === "EAN") {
            if (!$this->model_products_catalog->VerifyEanUnique($value, $prdCatalogId))
                return array(false, $this->lang->line('api_ean_invalid'));
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
            if ($key === "unity") $codeInfoProduct = $this->getCodeInfo('attribute_value', 'value', $value);
            elseif ($key === "manufacturer") $codeInfoProduct = $this->getCodeInfo('brands', 'name', $value);
            elseif ($key === "category") $codeInfoProduct = $this->getCodeInfo('categories', 'name', trim($value), "AND active = 1");

            if ($codeInfoProduct) $value_ok = array(true, $key === "unity" ? "[\"$codeInfoProduct\"]" : $codeInfoProduct);
            else{
                $required = $type == "I" ? $this->arrColumnsInsert[$key]['required'] : $this->arrColumnsUpdate[$key]['required'];
                if ($key === "unity" && $required) return array(false, $this->lang->line('api_invalid_unit'));
                elseif ($key === "manufacturer" && $required) return array(false, $this->lang->line('api_invalid_manufacturer'));
//                elseif ($key === "category" && $required) return array(false, "Entered category not found, enter a valid one.");
                else $value_ok = array(true, $key === "unity" ? '[""]' : null);
            }
        }
        if ($key === "types_variations") {
            $varArr = array();
            $varStr = "";
            if (count($value) === 1 && $value[0] === "") {
                $value_ok = array(true, "");;
            }
            else {
                foreach ($value as $type_v) {
                    switch ($type_v) {
                        case "size":
                            $varArr[$type_v] = "TAMANHO";
                            break;
                        case "color":
                            $varArr[$type_v] = "Cor";
                            break;
                        case "voltage":
                            $varArr[$type_v] = "VOLTAGEM";
                            break;
                        default:
                            return array(false, $this->lang->line('api_type_variation'));
                    }
                }
                if (isset($varArr['size'])) $varStr .= ";{$varArr['size']}";
                if (isset($varArr['color'])) $varStr .= ";{$varArr['color']}";
                if (isset($varArr['voltage'])) $varStr .= ";{$varArr['voltage']}";

                $value_ok = array(true, $varStr != "" ? substr($varStr,1) : "");
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
        if ($key === 'name') {
            $value_ok = array(true, trim($value));

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

        return $value_ok;
    }

    private function createArrayIten()
    {
        // Variaveis
        $arrVariations = array();
        $arrVariationsAttr = array();
        $arrSpecifications = array();
        $arrPublishedMarketplace = array();

        // Consulta
        $query = $this->getDataProduct();

        // Verifica se foi encontrado resultados
        if ($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $result = $query->result_array();

        $product = $result[0];

        /**
         * Imagens do produto
         */
        $arrImages = array();


        if (strpos("..".$product['image_p'],"http") == 0) {

            if ($product['catalog_id'])
                $imagesDir = FCPATH . 'assets/images/catalog_product_image/' . $product['catalog_id'];
            else
                $imagesDir = FCPATH . 'assets/images/product_image/' . $product['image_p'];

            $images = scandir($imagesDir);

            foreach($images as $image) {
                if (($image!=".") && ($image!="..") && ($image!="") && (!is_dir($imagesDir.'/'.$image))) {
                    array_push($arrImages, base_url('assets/images/product_image/' .$product['image_p'].'/'. $image));
                }
            }
        }

        /**
         * Variações de produto
         */
        if ($result[0]['has_variants'] != "") {
            foreach ($result as $product_var) {
                $values_var = array();
                if ($product_var['variant_sku'] === null) continue;

                $specificationsVal = explode(";", $product_var['variant_name']);
                $specificationsKey = explode(";", $product_var['has_variants']);
//                $images = explode(",", $product_var['variant_images']);
                foreach ($specificationsVal as $key => $value_var) {
                    array_push($values_var, array(
                        $this->type_variation[$specificationsKey[$key]] => $specificationsVal[$key]
                    ));
                }

                if ($product['catalog_id'])
                    $imagesVarDir = FCPATH . 'assets/images/catalog_product_image/' . $product['catalog_id'].'/'.$product_var['variant_image'];
                else
                    $imagesVarDir = FCPATH . 'assets/images/product_image/' . $product['image_p'].'/'.$product_var['variant_image'];

                $imagesVar = scandir($imagesVarDir);
                $arrImagesVar = array();

                foreach ($imagesVar as $imageVar) {
                    if (($imageVar != ".") && ($imageVar != "..") && ($imageVar != "")) {
                        array_push($arrImagesVar, base_url('assets/images/product_image/' . $product['image_p'].'/'.$product_var['variant_image'].'/'.$imageVar));
                    }
                }

                array_push($arrVariations,
                    array(
                        "sku" => $product_var['variant_sku'],
                        "qty" => $this->changeType($product_var['variant_qty'], "float"),
                        "EAN" => $this->changeType($product_var['variant_EAN'], "number"),
                        "price" => $this->changeType($product_var['variant_price'], "float"),
                        'images' => $arrImagesVar,
                        "variant" => $values_var,
                    )
                );

                if (count($arrImages) > 0) $product_return['product']["images"] = $arrImages;
            }
        }

        /**
         * Atributos do produto
         */
        if (!empty($product['attribute_value_id'])) {
            if (is_numeric($product['attribute_value_id']))
                $product['attribute_value_id'] = '["'.$product['attribute_value_id'].'"]';

            foreach (json_decode($product['attribute_value_id']) as $cod_attribute) {
                if ($cod_attribute == "") continue;
                $sqlAttr    = "SELECT * FROM attribute_value WHERE id = ? AND attribute_parent_id = 1";
                $attribute  = $this->db->query($sqlAttr, array($cod_attribute))->first_row();

                if ($attribute !== null)
                    array_push($arrVariationsAttr, $attribute->value);
            }
        }

        /**
         * Produtos publicados
         */
        foreach ($this->getMarketplacesPublished($product['code_p']) as $publishedMarketplace) {

            $arrPublishedMarketplace[$publishedMarketplace['int_to']] = $publishedMarketplace['skumkt'];
        }


        // Criar array
        $product_return = array(
            'product' => array(
                "sku"=> $product['product_sku'],
                "name"=> $product['product_name'],
                "description"=> $product['product_description'],
                "status"=> $product['product_status'] == 1 ? "enabled" : "disabled",
                "qty"=> $this->changeType($product['product_qty'], "float"),
                "price"=> $this->changeType($product['product_price'], "float"),
                //"promotional_price"=> 29.90,
                //"cost"=> $fetchProduct->price,
                "weight_gross"=> $this->changeType($product['product_peso_bruto'], "float"),
                "weight_liquid"=> $this->changeType($product['product_peso_liquido'], "float"),
                "height"=> $this->changeType($product['product_altura'], "float"),
                "width"=> $this->changeType($product['product_largura'], "float"),
                "length"=> $this->changeType($product['product_profundidade'], "float"),
                "brand"=> $product['brand_name'],
                "ean"=> $this->changeType($product['product_ean'], "number"),
                "ncm"=> $this->changeType($product['product_ncm'], "number"),
                "categories" => array(
                    array(
                        "code" => $this->changeType($product['category_id'], "int"),
                        "name" => $product['category_name']
                    )
                )
            )
        );
        if (count($arrImages) > 0) $product_return['product']["images"] = $arrImages;
        if (count($arrVariations) > 0) $product_return['product']["variations"] = $arrVariations;
        if (count($arrVariationsAttr) > 0) $product_return['product']["variation_attributes"] = $arrVariationsAttr;
        if (count($arrSpecifications) > 0) $product_return['product']["specifications"] = $arrSpecifications;
        if (count($arrPublishedMarketplace) > 0) $product_return['product']["published_marketplace"] = $arrPublishedMarketplace;

        return $product_return;
    }

    private function updateBySkuManufacturer($data, $sku)
    {

        $formatData = $this->formatDataForUpdate($data, $sku);

        if ($formatData['error']) return $formatData;

        if (count($formatData['data']) > 0) {
            $this->model_products_catalog->updateSimple($formatData['data'], $formatData['prd_catalog_id']);
        }

         // Primeira versão sem imagem, será liberado para ortobom [ORTB-126]
        /*if (count($imgsUpload)) {
            $dataSql = [];
            $uploadImg = $this->uploadImageProduct($imgsUpload);

            // Adiciona o nome da pasta no produto
            $dataSql['principal_image'] = $uploadImg['primary_image'] ?? null;

            $this->model_products_catalog->updateSimple($dataSql, $prdCatalogId);
        }*/

        return array('error' => false);
    }

    private function formatDataForUpdate($data, $sku)
    {
        $dataSql = array();
        $dataSqlDb = $this->model_products_catalog->getAllDataBySkuManufacturer($sku)[0];
        $erroColumn = array();
        $imgsUpload = array();

        if (!isset($data->product)) return array('error' => true, 'data' => $this->lang->line('api_not_product_key'));
        if (count((array)$data->product) === 0) return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));

        $prdCatalogId = $dataSqlDb['id'];
        foreach ($data->product as $key => $value) {
            if (!array_key_exists($key, $this->arrColumnsUpdate)) {
                array_push($erroColumn, $this->lang->line('api_parameter_not_match_field_update') . $key);
                break;
            }

            $value = $this->setValueVariableCorrect($key, $value, 'U');

            // Upload file não adiciona no array de update
            if ($key === "images") {
                if (is_string($value)) array_push($imgsUpload, $value);
                if (is_array($value)) $imgsUpload = $value;
                continue;
            }

            $verify = $this->verifyFields($key, $value, "U", $prdCatalogId);

            if (!$verify[0]) {
                array_push($erroColumn, $verify[1]);
                break;
            }

            $value = $verify[1];

            $dataSql[$this->arrColumnsUpdate[$key]['columnDatabase']] = $value;
        }

        if (count($erroColumn) > 0) {
            return array('error' => true, 'data' => implode($erroColumn,". "));
        }

        if (count($dataSql) === 0 && count($imgsUpload) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }

        if (isset($dataSql['category_id']) && $dataSql['category_id'] == null) {
            unset($dataSql['category_id']);
        }

        /** Atualizar o que vem e manter o que já tem sem alteração */
        foreach ($dataSqlDb as $key => $productDb) {
            if (in_array($key, [
                'id',
                'brand_code',
                'date_create',
                'date_update',
                'ref_id',
                'mkt_sku_id',
                'reason',
                'last_inactive_date',
                'product_name',
                'sku_name',
                'mkt_product_id'
            ])) { unset($dataSqlDb[$key]); continue; }
            if (!isset($dataSql[$key])) continue;
            if ($dataSql[$key] == "" || $dataSql[$key] == '[""]' || $dataSql[$key] == null) continue;
            if ($dataSql[$key] == $productDb) continue;

            $dataSqlDb[$key] = $dataSql[$key];
        }

        return array('error' => false, 'data' => $dataSql, 'prd_catalog_id' => $prdCatalogId);
    }

}
