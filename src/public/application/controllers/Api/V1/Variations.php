<?php

require APPPATH . "controllers/Api/V1/API.php";
require_once APPPATH . "controllers/Api/V1/Helpers/VariationTypeHelper.php";
use App\Libraries\FeatureFlag\FeatureManager;

/**
 * Class Variations
 *
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 *
 * @property model_log_products $model_log_products
 * @property Model_products $model_products
 * @property Model_settings $model_settings
 * @property Model_groups $model_groups
 * @property Model_api_integrations $model_api_integrations
 *
 * @property Bucket         $bucket
 * @property UploadProducts $uploadproducts
 */
class Variations extends API
{
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
    private $id_var = null;
    private $sku_var = null;
    private $arrColumnsUpdate = array(
        'product_variations'    => array('columnDatabase' => 'product_variations'),
        'types_variations'      => array('columnDatabase' => 'has_variants'),
    );
    private $arrColumnsInsert = array(
        'product_variations'=> array('columnDatabase' => 'product_variations'),
        'sku'               => array('columnDatabase' => 'sku')
    );
    private $type_variation = array(
        "Cor" => "color",
        "TAMANHO" => "size",
        "VOLTAGEM" => "voltage",
        "SABOR" => "flavor",
        "GRAU" => "degree",
        "LADO" => "side"

    );
    private $usePriceVariation = false;
    private $arrEANsCheck = array();

    private $product = null; 

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_log_products');
        $this->load->model('model_log_integration');
        $this->load->model('model_settings');
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->library('Bucket');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens
        $this->load->model('model_api_integrations');

        $this->__varTypeHelperConstruct();
    }

    public function index_get($sku = null, $sku_code = null, $id_code = null)
    {
        $sku = xssClean($sku);
        $sku_code = xssClean($sku_code);
        $id_code = xssClean($id_code);

        if(!$sku){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_informed'),"W");
            return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->sku = (string)$sku;

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        if($sku === "sku"){
            if(!$sku_code || !$id_code){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND);
            }
            $this->sku = (string)$sku_code;
            $this->sku_var = $id_code;
            if(!$this->checkSKUandSkuVar()){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND);
            }
        }
        else $this->sku = (string)$sku;

        // Verifica se o SKU informado está em branco
        if(empty($this->sku)) $result = false;

        // Verifica se foi informado um SKU
        if(!empty($this->sku)) $result = $this->createArrayIten();

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
//            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - sku: {$this->sku}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    public function skus_put(string $sku_product)
    {
        // Recupera dados enviado pelo body
        $data   = $this->inputClean();
        if (!is_null($sku_product)) {
            $sku_product = xssClean($sku_product);
        }

        if (!$sku_product){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_informed'),"W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_informed'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
            return;
        }

        $this->sku = $sku_product;

        $dataProduct = $this->getDataProduct();
        if (!$dataProduct) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_code_not_found') . " - sku: {$this->sku}","W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_code_not_found'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }

        try {
            $errors = $this->update_mass_sku($data, $dataProduct);
        } catch (Exception $exception) {
            $error_message = $exception->getMessage();

            $http_code = $error_message === 'Feature unavailable.' ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_BAD_REQUEST;
            // se não tiver autorização para atualizar o produto alem do estoque e preço, sera retornado um status 401
            $this->response($this->returnError($error_message), $http_code, $this->createButtonLogRequestIntegration($data));
            return;
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,'payload: ' . json_encode($data),"I");
        $this->model_log_products->create_log_products((array)$data, $dataProduct->id, 'Variation API');
        $response = array(
            'success' => true,
            "message" => $this->lang->line('api_variation_updated')
        );
        if (!empty($errors)) {
            sort($errors);
            $response['errors'] = $errors;
        }
        $this->response($response, REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
    }

    public function index_put($sku = null, $sku_code = null, $id_code = null)
    {
        // Recupera dados enviado pelo body
        $data   = $this->inputClean();
        if (!is_null($sku)) {
            $sku = xssClean($sku);
        }
        if (!is_null($sku_code)) {
            $sku_code = xssClean($sku_code);
        }
        if (!is_null($id_code)) {
            $id_code = xssClean($id_code);
        }

        if (!$sku){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_informed'),"W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_informed'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }
		
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
            return;
        }

        if ($sku === "code"){
            if(!$sku_code || !$id_code){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_code_sku_not_match'),"W");
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_code_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
                return;
            }
            $this->sku = (string) $sku_code;
            $this->id_var = (string) $id_code;
            $identify_log = 'ID: '.$this->id_var ;
            if (!$this->checkSKUandId()) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_code_sku_not_match'),"W");
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_code_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
                return;
            }
        } elseif ($sku === "sku") {
            if (!$sku_code || !$id_code) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
                return;
            }
            $this->sku = (string) $sku_code;
            $this->sku_var = $id_code;
            $identify_log = 'SKU Var: '.$this->sku_var ;
            if (!$this->checkSKUandSkuVar()) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
                return;
            }
        } else {
            $this->sku = (string) $sku;
            $identify_log = 'SKU: '.$this->sku;
        }

        $dataProduct = $this->getDataProduct();
        if (!$dataProduct) {
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_code_not_found') . " - sku: {$this->sku}","W");
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_code_not_found'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }

        // Verifica se o SKU informado está em branco
        if (empty($this->sku)) {
            $result = false;
        }

        // getEANsVariation
        if ($this->id_var || $this->sku_var) {
            foreach ($this->getDataVariation(false)->result_array() as $dataVar) {
                if ($this->sku_var == $dataVar['sku']) {
                    $this->id_var = (string) $dataVar['id'];
                    continue;
                }
                if ($this->id_var == $dataVar['id']) {
                    $this->sku_var = (string) $dataVar['sku'];
                    continue;
                }

                if (!empty($dataVar['EAN'])) {
                    $this->arrEANsCheck[] = $dataVar['EAN'];
                }
            }
        }

        if (isset($data->variation->price)) {
            $price_var = (float)number_format($data->variation->price, 2, '.', '');
            $list_price_var = $price_var;
            if (isset($data->variation->list_price)) {
                if (!is_null($data->variation->list_price)) {
                    $list_price_var = (float)number_format($data->variation->list_price, 2, '.', '');
                }
            }
            foreach ($this->getDataVariation(false)->result_array() as $dataVar) {
                // verica se alteracao e de preco
                $str_price = (float)number_format($dataVar["price"], 2, '.', '');
                $str_list_price = $str_price;
                if (array_key_exists('list_price',$dataVar)) {
                    if (!is_null($dataVar["list_price"])) {
                        $str_list_price = (float)number_format($dataVar["list_price"], 2, '.', '');
                    }
                }

                if (strval($price_var) != strval($str_price)) {
                    // verica se usa catalogo
                    $catalog = $this->getDataCatalogByStore($this->store_id);
                    if ($catalog) {
                        // verica se usa paramtro => catalog_products_dont_modify_price
                        $catalog_products_dont_modify_price = $this->model_settings->getSettingDatabyName('catalog_products_dont_modify_price');
                        if ($catalog_products_dont_modify_price) {
                            if ($catalog_products_dont_modify_price['status'] == 1) {
                                $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                                return;
                            }
                        }
                    }
                }
            }
        }

        if (isset($data->variation->price) && !empty($data->variation->price)) {
            $arrSql['price'] = roundDecimal($data->variation->price);
        }

        if (isset($data->variation->list_price) && !empty($data->variation->list_price)) {
            $arrSql['list_price'] = roundDecimal($data->variation->list_price);
        }

        if (isset($arrSql['list_price']) && isset($arrSql['price'])) {
            if ($arrSql['list_price'] < $arrSql['price']) {
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_list_price_price') . " SKU: {$this->sku_var}")), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        $data_variation_db = $this->getDataVariation()->row_array();

        // foi informado o sku na variação, mas não foi encontrada.
        if (!empty($this->sku_var) && !$data_variation_db) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_code_not_found'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }

        // price não deve ser maior que o list_price
        if (!empty($this->sku_var) && $data_variation_db && isset($arrSql['price']) && !isset($arrSql['list_price'])) {
            if ($arrSql['price'] > $data_variation_db['list_price']) {
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_list_price_price'))), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        // price não deve ser maior que o list_price
        if (!empty($this->sku_var) && $data_variation_db && isset($arrSql['list_price']) && !isset($arrSql['price'])) {
            if ($data_variation_db['price'] > $arrSql['list_price']) {
                $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_list_price_price'))), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        // Verifica se foi informado um SKU
        if (!empty($this->sku)) {
           $result = $this->sku_var ? $this->update_sku($data, $dataProduct, $data_variation_db) : $this->update($data, $dataProduct);
        }

        if (!empty($result['updated'])) {
            $this->sendProductToQueue($dataProduct->id);
        }

        // Verifica se foram encontrados resultados
        if (isset($result['error']) && $result['error']) {
            $http_code = $result['data'] === 'Feature unavailable.' ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_NOT_FOUND;

            if (isset($result['updated']) && $result['updated']) {
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,'payload: ' . json_encode($data),"I");
                $this->model_log_products->create_log_products((array)$data->variation, $dataProduct->id, 'Variation - API - : '.$identify_log);
                $http_code = REST_Controller::HTTP_OK;
            }
            // se não tiver autorização para atualizar o produto alem do estoque e preço, sera retornado um status 401
            $this->response($this->returnError($result['data']), $http_code, $this->createButtonLogRequestIntegration($data));
            return;
            // o certo nao seria assim ? => return $this->response($this->returnError(array('error' => true, 'data' => $result['data'])), $result['data'] === 'Feature unavailable.' ? REST_Controller::HTTP_UNAUTHORIZED : REST_Controller::HTTP_NOT_FOUND);
        }

        $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,'payload: ' . json_encode($data),"I");
        $this->model_log_products->create_log_products((array)$data->variation, $dataProduct->id, 'Variation - API - : '.$identify_log);
        $this->response(array('success' => true, "message" => $this->lang->line('api_variation_updated')), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
    }

    public function index_post($sku = null)
    {
        // Recupera dados enviado pelo body
        $data = $this->inputClean();
        $sku = xssClean($sku);
   
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
            return;
        }

        if ($this->store_id){
            $catalog = $this->getDataCatalogByStore($this->store_id);
            if ($catalog) {
                $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            return $this->response(array('success' => false, "message" => utf8_decode($disable_message)), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }        

        $this->sku = (string) $sku;
        if (empty($this->sku)) {
            $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }
        $dataProduct = $this->getDataProduct();
        if (empty($dataProduct)) {
            $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_code_not_found'))), REST_Controller::HTTP_NOT_FOUND, $this->createButtonLogRequestIntegration($data));
            return;
        }
        foreach ($this->getDataVariation(false)->result_array() as $dataVar)
            if (!empty($dataVar['EAN'])) {
                $this->arrEANsCheck[] = $dataVar['EAN'];
            }

        // Verifica se foi informado um SKU
        $result = $this->insert($data);

        // Verifica se foram encontrados resultados
        if(isset($result['error']) && $result['error']){
            $this->response($this->returnError($result['data']), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        $this->response(array('success' => true, "message" => $this->lang->line('api_variation_inserted')), REST_Controller::HTTP_CREATED, $this->createButtonLogRequestIntegration($data));
    }

    public function index_delete($sku = null, $sku_code = null, $id_code = null)
    {   
        if(!$sku){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_informed'),"W");
            return $this->response($this->returnError($this->lang->line('api_sku_not_informed')), REST_Controller::HTTP_NOT_FOUND);
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        if($this->store_id){
            $catalog = $this->getDataCatalogByStore($this->store_id);
            if ($catalog)
                return $this->response(array('success' => false, "message" =>  $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED);
        }

        
        //if (!$this->app_authorized || $this->company_id == $this->company_sika)
        //    return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        if($sku === "code"){
            if(!$sku_code || !$id_code){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_code_sku_not_match'),"W");
                return $this->response($this->returnError($this->lang->line('api_code_sku_not_match')), REST_Controller::HTTP_NOT_FOUND);
            }
            $this->sku = (string) $sku_code;
            $this->id_var = (string) $id_code;
            if(!$this->checkSKUandId()){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_code_sku_not_match'),"W");
                return $this->response($this->returnError($this->lang->line('api_code_sku_not_match')), REST_Controller::HTTP_NOT_FOUND);
            }
        }
        elseif($sku === "sku"){
            if(!$sku_code || !$id_code){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND);
            }
            $this->sku = (string) $sku_code;
            $this->sku_var = $id_code;
            if(!$this->checkSKUandSkuVar()){
                $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_not_match'),"W");
                return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_sku_not_match'))), REST_Controller::HTTP_NOT_FOUND);
            }
        }
        else $this->sku = (string) $sku;

        $dataProduct = $this->getDataProduct();
        if(!$dataProduct){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_sku_code_not_found') . " - sku: {$this->sku}","W");
            return $this->response($this->returnError($this->lang->line('api_sku_code_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }

        $canUpdateProduct   = true;
        $integrations       = $this->getPrdIntegration($dataProduct->id);

        foreach ($integrations as $integration) {
            // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
            if ($canUpdateProduct && $integration['skumkt']) {
                $canUpdateProduct = false;
            }
        }

        if (!$canUpdateProduct) {
            return $this->response(array('success' => false, "message" => $this->lang->line('api_product_already_integrated')), REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Verifica se foi informado um SKU
        $result = $this->id_var ? $this->remove_code($dataProduct) : ($this->sku_var ? $this->remove_sku($dataProduct) : $this->remove($dataProduct));

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
//            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $result['data'] . " - sku: {$this->sku}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, "message" => $this->lang->line('api_product_removed')), REST_Controller::HTTP_OK);
    }

    private function remove_code($dataProduct)
    {
        $this->db->trans_begin();

        $this->db->where('id', $this->id_var);
        $this->db->delete('prd_variants');

        $codsVariants = $this->getIdVariantProduct();
        if(count($codsVariants) === 0) {
            $sqlUpdateVariationProduct = $this->db->update_string('products', array('has_variants' => ""), array('sku' => $this->sku, 'store_id' => $this->store_id));
            $this->db->query($sqlUpdateVariationProduct);
        }

        $this->updateStockProduct($dataProduct);
        $this->updateOrderVariationAfterDelete();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function remove_sku($dataProduct)
    {
        $this->db->trans_begin();

        $this->db->where('sku', $this->sku_var);
        $this->db->delete('prd_variants');

        $codsVariants = $this->getIdVariantProduct();
        if(count($codsVariants) === 0) {
            $sqlUpdateVariationProduct = $this->db->update_string('products', array('has_variants' => ""), array('sku' => $this->sku, 'store_id' => $this->store_id));
            $this->db->query($sqlUpdateVariationProduct);
        }

        $this->updateStockProduct($dataProduct);
        $this->updateOrderVariationAfterDelete();

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' =>$this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function updateOrderVariationAfterDelete()
    {
        $sql = "SELECT p.sku as sku_p, v.variant, v.id, v.sku as sku_v 
                FROM products as p 
                JOIN prd_variants as v ON p.id = v.prd_id 
                WHERE p.sku = ? 
                AND store_id = ? 
                ORDER BY v.variant";

        $query = $this->db->query($sql, array($this->sku, $this->store_id));

        // Inicia transação
        $countVar = 0;

        foreach($query->result_array() as $new_var){
            $arr = array();
            if(substr($new_var['sku_v'], -2) ==  "-" . $new_var['variant']){
                $arr['sku'] = $new_var['sku_p'] . "-" . $countVar;
            }
            $arr['variant'] = $countVar;

            $countVar++;
            $strUpdate = $this->db->update_string('prd_variants', $arr, array('id' => $new_var['id']));
            $this->db->query($strUpdate);
        }
    }

    private function remove($dataProduct)
    {
        $codsVariants = $this->getIdVariantProduct();

        $this->db->trans_begin();

        if(count($codsVariants) === 0) return array('error' => true, 'data' => $this->lang->line('api_product_not_variation'));

        $this->db->where_in('id', $codsVariants);
        $this->db->delete('prd_variants');

        $sqlUpdateVariationProduct = $this->db->update_string('products', array('has_variants' => ""), array('sku' => $this->sku, 'store_id' => $this->store_id));
        $this->db->query($sqlUpdateVariationProduct);

        $this->updateStockProduct($dataProduct);

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' =>$this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        return array('error' => false);
    }

    private function insert($data)
    {
        $dataSql        = array();
        $erroColumn     = "";
        $arrVariations  = array();
        $newVariation   = false;
        $arrHasVariations = array();
        $skuVariationValid = array();
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if (!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) {
            $limite_imagens_aceitas_api = 6;
        }

        if (!isset($data->variation)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_variation_key'));
        }
        if (count((array)$data->variation) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_found_no_data'));
        }
        if (!isset($data->variation->product_variations) || count($data->variation->product_variations) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_product_variations_blank'));
        }
        if (!isset($data->variation->sku) && $this->sku === null) {
            return array('error' => true, 'data' => $this->lang->line('api_sku_code_not_found'));
        }

        if (isset($data->variation->sku)) {
            $this->sku = (string)$data->variation->sku;
        }

        $dataVar = $this->getLastVarProduct();
        $dataProduct = $this->getDataProduct();

        if (count($dataVar) === 0) {
            $newVariation       = true;
            $dataVar['var']     = $data->variation->types_variations;
            $dataVar['variant'] = 0;
            $dataVar['prd_id']  = $dataProduct->id;
            $dataVar['sku_v']   = array();
        }

        if (count($dataVar['var']) == 0) {
            return array('error' => true, 'data' => $this->lang->line('api_product_has_no_variation'));
        }

        $canUpdateProduct   = true;
        $integrations       = $this->getPrdIntegration($dataVar['prd_id']);

        foreach ($integrations as $integration) {
            // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
            if ($canUpdateProduct && $integration['skumkt']) {
                $canUpdateProduct = false;
            }
        }

        /*if (!$canUpdateProduct) {
            return array('error' => true, 'data' => $this->lang->line('api_product_already_integrated'));
        }*/

        $arrVarExist = $dataVar['var'];
        $countVariant = (int)$dataVar['variant'];

        $skuVariationValid = array_merge($this->getSkusVarProduct(), $skuVariationValid);

        foreach ($data->variation->product_variations as $keySql => $variations) {
            $countVariant++;
            $dataSql[$keySql] = array('prd_id' => $dataVar['prd_id'], 'variant' => $countVariant, 'sku' => null, 'status' => 1);
            $variations = (array)$variations;

            $valuesVarList = [];
            $typesVarList = [];
            foreach ($data->variation->types_variations ?? [] as $typeCode) {
                list($varCode, $variations) = $this->fetchCustomAttributesMapByCriteria($typeCode, $variations, true);
                if ($newVariation || in_array($varCode, $dataVar['var'])) {
                    $typesVarList[$typeCode] = $varCode;
                } else {
                    unset($variations[$varCode]);
                }
            }
            $typesVarList = $this->sortVariationTypesByCode($typesVarList ?? []);
            foreach ($typesVarList as $varExist) {
                if (!key_exists($varExist, $variations) && $erroColumn === "") {
                    $erroColumn = ($keySql + 1) . $this->lang->line('api_sku_variation_missing_attribute') . $varExist;
                    break;
                }

                if (in_array($varExist, array_values($this->type_variation)) && !empty($variations[$varExist] ?? '')) {
                    if (in_array($varExist, ['voltage'])) {
                        /*if (!in_array(strtolower($variations[$varExist]), [
                            '110', '220', '110v', '220v', 'bivolt'
                        ])) {
                            $erroColumn = $this->lang->line('api_the_volt') . ($keySql + 1) . $this->lang->line('api_variation_volt');
                            break;
                        }*/
                        $variations[$varExist] = strcasecmp($variations["voltage"], 'bivolt') === 0 ? 'Bivolt' : $variations[$varExist];
                        //$existVolt = in_array($variations[$varExist], ['110', '220']) ? 'V' : '';
                        //$variations[$varExist] = "{$variations[$varExist]}{$existVolt}";
                    }
                    $arrHasVariations[$varExist] = $varExist;
                    $valuesVarList[] = $variations[$varExist];
                }
            }
            $arrVarExist = array_merge($typesVarList, ['qty' => 'qty']);
            $dataSql[$keySql]['name'] = !empty($valuesVarList) ? implode(';', $valuesVarList) : '';

            if (empty($variations['sku_variation'] ?? '')) {
                return array('error' => true, 'data' => ($keySql + 1) . $this->lang->line('api_contain_field'));
            }

            $dataSql[$keySql]['price'] = (float)$dataProduct->price;
            $dataSql[$keySql]['list_price'] = (float)$dataProduct->list_price;
            $dataSql[$keySql]['image'] = md5(round(microtime(true) * 100000).rand(11111111,99999999));

            $checkVarExist = array_filter(array_map(function($checkVariations) use ($variations) {
                switch ($checkVariations) {
                    case "size":
                        return $variations['size'];
                    case "color":
                        return $variations['color'];
                    case "voltage":
                        return $variations['voltage'];
                    case "flavor":
                        return $variations['flavor'];
                    case "degree":
                        return $variations['degree'];
                    case "side":
                        return $variations['side'];
                    default:
                        return null;
                }
            }, $arrHasVariations), function($check_variation){
                return !is_null($check_variation);
            });

            $variations_new_variation = array_keys($checkVarExist);
            // Criando a primeira variação no produto
            if (empty($dataProduct->has_variants)) {
                $dataProduct->has_variants = implode(';', array_map(
                    function ($var) {
                        return $this->getPortugueseNameVariation($var);
                    }, $variations_new_variation
                ));
            } else {
                //Verificar se variações existem.
                $variations_product = array_map(
                    function($var){
                        return $this->getRealNameVariation($var);
                    }, explode(';', $dataProduct->has_variants)
                );

                foreach ($variations_product as $variation_product) {
                    if (!in_array($variation_product, $variations_new_variation)) {
                        return array('error' => true, 'data' => $this->lang->line('api_product_variation_type_invalid') . " ({$this->getPortugueseNameVariation($variation_product)})");
                    }
                }
            }


            // Não existem tipos de variação.
            $varition_check_exist = implode(';', $checkVarExist);
            if (empty($varition_check_exist)) {
                return array('error' => true, 'data' => $this->lang->line('api_product_variation_type_invalid'));
            }

            // Tipo duplicado.
            if (!empty($this->model_products->getVariantsByProd_idAndName($dataProduct->id, $varition_check_exist))) {
                return array('error' => true, 'data' => $this->lang->line('api_product_variation_is_already_in_use')." [$varition_check_exist]");
            }

            foreach ($variations as $key => $value) {
                if (in_array($key, $this->type_variation)) {
                    continue;
                }
                if (
                    !in_array($key, $arrVarExist) &&
                    $erroColumn === "" &&
                    $key != "price" &&
                    $key != "list_price" &&
                    $key != "qty" &&
                    $key != "sku_variation" &&
                    $key != "images" &&
                    $key != 'ean' &&
                    $key != 'EAN'
                ) {
                    $erroColumn = $this->lang->line('api_parameter_not_match_field_insert') . $key;
                }

                if ($value === "" && $key == "qty") {
                    $erroColumn = $this->lang->line('api_all_fields_informed') . $key;
                }

                if ($key == "sku_variation") {
                    if ($value == '') {
                        $erroColumn = $this->lang->line('api_sku_not_informed_variation');
                    }

                    if ($value == $this->sku) {
                        $erroColumn = $this->lang->line('api_sku_variation_different');
                    }

                    if (!$this->checkSkuAvailable($value, $dataVar['prd_id'])) {
                        $erroColumn = $this->lang->line('api_sku_in_use_other') . " SKU: {$value}";
                    }

                    $dataSql[$keySql]['sku'] = $value;
                    if (!in_array($value, $skuVariationValid)) {
                        $skuVariationValid[] = $value;
                    } else {
                        $erroColumn = $this->lang->line('api_two_equal');
                    }
                    continue;
                }

                if ($key == "qty") {
                    $dataSql[$keySql]['qty'] = (int)$value;
                }

                if ($key == "price") {
                    $dataSql[$keySql]['price'] = (float)number_format($value, 2, '.', '');
                    if ($dataSql[$keySql]['price'] == 0) {
                        $erroColumn = $this->lang->line('api_price_negative') . ($keySql + 1) . $this->lang->line('api_variation');
                    }
                }

                if ($key == "list_price") {
                    $dataSql[$keySql]['list_price'] = (float)number_format($value, 2, '.', '');
                    if (empty($dataSql[$keySql]['list_price'])) {
                        $dataSql[$keySql]['list_price'] = $dataSql[$keySql]['price'];
                    }
                    if ($dataSql[$keySql]['list_price'] < $dataSql[$keySql]['price'])  {
                        return array('error' => true, 'data' => $this->lang->line('api_list_price_price') . ($keySql + 1) . $this->lang->line('api_variation_th'));
                    }
                }

                if (isset($dataSql[$keySql]['price']) || isset($dataSql[$keySql]['list_price'])) {
                    if (empty($dataSql[$keySql]['price']) && empty($dataSql[$keySql]['list_price'])) {
                        return array('error' => true, 'data' => $this->lang->line('application_prices_error'));
                    }
        
                    if ((empty($dataSql[$keySql]['price']) && !empty($dataSql[$keySql]['list_price']))) {
                        $dataSql[$keySql]['price'] = $dataSql[$keySql]['list_price'];
                    }
        
                    if (empty($dataSql[$keySql]['list_price']) && !empty($dataSql[$keySql]['price'])) {
                        $dataSql[$keySql]['list_price'] = $dataSql[$keySql]['price'];
                    }
                }

                if ($key == "ean" || $key == "EAN") {
                    if (!$this->model_products->ean_check($value)) {
                        $erroColumn = $this->lang->line('api_ean_of') . ($keySql + 1) . $this->lang->line('api_invalid_variation');
                    }

                    if (!empty($value)) {
                        if (in_array($value, $this->arrEANsCheck)) {
                            $erroColumn = $this->lang->line('api_same_ean_variation') . ($keySql + 1) . $this->lang->line('api_variation_ean') . $value;
                        }

                        $this->arrEANsCheck[] = $value;
                    }

                    $dataSql[$keySql]['EAN'] = $value;
                }

                if ($key == "images") {
                    $dataSql[$keySql]['arrImages'] = (array)$value;

                    if (count($dataSql[$keySql]['arrImages']) > $limite_imagens_aceitas_api) {
                        $erroColumn = $this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images_in') . ($keySql + 1) . $this->lang->line('api_variation_th');
                    }
                }
            }
        }

        // Erros gerado no laço dos itens
        if ($erroColumn !== "") {
            return array('error' => true, 'data' => $erroColumn);
        }

        if (count($arrHasVariations) == 0 && $newVariation) {
            return array('error' => true, 'data' => $this->lang->line('api_no_type_variation_found'));
        }

        // Inicia transação
        $this->db->trans_begin();

        // Pasta do produto pai
        $pathProd = $dataProduct->image;
        // Verificar se as variações enviadas são as mesmas exstente no produto.

        foreach ($dataSql as $data) {
            $arrImages = $data['arrImages'] ?? null;
            // removo o arr de imagens para não enviar na query
            unset($data['arrImages']);

            if (is_null($data['EAN'])) {
                $data['EAN'] = '';
            }
            // inserir na tabela prd_variants com o sku e id do produto inserido
            $this->model_products->createvar($data);

            // Upload de imagens
            $uploadImg = $this->uploadImageVariation($arrImages, $pathProd, $data['image'], true);
            
            $ordem = 0;
            foreach ($arrImages as $arrImagesVar) {
                $LinkImageVar = is_array($arrImagesVar) ? current($arrImagesVar) : $arrImagesVar;
                if ($LinkImageVar == "") {
                    continue;
                }
                $dataSqlImgVar = array(
                    'store_id'      => $this->store_id, 
                    'company_id'    => $this->company_id, 
                    'ordem'         => $ordem,
                    'prd_id'        => $data['prd_id'],
                    'variant'       => $data['variant'],
                    'original_link' => $LinkImageVar,
                    'pathProd'      => $pathProd,
                    'pathVariant'   => $data['image'],
                    'status'        => 0,
                    'error'         => null
                );
                $this->model_products->createImage($dataSqlImgVar);
                $ordem++;
            }

            // Erro em upload de imagem
            if ($uploadImg['error'] != 0) {
                $this->db->trans_rollback();
                if ($uploadImg['error'] == 1) {
                    return array('error' => true, 'data' => $uploadImg['data']);
                }
                if ($uploadImg['error'] == 2) {
                    return array('error' => true, 'data' => $uploadImg['data']);
                }
                return array('error' => true);
            }
        }

        if ($newVariation) {
            $varArrHasVariation = [];
            foreach ($arrHasVariations as $code => $type_v) {
                $varArrHasVariation[$code] = $this->getPortugueseNameVariation($type_v);
            }
            $varArrHasVariation = $this->sortVariationTypesByName(array_values($varArrHasVariation));
            $hasVariation = !empty($varArrHasVariation) ? implode(';', $varArrHasVariation) : '';
            $this->model_products->updateProductBySkuAndStore(array('has_variants' => $hasVariation), $this->sku, $this->store_id);
        }

        $this->updateStockProduct($dataProduct);

        $imgsUpload = $this->bucket->getFinalObject("assets/images/product_image/".$pathProd);
        if (count($imgsUpload['contents']) === 0) {
            $principal_image_variant = null;
            foreach ($this->model_products->getVariantsByProd_id($dataVar['prd_id']) as $variant) {
                $path_variation = "assets/images/product_image/$pathProd/$variant[image]";
                $images = $this->bucket->getFinalObject($path_variation);

                foreach ($images['contents'] as $image) {
                    if ($image != "") {
                        $principal_image_variant = $image["url"];
                        break 2;
                    }
                }
            }

            $update_var = array();

            if (is_null($principal_image_variant)) {
                $update_var['situacao'] = Model_products::INCOMPLETE_SITUATION;
                $update_var['principal_image'] = NULL;
            } else if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
                if (
                    ($dataProduct->category_id != '[""]' || $publishWithoutCategory) &&
                    $dataProduct->brand_id != '[""]'
                ) {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
                if ($principal_image_variant !== $dataProduct->principal_image) {
                    $update_var['principal_image'] = $principal_image_variant;
                }
            } else {
                if (
                    $dataProduct->category_id != '[""]' &&
                    $dataProduct->brand_id != '[""]'
                ) {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
                if ($principal_image_variant !== $dataProduct->principal_image) {
                    $update_var['principal_image'] = $principal_image_variant;
                }
            }

            if (!empty($update_var)) {
                $this->model_products->update($update_var, $dataProduct->id);
            }
        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        $this->updatePriceProduct($dataProduct);
        $this->updateListPriceProduct($dataProduct);

        return array('error' => false);

    }

    private function updateStockProduct($dataProduct)
    {
        $qty = $this->getStockVariation($dataProduct);
        if ($qty !== false) {
            $product = $this->model_products->getProductCompleteBySkyAndStore($this->sku, $this->store_id);
            if ($product['qty'] != $qty) {
                $this->model_products->updateProductBySkuAndStore(array('qty' => $qty, 'stock_updated_at' => date('Y-m-d H:i:s')), $this->sku, $this->store_id);
            }
        }
    }

    private function updatePriceProduct($dataProduct = null)
    {

        $price = 0;
        if (is_null($dataProduct)) {
            // get data product
            $sql    = "SELECT id,price FROM products WHERE sku = ? AND store_id = ?";
            $query  = $this->db->query($sql,array($this->sku, $this->store_id));
            if ($query->num_rows() == 0) {
                return false;
            }
            $dataProduct = $query->first_row();
        }
        $price_product = $dataProduct->price;

        $sql    = "SELECT price,variant FROM prd_variants WHERE prd_id = ?";
        $query  = $this->db->query($sql,array($dataProduct->id));
        if ($query->num_rows() == 0) {
            return false;
        }

        foreach($query->result_array() as $var) {
            $price_var = $var['price'];

            $price = max($price, $price_var);
        }

        if ($price_product != $price) {
            $this->model_products->update(array('price' => $price), $dataProduct->id);
        }

        return true;
    }
    
    private function updateListPriceProduct($dataProduct = null)
    {
        $list_price = 0;

        if (is_null($dataProduct)) {
            // get data product
            $sql    = "SELECT id,list_price FROM products WHERE sku = ? AND store_id = ?";
            $query  = $this->db->query($sql,array($this->sku, $this->store_id));
            if ($query->num_rows() == 0) {
                return false;
            }
            $dataProduct = $query->first_row();
        }

        $list_price_product = $dataProduct->list_price;

        $sql    = "SELECT list_price, variant FROM prd_variants WHERE prd_id = ?";
        $query  = $this->db->query($sql, array($dataProduct->id));
        if ($query->num_rows() == 0) {
            return false;
        }

        foreach ($query->result_array() as $var) {
            $list_price_var = $var['list_price'];

            $list_price = max($list_price, $list_price_var);
        }

        if ($list_price_product != $list_price) {
            $this->model_products->update(array('list_price' => $list_price), $dataProduct->id);
        }

        return true;
    }

    private function getStockVariation($dataProduct)
    {
        $countVar = 0;

        $dataVariations = $this->model_products->getVariantsByProd_id($dataProduct->id);
        if (empty($dataVariations)) {
            return false;
        }

        foreach($dataVariations as $var) {
            $countVar += $var['qty'];
        }

        return $countVar;
    }

    private function getLastVarProduct()
    {
        $arrResult = array();

        $sql = "SELECT p.has_variants, p.id, v.variant, v.sku as sku_v  FROM products as p 
        JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND store_id = ? ORDER BY v.variant DESC";
        $query = $this->db->query($sql, array($this->sku, $this->store_id));

        if($query->num_rows() === 0) return $arrResult;

        $variations = $query->first_row();

        if($variations->has_variants === "") return $arrResult;


        foreach (explode(";", $variations->has_variants) as $var)
            array_push($arrResult, $this->type_variation[$var]);

        return array(
            'var'       => $arrResult,
            'prd_id'    => $variations->id,
            'variant'   => $variations->variant
        );
    }

    private function getSkusVarProduct()
    {
        $arrResult = array();

        $sql = "SELECT p.has_variants, p.id, v.variant, v.sku as sku_v  FROM products as p JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND store_id = ?";
        $query = $this->db->query($sql, array($this->sku, $this->store_id));

        if($query->num_rows() === 0) {return $arrResult;}

        $variations = $query->result_array();

        foreach ($variations as $var) {
            array_push($arrResult, $var['sku_v']);
        }

        return $arrResult;
    }

    private function update_sku($data, $dataProduct, $data_variation_db)
    {
        if(!isset($data->variation)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_variation_key'));
        }
        if(count((array)$data->variation) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }

        // primeiro atualizamos preços, estoque e status
        $arrSql = array();
        if(isset($data->variation->qty)) {
            $arrSql['qty'] = (int)$data->variation->qty;
        }
        if(isset($data->variation->price) && !empty($data->variation->price)) {
            $arrSql['price'] = (float)number_format($data->variation->price, 2, '.', '');
        }
        if(isset($data->variation->list_price) && !empty($data->variation->list_price)) {
            $arrSql['list_price'] = (float)number_format($data->variation->list_price, 2, '.', '');
        }

        if (isset($arrSql['price']) || isset($arrSql['list_price'])) {
            if (empty($arrSql['price']) && empty($arrSql['list_price'])) {
                return array('error' => true, 'data' => $this->lang->line('application_prices_error'));
            }

            // Existe price e list_price, serã validado para saber se o price é maior que o list_price
            if (isset($arrSql['price']) && isset($arrSql['list_price'])) {
                if ($arrSql['price'] > $arrSql['list_price']) {
                    return array('error' => true, 'data' => $this->lang->line('api_list_price_price'));
                }
            }

            // price não deve ser maior que o list_price
            if ($data_variation_db && isset($arrSql['price']) && !isset($arrSql['list_price'])) {
                if ($arrSql['price'] > $data_variation_db['list_price']) {
                    return array('error' => true, 'data' => $this->lang->line('api_list_price_price'));
                }
            }

            // price não deve ser maior que o list_price
            if ($data_variation_db && isset($arrSql['list_price']) && !isset($arrSql['price'])) {
                if ($data_variation_db['price'] > $arrSql['list_price']) {
                    return array('error' => true, 'data' => $this->lang->line('api_list_price_price'));
                }
            }
        }

        if (isset($arrSql['qty']) && (int)$arrSql['qty'] <= 0) {
            $arrSql['qty'] = 0;
        }

        if (isset($data->variation->active) && in_array($data->variation->active, [
            self::STATUS_ENABLED,
            self::STATUS_DISABLED
        ])) {
            $arrSql['status'] = self::MAP_STATUS[$data->variation->active] ?? Model_products::ACTIVE_PRODUCT;

            // Produto ficou inativo, zerar estoque.
            if (self::MAP_STATUS[$data->variation->active] == self::MAP_STATUS['disabled']) {
                $arrSql['qty'] = 0;
            }
        }

        if (isset($arrSql['price']) && $arrSql['price'] <= 0)
            return array('error' => true, 'data' => $this->lang->line('api_variation_price'));

        $updated = false;
        $varId = $this->getDataVariation()->row_array();
        if (!empty($arrSql)) {
            $this->model_products->updateVariationData($varId['id'], $dataProduct->id ,$arrSql);

            $updated = true;
        }

        if (isset($arrSql['qty'])) {
            $this->updateStockProduct($dataProduct);
        }

        if (isset($arrSql['price'])) {
            $this->updatePriceProduct($dataProduct);
        }

        if (isset($arrSql['list_price'])) {
            $this->updateListPriceProduct($dataProduct);
        }

        // agora tento alterar os demais campos
        $arrSql = array();
        if (isset($data->variation->ean)) {
            $arrSql['EAN'] = $data->variation->ean;
        }
        if (isset($data->variation->EAN)) {
            $arrSql['EAN'] = $data->variation->EAN;
        }
        if (isset($data->variation->images)) {
            $arrSql['images'] = (array)$data->variation->images;
        }

        $canUpdateProduct   = true;
        $integrations       = $this->getPrdIntegration($dataProduct->id);
        $api_integrations = $this->model_api_integrations->getIntegrationByStoreId($dataProduct->store_id);

        if (isset($this->user_email)) {
            $user_id = $this->model_users->getUserByEmail($this->user_email);
            // recuperar permissão no grupo do usuário, se pode solicitar um update
            $groupUser = $this->model_groups->getUserGroupByUserId($user_id[0]["id"]);
            foreach ($integrations as $integration) {
                // Produto já tem código SKU no marketplace e usuario não é administrador, não pode ser mais alterado.
                if ($canUpdateProduct && $integration['skumkt']) {
                    if (!$groupUser['only_admin']) {
                        $canUpdateProduct = false;
                        break;
                    }
                }
            }
        } else {
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

        if (!$canUpdateProduct && $store_integration != 'viavarejo_b2b') {
            if (empty($arrSql)) { // náo veio nada a mais, tá ok
                return array('error' => false);
            }

            if ($updated) {  // O resto não pode mais mudar
                return array('updated' => true, 'error' => true, "data" => $this->lang->line('api_product_already_integrated_qty_price_update'));
            }

            return array('error' => true, "data" => $this->lang->line('api_product_already_integrated'));
        }

        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if (!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) {
            $limite_imagens_aceitas_api = 6;
        }
        if (isset($arrSql['images']) && count($arrSql['images']) > $limite_imagens_aceitas_api) {
            return array('updated' => $updated, 'error' => true, 'data' => $this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images_variations'));
        }

        if (isset($arrSql['EAN'])) {  // por enquanto, só tem o EAN a mais
            if (!$this->model_products->ean_check($arrSql['EAN'])) {
                return array('updated' => $updated, 'error' => true, 'data' => $this->lang->line('api_invalid_ean_variation'));
            }
            if (!empty($arrSql['EAN'])) {
                if (in_array($arrSql['EAN'], $this->arrEANsCheck)) {
                    return array('updated' => $updated, 'error' => true, 'data' => $this->lang->line('api_same_ean_variation_ean') . $arrSql['EAN']);
                }

                $exist =$this->model_products->VerifyEanUnique($arrSql['EAN'], $this->store_id, $dataProduct->id); 
                if ($exist) {
                    return array('updated' => $updated, 'error' => true, 'data' => "O mesmo EAN não é permitido em mais de um produto ou variação. Este EAN ".$arrSql['EAN']." está sendo usado no produto de id ".$exist);
                }
                $this->arrEANsCheck[] = $arrSql['EAN'];
            }
            if (!empty($arrSql)) {
                // removo o arr de imagens para não enviar na query
                unset($arrSql['images']);
                if (is_null($varId)){
                    $varId = $this->getDataVariation(true)->row_array();
                }
                $strUpdate = $this->db->update_string('prd_variants', $arrSql, array('id' => $varId['id']));
                $this->db->query($strUpdate);
            }
        }

        if (isset($data->variation->images)) {
            $arrSql['images'] = (array)$data->variation->images; //pego de novo as imagens
        }
        $arrImages = $arrSql['images'] ?? null;

        // Inicia transação
        //$this->db->trans_begin();

        // Upload de imagens
        $pathProd =  $dataProduct->image;
        $pathVar = $this->getPathImage();
        if (trim($pathProd) == '') {
            log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro ao pegar o path do produto da variacao '.$this->id_var.' skuvar '.$this->sku_var.' sku '.$this->sku.' loja '.$this->store_id );
            return array('updated' => $updated, 'error' => true, 'data' => 'CONTACTE O SUPORTE IMEDITAMENTE.PROBLEMA NO PRODUTO');
        }
        if (trim($pathVar) == '') {
            log_message('error', 'APAGA '.$this->router->fetch_class().'/'.__FUNCTION__.' erro ao pegar o path da variacao '.$this->id_var.' skuvar '.$this->sku_var.' sku '.$this->sku.' loja '.$this->store_id);
            return array('updated' => $updated, 'error' => true, 'data' => 'CONTACTE O SUPORTE IMEDITAMENTE. PROBLEMA NA VARIACAO');
        }
        if (is_array($arrImages) && count($arrImages)) {

            $old_images = null;
            $bucketPath = $this->uploadproducts::PRODUCT_IMAGE_FOLDER."/$pathProd/$pathVar";
            if (!$dataProduct->is_on_bucket) {
                $old_images = $this->uploadproducts->getImagesFileByPath("$pathProd/$pathVar");
            } else {
                $bucketImages = $this->bucket->getFinalObject($bucketPath);
                if ($bucketImages["success"]) {
                    $old_images = array_column($bucketImages['contents'], "key");
                }
            }
            //$this->uploadproducts->deleteImagesDir("$pathProd/$pathVar");
            $uploadImg = $this->uploadImageVariation($arrImages, $pathProd, $pathVar, false);

             // Erro em upload de imagem
            if ($uploadImg['error'] != 0) {
                log_message('error', 'Erro ao fazer upload de imagens para o produto ' . $dataProduct->id . ' no diretório ' . $pathProd . '/' . $pathVar);

                // Verifica se há imagens que falharam e adiciona um log específico
                if (!empty($uploadImg['failed_images'])) {
                    foreach ($uploadImg['failed_images'] as $failedImage) {
                        log_message('error', 'Imagem falhou no upload: ' . $failedImage . ' para o produto ' . $dataProduct->id . ' na loja ' . $this->store_id);
                    }
                }

                if ($uploadImg['error'] == 1) {
                    return array('updated' => $updated, 'error' => true, 'data' => $uploadImg['data']);
                }
                if ($uploadImg['error'] == 2) {
                    return array('updated' => $updated, 'error' => true, 'data' => $uploadImg['data']);
                }
                return array('updated' => $updated, 'error' => true, 'data' => "As imagens enviadas não são válidas.");
            }

            // Todos os envios deram erro.
            if ($uploadImg['count_image_error'] == count($arrImages)) {
                return array('updated' => $updated, 'error' => true, 'data' => "As imagens enviadas não são válidas.");
            }

            if (!$dataProduct->is_on_bucket) {
                $this->uploadproducts->deleteImgError($old_images, "$pathProd/$pathVar");
            } else {
                $this->uploadproducts->deleteImgErrorBucket($old_images, "$pathProd/$pathVar");
            }

            $this->model_products->deleteImageVariation($pathProd,$pathVar);
            $ordem = 0;
            foreach ($arrImages as $arrImagesVar) {
                $LinkImageVar = is_array($arrImagesVar) ? current($arrImagesVar) : $arrImagesVar;
                if ($LinkImageVar == "") continue;

                $dataSqlImgVar = array(
                    'store_id'      => $this->store_id, 
                    'company_id'    => $this->company_id, 
                    'ordem'         => $ordem,
                    'prd_id'        => $dataProduct->id,
                    'variant'       => $this->id_var,
                    'original_link' => $LinkImageVar,
                    'pathProd'      => $pathProd,
                    'pathVariant'   => $pathVar,
                    'status'        => 0,
                    'error'         => null
                );
                $this->model_products->createImage($dataSqlImgVar);
                $ordem++;
            }
        }

        if (!$dataProduct->is_on_bucket) {
            $imgsUpload = $this->uploadproducts->countImagesDir($varId['image_p']);
        } else {
            $bucketPath = $this->uploadproducts::PRODUCT_IMAGE_FOLDER."/".$varId['image_p'];
            $imgsBucket = $this->bucket->getFinalObject($bucketPath);
            if($imgsBucket['success']){
                $imgsUpload = count($imgsBucket['contents']);
            }
        }

        if ($varId && $imgsUpload === 0) {
            $principal_image_variant = null;

            foreach ($this->model_products->getVariantsByProd_id($dataProduct->id) as $variant) {
                $path_variation = "assets/images/product_image/$varId[image_p]/$variant[image]";

                if (!$dataProduct->is_on_bucket) {
                    $images         = scandir(FCPATH . $path_variation);

                    foreach ($images as $image) {
                        if ($image != "." && $image != ".." && $image != "") {
                            $principal_image_variant = baseUrlPublic("$path_variation/$image");
                            break 2;
                        }
                    }
                } else {
                    $images = $this->bucket->getFinalObject($path_variation);
                    foreach($images['contents'] as $image){
                        $principal_image_variant = $image['url'];
                        break 2;
                    }
                }
            }

            $update_var = array();

            if (is_null($principal_image_variant)) {
                $update_var['situacao'] = Model_products::INCOMPLETE_SITUATION;
                $update_var['principal_image'] = NULL;
            } else if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
                if (
                    $varId['situacao'] != Model_products::COMPLETE_SITUATION &&
                    ($varId['category_id'] != '[""]' || $publishWithoutCategory) &&
                    $varId['brand_id'] != '[""]'
                ) {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
                if ($principal_image_variant !== $varId['principal_image_product']) {
                    $update_var['principal_image'] = $principal_image_variant;
                }
            } else {
                if (
                    $varId['situacao'] != Model_products::COMPLETE_SITUATION &&
                    $varId['category_id'] != '[""]' &&
                    $varId['brand_id'] != '[""]'
                ) {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
                if ($principal_image_variant !== $varId['principal_image_product']) {
                    $update_var['principal_image'] = $principal_image_variant;
                }
            }

            if (!empty($update_var)) {
                $this->model_products->update($update_var, $varId['prd_id']);
            }
        }

        return array('updated' => $updated, 'error' => false);
    }

    private function update_mass_sku($data, $dataProduct)
    {
        if (!isset($data->variations)) {
            return array('error' => true, 'data' => $this->lang->line('api_not_variation_key'));
        }
        if (count((array)$data->variations) === 0) {
            return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));
        }

        $stock_update = false;
        $price_update = false;
        $list_price_update = false;
        $errors = array();

        foreach ($data->variations as $key => $variation) {
            try {
                $this->sku_var = null;
                if (!property_exists($variation, 'sku_variation') || empty($variation->sku_variation)) {
                    throw new Exception($this->lang->line('api_sku_variation_incorrectly'));
                }

                $this->sku_var = $variation->sku_variation;
                $data_variation_db = $this->getDataVariation()->row_array();
                // primeiro atualizamos preços, estoque e status
                $arrSql = array();
                if (isset($variation->qty)) {
                    $arrSql['qty'] = (int)$variation->qty;
                }
                if (isset($variation->price) && !empty($variation->price)) {
                    $arrSql['price'] = (float)number_format($variation->price, 2, '.', '');
                }
                if (isset($variation->list_price) && !empty($variation->list_price)) {
                    $arrSql['list_price'] = (float)number_format($variation->list_price, 2, '.', '');
                }

                $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
                if (isset($arrSql['images']) && count($arrSql['images']) > $limite_imagens_aceitas_api) {
                    throw new Exception($this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images_variations'));
                }

                if (isset($arrSql['price']) || isset($arrSql['list_price'])) {
                    if (empty($arrSql['price']) && empty($arrSql['list_price'])) {
                        throw new Exception($this->lang->line('application_prices_error'));
                    }

                    // Existe price e list_price, serã validado para saber se o price é maior que o list_price
                    if (isset($arrSql['price']) && isset($arrSql['list_price'])) {
                        if ($arrSql['price'] > $arrSql['list_price']) {
                            throw new Exception($this->lang->line('api_list_price_price'));
                        }
                    }

                    // price não deve ser maior que o list_price
                    if ($data_variation_db && isset($arrSql['price']) && !isset($arrSql['list_price'])) {
                        if ($arrSql['price'] > $data_variation_db['list_price']) {
                            throw new Exception($this->lang->line('api_list_price_price'));
                        }
                    }

                    // price não deve ser maior que o list_price
                    if ($data_variation_db && isset($arrSql['list_price']) && !isset($arrSql['price'])) {
                        if ($data_variation_db['price'] > $arrSql['list_price']) {
                            throw new Exception($this->lang->line('api_list_price_price'));
                        }
                    }
                }

                if (isset($arrSql['qty']) && (int)$arrSql['qty'] <= 0) {
                    $arrSql['qty'] = 0;
                }

                if (isset($variation->active) && in_array($variation->active, [
                        self::STATUS_ENABLED, self::STATUS_DISABLED
                    ])) {
                    $arrSql['status'] = self::MAP_STATUS[$variation->active] ?? Model_products::ACTIVE_PRODUCT;

                    // Produto ficou inativo, zerar estoque.
                    if (self::MAP_STATUS[$variation->active] == self::MAP_STATUS['disabled']) {
                        $arrSql['qty'] = 0;
                    }
                }

                if (isset($arrSql['price']) && $arrSql['price'] <= 0) {
                    throw new Exception($this->lang->line('api_variation_price'));
                }

                $canUpdateProduct = true;
                $integrations = $this->getPrdIntegration($dataProduct->id);

                foreach ($integrations as $integration) {
                    // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
                    if ($canUpdateProduct && $integration['skumkt']) {
                        $canUpdateProduct = false;
                    }
                }
                

                if ($canUpdateProduct) {
                    if (isset($variation->ean)) {
                        $arrSql['EAN'] = $variation->ean;
                    }
                    if (isset($variation->EAN)) {
                        $arrSql['EAN'] = $variation->EAN;
                    }
                    if (isset($variation->images)) {
                        $arrSql['images'] = (array)$variation->images;
                    }
                }

                if (isset($arrSql['EAN'])) {
                    if (!$this->model_products->ean_check($arrSql['EAN'])) {
                        throw new Exception($this->lang->line('api_invalid_ean_variation') . ' ' . $arrSql['EAN']);
                    }
                    if (in_array($arrSql['EAN'], $this->arrEANsCheck)) {
                        throw new Exception($this->lang->line('api_same_ean_variation_ean') . ' ' . $arrSql['EAN']);
                    }

                    $exist = $this->model_products->VerifyEanUnique($arrSql['EAN'], $this->store_id, $dataProduct->id);
                    if ($exist) {
                        throw new Exception("O mesmo EAN não é permitido em mais de um produto ou variação. Este EAN " . $arrSql['EAN'] . " está sendo usado no produto de id " . $exist);
                    }

                    $this->arrEANsCheck[] = $arrSql['EAN'];
                }

                if (empty($arrSql)) {
                    continue;
                }

                if (isset($arrSql['qty'])) {
                    $stock_update = true;
                }

                if (isset($arrSql['price'])) {
                    $price_update = true;
                }

                if (isset($arrSql['list_price'])) {
                    $list_price_update = true;
                }

                $arrImages = null;
                if (isset($arrSql['images'])) {
                    $arrImages = $arrSql['images'];
                    unset($arrSql['images']);
                }

                $this->model_products->updateVariationData($data_variation_db['id'], $dataProduct->id, $arrSql);

                if (!$canUpdateProduct) {
                    continue;
                }

                // Upload de imagens
                $pathProd = $dataProduct->image;
                $pathVar = $data_variation_db['image_v'];
                if (trim($pathProd) == '') {
                    log_message('error', 'APAGA ' . $this->router->fetch_class() . '/' . __FUNCTION__ . ' erro ao pegar o path do produto da variacao ' . $this->id_var . ' skuvar ' . $this->sku_var . ' sku ' . $this->sku . ' loja ' . $this->store_id);
                    throw new Exception('CONTACTE O SUPORTE IMEDITAMENTE.PROBLEMA NO PRODUTO');
                }
                if (trim($pathVar) == '') {
                    log_message('error', 'APAGA ' . $this->router->fetch_class() . '/' . __FUNCTION__ . ' erro ao pegar o path da variacao ' . $this->id_var . ' skuvar ' . $this->sku_var . ' sku ' . $this->sku . ' loja ' . $this->store_id);
                    throw new Exception('CONTACTE O SUPORTE IMEDITAMENTE. PROBLEMA NA VARIACAO');
                }
                if (is_array($arrImages) && count($arrImages)) {
                    $this->uploadproducts->deleteImagesDir("$pathProd/$pathVar");
                    $uploadImg = $this->uploadImageVariation($arrImages, $pathProd, $pathVar, false);
                    // Erro em upload de imagem
                    if ($uploadImg['error'] != 0) {
                        //$this->db->trans_rollback();
                        if ($uploadImg['error'] == 1) {
                            throw new Exception($uploadImg['data']);
                        }
                        if ($uploadImg['error'] == 2) {
                            throw new Exception($uploadImg['data']);
                        }
                        return array('error' => true);
                    }

                    $this->model_products->deleteImageVariation($pathProd, $pathVar);
                    $ordem = 0;
                    foreach ($arrImages as $arrImagesVar) {
                        $LinkImageVar = is_array($arrImagesVar) ? current($arrImagesVar) : $arrImagesVar;
                        if ($LinkImageVar == "") {
                            continue;
                        }
                        $dataSqlImgVar = array(
                            'store_id' => $this->store_id,
                            'company_id' => $this->company_id,
                            'ordem' => $ordem,
                            'prd_id' => $dataProduct->id,
                            'variant' => $data_variation_db['variant'],
                            'original_link' => $LinkImageVar,
                            'pathProd' => $pathProd,
                            'pathVariant' => $pathVar,
                            'status' => 0,
                            'error' => null
                        );
                        $this->model_products->createImage($dataSqlImgVar);
                        $ordem++;
                    }
                }

                $imgsUpload = $this->uploadproducts->countImagesDir($data_variation_db['image_p']);

                if ($data_variation_db && $imgsUpload === 0) {
                    $principal_image_variant = null;

                    foreach ($this->model_products->getVariantsByProd_id($dataProduct->id) as $variant) {
                        $path_variation = "assets/images/product_image/$data_variation_db[image_p]/$variant[image]";
                        $images = scandir(FCPATH . $path_variation);

                        foreach ($images as $image) {
                            if ($image != "." && $image != ".." && $image != "") {
                                $principal_image_variant = baseUrlPublic("$path_variation/$image");
                                break 2;
                            }
                        }
                    }

                    $update_var = array();

                    if (is_null($principal_image_variant)) {
                        $update_var['situacao'] = Model_products::INCOMPLETE_SITUATION;
                        $update_var['principal_image'] = NULL;
                    } else if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                        $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
                        if (
                            $data_variation_db['situacao'] != Model_products::COMPLETE_SITUATION &&
                            ($data_variation_db['category_id'] != '[""]' || $publishWithoutCategory) &&
                            $data_variation_db['brand_id'] != '[""]'
                        ) {
                            $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                        }
                        if ($principal_image_variant !== $data_variation_db['principal_image_product']) {
                            $update_var['principal_image'] = $principal_image_variant;
                        }
                    } else {
                        if (
                            $data_variation_db['situacao'] != Model_products::COMPLETE_SITUATION &&
                            $data_variation_db['category_id'] != '[""]' &&
                            $data_variation_db['brand_id'] != '[""]'
                        ) {
                            $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                        }
                        if ($principal_image_variant !== $data_variation_db['principal_image_product']) {
                            $update_var['principal_image'] = $principal_image_variant;
                        }
                    }

                    if (!empty($update_var)) {
                        $this->model_products->update($update_var, $data_variation_db['prd_id']);
                    }
                }
            } catch (Exception $exception) {
                if ($this->sku_var) {
                    $errors[$key] = "$this->sku_var: {$exception->getMessage()}";
                } else {
                    $errors[$key] = $exception->getMessage();
                }
            }
        }

        if (count($errors) == count($data->variations)) {
            throw new Exception(implode(' | ', $errors));
        }

        if ($stock_update) {
            $this->updateStockProduct($dataProduct);
        }

        if ($price_update) {
            $this->updatePriceProduct($dataProduct);
        }

        if ($list_price_update) {
            $this->updateListPriceProduct($dataProduct);
        }

        return $errors;
    }

    private function update($data, $dataProduct)
    {
        $erroColumn = "";
        $product_variations = array();
        $arrVariations = array();

        if(!isset($data->variation)) return array('error' => true, 'data' => $this->lang->line('api_not_variation_key'));
        if(count((array)$data->variation) === 0) return array('error' => true, 'data' => $this->lang->line('api_no_data_update'));

        //if (!$this->app_authorized || $this->company_id == $this->company_sika)
        //    return array('error' => true, 'data' => 'Feature unavailable.');

        foreach ($data->variation as $key => $value) {
            if (!array_key_exists($key, $this->arrColumnsUpdate) && $erroColumn === "") {
                $erroColumn = $this->lang->line('api_parameter_not_match_field_insert') . $key;
            }

            if ($key === "types_variations") {
                $varTypeList = [];
                foreach($value as $type_v){
                    $varTypeList[] = $this->getPortugueseNameVariation($type_v);
                }
                $value = !empty($varTypeList) ? implode(';', $varTypeList) : "";
            }

            if($key == "product_variations") {
                $product_variations['product_variations'] = $value;
                continue;
            }
            if($key == "types_variations") {
                $product_variations['types_variations'] = $value;
            }
        }

        // $pdrIntegration = count($this->getPrdIntegration($dataProduct->id));
        // if ($pdrIntegration)
        //     return array('error' => true, 'data' => $this->lang->line('api_product_already_integrated'));

        // variações, se a quantidade de types_variations bate com a quantidade de itens no product_variations
        if(isset($product_variations['product_variations']) && isset($product_variations['types_variations'])) {
            if (count($product_variations['product_variations']) > 0 || $product_variations['types_variations'] != "") {
                $returnVariations = $this->createArrayProductsVariations($product_variations, $dataProduct);
                if ($returnVariations['success'] === false) {
                    return array('error' => true, 'data' => $returnVariations['data']);
                }
                $product_variations['types_variations'] = $returnVariations['types_variations'] ?? $product_variations['types_variations'];
                $arrVariations = $returnVariations['data'];
            }
        }

        if($erroColumn != "") return array('error' => true, 'data' => $erroColumn);

        $idsVariants    = $this->getIdVariantProduct();
        $variations_old = empty($arrVariations) ? array() : (count($idsVariants) ? $this->model_products->getVariantsByProd_id($dataProduct->id) : array());

        // Inicia transação
        $this->db->trans_begin();

        if(count($idsVariants) > 0){
            $this->db->where_in('id', $idsVariants);
            $this->db->delete('prd_variants');
        }

        $this->db->where(array('sku' => $this->sku, 'store_id' => $this->store_id));
        $this->db->update('products', array('has_variants' => $product_variations['types_variations']));

        $this->model_products->deleteImageVariation($dataProduct->image, null);

        $primary_image_variation = null;
        $path_product =  $dataProduct->image;

        foreach($arrVariations as $variation){
            $variation['prd_id']    = $dataProduct->id;
            $variation['sku']       = $variation['sku'] ?? "{$dataProduct->sku}-{$variation['variant']}";
            $variation['status']    = 1;
            $arrImages              = $variation['arrImages'];

            // removo o arr de imagens para não enviar na query
            unset($variation['arrImages']);

            // CHECA SE EXISTE A DATA DE PUBLICACAO NO ARRAY DE BACKUP DE VARIAÇÕES
            foreach($variations_old as $old_variant){
                if($old_variant['sku'] == $variation['sku']){
                    if(!empty($old_variant['created_at'])){
                        $variation['created_at'] = $old_variant['created_at'];
                    }
                }
            }

            // inserir na tabela prd_variants com o sku e id do produto inserido
            $this->model_products->createvar($variation);

            // Upload de imagens
            $uploadImgVar = $this->uploadImageVariation($arrImages, $path_product, $variation['image'], true);

            // Erro em upload de imagem
            if ($uploadImgVar['error'] != 0) {
                $this->db->trans_rollback();
                if ($uploadImgVar['error'] == 1) return array('error' => true, 'data' => $uploadImgVar['data']);
                if ($uploadImgVar['error'] == 2) return array('error' => true, 'data' => $uploadImgVar['data']);
                return array('error' => true);
            }

            $ordem = 0;
            foreach ($arrImages as $arrImagesVar) {
                $LinkImageVar = is_array($arrImagesVar) ? current($arrImagesVar) : $arrImagesVar;
                if ($LinkImageVar == "") continue;
                $dataSqlImgVar = array(
                    'store_id'      => $this->store_id, 
                    'company_id'    => $this->company_id, 
                    'ordem'         => $ordem,
                    'prd_id'        => $variation['prd_id'],
                    'variant'       => $variation['variant'],
                    'original_link' => $LinkImageVar,
                    'pathProd'      => $dataProduct->image,
                    'pathVariant'   => $variation['image'],
                    'status'        => 0,
                    'error'         => null
                );
                $this->model_products->createImage($dataSqlImgVar);
                $ordem++;
            }

            if (is_null($primary_image_variation) && $uploadImgVar['primary_image']) {
                $primary_image_variation = $uploadImgVar['primary_image'];
            }
        }

        $imgsUpload = $this->uploadproducts->countImagesDir($path_product);
        if ($dataProduct) {
            $update_var = array();

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
                if ($imgsUpload === 0 && empty($primary_image_variation)) {
                    $update_var['situacao'] = Model_products::INCOMPLETE_SITUATION;
                    $update_var['principal_image'] = null;
                } elseif (($dataProduct->category_id != '[""]' || $publishWithoutCategory) && $dataProduct->brand_id != '[""]') {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
            } else {
                if ($imgsUpload === 0 && empty($primary_image_variation)) {
                    $update_var['situacao'] = Model_products::INCOMPLETE_SITUATION;
                    $update_var['principal_image'] = null;
                } elseif ($dataProduct->category_id != '[""]' && $dataProduct->brand_id != '[""]') {
                    $update_var['situacao'] = Model_products::COMPLETE_SITUATION;
                }
            }

            if ($imgsUpload === 0 && !empty($primary_image_variation)) {
                $update_var['principal_image'] = $primary_image_variation;
            }

            if (!empty($update_var)) {
                $this->model_products->update($update_var, $dataProduct->id);
            }
        }

        $this->updateStockProduct($dataProduct);

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        $this->db->trans_commit();

        $this->updatePriceProduct($dataProduct);
        $this->updateListPriceProduct($dataProduct);

        // remove as imagens antigas das variações.
        foreach ($variations_old as $variation_old) {
            $this->uploadproducts->deleteDir("$dataProduct->image/{$variation_old['image']}");
        }

        $this->sendProductToQueue($dataProduct->id);

        return array('error' => false);
    }

    private function checkSKUandId()
    {
        $sql = "SELECT * FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND p.store_id = ? AND v.id = ?";
        return $this->db->query($sql, array($this->sku, $this->store_id, $this->id_var))->num_rows() === 1 ? true : false;
    }

    private function checkSKUandSkuVar()
    {
        $sql = "SELECT * FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND p.store_id = ? AND v.sku = ?";
        $ret= $this->db->query($sql, array($this->sku, $this->store_id, $this->sku_var))->num_rows() === 1 ? true : false;
        return $ret;
    }

    private function getDataProduct(): ?object
    {
        $product = $this->model_products->getProductBySkuAndStore($this->sku, $this->store_id);
        return empty($product) ? null : (object)$product;
    }

    private function getIdVariantProduct()
    {
        $arrVariant = array();
        $sql = "SELECT v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.sku = ? AND p.store_id = ?";
        foreach ($this->db->query($sql, array($this->sku, $this->store_id))->result_array() as $variant){
            array_push($arrVariant, $variant['id']);
        }
        return $arrVariant;
    }

    private function createArrayProductsVariations($product_variations, $dataProduct )
    {
        $sku_prod = $dataProduct->sku;
        $prd_id = $dataProduct->id;
        $countVariant = 0;
        $arr = array();
        $validSku = array();
        $check_values_variations = array();
        $limite_imagens_aceitas_api = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        foreach ($product_variations['product_variations'] as $variation){
            $varArr = array();
            $varStr = "";
            $variation = (array)$variation;

            if (!isset($variation['qty'])) {
                return array('success' => false, 'data' => $this->lang->line('api_qty_informed_variation'));
            }

            if (!isset($variation['sku_variation']) || $variation['sku_variation'] == "") {
                return array('success' => false, 'data' => $this->lang->line('api_sku_variation_incorrectly'));
            }

            if (in_array($variation['sku_variation'], $validSku)) {
                return array('success' => false, 'data' => $this->lang->line('api_two_equal'));
            }

            if ($variation['sku_variation'] == $sku_prod) {
                return array('success' => false, 'data' => $this->lang->line('api_sku_variation_different'));
            }

            if (!$this->checkSkuAvailable($variation['sku_variation'], $prd_id)) {
                return array('success' => false, 'data' => $this->lang->line('api_sku_in_use_other') . " SKU: {$variation['sku_variation']}");
            }

            $checkVariation = $variation;
            unset($checkVariation['sku_variation']);
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
                if (!key_exists($this->type_variation[$type], $variation)) {
                    continue;
//                    return array('success' => false, 'data' => $this->lang->line('api_the') . $this->type_variation[$type] . $this->lang->line('api_type_not_declared_product') . " SKU: {$variation['sku']}. Types: (" . implode(',', $variationInSku) . ")");
                }
                $typeVariations[] = $type;
            }
            $typeVariations = $this->sortVariationTypesByName($typeVariations);
            foreach($typeVariations as $type){
                switch (strtolower($type)){
                    case "cor":
                        $valuesVariationsList[] = $variation["color"];
                        $typeVariationsList[] = 'Cor';
                        break;
                    case "voltagem":
                        if ($variation["voltage"] == 'bivolt') {
                            $variation["voltage"] = 'Bivolt';
                        }
                        //if ($variation["voltage"] != 220 && $variation["voltage"] != 110 && $variation["voltage"] != 'Bivolt') return array('success' => false, 'data' => $this->lang->line('api_voltage_variation'));
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
                    default:
                        return array('success' => false, 'data' => $this->lang->line('api_type_variation'));
                }
            }
            $typesVariations    = !empty($typeVariationsList) ? $typeVariationsList : $typesVariations;
            $valuesVariations   = !empty($valuesVariationsList) ? $valuesVariationsList : $valuesVariationsList;

            $typesVariations    = implode(';', $typesVariations);
            $valuesVariations   = implode(';', $valuesVariations);

            if (in_array($valuesVariations, $check_values_variations)) {
                return array('success' => false, 'data' => $this->lang->line('api_variation_value_equal')." SKU: {$variation['sku']}");
            }
            
            // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
            if(isset($variation['price']) && empty($variation['price'])){
                if(isset($variation['list_price']) && !empty($variation['list_price'])){
                    $variation['price'] = $variation['list_price'];
                } else {    
                    return array('success' => false, 'data' => $this->lang->line('application_prices_error_por') . " SKU: {$variation['sku_variation']}");
                }
            }

            if(isset($variation['list_price']) && empty($variation['list_price'])){
                if(isset($variation['price']) && !empty($variation['price'])){
                    $variation['list_price'] = $variation['price'];
                } else {    
                    return array('success' => false, 'data' => $this->lang->line('application_prices_error_de') . " SKU: {$variation['sku_variation']}");
                }
            }

            // $price = $this->getPriceProduct();
            $price = $dataProduct->price;
            if (isset($variation['price'])) {
                $price = (float)number_format($variation['price'], 2, '.', '');
                if ($price == 0)
                    return array('success' => false, 'data' => $this->lang->line('api_variation_price') . " SKU: {$variation['sku_variation']}");
            }

            $list_price = $dataProduct->list_price;
            if (isset($variation['list_price'])) {
                $list_price = (float)number_format($variation['list_price'], 2, '.', '');
            }

            $images = null;
            if (isset($variation['images']) && count((array)$variation['images']) > 0) {
                $images = (array)$variation['images'];
                if (count($images) > $limite_imagens_aceitas_api)
                    return array('success' => false, 'data' => $this->lang->line('api_not_allowed_send_than_images') . $limite_imagens_aceitas_api . $this->lang->line('api_images_variations') . " SKU: {$variation['sku_variation']}");
            }
            $ean = '';
            if (isset($variation['ean']) || isset($variation['EAN'])) {
                $eanCheck = $variation['ean'] ?? $variation['EAN'];
                if (!$this->model_products->ean_check($eanCheck))
                    return array('success' => false, 'data' => $this->lang->line('api_invalid_ean_variation') . " SKU: {$variation['sku_variation']}");

                if (!empty($eanCheck)) {
                    if (in_array($eanCheck, $this->arrEANsCheck))
                        return array('success' => false, 'data' => $this->lang->line('api_ean_same_variation') . " EAN: {$eanCheck}");

                    $exist =$this->model_products->VerifyEanUnique($eanCheck, $this->store_id, $dataProduct->id); 
                    if ($exist) { 
                        return array('success' => false, 'data' => "O mesmo EAN não é permitido em mais de um produto ou variação. Este EAN {$eanCheck} está sendo usado no produto de id ".$exist);
                    }
                    array_push($this->arrEANsCheck, $eanCheck);
                }

                $ean = $eanCheck;
            }

            $arr[] = array(
                'prd_id'    => null,
                'name'      => $valuesVariations ?? '',
                'sku'       => $variation['sku_variation'] ?? null,
                'qty'       => $variation['qty'],
                'variant'   => $countVariant++,
                'price'     => $price,
                'list_price'=> $list_price ?? $price,
                'image'     => md5(round(microtime(true) * 100000).rand(11111111,99999999)),
                'EAN'       => $ean,
                'arrImages' => $images
            );
            $validSku[] = $variation['sku_variation'];
            $check_values_variations[] = $valuesVariations;
        }
        return array('success' => true, 'data' => $arr, 'types_variations' => $typesVariations ?? '');
    }

    private function createArrayIten()
    {
        $arrResult = array();
        // Consulta
        $query = $this->getDataVariation();

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));
        if($query->num_rows() === 1 && $query->result_array()[0]['id'] === null) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $result = $query->result_array();

        $arrVariants = explode(";", $result[0]['has_variants']);

        foreach ($result as $variation) {
            $arrVariantsRs = array();
            $countVariant = 0;
            $nameVariant = explode(";", $variation['name']);
            foreach ($arrVariants as $variant){
                $variant = $this->getRealNameVariation($variant);
                $arrVariantsRs[] = array(
                    "$variant" => $nameVariant[$countVariant++]
                );
            }

            if ($result[0]['catalog_id'])
                $imagesVarDir = FCPATH . 'assets/images/catalog_product_image/' . $result[0]['catalog_id'].'/'.$variation['image_v'];
            else
                $imagesVarDir = FCPATH . 'assets/images/product_image/' . $result[0]['image_p'].'/'.$variation['image_v'];

            $imagesVar = array();

            if (!$variation['is_on_bucket']) {
                if (is_dir($imagesVarDir)) {
                    $imagesVar = scandir($imagesVarDir);
                }
            }

            $arrImagesVar = array();

            if (!empty($variation['image_v'])) {
                if (!$variation['is_on_bucket']) {
                    foreach ($imagesVar as $imageVar) {
                        if (($imageVar != ".") && ($imageVar != "..") && ($imageVar != "")) {
                            array_push($arrImagesVar, baseUrlPublic('assets/images/product_image/' . $result[0]['image_p'] . '/' . $variation['image_v'] . '/' . $imageVar));
                        }
                    }
                } else {
                    // Adiciona cada imagem diretamente.
                    foreach ($this->bucket->getFinalObject($imagesVarDir)['contents'] as $key => $content) {
                        array_push($arrImagesVar, $content['key']);
                    }
                }
            }

            $stock_var      = $variation['qty'];
            $price_var      = $variation['price'];
            $list_price_var = $variation['list_price'] ?? $variation['price'];

            $arrResult[] = array(
                'cod'           => $this->changeType($variation['id'], "int"),
                'variant_order' => $this->changeType($variation['variant'], "int"),
                'qty'           => $this->changeType($stock_var, "int"),
                'price'         => $this->changeType($price_var, "float"),
                'list_price'    => $this->changeType($list_price_var, "float"),
                'sku'           => $this->changeType($variation['sku'], "string"),
                'EAN'           => $this->changeType($variation['EAN'], "string"),
                'images'        => $arrImagesVar,
                'status'        => $variation['status'] == 0 ? "disabled" : "enabled",
                'variants'      => $arrVariantsRs,
            );
        }
        return array("variation" => $arrResult);
    }

    private function getDataVariation($oneVar = true)
    {
        $where = "";
        if($this->sku_var && $oneVar) $where = " AND v.sku = ".(string)$this->db->escape($this->sku_var);
        $sql = "SELECT 
                v.id, 
                v.variant, 
                v.name,
                v.qty,
                v.price,
                v.list_price,
                v.status, 
                v.sku, 
                v.EAN, 
                v.prd_id, 
                v.image as image_v, 
                p.has_variants, 
                p.situacao, 
                p.product_catalog_id as catalog_id, 
                p.image as image_p, 
                p.principal_image as principal_image_product, 
                p.category_id, 
                p.brand_id,
                p.is_on_bucket
                FROM products as p 
                LEFT JOIN prd_variants as v ON p.id = v.prd_id
                WHERE p.sku = ? 
                AND p.store_id = ? {$where}";
        return $this->db->query($sql, array($this->sku, $this->store_id));
    }

    private function checkSkuAvailable($sku, $prd_id)
    {
        $sql = "SELECT p.id,v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.store_id = ?  AND p.id <> ? AND (p.sku = ? OR v.sku = ?) limit 1";
        $query = $this->db->query($sql, array($this->store_id, $prd_id, $sku, $sku));
        return $query->row_array() ? false : true;
    }
 
    private function getPathImage()
    {
        $where = $this->id_var ? "AND v.id = ".$this->db->escape($this->id_var) : "AND v.sku = ".$this->db->escape($this->sku_var);
        $sql = "SELECT v.image FROM products p JOIN prd_variants v ON p.id = v.prd_id WHERE p.sku = ? AND p.store_id = ? {$where}";
        $query = $this->db->query($sql, array($this->sku, $this->store_id));
        return $query->first_row()->image;      
    }

    private function uploadImageVariation($arrImg, $pathProducts, $pathVariation, $newImage)
    {
        $errorImage = array('error' => 0, 'failed_images' => array());
        $targetDir = "assets/images/product_image/{$pathProducts}/{$pathVariation}";
        $count_image_error = 0;

        if ($arrImg === null) return $errorImage;

        $primary_image = null;

        foreach ($arrImg as $image) {
            if ($image == "") continue;

            $upload = $this->uploadproducts->sendImageForBucket("{$targetDir}/", $image);

            if ($upload['success'] == false) {
                $errorImage['failed_images'][] = $image;
                $data = array(
                    'store_id'      => $this->store_id,
                    'company_id'    => $this->company_id,
                    'title'         => 'Falha no upload da imagem: ' . $image,
                    'description'   => 'Não foi possível atualizar a imagem: ' . $image . ' devido a um problema de indisponibilidade.',
                    'type'          => 'E',
                    'job'           => 'ApiProduct',
                    'unique_id'     => $this->sku,
                    'status'        => 1,
                );
                $this->model_log_integration->create($data);
                $count_image_error++;
                continue;
            }

            if (is_null($primary_image)) {
                $primary_image = $upload['path'];
            }
        }

        $errorImage['primary_image'] = $primary_image;
        $errorImage['count_image_error'] = $count_image_error;

        return $errorImage;
    }

    private function getRealNameVariation(string $variant): string
    {
        switch ($variant){
            case "Cor":
                $variant = "color";
                break;
            case "TAMANHO":
                $variant = "size";
                break;
            case "VOLTAGEM":
                $variant = "voltage";
                break;
            case "SABOR":
                $variant = "flavor";
                break;
            case "GRAU":
                $variant = "degree";
                break;
            case "LADO":
                $variant = "side";
                break;
        }

        return $variant;
    }

    private function getPortugueseNameVariation(string $variant): string
    {
        switch ($variant){
            case "color":
                $variant = "Cor";
                break;
            case "size":
                $variant = "TAMANHO";
                break;
            case "voltage":
                $variant = "VOLTAGEM";
                break;
            case "flavor":
                $variant = "SABOR";
                break;
            case "degree":
                $variant = "GRAU";
                break;
            case "side":
                $variant = "LADO";
                break;
        }

        return $variant;
    }

}