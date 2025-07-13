<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Produtos
 
 */
require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Helpers/StringHandler.php";
require APPPATH . "libraries/Traits/LengthValidationProduct.trait.php";
require APPPATH . "libraries/Traits/CheckImageProduct.trait.php";
defined('BASEPATH') or exit('No direct script access allowed');

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
use App\Libraries\FeatureFlag\FeatureManager;

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Security $security
 * @property CI_Parser $parser
 * @property CI_Lang $lang
 * @property CI_Loader $load
 * @property CI_Output $output
 *
 * @property BlacklistOfWords $blacklistofwords
 * @property Bucket $bucket
 * @property Excel $excel
 * @property UploadProducts $uploadproducts
 * @property FileDir $filedir
 * @property Model_products $model_products
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_stores $model_stores
 * @property Model_attributes $model_attributes
 * @property Model_reports $model_reports
 * @property Model_integrations $model_integrations
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_promotions $model_promotions
 * @property Model_campaigns $model_campaigns
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_orders $model_orders
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_blingultenvio $model_blingultenvio
 * @property Model_settings $model_settings
 * @property Model_log_products $model_log_products
 * @property Model_products_catalog $model_products_catalog
 * @property Model_queue_products_notify_omnilogic $model_queue_products_notify_omnilogic
 * @property Model_products_category_mkt $model_products_category_mkt
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property Model_log_integration_product_marketplace $model_log_integration_product_marketplace
 * @property Model_csv_import_attributes_products $model_csv_import_attributes_products
 * @property Model_collections $model_collections
 * @property Model_catalogs $model_catalogs
 * @property Model_control_sync_skuseller_skumkt $model_control_sync_skuseller_skumkt
 * @property Model_control_sequential_skumkts $model_control_sequential_skumkts
 * @property Model_integrations_settings $model_integrations_settings
 * @property Model_sku_locks $model_sku_locks
 */
class Products extends Admin_Controller
{
    use CheckImageProduct;
    use LengthValidationProduct;
    public $allowable_tags = null;
    public $hasDefaultValueVariation = false;
    const UNDER_ANALYSISS = 4;
    const PRODUCTS_ATTR_IMPORT_ROUTE = 'products/importAttributes';

    const COLOR_DEFAULT = 'Cor';
	const SIZE_DEFAULT = 'TAMANHO';
	const VOLTAGE_DEFAULT = 'VOLTAGEM';
    const FLAVOR_DEFAULT = 'SABOR';
    const DEGREE_DEFAULT = 'GRAU';
    const SIDE_DEFAULT = 'LADO';

    var $vtexsellercenters;
	var $vtexsellercentersNames;

    /**
     * @var DeleteProduct
     */
    public $deleteProduct;

    protected $editablePrice = null;
    protected $percPriceCatalog = null;

    protected $listingProductView = 'products/index';
    protected $creationProductView = 'products/create';
    protected $editProductView = 'products/edit';

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_products');

        $this->load->library('BlacklistOfWords');
        $this->load->library('Bucket');
        $this->load->library('excel');
        $this->load->model('model_products');
        $this->load->model('model_catalogs');
        $this->load->model('model_brands');
        $this->load->model('model_category');
        $this->load->model('model_stores');
        $this->load->model('model_attributes');
        $this->load->model('model_reports');
        $this->load->model('model_integrations');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_promotions');
        $this->load->model('model_campaigns');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_errors_transformation');
        $this->load->model('model_orders');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_blingultenvio');
        $this->load->model('model_settings');
        $this->load->model('model_log_products');
        $this->load->model('model_products_catalog');
        $this->load->model('model_queue_products_notify_omnilogic');
		$this->load->model('model_products_category_mkt');  
		$this->load->model('model_queue_products_marketplace'); 
        $this->load->model('model_log_integration_product_marketplace');
        $this->load->model('model_csv_import_attributes_products');
        $this->load->model('model_collections');
        $this->load->model('model_stores_multi_channel_fulfillment');
        $this->load->model('model_control_sync_skuseller_skumkt');
        $this->load->model('model_control_sequential_skumkts');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_sku_locks');
        $this->load->library('UploadProducts');
        $this->load->library('FileDir');

        $this->listingProductView = $this->model_settings->getValueIfAtiveByName('view_product_listing') ?: $this->listingProductView;
        $this->creationProductView = $this->model_settings->getValueIfAtiveByName('view_product_creation') ?: $this->creationProductView;
        $this->editProductView = $this->model_settings->getValueIfAtiveByName('view_product_edit') ?: $this->editProductView;

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $this->hasDefaultValueVariation = $this->model_settings->getValueIfAtiveByName('variacao_valor_default');

        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = " . $usercomp;
        $this->data['mycontroller'] = $this;

        $this->limite_variacoes = $this->model_settings->getLimiteVariationActive();
        if(!empty($this->limite_variacoes)) {
            $this->limite_variacoes = 1;
        }else{
            $this->limite_variacoes = 0;
        }

        $valids = array();
        $attribs = $this->model_attributes->getActiveAttributeData('products');
        foreach ($attribs as $attrib) {
            $values = $this->model_attributes->getAttributeValueData($attrib['id']);
            $y = array();
            foreach ($values as $x) {
                $y[$x['value']] = $x['id'];
            }
            $valids[strtolower($attrib['name'])] = $y;
        }
        $this->data['valids'] = $valids;
        if ($this->session->userdata('ordersfilter') !== Null) {
            $ordersfilter = $this->session->userdata('ordersfilter');
        } else {
            $ordersfilter = "";
        }
        $this->data['ordersfilter'] = $ordersfilter;

		$int_tosvtex = $this->getVtexIntegrations();
        $this->vtexsellercenters = $int_tosvtex['int_to'];
	    $this->vtexsellercentersNames = $int_tosvtex['name'];

        $this->loadLengthSettings();

        $this->load->library('DeleteProduct', [
            'productModel' => $this->model_products,
            'lang' => $this->lang
        ], 'deleteProduct');

        $this->variant_color  = $this->model_settings->getValueIfAtiveByName('variant_color_attribute');
		if (!$this->variant_color) {  $this->variant_color = self::COLOR_DEFAULT; }

		$this->variant_size  = $this->model_settings->getValueIfAtiveByName('variant_size_attribute');
		if (!$this->variant_size) {  $this->variant_size = self::SIZE_DEFAULT; }

		$this->variant_voltage  = $this->model_settings->getValueIfAtiveByName('variant_voltage_attribute');
		if (!$this->variant_voltage) {  $this->variant_voltage = self::VOLTAGE_DEFAULT; }

		$this->variant_flavor  = $this->model_settings->getValueIfAtiveByName('variant_flavor_attribute');
		if (!$this->variant_flavor) {  $this->variant_flavor = self::FLAVOR_DEFAULT; }

        $this->variant_degree  = $this->model_settings->getValueIfAtiveByName('variant_degree_attribute');
		if (!$this->variant_degree) {  $this->variant_degree = self::DEGREE_DEFAULT; }

        $this->variant_side  = $this->model_settings->getValueIfAtiveByName('variant_side_attribute');
		if (!$this->variant_side) {  $this->variant_side = self::SIDE_DEFAULT; }

    }

    /*
     * It only redirects to the manage product page
     */
    public function index()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $identifying_technical_specification = $this->model_settings->getSettingDatabyName('identifying_technical_specification');
        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);
        $this->data['filters'] = $this->model_reports->getFilters('products');

        //  $this->data['nameOfIntegrations'] = [
        $nameOfIntegrations = [
            'CAR'  => 'Carrefour',
            'ML'   => 'Mercado Livre Premium',
            'MLC'  => 'Mercado Livre Clássico',
            'VIA'  => 'Via Varejo',
        ];

        $activeIntegrations = $this->model_integrations->getIntegrations();

        foreach ($activeIntegrations as $key => $activeIntegration) {
            if (!array_key_exists($activeIntegration['int_to'], $nameOfIntegrations)) {
                $nameOfIntegrations[$activeIntegration['int_to']] = $activeIntegration['int_to'];
            }
        }
        $this->data['nameOfIntegrations'] = $nameOfIntegrations;
        $this->data['activeIntegrations'] = is_array($activeIntegrations) ? $activeIntegrations : array();

        $this->data['without_stock'] = 2;

        $this->data['stores_filter'] = $this->model_stores->getActiveStore();


        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
		$this->data['sellercenter_name'] = 'Conecta Lá';
        if ($settingSellerCenter) {
        	$this->data['sellercenter_name'] = $settingSellerCenter['value'];
        }

        $this->data['collections_catalog'] = $this->model_catalogs->getCollections();
        $this->data['identifying_technical_specification'] = $this->model_settings->getSettingDatabyName('identifying_technical_specification');

        $this->data['page_title'] = $this->lang->line('application_registered_products');
        $this->render_template($this->listingProductView, $this->data);
    }

    public function filter()
    {

        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['products_without_stock'] = 0;
        $this->data['products_incomplete'] = 0;
        $this->data['products_complete'] = 0;
        $this->data['filters'] = $this->model_reports->getFilters('products');
        $this->data['page_title'] = $this->lang->line('application_manage_products_filtered');
        $ordersfilter = "";
        if (!is_null($this->postClean('do_filter'))) {
            if ((!is_null($this->postClean('id'))) && ($this->postClean('id_op') != "0")) {
                $ordersfilter .= " AND id " . $this->postClean('id_op') . " " . $this->postClean('id');
            }
            if ((!is_null($this->postClean('sku')))  && ($this->postClean('sku_op') != "0")) {
                $ordersfilter .= " AND sku " . $this->postClean('sku_op') . " '" . $this->postClean('sku') . "'";
            }
            if (!is_null($this->postClean('products_complete')) || !is_null($this->postClean('products_incomplete')) || !is_null($this->postClean('products_without_stock')) || !is_null($this->postClean('products_high_stock')) || !is_null($this->postClean('products_out_price')) || !is_null($this->postClean('products_low_stock')) || !is_null($this->postClean('products_published')) || !is_null($this->postClean('no_products_kit')) || !is_null($this->postClean('products_kit')) || !is_null($this->postClean('products_filter_price_catalog'))) {

                $user_id = $this->session->userdata('id');
                $is_admin = ($user_id == 1) ? true : false;
                $ordersfilter .= " AND (";
                $operator_or = false; // Adiciona operador OR na consulta entre os tipos de filtro

                if (!is_null($this->postClean("products_complete"))) {
                    $ordersfilter .= " p.status=1 and p.situacao = 2";
                    $operator_or = true;
                    $this->data['products_complete'] = 1;
                    $this->data['products_incomplete'] = 2;
                }
                if (!is_null($this->postClean("products_incomplete"))) {
                    $ordersfilter .= " p.status=1 and situacao = 1";
                    $operator_or = true;
					$this->data['products_complete'] = 1;
                    $this->data['products_incomplete'] = true;
                }
                if (!is_null($this->postClean("products_without_stock"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $ordersfilter .= " qty = 0";
                    $operator_or = true;
                    $this->data['products_without_stock'] = true;
                }
                if (!is_null($this->postClean("products_high_stock"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $query = $this->model_products->getProductHighStock($this->data['usercomp'], false);

                    $where_in = $this->getCodsArrayFilter($query, 'product_id');

                    $ordersfilter .=  " p.id IN ($where_in)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("products_out_price"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $usercomp = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

                    $query = $this->model_products->getProductsOutOfPrice($usercomp, false);

                    $where_in = $this->getCodsArrayFilter($query, 'prd_id');

                    $ordersfilter .=  " p.id IN ($where_in)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("products_low_stock"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $usercomp = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

                    $query = $this->model_products->getProductsLowStock($usercomp, false);

                    $where_in = $this->getCodsArrayFilter($query, 'prd_id');

                    $ordersfilter .=  " p.id IN ($where_in)";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("products_published"))) {
                    if ($operator_or) { $ordersfilter .= " OR";} 
                    $usercomp = $is_admin ? "" : " AND company_id = " . $this->data['usercomp'];
                    $usercomp = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

                    $query = $this->model_products->getProductsPublished($usercomp, false);

                    $where_in = $this->getCodsArrayFilter($query, 'prd_id');

                    $ordersfilter .=  " p.id IN ($where_in)";
                }
                if (!is_null($this->postClean("products_kit"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $ordersfilter .= " is_kit = 1";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("no_products_kit"))) {
                    if ($operator_or) {$ordersfilter .= " OR";}
                    $ordersfilter .= " is_kit = 0";
                    $operator_or = true;
                }
                if (!is_null($this->postClean("products_filter_price_catalog"))) {
                    $result = $this->model_products_catalog->getProductsWithChangedPrice();

                    $where_in = '';

                    foreach ($result as $res) {
                        $where_in .= $res['product_catalog_id'] . ',';
                    }
                    $where_in = substr($where_in, 0, -1);

                    $ordersfilter .=  " p.product_catalog_id IN ($where_in)";
                }
                $ordersfilter .= ")";
            }
        }
        $this->session->set_userdata(array('ordersfilter' => $ordersfilter));
        $from = !is_null($this->postClean('from')) ? $this->postClean('from') : 'index';
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();

        $this->data['stores_filter'] = $this->model_stores->getActiveStore();
		
		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
		$this->data['sellercenter_name'] = 'Conecta Lá';
        if ($settingSellerCenter) {
        	$this->data['sellercenter_name'] = $settingSellerCenter['value'];
        }
        $this->data['identifying_technical_specification'] = $this->model_settings->getSettingDatabyName('identifying_technical_specification');
        $this->render_template('products/'.$from, $this->data);
    }

    public function filtered()
    {
        $this->data['filters'] = $this->model_reports->getFilters('products');
        $this->data['page_title'] = $this->lang->line('application_manage_products_filtered');

        $ordersfilter = "";

        if (!is_null($this->postClean('do_filter'))) {

            $user_id = $this->session->userdata('id');
            $is_admin = ($user_id == 1) ? true : false;
            $ordersfilter .= " AND (";

            if (!is_null($this->postClean("activedProducts"))) {
                $ordersfilter .= " p.status = 1";
            }

            if (!is_null($this->postClean("InactivedProducts"))) {
                $ordersfilter .= " p.status = 2";
            }

            if (!is_null($this->postClean("discontinuedProducts"))) {
                $ordersfilter .= " p.status = 3";
            }

            if (!is_null($this->postClean("lockedProducts"))) {
                $ordersfilter .= " p.status = 4";
            }

            if (!is_null($this->postClean("CompletedProducts"))) {
                $ordersfilter .= " p.situacao = 2";
            }

            if (!is_null($this->postClean("incompletedProducts"))) {
                $ordersfilter .= " p.situacao = 1";
            }

            if (!is_null($this->postClean("integratedProducts"))) {
                $ordersfilter .= " i.status_int = 2";
            }

            if (!is_null($this->postClean("publichedProducts"))) {
                $ordersfilter .= " i.status_int = 2 AND i.status = 0";
            }

            if (!is_null($this->postClean("errorsTransformationProducts"))) {
                $ordersfilter .= " i.prd_id IS NOT NULL";
            }

            $ordersfilter .= ")";
        }

        $this->session->set_userdata(array('ordersfilter' => $ordersfilter));
        $from = !is_null($this->postClean('from')) ? $this->postClean('from') : 'index';
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('products/' . $from, $this->data);
    }
    /*
    * It Fetches the products data from the product table 
    * this function is called from the datatable ajax function
    */
    public function fetchProductData($isMkt = false)
    {

        $identifying_technical_specification = $this->model_settings->getSettingDatabyName('identifying_technical_specification');
        $isMkt = $this->postClean('ismkt',TRUE);
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $busca = $postdata['search'];

        if ($busca['value']) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $this->data['ordersfilter'] .= " AND ( sku like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR p.id like '%" . $busca['value'] . "%' OR p.EAN like '%" . $busca['value'] . "%')";
            }
        }

        $priceRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
        if (in_array('disablePrice', $this->permission)) {
            $priceRO = true;
        }

        $percPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_percentage_update_price_catalog');
        $daysPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_days_update_price_catalog');
        if (!$percPriceCatalogSetting || !$daysPriceCatalogSetting || $daysPriceCatalogSetting['status'] == 2 || $percPriceCatalogSetting['status'] == 2)
            {$percPriceCatalog = false;}
        else {$percPriceCatalog = $percPriceCatalogSetting['value'];}

        if (trim($postdata['sku'])) {
            $postdata['sku']=preg_replace('/\'/', '', $postdata['sku']);
            $skus=explode(";", $postdata['sku']);
            $in="";
            if(count($skus)>1){
                $sku='(';
                $delimiter='';
                foreach($skus as $sk){
                    $sku .= $delimiter.'"'.$sk.'"';
                    $delimiter=',';
                    $last_sku=$sk;
                }
                $sku.=')';
                $in="p.sku in $sku";
            }else{
                $last_sku=$postdata['sku'];
            }
            $sku = '\'%' . $last_sku . '%\'';
            $like="p.sku LIKE $sku";
            if(empty($in)){
                $this->data['ordersfilter'] .= " AND {$like} ";
            }else{
                $this->data['ordersfilter'] .= " AND ({$in} OR {$like}) ";
            }
        }
        if (trim($postdata['product'])) {
            $product = '\'%' . $postdata['product'] . '%\'';
            $this->data['ordersfilter'] .= " AND p.name LIKE $product ";
        }
        
        $deletedStatus = Model_products::DELETED_PRODUCT;
        if ($postdata['status']) {
            $this->data['ordersfilter'] .= " AND (p.status = {$postdata['status']}
            AND p.status NOT IN ({$deletedStatus}))";
        } else {
            $this->data['ordersfilter'] .= " AND p.status NOT IN ({$deletedStatus})";
        }

        if ($postdata['situation']) {
            $this->data['ordersfilter'] .= " AND p.situacao = " . $postdata['situation'];
        }

        if ($postdata['estoque']) {
            switch ($postdata['estoque']) {
                case 1:
                    $this->data['ordersfilter'] .= " AND p.qty > 0 ";
                    break;
                case 2:
                    $this->data['ordersfilter'] .= " AND p.qty < 1 ";
                    break;
            }
        }

        if ($postdata['kit']) {
            switch ($postdata['kit']) {
                case 1:
                    $this->data['ordersfilter'] .= " AND p.is_kit = true ";
                    break;
                case 2:
                    $this->data['ordersfilter'] .= " AND p.is_kit != true ";
                    break;
            }
        }

        if (isset($postdata['lojas'])) {
            if (is_array($postdata['lojas'])) {
                $lojas = $postdata['lojas'];
                $this->data['ordersfilter'] .= " AND (";
                foreach ($lojas as $loja) {
                    $this->data['ordersfilter'] .= "s.id = " . (int)$loja . " OR ";
                }
                $this->data['ordersfilter'] = substr($this->data['ordersfilter'], 0, (strlen($this->data['ordersfilter']) - 3));
                $this->data['ordersfilter'] .= ") ";
            }
        }

        if (isset($postdata['marketplace'])) {
            $this->data['join'] = " LEFT JOIN prd_to_integration i ON i.prd_id = p.id 
                                    LEFT JOIN errors_transformation et ON et.prd_id = p.id ";

            $whereJoin = " AND ( ";
            foreach ($postdata['marketplace'] as $key => $marketplace) {
                $whereJoin .= " i.int_to = '" . $marketplace . "' ";
                ($key + 1) < (count($postdata['marketplace'])) ? $whereJoin .= " OR " : '';
            }
            $whereJoin .= " ) ";

            $this->data['ordersfilter'] .= $whereJoin;

            if ($postdata['integration'] != 999) {
                switch ($postdata['integration']) {
                    case 30:
                        $this->data['ordersfilter'] .= " AND et.status = 0 ";
                        break;
                    case 40:
                        $this->data['ordersfilter'] .= " AND i.ad_link IS NOT NULL ";
                        break;
                    default:
                        $this->data['ordersfilter'] .= " AND i.status_int = " . $postdata['integration'];
                        break;
                }
            }
        }

        if($identifying_technical_specification && $identifying_technical_specification['status'] == 1) {
            $this->data['joinCollection'] = " LEFT JOIN catalogs_products_catalog cpc ON p.product_catalog_id = cpc.product_catalog_id LEFT JOIN catalogs c ON cpc.catalog_id = c.id ";

            if (isset($postdata['colecoes']) && !empty($postdata['colecoes'])) {
                $attributes = implode('","', $postdata['colecoes']);
                $whereJoin = ' AND c.attribute_value IN ("' . $attributes . '")';
                $this->data['ordersfilter'] .= $whereJoin;
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'sku', 'name', 'CAST(price AS DECIMAL(12,2))', 'CAST(qty AS UNSIGNED)', 's.name', 'p.id', '', 'p.status', 'p.situacao', '', '', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
            $this->data['orderby'] = $sOrder;
        }

        $result = array();
        if (isset($this->data['ordersfilter'])) {
            $this->session->set_userdata('productExportFilters', $this->data['ordersfilter']);
            $filtered = $this->model_products->getProductCount($this->data['ordersfilter']);
        } else {
            $filtered = 0;
            if ($this->session->has_userdata('productExportFilters')) {
                $this->session->unset_userdata('productExportFilters');
            }
        }

        $data = $this->model_products->getProductData($ini, null, $length);
        $i = 0;
        $this->session->unset_userdata('ordersfilter');
        foreach ($data as $key => $value) {
            $i++;
            $buttons = '';

            $collection = "";

            if(isset($value['attribute_value'])){
                $collection = $value['attribute_value'] ?? "";
            }

            if (in_array('deleteProduct', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc(' . $value['id'] . ',\'' . $value['sku'] . '\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }
            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }
            switch ($value['status']) {
                case 1:
                    if ($value['situacao'] == "1") {
                        $status =  '<span class="label label-warning">' . $this->lang->line('application_active') . '</span>';
                    } else {
                        $status =  '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
                    }
                    break;
                case 4:
                    $status = '<span class="label label-danger" data-toggle="tooltip" title="' . $this->lang->line('application_product_has_prohibited_word') . '">' . $this->lang->line('application_under_analysis') . '</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
                    break;
            }

            $situacao = '';
            switch ($value['situacao']) {
                case 1:
                    $situacao = '<span class="label label-danger">' . $this->lang->line('application_incomplete') . '</span>';
                    break;
                case 2:
                    $situacao = '<span class="label label-success">' . $this->lang->line('application_complete') . '</span>';
                    break;
            }

            $integrations = $this->model_integrations->getIntegrationsProduct($value['id'], 1);
            if ($integrations) {
                $plataforma = "";
                foreach ($integrations as $v) {
                    ////$error_transformation = $this->model_errors_transformation->countErrorsByProductId($value['id'],$v['int_to']);
                    //if ($error_transformation >0) {
                    if ($v['rule']) {
                        $ruleBlock = array();
                        $ruleId = is_numeric($v['rule']) ? (array)$v['rule'] : json_decode($v['rule']);
                        foreach ($ruleId as $ruleBlockId) {
                            $rule = $this->model_blacklist_words->getWordById($ruleBlockId);
                            if ($rule)
                                array_push($ruleBlock, strtoupper(str_replace('"', "'", $rule['sentence'])));
                        }
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" data-html="true" title="' . implode('<br><br>', $ruleBlock) . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['errors'] == 1) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_errors_tranformation'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 0) {
                        $plataforma .= '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 1) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 2) {
                        $plataforma .= '<span class="label label-primary" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 11) {
                        $over = $this->model_integrations->getPrdBestPrice($value['EAN']);
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . ' (' . $over . ')">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 12) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 13) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . ' ">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 14) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_release'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 20) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 21) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 22) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 23) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 24) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 90) {
                        $plataforma .= '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_inactive'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 91) {
                        $plataforma .= '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_no_logistics'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 99) {
                        $plataforma .= '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } else {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_out_of_stock'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    }
                }
            } else {
                $plataforma = "<span></span>";
            }
            $is_kit = '';
            if ($value['is_kit'] == 1) {
                $is_kit = '<br><span class="label label-warning">Kit</span>';
            }

            $link_id = "<a href='" . base_url('products/update/' . $value['id']) . "'>" . $value['sku'] . ' ' . $is_kit . "</a>";
            $qty_read_only = (trim($value['has_variants']) ? 'disabled data-toggle="tooltip" data-placement="top" title="Produto com variação" data-container="body"' : '');
            $price_read_only = ($priceRO) ? 'disabled' : '';

            $colorAlertPrice = '';
            $directionAlertPrice = '';

            $price = $value['price'];
            $stock = $value['qty'];

            $qty_status = '';
            if ($stock <= 10) {
                $qty_status = '<span class="label label-warning">' . $this->lang->line('application_low') . ' !</span>';
            } else if ($stock <= 0) {
                $qty_status = '<span class="label label-danger">' . $this->lang->line('application_out_stock') . ' !</span>';
            }

            if ($percPriceCatalog && $stock > 0) {
                $productWithChangedPrice = $this->model_products_catalog->getProductWithChangedPrice($value['product_catalog_id']);
                if ($productWithChangedPrice) {
                    $colorAlertPrice = 'style="color: red"';
                    $directionAlertPrice = $productWithChangedPrice['old_price'] > $productWithChangedPrice['new_price'] ? '&nbsp;<i class="fas fa-arrow-down" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu redução."></i>' : '&nbsp;<i class="fas fa-arrow-up" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu aumento."></i>';
                }
            }

            $priceFormatted = $this->formatprice($price);

            if ($isMkt) {
                $result[$key] = array(
                    $value['id'],
                    $value['id'] . "|" . $value['company_id'],
                    $img,
                    $link_id, // $value['sku'].' '.$is_kit,
                    $value['name'],
                    "<input type='text' class='form-control' $price_read_only onchange='this.value=changePrice($value[id], $price, this.value, this)' onfocus='this.value=$price' onKeyUp='this.value=formatPrice(this.value)' value='$priceFormatted' size='7' {$colorAlertPrice} " . ($value['is_kit'] != 1 ? '' : 'disabled') . "/>" . $directionAlertPrice,
                    "<input type='text' class='form-control' $qty_read_only onchange='changeQty($value[id], $stock, this.value)' onKeyPress='return digitos(event, this)' value='$stock' size='3' /> . ' ' . $qty_status", //$value['qty'] . ' ' . $qty_status,
                    $value['loja'],
                    $value['id'],
                    $status,
                    $situacao,
                    $plataforma,
                    //$buttons
                );
                if($identifying_technical_specification && $identifying_technical_specification['status'] == 1){
                    array_splice($result[$key], 10, 0, $collection);
                }
            } else {
                $result[$key] = array(
                    $value['id'],
                    $img,
                    $link_id, // $value['sku'].' '.$is_kit,
                    $value['name'],
                    "<input type='text' class='form-control' $price_read_only onchange='this.value=changePrice($value[id], $price, this.value, this)' onfocus='this.value=$price' onKeyUp='this.value=formatPrice(this.value)' value='$priceFormatted' size='7' {$colorAlertPrice} " . ($value['is_kit'] != 1 ? '' : 'disabled') . "/>" . $directionAlertPrice, //$value['price'],
                    "<input type='text' class='form-control' $qty_read_only onchange='changeQty($value[id], $stock, this.value)' onKeyPress='return digitos(event, this)' value='$stock' size='3' />" . ' ' . $qty_status, //$value['qty'] . ' ' . $qty_status,
                    $value['loja'],
                    $value['id'],
                    $status,
                    $situacao,
                    $plataforma,
                    //$buttons
                );
                if($identifying_technical_specification && $identifying_technical_specification['status'] == 1){
                    array_splice($result[$key], 10, 0, $collection);
                }
            }
        } // /foreach

        if ($filtered == 0) {
            $filtered = $i;
        }
        
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_products->getProductCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        echo json_encode($output);

    }

    /*
     * If the validation is not valid, then it redirects to the create page.
     * If the validation for each input field is valid then it inserts the data into the database
     * and it stores the operation message into the session flashdata and display on the manage product page
     */
    public function create()
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }      

		$this->data['sellercenter_name'] = 'conectala';
		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        if ($settingSellerCenter)
            $this->data['sellercenter_name'] = $settingSellerCenter['value'];

        $Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
        if (!in_array('updateProductsMarketplace', $this->permission)) {
            $Preco_Quantidade_Por_Marketplace = 0;
        }

        $this->data['dimenssion_min_product_image'] = null;
        $this->data['dimenssion_max_product_image'] = null;
        $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
        if ($product_image_rules) {
            $exp_product_image_rules = explode(';', $product_image_rules);
            if (count($exp_product_image_rules) === 2) {
                $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                $this->data['dimenssion_min_product_image'] = $dimenssion_min_validate;
                $this->data['dimenssion_max_product_image'] = $dimenssion_max_validate;
            }
        }

        $this->data['product_length_name'] = $this->product_length_name;
        $this->data['product_length_description'] = $this->product_length_description;
        $this->data['product_length_sku'] = $this->product_length_sku;
        $this->data['displayPriceByVariation'] = $this->model_settings->getStatusbyName('price_variation');
        $this->data['disableBrandCreationbySeller'] = $this->model_settings->getValueIfAtiveByName('disable_brand_creation_by_seller');
        $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);
        $this->form_validation->set_rules('product_name', $this->lang->line('application_product_name'), 'trim|required|max_length[' . $this->product_length_name . ']');
        $this->form_validation->set_rules('sku', $this->lang->line('application_sku'), 'trim|required|callback_validateLengthSku');
        $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required|max_length[' .  $this->product_length_description . ']');
        $this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|greater_than[0]');
        if ($this->postClean('semvar',TRUE) == "on") {
            $this->form_validation->set_rules('qty', $this->lang->line('application_item_qty'), 'trim|required');
        } else {
            $this->form_validation->set_rules('SKU_V[]', $this->lang->line('application_variation_sku'), 'trim|required|callback_validateLengthSku');
            
        }

        $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');
        $this->form_validation->set_rules('status', $this->lang->line('application_status'), 'trim|required');
        if ($require_ean) {
            $this->form_validation->set_rules('EAN', $this->lang->line('application_ean'), 'trim|required|callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|null]');
        } else {
            $this->form_validation->set_rules('EAN', $this->lang->line('application_ean'), 'callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|null]');
        }

        $this->form_validation->set_rules('prazo_operacional_extra', $this->lang->line('application_extra_operating_time'), 'trim|is_natural|less_than[100]');
        $this->form_validation->set_rules('peso_liquido', $this->lang->line('application_net_weight'), 'trim|required');
        $this->form_validation->set_rules('peso_bruto', $this->lang->line('application_weight'), 'trim|required');
        $this->form_validation->set_rules('largura', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('altura', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('profundidade', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('products_package', $this->lang->line('application_products_by_packaging'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('garantia', $this->lang->line('application_garanty'), 'trim|required');
        //		$this->form_validation->set_rules('CEST', $this->lang->line('application_cest'), 'trim|required');
        //		$this->form_validation->set_rules('FCI', $this->lang->line('application_fci'), 'trim|required');
        //     $this->form_validation->set_rules('NCM', $this->lang->line('application_NCM'), 'trim|required|exact_length[10]');
        $this->form_validation->set_rules('origin', $this->lang->line('application_origin_product'), 'trim|required|numeric');

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
            $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
            // usuário pode alterar a categoria
            if (!in_array('disabledCategoryPermission', $this->permission) && !$publishWithoutCategory) {
                $this->form_validation->set_rules('category[]', $this->lang->line('application_category'), 'trim|required');
            }
        } else {
            if (!in_array('disabledCategoryPermission', $this->permission)) {
                $this->form_validation->set_rules('category[]', $this->lang->line('application_category'), 'trim|required');
            }
        }

        $this->form_validation->set_rules('brands[]', $this->lang->line('application_brands'), 'trim|required');

        if ($this->postClean('semvar',TRUE) !== "on") {
            $req = $require_ean ? 'required|' : '';
            $this->form_validation->set_rules('EAN_V[]', $this->lang->line('application_ean'), 'trim|' . $req . 'callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|null]');
        }

        //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );
        $principal_image = '';
        $allgood = false;
        $semFoto = true;
        if ($this->form_validation->run() == TRUE) {
            // Pega o token da imagem e cria a base do diretório assets com ela.
            $upload_image = $this->postClean('product_image', TRUE);
            $asset_image = "assets/images/product_image/$upload_image/";

            // Verifica se é um diretório no bucket ou se já existe.
            if ($upload_image && ($this->bucket->isDirectory($asset_image))) {
                //$this->session->set_flashdata('error', 'No images uploaded');
                $allgood = true;
                $semFoto = false;
                // Pega todas imagens do diretório.
                $product_image = $this->bucket->getFinalObject($asset_image);

                // Pega a primeira imagem para a foto.
                if ($product_image['success'] && count($product_image['contents'])) {
                    $foto = $product_image['contents'][0]['url'];
                }
            } else {
                $this->data['upload_image'] = $upload_image;
                $allgood = true;
                $semFoto = false;
                // Pega a primeira imagem do produto caso a pasta já exista.
                $product_images = $this->bucket->getFinalObject($asset_image);
                if ($product_images['success'] && count($product_images['contents'])) {
                    $principal_image = $product_images['contents'][0]['url'] ?? "";
                }
            }
        }
        if ($allgood) {
            if ($this->model_products->checkIfSkuExists($this->data['usercomp'], $this->postClean('store',TRUE), $this->postClean('sku',TRUE))) {
                $this->session->set_flashdata('error', $this->lang->line('messages_sku_already_exist'));
                $allgood = false;
            }
        }

        // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
        if($this->input->post()) {
            $preco_de = $this->postClean('list_price', TRUE);
            $preco_por = $this->postClean('price', TRUE);
            if (empty($preco_de) || empty($preco_por)) {
                $this->session->set_flashdata('error', $this->lang->line('application_prices_error'));
                $allgood = false;
            }
        }

        // Testo se tem atributos de marketplace obrigatórios como variação 
        if (!is_null($this->postClean('category',TRUE))) {

            $msgError = '';

            // Agora pego os campos do ML. ML é mais simples e só obriga Cor ou tamanho e não precisa que seja 
            $arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), 'ML');
            $campos_att = $arr[0];
            foreach ($campos_att as $campo_att) {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                    if ($campo_att['id_atributo'] == 'COLOR') {
                        if ($this->postClean('colorvar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_color_variant_mercado_livre') . '<br>';
                        }
                    }
                    if ($campo_att['id_atributo'] == 'SIZE') {
                        if ($this->postClean('sizevar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_size_variant_mercado_livre') . '<br>';
                        }
                    }
                    if ($campo_att['id_atributo'] == 'FLAVOR') {
                        if ($this->postClean('saborvar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_size_variant_mercado_livre') . '<br>';
                        }
                    }
                }
            }

            // Agora pego os campos da Via Varejo
            $arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), 'Via');
            $campos_att = $arr[0];
            foreach ($campos_att as $campo_att) {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                    if ($campo_att['nome'] == 'Cor') {  // campo cor da Via Varejo
                        if ($this->postClean('colorvar',TRUE) === "on") {
                            $coreslidas = json_decode($campo_att['valor'], true);
                            $coresvalidas = array();
                            foreach ($coreslidas as $corlida) {
                                $coresvalidas[] = trim(ucfirst(strtolower($corlida['udaValue'])));
                            }
                            $cores = $this->postClean('C',TRUE);
                            foreach ($cores as $key => $cor) {
                                $i = $key + 1;
                                if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
                                    $msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para Via Varejo são: ' . implode(",", $coresvalidas) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Tamanho') {
                        if ($this->postClean('sizevar',TRUE) === "on") {
                            $tamlidas = json_decode($campo_att['valor'], true);
                            $tamvalidos = array();
                            foreach ($tamlidas as $tamlida) {
                                $tamvalidos[] = trim($tamlida['udaValue']);
                            }
                            $tams = $this->postClean('T',TRUE);
                            foreach ($tams as $key => $tam) {
                                $i = $key + 1;
                                if (!in_array($tam, $tamvalidos)) {
                                    $msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para Via Varejo são: ' . implode(",", $tamvalidos) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Voltagem') {
                        if ($this->postClean('voltvar',TRUE) === "on") {
                            $volts = $this->postClean('V',TRUE);
                        }
                    }
                    if ($campo_att['nome'] == 'Sabor') {
                        if ($this->postClean('saborvar',TRUE) === "on") {
                            $flavor = $this->postClean('sb',TRUE);
                        }
                    }
                }
            }

            foreach ($this->vtexsellercenters as $sellercenter) {
                $lcseller = strtolower($sellercenter);
                // Agora pego os campos da Novo Mundo
                $arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), $sellercenter);
                $campos_att = $arr[0];
                foreach ($campos_att as $campo_att) {
                    if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                        if ((strtoupper($campo_att['nome']) == 'COR') || (strtoupper($campo_att['nome']) == 'CORES') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_color)))  {  // campo cor da
                            if ($this->postClean('colorvar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                                    $msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                            } else {
                                $coreslidas = json_decode($campo_att['valor'], true);
                                $coresvalidas = array();
                                foreach ($coreslidas as $corlida) {
                                    $coresvalidas[] = trim(ucfirst(strtolower($corlida['Value'])));
                                }
                                $cores = $this->postClean('C',TRUE);
                                foreach ($cores as $key => $cor) {
                                    $i = $key + 1;
                                    if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
                                        $msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $coresvalidas) . '<br>';
                                    }
                                }
                            }
                        }elseif ((strtoupper($campo_att['nome']) == 'TAMANHO') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_size))) {
                            if ($this->postClean('sizevar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                            	    $msgError .= sprintf($this->lang->line('messages_error_size_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                                // $msgError .= $this->lang->line('messages_error_size_variant_' . $lcseller) . '<br>';
                            } else {
                                $tamlidas = json_decode($campo_att['valor'], true);
                                $tamvalidos = array();
                                foreach ($tamlidas as $tamlida) {
                                    $tamvalidos[] = trim($tamlida['Value']);
                                }
                                $tams = $this->postClean('T',TRUE);
                                foreach ($tams as $key => $tam) {
                                    $i = $key + 1;
                                    if (!in_array($tam, $tamvalidos)) {
                                        $msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $tamvalidos) . '<br>';
                                    }
                                }
                            }
                        }elseif ((strtoupper($campo_att['nome']) == 'VOLTAGEM') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_voltage))){
                            if ($this->postClean('voltvar',TRUE) !== "on") {
                                // $msgError .= $this->lang->line('messages_error_voltage_variant_' . $lcseller) . '<br>';
                                if ($this->hasDefaultValueVariation === false) {
								    $msgError .= sprintf($this->lang->line('messages_error_voltage_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                            } else {
                                $volts = $this->postClean('V',TRUE);
                            }
                        }elseif ((strtoupper($campo_att['nome']) == 'Sabor') || (strtoupper($campo_att['nome']) == 'Sabor') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_flavor)))  {
                            if ($this->postClean('saborvar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                                    $msgError .= sprintf($this->lang->line('messages_error_flavor_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                            } else {
                                $saboreslida = json_decode($campo_att['valor'], true);
                                $saboresvalidas = array();
                                foreach ($saboreslida as $saboreslidas) {
                                    $saboresvalidas[] = trim(ucfirst(strtolower($saboreslidas['Value'])));
                                }
                                $sabores = $this->postClean('sb',TRUE);
                                foreach ($sabores as $key => $sabor) {
                                    $i = $key + 1;
                                    if (!in_array(ucfirst(strtolower($sabor)), $saboresvalidas)) {
                                        $msgError .= 'Sabor "' . $sabor . '" inválido na variação ' . $i . '. Sabores válidas para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $saboresvalidas) . '<br>';
                                    }
                                }
                            }
                        }else {
                            if ($this->hasDefaultValueVariation === false) {
                                $msgError .= 'Esta categoria na '.$lcseller.' obriga a variação por '.$campo_att['nome'].' mas o sistema não suporta<br>';
                            }
                        }
                    }
                }
				if ($this->postClean('colorvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Cor','Cores', $this->variant_color));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Cor'.'<br>';
					}
				}
				if ($this->postClean('voltvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Voltagem', $this->variant_voltage));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Voltagem'.'<br>';
					}
				}
				if ($this->postClean('sizevar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Tamanho',$this->variant_size));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Tamanho'.'<br>';
					}
				}
                if ($this->postClean('grauvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Grau',$this->variant_degree));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Grau'.'<br>';
					}
				}
                if ($this->postClean('ladovar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Lado',$this->variant_side));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Lado'.'<br>';
					}
				}
				/*if ($this->postClean('saborvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('SABOR',$this->variant_flavor));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Tamanho'.'<br>';
					}
				}*/
            }

            if ($msgError !== '') {
                $this->session->set_flashdata('error', $msgError);
                $allgood = false;
            }
        }

        if ($allgood && $this->postClean('sku',TRUE)) {
            $comvar = $this->postClean('semvar',TRUE);
            $has_var = "";
            if ($comvar == "on") {
                $qty = $this->postClean('qty',TRUE);
            } else {
                $qty = 0;
                if ($this->postClean('sizevar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "TAMANHO" : ";TAMANHO";
                }
                if ($this->postClean('colorvar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "Cor" : ";Cor";
                }
                if ($this->postClean('voltvar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "VOLTAGEM" : ";VOLTAGEM";
                }

                $this->data['flavor_active'] = $this->model_settings->getFlavorActive();
                if($this->data['flavor_active']){
                    if ($this->postClean('saborvar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "SABOR" : ";SABOR";
                    }
                }

                $this->data['degree_active'] = $this->model_settings->getDegreeActive();
                if($this->data['degree_active']){
                    if ($this->postClean('grauvar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "GRAU" : ";GRAU";
                    }
                }

                $this->data['side_active'] = $this->model_settings->getSideActive();
                if($this->data['side_active']){
                    if ($this->postClean('ladovar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "LADO" : ";LADO";
                    }
                }
            }

            // vejo se o sku está disponível para uso
            if ($has_var != "") {
                $skuVar = $this->postClean('SKU_V',TRUE);
                for ($x = 0; $x <= $this->postClean('numvar',TRUE) - 1; $x++) {
                    if (!$this->model_products->checkSkuAvailable($skuVar[$x], $this->postClean('store',TRUE))) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_variant_sku_available') . $skuVar[$x]);
                        $allgood = false;
                        break;
                    }
                    if ($skuVar[$x] == $this->postClean('sku',TRUE)) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_variant_sku_equal_product_sku'));
                        $allgood = false;
                        break;
                    }
                }

                if($this->limite_variacoes == 1){

                    $variacoes = explode(';',$has_var);

                    if(count($variacoes) > 2){
                        $this->session->set_flashdata('error', $this->lang->line('api_variation_limit'));
                        $allgood = false;
                    }    

                }
            }
            if (!$this->model_products->checkSkuAvailable($this->postClean('sku',TRUE), $this->postClean('store',TRUE))) {
                $this->session->set_flashdata('error', $this->lang->line('messages_product_sku_available') . $this->postClean('sku',TRUE));
                $allgood = false;
            }
            $list_price = $this->postClean('list_price',TRUE);
            if($list_price == ''){
                $list_price = $this->postClean('price',TRUE);
            }
            if($this->postClean('price',TRUE) > $list_price){
                $this->session->set_flashdata('error', $this->lang->line('application_price_error'));
                $allgood = false;
            }
        }

        if ($allgood) {


            $principal_image2 = !empty($foto ?? '') ? $foto : '';

            // Gero um novo código para o diretório do arquivo.
            // Será salvo no banco e utilizado para inserir imagens no futuro.
            if ($semFoto) {
                $dirImage = get_instance()->getGUID(false); // gero um novo diretorio para as imagens 
                $upload_image = $dirImage;
            }

            $semFabricante = (trim($this->postClean('brands',TRUE)[0]) == '');

            $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
            $semCategoria = (trim($this->postClean('category',TRUE)[0]) == '');

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                if (($semFoto) || ($semCategoria && !$publishWithoutCategory) || ($semFabricante)) {
                    $situacao = '1';
                } else {
                    $situacao = '2';
                }
            } else {
                if (($semFoto) || ($semCategoria) || ($semFabricante)) {
                    $situacao = '1';
                } else {
                    $situacao = '2';
                }
            }
            $prazo_operacional_extra = trim($this->postClean('prazo_operacional_extra',TRUE));
            if ($prazo_operacional_extra == '') {
                $prazo_operacional_extra  = 0;
            }
            $loja = $this->model_stores->getStoresData($this->postClean('store',TRUE));

            $list_price = $this->postClean('list_price',TRUE);
            if($list_price == ''){
                $list_price = $this->postClean('price',TRUE);
            }

            $data_prod = array(
                'name' => $this->postClean('product_name',TRUE),
                'sku' => $this->postClean('sku',TRUE),
                'price' => $this->postClean('price',TRUE),
                'list_price' => $list_price,
                'qty' => $qty,
                'image' => $upload_image,
                'principal_image' => ($principal_image != '' ? $principal_image : $principal_image2),
                'description' => strip_tags_products($this->postClean('description',true, false, false), $this->allowable_tags), // $this->postClean('description'),
                'attribute_value_id' => json_encode($this->postClean('attributes_value_id',TRUE)),
                'brand_id' => json_encode($this->postClean('brands',TRUE)),
                'category_id' => json_encode($this->postClean('category',TRUE)),
                'store_id' => $this->postClean('store',TRUE),
                'status' => $this->postClean('status',TRUE),
                'EAN' => $this->postClean('EAN',TRUE),
                'codigo_do_fabricante' => $this->postClean('codigo_do_fabricante',TRUE),
                'peso_liquido' => $this->postClean('peso_liquido',TRUE),
                'peso_bruto' => $this->postClean('peso_bruto',TRUE),
                'largura' => $this->postClean('largura',TRUE),
                'altura' => $this->postClean('altura',TRUE),
                'profundidade' => $this->postClean('profundidade',TRUE),
                'products_package' => $this->postClean('products_package',TRUE),
                'actual_width' => $this->postClean('actual_width',TRUE),
                'actual_height' => $this->postClean('actual_height',TRUE),
                'actual_depth' => $this->postClean('actual_depth',TRUE),
                'garantia' => $this->postClean('garantia',TRUE),
                //        		'CEST' => $this->postClean('CEST',TRUE),
                //        		'FCI' => $this->postClean('FCI',TRUE),
                'NCM' => preg_replace('/[^\d\+]/', '', $this->postClean('NCM',TRUE)),
                'origin' => $this->postClean('origin',TRUE),
                'has_variants' => $has_var,
                'company_id' => $loja['company_id'],
                'situacao' => $situacao,
                'prazo_operacional_extra' => $prazo_operacional_extra,
            );

            $category_id = $this->postClean('category',TRUE)[0];

            if(!empty($category_id)){
                $data_prod['categorized_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            $create = $this->model_products->create($data_prod);

            // bloqueia produto se necessário
            $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($data_prod, $create);

            if ($create != false) {
                $log_var = array('id' => $create);
                $this->log_data('Products', 'create', json_encode(array_merge($log_var, $data_prod)), "I");
                $this->model_atributos_categorias_marketplaces->saveBrandName($create, $this->postClean('brands',TRUE)[0]);

                // gravo o stores_tipovolumes para avisar se mudou o tipo de volume e mudar no frete rápido
                if (!$semCategoria) {
                    $categoria = $this->model_category->getCategoryData($this->postClean('category',TRUE));
                    if (!is_null($categoria['tipo_volume_id'])) {
                        $datastorestiposvolumes = array(
                            'store_id' => $this->postClean('store',TRUE),
                            'tipos_volumes_id' => $categoria['tipo_volume_id'],
                            'status' => 1,
                        );
                        $this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
                    }
                }

                // $cmd = $this->model_atributos_categorias_marketplaces->apagaAtributosAntigos($create);

                if ($has_var != "") {

                    $qty = 0;
                    $tam = $this->postClean('T',TRUE);
                    $cor = $this->postClean('C',TRUE);
                    $volt = $this->postClean('V',TRUE);
                    $sabor = $this->postClean('sb',TRUE);
                    $grau = $this->postClean('gr',TRUE);
                    $lado = $this->postClean('ld',TRUE);
                    // $preco = $this->postClean('P');
                    $qtd = $this->postClean('Q',TRUE);
                    $skuVar = $this->postClean('SKU_V',TRUE);
                    $eanVar = $this->postClean('EAN_V',TRUE);
                    $priceVar = $this->postClean('PRICE_V',TRUE);
                    $listPriceVar = $this->postClean('LIST_PRICE_V',TRUE);
                    $image_folder = $this->postClean('IMAGEM',TRUE);
                    $countVarEmpty = 1;

                    for ($x = 0; $x <= $this->postClean('numvar',TRUE) - 1; $x++) {
                        $variant = $x;

                        $variante = "";
                        if ($this->postClean('sizevar',TRUE) == "on") {
                            $variante .= ";" . $tam[$x];
                        }
                        if ($this->postClean('colorvar',TRUE) == "on") {
                            $variante .= ";" . $cor[$x];
                        }
                        if ($this->postClean('voltvar',TRUE) == "on") {
                            $variante .= ";" . $volt[$x];
                        }
                        if ($this->postClean('saborvar',TRUE) == "on") {
                            $variante .= ";" . $sabor[$x];
                        }
                        if ($this->postClean('grauvar',TRUE) == "on") {
                            $variante .= ";" . $grau[$x];
                        }
                        if ($this->postClean('ladovar',TRUE) == "on") {
                            $variante .= ";" . $lado[$x];
                        }
                        $variante = substr($variante, 1);

                        // Mantem os sku das variações ou cria novos que não existem
                        if (empty($skuVar[$x])) {
                            $skuVar[$x] = $this->postClean('sku',TRUE) . "-{$countVarEmpty}";

                            while ($this->model_products->getVariantsForSku($create, $skuVar[$x])) {
                                $skuVar[$x] = $this->postClean('sku',TRUE) . "-{$countVarEmpty}";
                                $countVarEmpty++;
                            }
                        }

                        $targetDir = FCPATH . 'assets/images/product_image/' . $upload_image . '/' . $image_folder[$x];;
                        if (!file_exists($targetDir)) {
                            // cria o diretorio para o produto receber as imagens 
                            // @mkdir($targetDir,775,true);
                            //chmod($targetDir,775);
                            @mkdir($targetDir);
                        }

                        $price_var      = (is_null($priceVar[$variant]) || trim($priceVar[$variant] == '')) ? $this->postClean('price',TRUE) : $priceVar[$variant];
                        $list_price_var = (is_null($listPriceVar[$variant]) || trim($listPriceVar[$variant] == '')) ? $this->postClean('price',TRUE) : $listPriceVar[$variant];
                        $qty_var        = (int)$qtd[$variant];

                        if(!is_null($listPriceVar[$x])){
                            if($listPriceVar[$x] < $priceVar[$x]){
                               $listPriceVar[$x] = $priceVar[$x];
                            }
                        }

                        $data_var = array(
                            'prd_id' => $create,
                            'variant' => $x,
                            'name' => $variante,
                            'sku' => $skuVar[$x],
                            'price' => $price_var,
                            'list_price' => $list_price_var,
                            'qty' => $qty_var,
                            'image' => $image_folder[$x],
                            'status' => 1,
                            'EAN' => $eanVar[$x],
                            'codigo_do_fabricante' => '',
                        );
                        $qty += (int)$qtd[$x];
                        $createvar = $this->model_products->createvar($data_var);
                        $this->model_log_products->create_log_products($data_var, $create, 'Criado Variaçao ' . $x);
                        $this->log_data('Products', 'create_variation', json_encode($data_var), "I");
                    }
                    $data_prod['qty'] = $qty;
                    $this->log_data('Products', 'edit_after_qty_var', json_encode($data_prod), "I");
                    //update da quantidade do produto pai
                    $update = $this->model_products->update($data_prod, $create);
                }
                $minhasLojas = $this->model_stores->getActiveStore();
                if (($this->data['userstore'] != 0)) {  // se o usuário é de uma loja só, posso pegar o preço por variação
                    $integrations = $this->model_integrations->getIntegrationsbyStoreId($this->data['userstore']);
                } elseif (count($minhasLojas)  == 1) { // se o usuário só tem cadastrada uma loja só na sua empresa, posso pegar o preço por variação
                    $integrations = $this->model_integrations->getIntegrationsbyStoreId($minhasLojas[0]['id']);
                } else {
                    $integrations = array();
                }
                // altero agora os preços e qty por marketplace que foram criados automaticamente pelo model_products->create
                foreach ($integrations as $integration) {
                    $this->model_products_marketplace->createIfNotExist($integration['int_to'], $create, $integration['int_type'] == 'DIRECT');

                    $products_marketplace = $this->model_products_marketplace->getAllDataByIntToProduct($integration['int_to'], $create);
                    foreach ($products_marketplace as $product_marketplace) {
                        if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
                            if ($this->postClean('samePrice_' . $integration['id'],TRUE) == 'on') {
                                $data = array(
                                    'same_price' => true,
                                    'price' => $this->postClean('price',TRUE),
                                );
                            } else {
                                $data = array(
                                    'same_price' => false,
                                    'price' => $this->postClean('price_' . $integration['id'],TRUE),
                                );
                            }

                            // gravo log e update 
                            $log = [
                                'id'           => $product_marketplace['id'],
                                'int_to'      => $product_marketplace['int_to'],
                                'product_id' => $create,
                                'old_price'    => 'NOVO',
                                'new_price'    => $data['price']
                            ];
                            $this->log_data('ProductsMarketPlace', 'Update_Price', json_encode($log), 'I');
                            $this->model_log_products->create_log_products(array('price' => $data['price']), $create, 'Preço Marketplace ' . $product_marketplace['int_to']);
                            $this->model_products_marketplace->updateAllVariants($data, $product_marketplace['int_to'], $product_marketplace['prd_id']);
                        }
                        if ($product_marketplace['hub']) {
                            if ($product_marketplace['variant'] == '') {
                                if ($this->postClean('sameQty_' . $integration['id'],TRUE) == 'on') {
                                    $data = array(
                                        'same_qty' => true,
                                        'qty' => $this->postClean('qty',TRUE),
                                    );
                                } else {
                                    $data = array(
                                        'same_qty' => false,
                                        'qty' => $this->postClean('qty_' . $integration['id'],TRUE),
                                    );
                                }

                                // gravo log e update somente se alterou...
                                $log = [
                                    'id'           => $product_marketplace['id'],
                                    'int_to'      => $product_marketplace['int_to'],
                                    'product_id' => $create,
                                    'old_qty'    => 'NOVO',
                                    'new_qty'    => $data['qty']
                                ];
                                $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
                                $this->model_log_products->create_log_products(array('qty' => $data['qty']), $create, 'Estoque Marketplace ' . $product_marketplace['int_to']);
                                $this->model_products_marketplace->update($data, $product_marketplace['id']);
                            }
                        }
                    }
                }

                $product_saved=$this->model_products->getProductData(0, $create);
                $this->checkImageProduct($product_saved);
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect("products/attributes/create/$create/$category_id", 'refresh');
                // redirect("products", 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect($this->creationProductView, 'refresh');
            }
        } else {
            // false case
            $this->data['prdtoken'] = get_instance()->getGUID(false);
            $this->data['upload_image'] = $this->postClean('product_image',TRUE);
            if (($this->data['upload_image'] != '') && (!is_null($this->data['upload_image']))) {
                $this->data['prdtoken'] =  $this->data['upload_image'];
            }
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');

            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;

                $value = $this->model_attributes->getAttributeValueData($v['id']);

                $attributes_final_data[$k]['attribute_value'] = $value;
            }

            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();
            $this->data['stores'] = $this->model_stores->getActiveStore();

            // leio todos os atributos por categoria
            /*
            $atributos= $this->model_atributos_categorias_marketplaces->getAllAtributosMarketplaces();
            $this->data['atributosmarketplace'] = $atributos;
            foreach ($atributos as $campo) {
                $this->data['product_atributos_marketplace'][$campo['nome']] = '';
            }
			 */

            $comvar = $this->postClean('semvar',TRUE);
            $variacaotamanho = array();
            $variacaocor = array();
            $variacaovoltagem = array();
            $variacaosabor = array();
            $variacaograu = array();
            $variacaolado = array();
            $variacaoquantidade = array();
            $variacaosku = array();
            $variacaoean = array();
            $variacaoprice = array();
            $variacaolistprice = array();
            $variacaoimagem = array();
            if ($comvar != "on") {
                if ($this->postClean('sizevar',TRUE) == "on") {
                    $variacaotamanho =  $this->postClean('T',TRUE);
                }
                if ($this->postClean('colorvar',TRUE) == "on") {
                    $variacaocor =  $this->postClean('C',TRUE);
                }
                if ($this->postClean('voltvar',TRUE) == "on") {
                    $variacaovoltagem =  $this->postClean('V',TRUE);
                }
                if ($this->postClean('saborvar',TRUE) == "on") {
                    $variacaosabor =  $this->postClean('sb',TRUE);
                }
                if ($this->postClean('grauvar',TRUE) == "on") {
                    $variacaograu =  $this->postClean('gr',TRUE);
                }
                if ($this->postClean('ladovar',TRUE) == "on") {
                    $variacaolado =  $this->postClean('ld',TRUE);
                }
                $variacaoquantidade = $this->postClean('Q',TRUE);
                $variacaosku = $this->postClean('SKU_V',TRUE);
                $variacaoprice = $this->postClean('PRICE_V',TRUE);
                $variacaolistprice = $this->postClean('LIST_PRICE_V',TRUE);
                $variacaoean = $this->postClean('EAN_V',TRUE);
                $variacaoimagem = $this->postClean('IMAGEM',TRUE);
            }
            $this->data['variacaotamanho'] = $variacaotamanho;
            $this->data['variacaocor'] = $variacaocor;
            $this->data['variacaovoltagem'] = $variacaovoltagem;
            $this->data['variacaosabor'] = $variacaosabor;
            $this->data['variacaograu'] = $variacaograu;
            $this->data['variacaolado'] = $variacaolado;
            $this->data['variacaoquantidade'] = $variacaoquantidade;
            $this->data['variacaosku'] = $variacaosku;
            $this->data['variacaoean'] = $variacaoean;
            $this->data['variacaoprice'] = $variacaoprice;
            $this->data['variacaolistprice'] = $variacaolistprice;
            $this->data['variacaoimagem'] = $variacaoimagem;
            $this->data['require_ean'] = $require_ean;
            $this->data['invalid_ean'] = $this->lang->line('application_ean') . ' inválido';


            $this->data['integrations'] = array();  // Não mostra o preço por variação e deixar criar o default para mudar depois
            if ($Preco_Quantidade_Por_Marketplace == 1) {
                if (($this->data['userstore'] != 0)) {  // se o usuário é de uma loja só posso pegar o preço por variação
                    $this->data['integrations'] = $this->model_integrations->getIntegrationsbyStoreId($this->data['userstore']);
                } elseif (count($this->data['stores'])  == 1) { // se o usuário só tem cadastrada uma loja só na sua empresa, posso pegar o preço por variação
                    $this->data['integrations'] = $this->model_integrations->getIntegrationsbyStoreId($this->data['stores'][0]['id']);
                }
            }

            $key = '';
            $keys = array_merge(range('A', 'Z'), range('a', 'z'));

            for ($i = 0; $i < 15; $i++) {
                $key .= $keys[array_rand($keys)];
            }
            $this->data['imagemvariant0'] = $key;

            $this->data['flavor_active'] = $this->model_settings->getFlavorActive();
            if(!$this->data['flavor_active']){
                $this->data['flavor_active'] = "";
            }

            $this->data['degree_active'] = $this->model_settings->getDegreeActive();
            if(!$this->data['degree_active']){
                $this->data['degree_active'] = "";
            }

            $this->data['side_active'] = $this->model_settings->getSideActive();
            if(!$this->data['side_active']){
                $this->data['side_active'] = "";
            }

            $this->render_template($this->creationProductView, $this->data);
        }
    }

    public function attributes($action = 'create', $product = null, $category = null)
    {
        if ($product == null) {
            redirect('products', 'refresh');
        }

        $product_data = $this->model_products->verifyProductsOfStore($product);
        if (!$product_data) {
            redirect('dashboard', 'refresh');
        }

        // Pego os campos do Mercado Livre
        $arr = $this->pegaCamposMKTdaMinhaCategoria($category, 'ML', $product);
        $campos_att = $arr[0];
        $category_ml = isset($arr[1]['nome']) ? $arr[1]['nome'] : "";
        $enriched_ml = $arr[2];
        // PARA TESTAR, UTILIZAR A CATEGORIA 2093 (UTILIDADES DOMÉSTICAS > CONJUNTO DE PANELAS > CONJUNTO DE PANELAS DE CERÂMICA)

        // se alterar esta lista aqui, lembre-se de alterar em BatchC/MLLeilao e MLSyncProducts
        $ignoreML = array('BRAND', 'EAN', 'GTIN', 'SELLER_SKU', 'EXCLUSIVE_CHANNEL', 'ITEM_CONDITION');
        $tipos_variacao = explode(";", strtoupper($product_data['has_variants']));
        $fieldsML = [];
        foreach ($campos_att as $campo_att) {
            //if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) { // se deveria ser uma variação, tb não mostro na segunda tela 
            //	$ignoreML[] = $campo_att['id_atributo'];
            //}
            if ($product_data['has_variants'] != "") {
                if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                    $ignoreML[] = $campo_att['id_atributo'];
                }
            }

            in_array($campo_att['id_atributo'], $ignoreML) ? '' : array_push($fieldsML, $campo_att);
        }

        // Agora pego os campos da Via Varejo
        $arr = $this->pegaCamposMKTdaMinhaCategoria($category, 'VIA');
        $campos_att = $arr[0];
        $ignoreVia = array('SELECIONE', 'GARANTIA');
        $fieldsVia = [];
        foreach ($campos_att as $campo_att) {
            if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) { // se deveria ser uma variação, tb não mostro na segunda tela 
                if (!in_array(strtoupper($campo_att['nome']), $tipos_variacao)) {
                    array_push($fieldsVia, $campo_att);
                }
                $ignoreVia[] = strtoupper($campo_att['nome']);
            } elseif ($product_data['has_variants'] != "") {
                if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                    $ignoreVia[] = strtoupper($campo_att['nome']);
                }
            }

            in_array(strtoupper($campo_att['nome']), $ignoreVia) ? '' : array_push($fieldsVia, $campo_att);
        }


        // Agora pego os campos da Novo Mundo
        $naoachou = (empty($fieldsML)) && (empty($fieldsVia));

        foreach ($this->vtexsellercenters as $sellercenter)
        {
            $arr = $this->pegaCamposMKTdaMinhaCategoria($category, $sellercenter);
            $campos_att = $arr[0];
            $sellercenter = str_replace('&', '', $sellercenter);
            ${'ignore' . $sellercenter} = array();
            ${'fields' . $sellercenter} = array();

            $ignoreFields = $this->model_settings->getValueIfAtiveByName('ignore_atributtes_sellercenter_'.$sellercenter);
            if($ignoreFields){
                $ignoreFields = explode(',', str_replace(" ", "",$ignoreFields));
            }else{
                $ignoreFields = array();
            }

            foreach ($campos_att as $campo_att) 
            {
                if(in_array(strtoupper($campo_att['nome']), $ignoreFields)){
                    continue;
                }

                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) 
                { 
                    if (strtoupper($campo_att['nome']) == strtoupper($this->variant_color)) {
                        $campo_att['nome'] = strtoupper(self::COLOR_DEFAULT);
                    }
                    if (strtoupper($campo_att['nome']) == strtoupper($this->variant_size)) {
                        $campo_att['nome'] = strtoupper(self::SIZE_DEFAULT);
                    }
                    if (strtoupper($campo_att['nome']) == strtoupper($this->variant_voltage)) {
                        $campo_att['nome'] = strtoupper(self::VOLTAGE_DEFAULT);
                    }

                    if (!in_array(strtoupper($campo_att['nome']), $tipos_variacao)) {
                        array_push(${'fields' . $sellercenter}, $campo_att);
                    }
                    if ($product_data['has_variants'] != "") {
                        // se deveria ser uma variação, tb não mostro na segunda tela
                        if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) {
                            ${'ignore' . $sellercenter}[] = strtoupper($campo_att['nome']);
                        }
                    }
                } 
                elseif ($product_data['has_variants'] != "") 
                {
                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) {
                        ${'ignore' . $sellercenter}[] = strtoupper($campo_att['nome']);
                    }
                }

                if (!in_array(strtoupper($campo_att['nome']), ${'ignore' . $sellercenter})) {
                    $can_add = true;
                    foreach (${'fields' . $sellercenter} as $field) {
                        if (strtoupper($campo_att['nome']) == strtoupper($field['nome']))
                            $can_add = false;
                    }
                    if ($can_add) { 
                        array_push(${'fields' . $sellercenter}, $campo_att);
                    }
                }
                // in_array(strtoupper($campo_att['nome']), ${'ignore' . $sellercenter}) ? '' : array_push(${'fields' . $sellercenter}, $campo_att);
            }

            $naoachou = $naoachou && empty(${'fields' . $sellercenter});
        }

        if (($naoachou) && $category !== null) 
        {
            $this->model_atributos_categorias_marketplaces->deleteAtributos($product);
        }

         // Agora pego os campos da NM
         $arr = $this->pegaCamposMKTdaMinhaCategoria($category, 'NM');
         $campos_att = $arr[0];
        //  $ignoreVia = array('SELECIONE', 'GARANTIA');
         $ignoreNM = [];
         $fieldsNM = [];

         foreach ($campos_att as $campo_att) 
         {
             if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) 
             { 
                 // se deveria ser uma variação, tb não mostro na segunda tela 
                 if (!in_array(strtoupper($campo_att['nome']), $tipos_variacao)) 
                 {
                     array_push($fieldsNM, $campo_att);
                 }
                 $ignoreNM[] = strtoupper($campo_att['nome']);
             } 
             elseif ($product_data['has_variants'] != "") 
             {
                 if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) 
                 { 
                     // ignora os atributos que estão na variação. 
                     $ignoreNM[] = strtoupper($campo_att['nome']);
                 }
             }
 
             in_array(strtoupper($campo_att['nome']), $ignoreNM) ? '' : array_push($fieldsNM, $campo_att);
         }


          // Agora pego os campos da ORT
          $arr = $this->pegaCamposMKTdaMinhaCategoria($category, 'ORT');
          $campos_att = $arr[0];
         //  $ignoreVia = array('SELECIONE', 'GARANTIA');
          $ignoreORT = [];
          $fieldsORT = [];
 
          foreach ($campos_att as $campo_att) 
          {
              if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) 
              { 
                  // se deveria ser uma variação, tb não mostro na segunda tela 
                  if (!in_array(strtoupper($campo_att['nome']), $tipos_variacao)) 
                  {
                      array_push($fieldsORT, $campo_att);
                  }
                  $ignoreORT[] = strtoupper($campo_att['nome']);
              } 
              elseif ($product_data['has_variants'] != "") 
              {
                  if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) 
                  { 
                      // ignora os atributos que estão na variação. 
                      $ignoreORT[] = strtoupper($campo_att['nome']);
                  }
              }
  
              in_array(strtoupper($campo_att['nome']), $ignoreORT) ? '' : array_push($fieldsORT, $campo_att);
          }

          // Agora pego os campos da Angeloni
          $arr = $this->pegaCamposMKTdaMinhaCategoria($category, 'Angeloni');
          $campos_att = $arr[0];
          $ignoreAngeloni = [];
          $fieldsAngeloni = [];
 
          foreach ($campos_att as $campo_att) 
          {
            if ($product_data['has_variants'] != "") 
            {
                if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) 
                { 
                    // ignora os atributos que estão na variação. 
                    $ignoreAngeloni[] = strtoupper($campo_att['nome']);
                }
            }

            in_array(strtoupper($campo_att['nome']), $ignoreAngeloni) ? '' : array_push($fieldsAngeloni, $campo_att);
          }


        // Obrigatórios do Mercado Livre
        if ($this->postClean('obrigML[]',TRUE) != null) {
            foreach ($this->postClean('obrigML[]',TRUE) as $key => $obrig) {
                if ($obrig == '1') {
                    //$this->form_validation->set_rules("valorML[$key]", $this->lang->line('application_'.strtolower($this->postClean("id_atributoML[$key]",TRUE))), 'trim|required');
                    $this->form_validation->set_rules("valorML[$key]", $this->postClean("nomeML[$key]",TRUE), 'trim|required');
                } else {
                    //$this->form_validation->set_rules("valorML[$key]", $this->lang->line('application_'.strtolower($this->postClean("id_atributoML[$key]",TRUE))), 'trim');
                    $this->form_validation->set_rules("valorML[$key]",  $this->postClean("nomeML[$key]",TRUE), 'trim');
                }
            }
        }

        // Obrigatórios da Via Varejo
        if ($this->postClean('obrigVia[]',TRUE) != null) {
            foreach ($this->postClean('obrigVia[]',TRUE) as $key => $obrig) {
                if ($obrig == '1') {
                    $this->form_validation->set_rules("valorVia[$key]", $this->postClean("nomeVia[$key]",TRUE), 'trim|required');
                } else {
                    $this->form_validation->set_rules("valorVia[$key]", $this->postClean("nomeVia[$key]",TRUE), 'trim');
                }
            }
        }

        foreach ($this->vtexsellercenters as $sellercenter) {
            // Obrigatórios da Novo Mundo
            $sellercenter = str_replace('&', '', $sellercenter);
            if ($this->postClean('obrig' . $sellercenter . '[]',TRUE) != null) {
                foreach ($this->postClean('obrig' . $sellercenter . '[]',TRUE) as $key => $obrig) {
                    if ($obrig == '1') {
                        $this->form_validation->set_rules("valor" . $sellercenter . "[$key]", $this->postClean("nome" . $sellercenter . "[$key]",TRUE), 'trim|required');
                    } else {
                        $this->form_validation->set_rules("valor" . $sellercenter . "[$key]", $this->postClean("nome" . $sellercenter . "[$key]",TRUE), 'trim');
                    }
                }
            }
        }

        $this->form_validation->set_rules("attributeCustom_name", 'Custom name', 'trim');
        $this->form_validation->set_rules("attributeCustom_value", 'Custom value', 'trim');

        if ($this->form_validation->run()) {

            if ($category !== null) $this->model_atributos_categorias_marketplaces->deleteAtributos($this->postClean("id_product",TRUE));

            $attrCustonsName  = $this->postClean('attributeCustom_name',TRUE);
            $attrCustonsValue = $this->postClean('attributeCustom_value',TRUE);

            $this->model_products->removeAttributesCustomProduct($product);

            if ($attrCustonsName) {

                foreach ($attrCustonsName as $key => $_) {
                    $attrCustomName = $attrCustonsName[$key];
                    $attrCustomValue = $attrCustonsValue[$key];

                    $this->model_products->insertAttributesCustomProduct($product, $attrCustomName, $attrCustomValue);
                }
            }

            // Gravo os atributos Mercado livre
            if (!empty($fieldsML)) {
                $atributos = $this->postClean('id_atributoML',TRUE);
                foreach ($atributos as $key => $atributo) {
                    if ($this->postClean("valorML[$key]",TRUE) != "" && $this->postClean("valorML[$key]",TRUE) != null) {
                        $data = [
                            'id_product'  => $this->postClean("id_product",TRUE),
                            'id_atributo' => $this->postClean("id_atributoML[$key]",TRUE),
                            'valor'       => $this->postClean("valorML[$key]",TRUE),
                            'int_to'      => 'ML',
                        ];
                        $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
                    }
                }
            }
            // Gravo os atributos Via Varejo
            if (!empty($fieldsVia)) {
                $atributos = $this->postClean('id_atributoVia',TRUE);
                foreach ($atributos as $key => $atributo) {
                    if ($this->postClean("valorVia[$key]",TRUE) != "" && $this->postClean("valorVia[$key]",TRUE) != null) {
                        $data = [
                            'id_product'  => $this->postClean("id_product",TRUE),
                            'id_atributo' => $this->postClean("id_atributoVia[$key]",TRUE),
                            'valor'       => $this->postClean("valorVia[$key]",TRUE),
                            'int_to'      => 'VIA',
                        ];
                        $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
                    }
                }
            }
            // Gravo os atributos NovoMundo
            foreach ($this->vtexsellercenters as $sellercenter) {
                $sellercenter = str_replace('&', '', $sellercenter);
                if (!empty(${'fields' . $sellercenter})) {
                    $atributos = $this->postClean('id_atributo' . $sellercenter,TRUE);
                    foreach ($atributos as $key => $atributo) {
                        if ($this->postClean("valor" . $sellercenter . "[$key]",TRUE) != "" && $this->postClean("valor" . $sellercenter . "[$key]",TRUE) != null) {
                            $data = [
                                'id_product'  => $this->postClean("id_product",TRUE),
                                'id_atributo' => $this->postClean("id_atributo" . $sellercenter . "[$key]",TRUE),
                                'valor'       => $this->postClean("valor" . $sellercenter . "[$key]",TRUE),
                                'int_to'      => $sellercenter,
                            ];
                            $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
                        }
                    }
                }
            }


            // braun ->Gravo os atributos SC NovoMundo
            if (!empty($fieldsNM))
            {
                $atributos = $this->postClean('id_atributoNM',TRUE);

                foreach ($atributos as $key => $atributo)
                {
                    if ($this->postClean("valorNM[$key]",TRUE) != "" && $this->postClean("valorNM[$key]",TRUE) != null)
                    {
                        $data = [
                            'id_product'  => $this->postClean("id_product",TRUE),
                            'id_atributo' => $this->postClean("id_atributoNM[$key]",TRUE),
                            'valor'       => $this->postClean("valorNM[$key]",TRUE),
                            'int_to'      => 'NM',
                        ];
                        $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
                    }
                }
            }

            // braun ->Gravo os atributos SC ORtobom
            if (!empty($fieldsORT))
            {
                $atributos = $this->postClean('id_atributoORT',TRUE);

                foreach ($atributos as $key => $atributo)
                {
                    if ($this->postClean("valorORT[$key]",TRUE) != "" && $this->postClean("valorORT[$key]",TRUE) != null)
                    {
                        $data = [
                            'id_product'  => $this->postClean("id_product",TRUE),
                            'id_atributo' => $this->postClean("id_atributoORT[$key]",TRUE),
                            'valor'       => $this->postClean("valorORT[$key]",TRUE),
                            'int_to'      => 'ORT',
                        ];
                        $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data);
                    }
                }
            }



            $this->model_products->update(array('date_update' => date("Y-m-d H:i:s")), $product_data['id']);   // forcar o re-envio para o marketplace 

            $isCollection = $this->model_settings->getValueIfAtiveByName('collection_occ');
            if($isCollection){
    
                $product_collections = $this->postClean('product_collections');
    
                if($product_collections){
                    $this->model_collections->removeProductCollections($product);
                }
    
                foreach($product_collections as $pc){
    
                    $collection = $this->model_collections->getCollectionData($pc);
    
                    $data = array(
                        'product_id' => $product,
                        'collection_id' => $pc,
                        'mktp_collection_id' => $collection['mktp_id'],
                        'user' => $_SESSION['username'],
                        'date' => date_create()->format('Y-m-d H:i:s'),   
                    );
    
                    $this->model_collections->createProductCollection($data);
    
                }
                
            }

            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect('products', 'refresh');
        }

        if ($action == 'edit') {
            $this->data['attributes'] = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($product);
        } else {
            $this->data['attributes'] = '';
        }

        $this->data['camposML'] = $fieldsML;
        $this->data['category_ml'] = $category_ml;
        $this->data['enriched_ml'] = $enriched_ml;
        $this->data['camposVIA'] = $fieldsVia;

        $this->data['camposNM'] = $fieldsNM;

        $this->data['camposCustom'] = $this->model_products->getAttributesCustomProduct($product);
        $this->data['allAttributesCustom'] = $this->model_products->getAttributesCustom();
        foreach ($this->vtexsellercenters as $sellercenter) {
            $sellercenter = str_replace('&', '', $sellercenter);
            $this->data['campos' . $sellercenter] = ${'fields' . $sellercenter};
        }
        $this->data['sellercenters'] = $this->vtexsellercenters;
		$this->data['sellercentersnames'] = $this->vtexsellercentersNames;
		
        $this->data['tamanho_default'] = $this->model_settings->getValueIfAtiveByName('variacao_tamanho_default');
        $this->data['cor_default'] = $this->model_settings->getValueIfAtiveByName('variacao_cor_default');
        $this->data['show_marketplace_attributes_only_to_admin'] = explode(';', $this->model_settings->getValueIfAtiveByName('show_marketplace_attributes_only_to_admin'));
        //braun -> engesso sellercenters dentro para rodar no loop
        // $this->data['sellercenters'][] = 'NM';
        // $this->data['sellercenters'][] = 'ORT';
        $isCollection = $this->model_settings->getValueIfAtiveByName('collection_occ');
        if($isCollection){
           $collections = $this->model_collections->getCollectionData();
           $this->data['collections'] = $collections;
           $productCollections = $this->model_collections->getProductCollectionByProductId($product);
           $nav = [];
           foreach($productCollections as $pc){
            array_push($nav, $pc['collection_id']); 
           }
           $this->data['productCollections'] = $nav;
        }

        $this->data['product']  = $product;
        $this->data['category'] = $category;
        $this->data['product_data'] = $product_data;
        $this->render_template('products/attributes', $this->data);
    }

    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_image()
    {
        // assets/images/product_image
        $config['upload_path'] = 'assets/images/product_image';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '1500';

        // $config['max_width']  = '1024';
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('product_image')) {
            $error = $this->upload->display_errors();
            return array(false, $error);
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['product_image']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return array(true, ($data == true) ? $path : false);
        }
    }

    public function checkMinValue($field, $min)
    {
        if ((int)$field < (int)$min) {
            $this->form_validation->set_message('checkMinValue', '%s não pode ser menor que "' . $min . '"');
            return FALSE;
        }
        return true;
    }

    public function checkEan($ean)
    {

        if (!$this->model_products->ean_check($ean)) {
            $this->form_validation->set_message('checkEan', $this->lang->line('application_ean') . ' ' . $ean . ' inválido');
            return false;
        }
        return true;
    }

    public function checkUniqueEan($ean, $store_product_id)
    {

        if ((is_null($ean)) || (trim($ean) == '')) {
            return true;
        }
        $store_product_id = explode('|', $store_product_id);

        $exist = $this->model_products->VerifyEanUnique($ean, $store_product_id[0], $store_product_id[1]);
        if ($exist) {
            $this->form_validation->set_message('checkUniqueEan', $this->lang->line('application_ean') . ' ' . $ean . ' já cadastrado, id=' . $exist);
            return FALSE;
        }
        return true;
    }

    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_file()
    {
        // assets/files/product_upload
        $config['upload_path'] = 'assets/files/product_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'csv|txt';
        $config['max_size'] = '100000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('product_upload')) {
            $error = $this->upload->display_errors();
            //Var_dump($error);
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            $this->data['upload_msg'] = $error;
            return false;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['product_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data == true) ? $path : false;
        }
    }

    public function view($product_id = null)
    {
        $this->update($product_id);
    }

    /*
     * If the validation is not valid, then it redirects to the edit product page
     * If the validation is successfully then it updates the data into the database
     * and it stores the operation message into the session flashdata and display on the manage product page
     */
    public function update($product_id = null)
    {
        // $product_length_name = $this->model_settings->getValueIfAtiveByName('product_length_name');
        // dd($this->product_length_name,$product_length_name );
        if (!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (isset($_COOKIE['getparam'])) {
            $product_id = $_COOKIE['getparam'];
            setcookie("getparam", "", 1, "/","",true,true);
        }
        if (!$product_id) {
            redirect('dashboard', 'refresh');
        }
        $this->data['sellercenter_name'] = $this->model_settings->getValueIfAtiveByName('sellercenter_name');

        $can_variation_in_attributes = !!$this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
        $this->data['can_variation_in_attributes'] = $can_variation_in_attributes;

        $Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
        if (!in_array('updateProductsMarketplace', $this->permission)) {
            $Preco_Quantidade_Por_Marketplace = 0;
        }

        $this->data['dimenssion_min_product_image'] = null;
        $this->data['dimenssion_max_product_image'] = null;
        $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
        if ($product_image_rules) {
            $exp_product_image_rules = explode(';', $product_image_rules);
            if (count($exp_product_image_rules) === 2) {
                $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                $this->data['dimenssion_min_product_image'] = $dimenssion_min_validate;
                $this->data['dimenssion_max_product_image'] = $dimenssion_max_validate;
            }
        }

        $this->data['product_length_name'] = $this->product_length_name ;
        $this->data['product_length_description'] = $this->product_length_description;
        $this->data['product_length_sku'] = $this->product_length_sku;
        $this->data['displayPriceByVariation'] = $this->model_settings->getStatusbyName('price_variation');
        $this->data['disableBrandCreationbySeller'] = $this->model_settings->getValueIfAtiveByName('disable_brand_creation_by_seller');
        $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);

        $product_data =$this->model_products->verifyProductsOfStore($product_id);
        
        if (!$product_data) {
            redirect('dashboard', 'refresh');
        }
        
        ////// Bloqueio de Prazo fixo (FR)
        $idCategory_id = $product_data['category_id'];  //pego o id da categoria setado no banco
        $idCategory_id = trim($idCategory_id, '[" "]'); //limpo ela removendo esses [" "] caracteres
        $block = $this->model_category->getcategoryBlock($idCategory_id); //seleciono na model
        $name_cat = $this->model_category->getcategoryName($idCategory_id); //seleciono na model

        $days_cross_docking = $this->model_category->getcategoryDays_cross_docking($idCategory_id); //seleciono na model
        $this->data['idCategory_id'] =  $idCategory_id;
        $this->data['name_cat'] =  $name_cat;
        
        $cat_exception = isset($cat_exception) ? $cat_exception : "";
        $this->data['cat_exception'] =  $cat_exception;

        if($block == 1){ //retorno
            $this->data['campoBlock'] =  $campoBlock = "readonly";
            $this->data['days_cross_docking'] =   $days_cross_docking;
        }else{
            $this->data['campoBlock'] =  $campoBlock = ""; 
        }

        $productDeleted = $product_data['status'] == Model_products::DELETED_PRODUCT;
        if ($productDeleted) {
            $this->session->set_flashdata('error', strlen($this->session->flashdata('error')) > 0
                ? $this->session->flashdata('error')
                : $this->lang->line('messages_edit_product_removed'));
        }

        if ($product_data['is_kit']) {
            redirect('productsKit/update/' . $product_id, 'refresh');
            return;
        }

        if (!is_null($product_data['product_catalog_id'])) {
            redirect('catalogProducts/updateFromCatalog/' . $product_id, 'refresh');
            return;
        }

        if ($this->model_settings->getStatusbyName('stores_multi_cd') == 1) {
			$store = $this->model_stores->getStoresData($product_data['store_id']);
			if ($store['type_store'] ==2)  {
				$multi_channel = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($store['id'], $store['company_id'], 1);
				if ($multi_channel){
					$original_store_id = $multi_channel[0]['store_id_principal']; 
					if ($product_data['has_variants'] == '') {
						$prd_original = $this->model_products->getProductComplete($product_data['sku'], $store['company_id'], $original_store_id);
						if ($prd_original) {
							$this->data['prd_original'] =  $prd_original;
						}
					}
					else{
						$variants  = $this->model_products->getVariants($product_data['id']);						
						foreach ($variants as $variant) {
							$variant_original = $this->model_products->getVariantsBySkuAndStore($variant['sku'], $original_store_id);
							if ($variant_original) {
								$this->data['prd_original'] = $this->model_products->getProductData(0,$variant_original['prd_id']);
								break;
							}
						}
					}
				}
			}
		}

        $this->data['usergroup'] = $this->session->userdata('group_id');
        $this->form_validation->set_rules('sku', $this->lang->line('application_sku'), 'trim|required|callback_validateLengthSku');
        if ($this->postClean('has_integration') || $this->postClean('status',TRUE) != 1) {
            $this->form_validation->set_rules('product_name', $this->lang->line('application_product_name'), 'trim|required');
            $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
        } else {
            $this->form_validation->set_rules('product_name', $this->lang->line('application_product_name'), 'trim|required|max_length[' . $this->product_length_name . ']');
            $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required|max_length[' . $this->product_length_description . ']');
        }
        $this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|greater_than[0]');
        if ($this->postClean('semvar',TRUE) == "on") {
            $this->form_validation->set_rules('qty', $this->lang->line('application_item_qty'), 'trim|required');
        } else {
            $this->form_validation->set_rules('SKU_V[]', $this->lang->line('application_variation_sku'), 'trim|required|callback_validateLengthSku');
        }
        /*
        if ($this->data['displayPriceByVariation'] == '1' && $this->postClean('semvar',TRUE) != "on") {
            $this->form_validation->set_rules('PRICE_V[]', $this->lang->line('application_price'), 'trim|numeric');
        }
		 */

        $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'trim|required');
        $this->form_validation->set_rules('status', $this->lang->line('application_status'), 'trim|required');
        if ($require_ean) {
            $this->form_validation->set_rules('EAN', $this->lang->line('application_ean'), 'trim|required|callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|' . $product_id . ']');
        } else {
            $this->form_validation->set_rules('EAN', $this->lang->line('application_ean'), 'callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|' . $product_id . ']');
        }

        // $this->form_validation->set_rules('codigo_do_fabricante', $this->lang->line('application_brand_code'), 'trim');
        $this->form_validation->set_rules('prazo_operacional_extra', $this->lang->line('application_extra_operating_time'), 'trim|is_natural|less_than[100]');
        
        $this->form_validation->set_rules('peso_liquido', $this->lang->line('application_net_weight'), 'trim|required');
        $this->form_validation->set_rules('peso_bruto', $this->lang->line('application_weight'), 'trim|required');
        $this->form_validation->set_rules('largura', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('altura', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('profundidade', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('products_package', $this->lang->line('application_products_by_packaging'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('garantia', $this->lang->line('application_garanty'), 'trim|required');

        if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
            $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
            // usuário pode alterar a categoria
            if (!in_array('disabledCategoryPermission', $this->permission) && !$publishWithoutCategory) {
                $this->form_validation->set_rules('category[]', $this->lang->line('application_category'), 'trim|required');
            } elseif (!empty($this->postClean())) {
                // Sobrescreve a categoria enviada pelo o usuário, pela a categoria que está no banco.
                $_POST['category'] = array(json_decode($product_data['category_id'])[0]);
            }
        } else {
            if (!in_array('disabledCategoryPermission', $this->permission)) {
                $this->form_validation->set_rules('category[]', $this->lang->line('application_category'), 'trim|required');
            } elseif (!empty($this->postClean())) {
                // Sobrescreve a categoria enviada pelo o usuário, pela a categoria que está no banco.
                $_POST['category'] = array(json_decode($product_data['category_id'])[0]);
            }
        }

        $this->form_validation->set_rules('brands[]', $this->lang->line('application_brands'), 'trim|required');
        //        $this->form_validation->set_rules('CEST', $this->lang->line('application_cest'), 'trim|required');
        //        $this->form_validation->set_rules('FCI', $this->lang->line('application_fci'), 'trim|required');
        //       $this->form_validation->set_rules('NCM', $this->lang->line('application_NCM'), 'trim|required|exact_length[10]');
        $this->form_validation->set_rules('origin', $this->lang->line('application_origin_product'), 'trim|required|numeric');

        if ($this->postClean('semvar',TRUE) !== "on") {
            $req = $require_ean ? 'required|' : '';
            $this->form_validation->set_rules('EAN_V[]', $this->lang->line('application_ean'), 'trim|' . $req . 'callback_checkEan|callback_checkUniqueEan[' . $this->postClean('store',TRUE) . '|' . $product_id . ']');
        }

        $categoria_sel = $this->postClean('category',TRUE);

        //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );

        $allgood = false;
        $principal_image = '';
        $principal_image_product = null;
        $principal_image_variant = null;
        if ($this->form_validation->run() == TRUE) {
            $upload_image = $this->postClean('product_image',TRUE);
            if (!$upload_image) {
                // $this->session->set_flashdata('error', 'No images uploaded');
                $semFoto = TRUE;
            } else {
                $this->data['upload_image'] = $upload_image;
                $allgood = true;
                $semFoto = false;
            }

            $numft = 0;
            if (strpos(".." . $upload_image, "http") > 0) {
                $fotos = explode(",", $upload_image);
                $numft = count($fotos);
            } else {
                // Verifica se está no bucket ou não.
                // Posso fazer o envio aqui, mas para um processo de migração mais consistente, mantenho em disco até que tenha migrado todos.
                if (!$product_data['is_on_bucket']) {
                    if (!is_dir(FCPATH . 'assets/images/product_image/' . $upload_image)) {
                        @mkdir(FCPATH . 'assets/images/product_image/' . $upload_image);
                        // chmod(FCPATH . 'assets/images/product_image/' . $upload_image,775);
                    }
                    $fotos = scandir(FCPATH . 'assets/images/product_image/' . $upload_image);
                    foreach ($fotos as $foto) {
                        if (!is_dir(FCPATH . 'assets/images/product_image/' . $upload_image . '/' . $foto)) {
                            if (is_null($principal_image_product)) {
                                $principal_image_product = baseUrlPublic('assets/images/product_image/' . $upload_image) . '/' . $foto;
                            }
                            $numft++;
                        }
                    }

                    if (empty($this->postClean('semvar'))) {
                        foreach ($this->postClean('IMAGEM') ?? array() as $path_variation_image) {
                            $imagens = scandir(FCPATH . 'assets/images/product_image/' . $upload_image . '/' . $path_variation_image);
                            foreach ($imagens as $imagem) {
                                if (($imagem != ".") && ($imagem != "..") && ($imagem != "")) {
                                    $numft++;
                                    $principal_image_variant = baseUrlPublic('assets/images/product_image/' . $upload_image) . '/' . $path_variation_image . '/' . $imagem;
                                    break 2;
                                }
                            }
                        }
                    }
                } else {
                    // Caso seja do bucket, pega as fotos.
                    $fotos = $this->bucket->getFinalObject("assets/images/product_image/" . $upload_image);
                    
                    // Percorre cada imagem do contents. Não verifica por sucesso, caso seja vazio não acontecerá nada,
                    foreach ($fotos['contents'] as $foto) {
                        if (is_null($principal_image_product)) {
                            $principal_image_product = $foto['url'];
                        }
                        $numft++;
                    }

                    // Caso tenha variação, então percorre e adiciona as imagens.
                    if (empty($this->postClean('semvar'))) {
                        foreach ($this->postClean('IMAGEM') ?? array() as $path_variation_image) {
                            $imagens = $this->bucket->getFinalObject('assets/images/product_image/' . $upload_image . '/' . $path_variation_image);
                            foreach ($imagens['contents'] as $imagem) {
                                    $numft++;
                                    $principal_image_variant = $imagem['url'];
                                    break 2;
                            }
                        }
                    }
                }
            }

            if (!is_null($principal_image_product)) {
                $principal_image = $principal_image_product;
            } elseif (!is_null($principal_image_variant)) {
                $principal_image = $principal_image_variant;
            }

            if ($numft == 0) {
                //$this->session->set_flashdata('error', 'No images uploaded');
                //$allgood = false;
                $semFoto = TRUE;
            }
            $allgood = true;
        }


        if (!empty($this->postClean()) && $this->postClean('semvar') !== "on") {
            $arr_check_values_var = array();
            foreach ($this->postClean('SKU_V') as $key_check_var => $check_var) {
                $check_tam      = $this->postClean('T')[$key_check_var] ?? '';
                $check_cor      = $this->postClean('C')[$key_check_var] ?? '';
                $check_volt     = $this->postClean('V')[$key_check_var] ?? '';
                $check_sabor    = $this->postClean('sb')[$key_check_var] ?? '';
                $check_grau     = $this->postClean('gr')[$key_check_var] ?? '';
                $check_lado     = $this->postClean('ld')[$key_check_var] ?? '';

                $values_check = "$check_tam-$check_cor-$check_volt-$check_sabor-$check_grau-$check_lado";
                if (in_array($values_check, $arr_check_values_var)){
                    $this->session->set_flashdata('error', $this->lang->line('application_variation_value_equal'));
                    $allgood = false;
                }
                $arr_check_values_var[] = $values_check;
            }

        }

        // VALIDAÇÃO DE PREÇO PARA E PREÇO POR
        if($this->input->post()) {
            $preco_de = $this->postClean('list_price', TRUE);
            $preco_por = $this->postClean('price', TRUE);
            if (empty($preco_de) || empty($preco_por)) {
                $this->session->set_flashdata('error', $this->lang->line('application_prices_error'));
                $allgood = false;
            }
        }

        // verifico se o produto já tem integração com marketplaces 
        $integratedProducts = $this->model_integrations->getPrdIntegration($product_id);
        $integratedProduct = false;
        if ($integratedProducts) {            
            foreach ($integratedProducts as $intProduct) {
                if (!is_null($intProduct['skumkt'])) {
                    $integratedProduct = true;
                    break;
                }
            }
        }
        $semCamposVariacao = false;
        // Testo se tem atributos de marketplace obrigatórios como variação 
        if (!is_null($this->postClean('category',TRUE))) {
            $msgError = '';

            // Agora pego os campos do ML. ML é mais simples e só obriga Cor ou tamanho e não precisa que seja 
            $arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), 'ML');
            $campos_att = $arr[0];
            foreach ($campos_att as $campo_att) {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                    if ($campo_att['id_atributo'] == 'COLOR') {
                        if ($this->postClean('colorvar',TRUE) !== "on") {
                            $msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                        }
                    }
                    if ($campo_att['id_atributo'] == 'SIZE') {
                        if ($this->postClean('sizevar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_size_variant_mercado_livre') . '<br>';
                        }
                    }
                }
            }

            // Agora pego os campos da Via Varejo
            $arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), 'VIA');
            $campos_att = $arr[0];
            foreach ($campos_att as $campo_att) {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                    if ($campo_att['nome'] == 'Cor') {  // campo cor da Via Varejo
                        if ($this->postClean('colorvar',TRUE) === "on") {
                            $coreslidas = json_decode($campo_att['valor'], true);
                            $coresvalidas = array();
                            foreach ($coreslidas as $corlida) {
                                $coresvalidas[] = trim(ucfirst(strtolower($corlida['udaValue'])));
                            }
                            $cores = $this->postClean('C',TRUE);
                            foreach ($cores as $key => $cor) {
                                if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
                                    $msgError .= 'Cor "' . $cor . '" inválida na variação ' . $key . '. Cores válidas para Via Varejo são: ' . implode(",", $coresvalidas) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Tamanho') {
                        if ($this->postClean('sizevar',TRUE) === "on") {
                            $tamlidas = json_decode($campo_att['valor'], true);
                            $tamvalidos = array();
                            foreach ($tamlidas as $tamlida) {
                                $tamvalidos[] = trim($tamlida['udaValue']);
                            }
                            $tams = $this->postClean('T',TRUE);
                            foreach ($tams as $key => $tam) {
                                if (!in_array($tam, $tamvalidos)) {
                                    $msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $key . '. Tamanhos válidos para Via Varejo são: ' . implode(",", $tamvalidos) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Voltagem') {
                        if ($this->postClean('voltvar',TRUE) === "on") {
                            $volts = $this->postClean('V',TRUE);
                        }
                    }
                    if ($campo_att['nome'] == 'SABOR') {
                        if ($this->postClean('saborvar',TRUE) === "on") {
                            $sabores = $this->postClean('sb',TRUE);
                        }
                    }
                }
            }

            // Agora pego os campos da Novo Mundo
            $arr =  $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), 'NovoMundo');
            $campos_att = $arr[0];
            foreach ($campos_att as $campo_att) {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                    if ($campo_att['nome'] == 'Cor') {  // campo cor da Via Varejo
                        if ($this->postClean('colorvar',TRUE) !== "on") {
                            $msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                        } else {
                            $coreslidas = json_decode($campo_att['valor'], true);
                            $coresvalidas = array();
                            foreach ($coreslidas as $corlida) {
                                $coresvalidas[] = trim(ucfirst(strtolower($corlida['Value'])));
                            }
                            $cores = $this->postClean('C',TRUE);
                            foreach ($cores as $key => $cor) {
                                $i = $key + 1;
                                if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
                                    $msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para NovoMundo são: ' . implode(",", $coresvalidas) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Tamanho') {
                        if ($this->postClean('sizevar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_size_variant_novo_mundo') . '<br>';
                        } else {
                            $tamlidas = json_decode($campo_att['valor'], true);
                            $tamvalidos = array();
                            foreach ($tamlidas as $tamlida) {
                                $tamvalidos[] = trim($tamlida['Value']);
                            }
                            $tams = $this->postClean('T',TRUE);
                            foreach ($tams as $key => $tam) {
                                $i = $key + 1;
                                if (!in_array($tam, $tamvalidos)) {
                                    $msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para NovoMundo são: ' . implode(",", $tamvalidos) . '<br>';
                                }
                            }
                        }
                    }
                    if ($campo_att['nome'] == 'Voltagem') {
                        if ($this->postClean('voltvar',TRUE) !== "on") {
                            $msgError .= $this->lang->line('messages_error_voltage_variant_novo_mundo') . '<br>';
                        } else {
                            $volts = $this->postClean('V',TRUE);
                        }
                    }
                }
            }

            //aki
            foreach ($this->vtexsellercenters as $sellercenter) {
                $lcseller = strtolower($sellercenter);
                // Agora pego os campos da Novo Mundo, ortobom e Casa&Video
                $arr =  $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), $sellercenter);
                $campos_att = $arr[0];
                foreach ($campos_att as $campo_att) {
                    if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
                        if ((strtoupper($campo_att['nome']) == 'COR') || (strtoupper($campo_att['nome']) == 'CORES') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_color))) {
                            if ($this->postClean('colorvar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                                    $msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                                else if ($this->postClean('semvar', true) !== "on" && $this->hasDefaultValueVariation === false) {
                                    $msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                            } else {
                                $coreslidas = json_decode($campo_att['valor'], true);
                                $coresvalidas = array();
                                foreach ($coreslidas as $corlida) {
                                    $coresvalidas[] = trim(ucfirst(strtolower($corlida['Value'])));
                                }
                                $cores = $this->postClean('C',TRUE);
                                foreach ($cores as $key => $cor) {
                                    $i = $key + 1;
                                    if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
                                        $msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $coresvalidas) . '<br>';
                                    }
                                }
                            }
                        }
                        if ((strtoupper($campo_att['nome']) == 'TAMANHO') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_size))){
                            if ($this->postClean('sizevar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                                	$msgError .= sprintf($this->lang->line('messages_error_size_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                    
                                }
                                else if ($this->postClean('semvar', true) !== "on" && $this->hasDefaultValueVariation === false) {
                                	$msgError .= sprintf($this->lang->line('messages_error_size_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                   
                                }
                            } else {
                                $tamlidas = json_decode($campo_att['valor'], true);
                                $tamvalidos = array();
                                if (!empty($tamlidas)) {
                                    foreach ($tamlidas as $tamlida) {
                                        $tamvalidos[] = trim($tamlida['Value']);
                                    }
                                    $tams = $this->postClean('T', TRUE);
                                    foreach ($tams as $key => $tam) {
                                        $i = $key + 1;
                                        if (!in_array($tam, $tamvalidos)) {
                                            $msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $tamvalidos) . '<br>';
                                        }
                                    }
                                }
                            }
                        }
                        if ((strtoupper($campo_att['nome']) == 'VOLTAGEM') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_voltage))) {
                            if ($this->postClean('voltvar',TRUE) !== "on") {
                                if ($this->hasDefaultValueVariation === false) {
                                    
									$msgError .= sprintf($this->lang->line('messages_error_voltage_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                                else if ($this->postClean('semvar', true) !== "on" && $this->hasDefaultValueVariation === false) {
                                    
									$msgError .= sprintf($this->lang->line('messages_error_voltage_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
                                }
                            } else {
                                $volts = $this->postClean('V',TRUE);
                            }
                        }
                    }
                }
				if ($this->postClean('colorvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Cor','Cores', $this->variant_color));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Cor'.'<br>';
					}
				}
				if ($this->postClean('voltvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array( 'Voltagem', $this->variant_voltage));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Voltagem'.'<br>';
					}
				}
				if ($this->postClean('sizevar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Tamanho', $this->variant_size));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Tamanho'.'<br>';
					}
				}
				if ($this->postClean('saborvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('SABOR', $this->variant_flavor));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Sabor'.'<br>';
					}
				}
                if ($this->postClean('grauvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('GRAU', $this->variant_degree));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Sabor'.'<br>';
					}
				}
                if ($this->postClean('ladovar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('LADO', $this->variant_side));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Sabor'.'<br>';
					}
				}
            }
            if ($msgError !== '') {
                $this->session->set_flashdata('error', $msgError);
                if (!$integratedProduct) {
                    $allgood = false;   // se não foi integrado, não salvo
                } else {
                    $allgood = true;   // se não foi integrado, não salvo
                    $semCamposVariacao = true;  // se já foi integrado, deixo salvar a alteração mesmo que não vai enviar para o marketplace
                }
            }
        }

        if ($allgood && $this->postClean('sku',TRUE)) {
            $comvar = $this->postClean('semvar',TRUE);
            $has_var = "";
            if ($comvar != "on") {
            
                if ($this->postClean('sizevar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "TAMANHO" : ";TAMANHO";
                }
                if ($this->postClean('colorvar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "Cor" : ";Cor";
                }
                if ($this->postClean('voltvar',TRUE) == "on") {
                    $has_var .= ($has_var == "") ? "VOLTAGEM" : ";VOLTAGEM";
                }
                $this->data['flavor_active'] = $this->model_settings->getFlavorActive();
                if($this->data['flavor_active']){
                    if ($this->postClean('saborvar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "SABOR" : ";SABOR";
                    }
                }

                $this->data['degree_active'] = $this->model_settings->getDegreeActive();
                if($this->data['degree_active']){
                    if ($this->postClean('grauvar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "GRAU" : ";GRAU";
                    }
                }

                $this->data['side_active'] = $this->model_settings->getSideActive();
                if($this->data['side_active']){
                    if ($this->postClean('ladovar',TRUE) == "on") {
                        $has_var .= ($has_var == "") ? "LADO" : ";LADO";
                    }
                }
            }

            // vejo se o sku está disponível para uso
            if ($has_var != "") {
                $skuVar = $this->postClean('SKU_V',TRUE);
                for ($x = 0; $x <= $this->postClean('numvar',TRUE) - 1; $x++) {
                    if (!$this->model_products->checkSkuAvailable($skuVar[$x], $product_data['store_id'], $product_data['id'])) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_variant_sku_available') . $skuVar[$x]);
                        $allgood = false;
                        break;
                    }
                    if ($skuVar[$x] == $this->postClean('sku',TRUE)) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_variant_sku_equal_product_sku'));
                        $allgood = false;
                        break;
                    }
                }

                if($this->limite_variacoes == 1){

                    $variacoes = explode(';',$has_var);

                    if(count($variacoes) > 2){
                        $this->session->set_flashdata('error', $this->lang->line('api_variation_limit'));
                        $allgood = false;
                    }    

                }
            }
            if (!$this->model_products->checkSkuAvailable($this->postClean('sku',TRUE), $product_data['store_id'], $product_data['id'])) {
                $this->session->set_flashdata('error', $this->lang->line('messages_product_sku_available') . $this->postClean('sku',TRUE));
                $allgood = false;
            }

            $list_price = $this->postClean('list_price',TRUE);
            if($list_price == ''){
                $list_price = $this->postClean('price',TRUE);
            }
            if($this->postClean('price',TRUE) > $list_price){
                $this->session->set_flashdata('error', $this->lang->line('application_price_error'));
                $allgood = false;
            }
        }

        if ($allgood) {
            $semFabricante = (trim($this->postClean('brands',TRUE)[0]) == '');

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $publishWithoutCategory = $this->model_settings->getValueIfAtiveByName("publish_without_category");
                $semCategoria = (trim($this->postClean('category', TRUE)[0]) == '');
                // if (($semFoto) || ($semCategoria) || ($semFabricante) || ($semCamposVariacao)) { // voltar depois da black friday
                if (($semFoto) || ($semCategoria && !$publishWithoutCategory) || ($semFabricante)) {
                    $situacao = '1';
                } else {
                    $situacao = '2';
                }
            } else {
                $semCategoria = (trim($this->postClean('category', TRUE)[0]) == '');
                // if (($semFoto) || ($semCategoria) || ($semFabricante) || ($semCamposVariacao)) { // voltar depois da black friday
                if (($semFoto) || ($semCategoria) || ($semFabricante)) {
                    $situacao = '1';
                } else {
                    $situacao = '2';
                }
            }

            $prazo_operacional_extra = trim($this->postClean('prazo_operacional_extra',TRUE));
            if ($prazo_operacional_extra == '') {
                $prazo_operacional_extra  = 0;
            }

            $list_price = $this->postClean('list_price',TRUE);
            if($list_price == ''){
                $list_price = $this->postClean('price',TRUE);
            }


            $liberar = $this->postClean('libera',TRUE); //liberar prazo fixo


            $data_prod = array(
                'name' => $this->input->post('product_name'),
                'sku' => $this->postClean('sku',TRUE),
                'price' => $this->postClean('price',TRUE),
                'list_price' => $list_price,
                'qty' => $this->postClean('qty',TRUE),
                'description' => strip_tags_products($this->postClean('description',true, false, false), $this->allowable_tags), //$this->postClean('description'),
                'attribute_value_id' => json_encode($this->postClean('attributes_value_id',TRUE)),
                'brand_id' => json_encode($this->postClean('brands',TRUE)),
                'category_id' => json_encode($this->postClean('category',TRUE)),
                'store_id' => $this->postClean('store',TRUE),
                'status' => $this->postClean('status',TRUE),
                'EAN' => $this->postClean('EAN',TRUE),
                'codigo_do_fabricante' => $this->postClean('codigo_do_fabricante',TRUE),
                'peso_liquido' => $this->postClean('peso_liquido',TRUE),
                'peso_bruto' => $this->postClean('peso_bruto',TRUE),
                'largura' => $this->postClean('largura',TRUE),
                'altura' => $this->postClean('altura',TRUE),
                'profundidade' => $this->postClean('profundidade',TRUE),
                'products_package' => $this->postClean('products_package',TRUE),
                'actual_width' => $this->postClean('actual_width',TRUE),
                'actual_height' => $this->postClean('actual_height',TRUE),
                'actual_depth' => $this->postClean('actual_depth',TRUE),
                'garantia' => $this->postClean('garantia',TRUE),                
                'NCM' => preg_replace('/[^\d\+]/', '', $this->postClean('NCM',TRUE)),
                'origin' => $this->postClean('origin',TRUE),
                'principal_image' => $principal_image,
                'has_variants' => $has_var,
                'situacao' => $situacao,
                'prazo_operacional_extra' => $prazo_operacional_extra,
                'prazo_fixo' => $liberar,
                'date_update' => date('Y-m-d H:i:s'),  // Forço a data para que funcione o sincronismos de produtos novamente
            );

            if(empty($idCategory_id) && $data_prod['category_id'] != '[""]'){
                $data_prod['categorized_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            //(FR)
            $product_name = $this->postClean('product_name',TRUE);
            $skucod = $this->postClean('sku',TRUE);
            $libera = $this->postClean('libera',TRUE);
            $body = "O produto $product_name SKU ( $skucod ) teve o Prazo Operacional alterado para $prazo_operacional_extra dias ";

            if($libera == 'sim'){  //se alterou o prazo limite envio um email (FR)
                //$product_data['store_id'];
                $responsible_email = $this->model_stores->getStoresBymail($product_data['store_id']);
                $resp = $this->sendEmailMarketing($responsible_email, 'Prazo Operacional Alterado', $body);
            }
            //(FR)


            if ($integratedProduct) {
                unset($data_prod['has_variants']);
            }

            if (!empty($data_prod['qty']) && $data_prod['qty'] != $product_data['qty']) {
                $data_prod['stock_updated_at'] = date('Y-m-d H:i:s');
            }

            $update = $this->model_products->update($data_prod, $product_id);

            // bloqueia produto se necessário
            $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($data_prod, $product_id);

            $category_id = $this->postClean('category',TRUE)[0];
            $this->model_atributos_categorias_marketplaces->saveBrandName($product_id, $this->postClean('brands',TRUE)[0]);

            if ($update == true) {
                $log_var = array('id' => $product_id);
                $this->log_data('Products', 'edit_after', json_encode(array_merge($log_var, $data_prod)), "I");
                // gravo o stores_tipovolumes para avisar se mudou o tipo de volume e mudar no frete rápid (chines)

                if (!$semCategoria) {
                    $categoria = $this->model_category->getCategoryData($this->postClean('category',TRUE));
                    if (!is_null($categoria['tipo_volume_id'])) {
                        $datastorestiposvolumes = array(
                            'store_id' => $this->postClean('store',TRUE),
                            'tipos_volumes_id' => $categoria['tipo_volume_id'],
                            'status' => 1,
                        );
                        $this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
                    }
                }

                $oldVariants = $this->model_products->getVariants($product_id);
                $idErpVariants = array();
                foreach ($oldVariants as $oldVariant) {
                    $idErpVariants[$oldVariant['sku']] = array(
                        'variant_id_erp' => $oldVariant['variant_id_erp'],
                        'status'         => $oldVariant['status'],
                        'created_at'     => $oldVariant['created_at']
                    );
                }

                $this->log_data('Products', 'edit_before_variation', json_encode($oldVariants));
                //$this->model_products->deletevar($product_id);
                /*if (!$integratedProduct || $this->data['only_admin']) {
                    $this->model_products->deletevar($product_id);
                }
                else {
                    $has_var = $this->model_products->getProductData(0, $product_id)['has_variants'];
                }*/

                if ($has_var != "") {
                    $upload_image = $this->postClean('product_image',TRUE);

                    $qty = 0;
                    $tam = $this->postClean('T',TRUE);
                    $cor = $this->postClean('C',TRUE);
                    $volt = $this->postClean('V',TRUE);
                    $sabor = $this->postClean('sb',TRUE);
                    $grau = $this->postClean('gr',TRUE);
                    $lado = $this->postClean('ld',TRUE);
                    //$preco = $this->postClean('P',TRUE);
                    $qtd = $this->postClean('Q',TRUE);
                    $skuVar = $this->postClean('SKU_V',TRUE);
                    $eanVar = $this->postClean('EAN_V',TRUE);
                    $priceVar = $this->postClean('PRICE_V',TRUE);
                    $listPriceVar = $this->postClean('LIST_PRICE_V',TRUE);
                    $image_folder = $this->postClean('IMAGEM',TRUE);

                    $countVarEmpty = 1;

                    for ($x = 0; $x <= $this->postClean('numvar',TRUE) - 1; $x++) {

                        $variant = $x;

                        if(!is_null($listPriceVar[$x])){
                            if($listPriceVar[$x] < $priceVar[$x]){
                                $listPriceVar[$x] = $priceVar[$x];
                            }
                        }

                        $price_var      = (is_null($priceVar[$variant]) || trim($priceVar[$variant] == '')) ? $this->postClean('price',TRUE) : $priceVar[$variant];
                        $list_price_var = (is_null($listPriceVar[$variant]) || trim($listPriceVar[$variant] == '')) ? $this->postClean('price',TRUE) : $listPriceVar[$variant];
                        $qty_var        = (int)$qtd[$variant];

                        /*if ($integratedProduct) {
                            // só poderá alterar a qtd e sku
                            $updateVar = array(
                                'sku' => $skuVar[$x],
                                'price' => $price_var,
                                'list_price' => $list_price_var,
                                'ean' => $eanVar[$x],
                                'qty' => $qty_var
                            );
                            $this->model_products->updateVar($updateVar, $product_id, $x);
                            $qty += $qty_var;

                            $updateVar['variant'] = $x;
                            $log_var = array('id' => $product_id, "variation" => $updateVar);
                            $this->log_data('Products', 'edit_after_variation', json_encode($log_var), "I");
                            continue;
                        }*/

                        $variante = "";
                        if ($this->postClean('sizevar',TRUE) == "on") {
                            $variante .= ";" . $tam[$x];
                        }
                        if ($this->postClean('colorvar',TRUE) == "on") {
                            $variante .= ";" . $cor[$x];
                        }
                        if ($this->postClean('voltvar',TRUE) == "on") {
                            $variante .= ";" . $volt[$x];
                        }
                        if ($this->postClean('saborvar',TRUE) == "on") {
                            $variante .= ";" . $sabor[$x];
                        }
                        if ($this->postClean('grauvar',TRUE) == "on") {
                            $variante .= ";" . $grau[$x];
                        }
                        if ($this->postClean('ladovar',TRUE) == "on") {
                            $variante .= ";" . $lado[$x];
                        }
                        $variante = substr($variante, 1);

                        // Mantem os sku das variações ou cria novos que não existem
                        if (empty($skuVar[$x])) {
                            $skuVar[$x] = $this->postClean('sku',TRUE) . "-{$countVarEmpty}";

                            while ($this->model_products->getVariantsForSku($product_id, $skuVar[$x])) {
                                $skuVar[$x] = $this->postClean('sku',TRUE) . "-{$countVarEmpty}";
                                $countVarEmpty++;
                            }
                        }

                        $data_var = array(
                            'prd_id' => $product_id,
                            'variant' => $x,
                            'name' => $variante,
                            'sku' => $skuVar[$x],
                            'price' => $price_var,
                            'list_price' => $list_price_var,
                            //  'price' => $preco[$x],
                            // 'price' => $this->postClean('price',TRUE),
                            'qty' => $qtd[$x],
                            'image' => $image_folder[$x],
                            'status' => 1,
                            'EAN' => $eanVar[$x],
                            'codigo_do_fabricante' => '',
                        );

                        if (array_key_exists($skuVar[$x], $idErpVariants)) {
                            $data_var['variant_id_erp'] = $idErpVariants[$skuVar[$x]]['variant_id_erp'];
                            $data_var['created_at'] = $idErpVariants[$skuVar[$x]]['created_at'];
                            $data_var['status'] = $idErpVariants[$skuVar[$x]]['status'];
                        }

                        $qty += $qty_var;

                        //Identificando se a variação já está cadastrada antes de cadastrar uma nova
                        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){

                            $variant_inserted = $this->model_products->getDataPrdVariant($product_id, $x);

                            if ($variant_inserted){
                                $this->model_products->updateVariationData($variant_inserted['id'], $product_id, $data_var);
                            }else{
                                $this->model_products->createvar($data_var);
                            }

                        }else{
                            $this->model_products->createvar($data_var);
                        }

                        $this->model_log_products->create_log_products($data_var, $product_id, 'Alterado Variação ' . $x);
                        $this->log_data('Products', 'edit_after_variation', json_encode($data_var), "I");
                    }
                    $data_prod['qty'] = $qty;

                    if (!empty($data_prod['qty']) && $data_prod['qty'] != $product_data['qty']) {
                        $data_prod['stock_updated_at'] = date('Y-m-d H:i:s');
                    }

                    $this->log_data('Products', 'edit_after_qty_var', json_encode($data_prod), "I");
                    $update = $this->model_products->update($data_prod, $product_id);
                }
                // apago outros diretórios
                /* $otherFolders = scandir(FCPATH . 'assets/images/product_image/' . $upload_image);
                if (isset($image_folder)) {
                    foreach ($otherFolders as $otherFolder) {
                        if (is_dir(FCPATH . 'assets/images/product_image/' . $upload_image . '/' . $otherFolder) && ($otherFolder != '.') && ($otherFolder != '..')) {
                            if (!in_array($otherFolder, $image_folder)) {
                                // $this->deleteDir(FCPATH . 'assets/images/product_image/' . $upload_image.'/'.$otherFolder);
                            }
                        }
                    }
                }*/

                if ($Preco_Quantidade_Por_Marketplace == 1) {  // se o parametro estiver ligado, pego o resultado do sistema.
                    $products_marketplace = $this->model_products_marketplace->getAllDataByProduct($product_id);
                    foreach ($products_marketplace as $product_marketplace) {
                        if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
                            if (($this->postClean('samePrice_' . $product_marketplace['int_to'],TRUE) != 'on') && (!is_null($this->postClean('price_' . $product_marketplace['int_to'],TRUE)))) {
                                $data = array(
                                    'same_price' => false,
                                    'price' => $this->postClean('price_' . $product_marketplace['int_to'],TRUE),
                                );
                            } else {
                                $data = array(
                                    'same_price' => true,
                                    'price' => $this->postClean('price',TRUE),
                                );
                            }

                            // gravo log somente se alterou...
                            if (($data['same_price'] != $product_marketplace['same_price']) || ($data['price'] != $product_marketplace['price'])) {
                                $log = [
                                    'id'           => $product_marketplace['id'],
                                    'int_to'      => $product_marketplace['int_to'],
                                    'product_id' => $product_id,
                                    'old_same_price'    => $product_marketplace['same_price'],
                                    'same_price'    => $data['same_price'],
                                    'old_price'    => $product_marketplace['price'],
                                    'new_price'    => $data['price']
                                ];
                                $this->log_data('ProductsMarketPlace', 'Update_PriceX', json_encode($log), 'I');
                                $this->model_log_products->create_log_products(array('price' => $data['price']), $product_id, 'Preço Marketplace ' . $product_marketplace['int_to']);
                            }
                            $this->model_products_marketplace->updateAllVariants($data, $product_marketplace['int_to'], $product_marketplace['prd_id']);
                        }
                        if ($product_marketplace['hub']) {
                            if ($product_marketplace['variant'] == '') {
                                if ($this->postClean('sameQty_' . $product_marketplace['int_to'],TRUE) != 'on') {
                                    $data = array(
                                        'same_qty' => false,
                                        'qty' => $this->postClean('qty_' . $product_marketplace['int_to'],TRUE),
                                    );
                                } else {
                                    $data = array(
                                        'same_qty' => true,
                                        'qty' => $this->postClean('qty',TRUE),
                                    );
                                }

                                // gravo log e update somente se alterou...
                                if (($data['same_qty'] != $product_marketplace['same_qty']) || ($data['qty'] != $product_marketplace['qty'])) {
                                    $log = [
                                        'id'           => $product_marketplace['id'],
                                        'int_to'      => $product_marketplace['int_to'],
                                        'product_id' => $product_id,
                                        'old_same_qty'    => $product_marketplace['same_qty'],
                                        'same_qty'    => $data['same_qty'],
                                        'old_qty'    => $product_marketplace['qty'],
                                        'new_qty'    => $data['qty']
                                    ];
                                    $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
                                    $this->model_log_products->create_log_products(array('qty' => $data['qty']), $product_id, 'Estoque Marketplace ' . $product_marketplace['int_to']);
                                    $this->model_products_marketplace->update($data, $product_marketplace['id']);
                                }
                            } else {  /*  falta colocar na tela as qtd por variação 
								$qtd = $this->postClean('Q',TRUE);
								if ($this->postClean('sameQty_'.$product_marketplace['id'],TRUE) == 'on') {
									$data = array(
										'same_qty' => true,
										'qty' => $qtd[$product_marketplace['variant']],
									);
								}else{
									$data = array(
										'same_qty' => false,
										'qty' => $this->postClean('qty_'.$product_marketplace['id'],TRUE),
									);
								}
								// gravo log e update somente se alterou...
								if (($data['same_qty'] != $product_marketplace['same_qty']) || ($data['qty'] != $product_marketplace['qty'])) {
									$log = [
							            'id' 	 	 => $product_marketplace['id'],
							            'int_to' 	 => $product_marketplace['int_to'],
							            'product_id' => $product_id,
							            'variant'    => $product_marketplace['variant'],
							            'old_qty'    => $product_marketplace['same_qty'],
							            'new_qty'    => $data['qty']
							        ];
							        $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
									$this->model_products_marketplace->update($data,$product_marketplace['id']);	
								} */
                            }
                        }
                    }
                }
                $product_saved=$this->model_products->getProductData(0, $product_id);
                $this->checkImageProduct($product_saved);
                $this->model_products->update(array('date_update' => date("Y-m-d H:i:s")), $product_id);
                if (!$semCamposVariacao) {
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                    redirect("products/attributes/edit/$product_id/$category_id", 'refresh');
                } else {
                    // Salvou o produto mas tem o erro da categoria que exige variação, então fico parado aqui mesmo
                    $this->session->set_flashdata('error', $this->lang->line('messages_save_with_errors') . '<br>' . $msgError);
                    redirect("products/attributes/edit/$product_id/$category_id", 'refresh');
                }

            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('products/update/' . $product_id, 'refresh');
            }
        } else {
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');

            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;

                $value = $this->model_attributes->getAttributeValueData($v['id']);

                $attributes_final_data[$k]['attribute_value'] = $value;
            }

            // false case
            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();

            // Pega os atributos da Mercado Livre
            $arr =  $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']), 'ML');
            $campos_att = $arr[0];
            $ignoreML = array('BRAND', 'EAN', 'GTIN', 'SELLER_SKU', 'EXCLUSIVE_CHANNEL', 'ITEM_CONDITION');
            if ($product_data['has_variants'] != "") {
                $tipos_variacao = explode(";", strtoupper($product_data['has_variants']));
            }
            $fieldsML = [];
            foreach ($campos_att as $campo_att) {
                if ($product_data['has_variants'] != "") {
                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                        $ignoreML[] = $campo_att['id_atributo'];
                    }
                }
                in_array($campo_att['id_atributo'], $ignoreML) ? '' : array_push($fieldsML, $campo_att);
            }

            // Agora pego os campos da Via Varejo
            $arr =  $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']), 'VIA');
            $campos_att = $arr[0];
            $ignoreVia = array('SELECIONE', 'GARANTIA');
            $fieldsVia = [];
            foreach ($campos_att as $campo_att) {
                if ($product_data['has_variants'] != "") {
                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                        $ignoreVia[] = strtoupper($campo_att['nome']);
                    }
                }

                in_array(strtoupper($campo_att['nome']), $ignoreVia) ? '' : array_push($fieldsVia, $campo_att);
            }

            // Agora pego os campos da NovoMundo
            $naoachou = empty($fieldsML) && empty($fieldsVia);
            foreach ($this->vtexsellercenters as $sellercenter) {
                // Agora pego os campos da NovoMundo
                $arr  = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']), $sellercenter);
                $campos_att = $arr[0];
                $sellercenter = str_replace('&', '', $sellercenter);
                ${'ignore' . $sellercenter} = array();
                ${'fields' . $sellercenter} = array();
                foreach ($campos_att as $campo_att) {
                    if ($product_data['has_variants'] != "") {
                        if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                            ${'ignore' . $sellercenter}[] = strtoupper($campo_att['nome']);
                        }
                    }

                    in_array(strtoupper($campo_att['nome']), ${'ignore' . $sellercenter}) ? '' : array_push(${'fields' . $sellercenter}, $campo_att);
                }
                $naoachou = $naoachou && empty(${'fields' . $sellercenter});
            }

            $this->data['show_attributes_button'] = $naoachou;

            $product_data = $this->model_products->getProductData(0, $product_id);
            
            if ($this->postClean('product_name',TRUE)) {$product_data['name'] = $this->postClean('product_name',TRUE);}
            if ($this->postClean('sku',TRUE)) {$product_data['sku'] = $this->postClean('sku',TRUE);}
            if ($this->postClean('status',TRUE)) {$product_data['status'] = $this->postClean('status',TRUE);}

            if ($this->postClean('description',TRUE, false, false)) {
                $product_data['description'] = strip_tags_products($this->postClean('description', true, false, false), $this->allowable_tags); // $this->postClean('description');
            } else {
                $product_data['description'] = strip_tags_products($product_data['description'], $this->allowable_tags);
            }

            if ($this->postClean('price',TRUE)) $product_data['price'] = $this->postClean('price',TRUE);
            if ($this->postClean('qty',TRUE)) $product_data['qty'] = $this->postClean('qty',TRUE);
            if ($this->postClean('EAN',TRUE)) $product_data['EAN'] = $this->postClean('EAN',TRUE);
            if ($this->postClean('codigo_do_fabricante',TRUE)) $product_data['codigo_do_fabricante'] = $this->postClean('codigo_do_fabricante',TRUE);
            if ($this->postClean('peso_liquido',TRUE)) $product_data['peso_liquido'] = $this->postClean('peso_liquido',TRUE);
            if ($this->postClean('peso_bruto',TRUE)) $product_data['peso_bruto'] = $this->postClean('peso_bruto',TRUE);
            if ($this->postClean('largura',TRUE)) $product_data['largura'] = $this->postClean('largura',TRUE);
            if ($this->postClean('altura',TRUE)) $product_data['altura'] = $this->postClean('altura',TRUE);
            if ($this->postClean('profundidade',TRUE)) $product_data['profundidade'] = $this->postClean('profundidade',TRUE);
            if ($this->postClean('garantia',TRUE)) $product_data['garantia'] = $this->postClean('garantia',TRUE);
            //            if ($this->postClean('CEST')) $product_data['CEST'] = $this->postClean('CEST');
            //            if ($this->postClean('FCI')) $product_data['FCI'] = $this->postClean('FCI');
            if ($this->postClean('NCM',TRUE)) $product_data['NCM'] = $this->postClean('NCM',TRUE);
            if ($this->postClean('origin',TRUE)) $product_data['origin'] = $this->postClean('origin',TRUE);
            if ($this->postClean('attributes_value_id',TRUE)) $product_data['attributes_value_id'] = $this->postClean('attributes_value_id',TRUE);
            if ($this->postClean('brands',TRUE)) $product_data['brand_id'] = json_encode($this->postClean('brands',TRUE));
            if ($this->postClean('store',TRUE)) $product_data['store_id'] = $this->postClean('store',TRUE);
            if ($this->postClean('category',TRUE)) $product_data['category_id'] = json_encode($this->postClean('category',TRUE));
            if (!$this->postClean('NCM',TRUE)) $product_data['NCM'] = preg_replace("/^(\d{4})(\d{2})(\d{2})$/", "$1.$2.$3", $product_data['NCM'],TRUE);

            $product_data['with_variation'] = $this->postClean('with_variation', TRUE);

            $product_data['bestprice'] = $this->model_integrations->getPrdBestPrice($product_data['EAN']);

            $better_price_by_ean = $this->model_products->getBetterPriceByEan($product_data['EAN'], $product_data['price']);
            if ($better_price_by_ean) {
                // fazer cálculo
                $original_price = (float)$product_data['price'];
                $product_data['competitiveness'] = $better_price_by_ean < $original_price ? ((($original_price - $better_price_by_ean) / $better_price_by_ean) * 100) : false;
            } else {
                $product_data['competitiveness'] = false;
            }

            // so posso alterar para as lojas da mesma empresa 
            $this->data['stores'] = $this->model_stores->getMyCompanyStores($product_data['company_id']);

            if ($product_data['image'] == '') { // se o produto perdeu a imagem, cria um diretorio novo para ele 
                $serverpath = $_SERVER['SCRIPT_FILENAME'];
                $pos = strpos($serverpath, 'assets');
                $serverpath = substr($serverpath, 0, $pos);
                $targetDir = $serverpath . 'assets/images/product_image/';
                $dirImage = get_instance()->getGUID(false); // gero um novo diretorio para as imagens 
                $targetDir .= $dirImage;
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir);
                    //chmod($targetDir,775);
                }
                $product_data['image'] = $dirImage;
            }

            $product_data['rule'] = $this->model_blacklist_words->getAllProductLocks($product_data['id']);

            $this->data['product_data'] = $this->security->xss_clean($product_data);
            $this->log_data('Products', 'edit_before', json_encode($product_data), "I");  // LOg DATA
            if ($product_data['has_variants'] != "") {
                $this->data['variants'] = $this->model_products->getVariants($product_data['id']);
                $product_variants = $this->model_products->getProductVariants($product_id, $product_data['has_variants']);
            } else {
                $product_variants['numvars'] = 0;
            }
            $this->data['product_variants'] = $product_variants;

            //  $integrations = $this->model_integrations->getPrdIntegration($product_id,$product_data['company_id'],1);
            $integrations = $this->model_integrations->getPrdIntegration($product_id);
            $canUpdateProduct = true;
            $published_variations = [];

            $error_transformation_product = current(
                array_filter(
                    $this->model_errors_transformation->getErrorsByProductId($product_id, null),
                    function($error){
                        return is_null($error['variant']);
                    }
                )
            );
            if ($integrations) {
                $integracoes = array();
                $i = 0;
                foreach ($integrations as $v) {
                    $error_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id, $v['int_to'], $v['variant'] === '' ? null : $v['variant']);
                    $integracoes[$i]['int_to'] = $v['int_to'];
                    $integracoes[$i]['skubling'] = $v['skubling'];
                    $integracoes[$i]['skumkt'] = $v['skumkt'];
                    $published_variations[$v['variant']] = !empty($v['skumkt']);

                    // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
                    if ($canUpdateProduct && $integracoes[$i]['skumkt']) {
                        $canUpdateProduct = false;
                    }

                    if ($v['rule']) {
                        $ruleBlock = array();
                        $ruleId = is_numeric($v['rule']) ? (array)$v['rule'] : json_decode($v['rule']);
                        foreach ($ruleId as $ruleBlockId) {
                            $rule = $this->model_blacklist_words->getWordById($ruleBlockId);
                            if ($rule)
                                array_push($ruleBlock, '<span class="label label-danger">' . strtoupper($rule['sentence']) . '</span>');
                        }
                        $integracoes[$i]['status_int'] = implode('<br>', $ruleBlock);
                    } elseif (!empty($error_transformation) || !empty($error_transformation_product)) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_errors_tranformation'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 0) {
                        $integracoes[$i]['status_int'] = '<span class="label label-warning">' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 1) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 2) {
                        $integracoes[$i]['status_int'] = '<span class="label label-primary">' . mb_strtoupper($this->lang->line('application_product_sent'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 11) {
                        $over = $this->model_integrations->getPrdBestPrice($product_data['EAN']);
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . ' (' . $over . ')</span>';
                    } elseif ($v['status_int'] == 12) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 13) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 14) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_product_release'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 20) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 21) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 22) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 23) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 24) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 99) {
                        $integracoes[$i]['status_int'] = '<span class="label label-warning">' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 90) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">' . mb_strtoupper($this->lang->line('application_product_inactive'), 'UTF-8') . '</span>';
                    } elseif ($v['status_int'] == 91) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">' . mb_strtoupper($this->lang->line('application_no_logistics'), 'UTF-8') . '</span>';
                    } else {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">' . mb_strtoupper($this->lang->line('application_product_out_of_stock'), 'UTF-8') . '</span>';
                    }
                    $integracoes[$i]['ad_link'] = $v['ad_link'];
                    $integracoes[$i]['name'] = $v['name'];
                    $integracoes[$i]['quality'] = $v['quality'];
                    $integracoes[$i]['approved'] = $v['approved'];
                    $integracoes[$i]['auto_approve'] = $v['auto_approve'];
                    $integracoes[$i]['status'] = $v['status'];
                    $integracoes[$i]['date_last_int'] = $v['date_last_int'];
                    $integracoes[$i]['id'] = $v['id'];
                    $i++;
                }
                // not administrator
                // $this->data['notAdmin'] = ($this->data['usercomp'] == '1' && $this->data['usergroup'] == '1' ? false : true);
                $this->data['notAdmin'] = ($this->data['usercomp'] == '1' ? false : true);
                $this->data['integracoes'] = $integracoes;
            }

            $this->data['storeCanUpdateProduct'] = $canUpdateProduct;
            $this->data['published_variations'] = $published_variations;

            // leio o valor inicial dos atributos do produto
            $produtos_atributos = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($product_id);
            foreach ($produtos_atributos as $produto_atributo) {
                $this->data['product_atributos_marketplace'][$produto_atributo['id_atributo']] = $produto_atributo['valor'];
            }

            //pego a promoção do produto se tiver
            $promotion = $this->model_promotions->getPromotionByProductId($product_id);
            if ($promotion) {
                $this->data['promotion'] = $promotion;
            }
            $campaigns = $this->model_campaigns->getCampaignByProductId($product_id);
            $this->data['campaigns'] = $campaigns;

            $errors_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id);
            $this->data['errors_transformation'] = $errors_transformation;

            if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")) {
                $locksProduct = $this->model_sku_locks->get(["prd_id" => $product_id]);
                $this->data['sku_locks'] = $locksProduct;
            }
            
            $comvar = $this->postClean('semvar',TRUE);
            $variacaotamanho = array();
            $variacaocor = array();
            $variacaovoltagem = array();
            $variacaosabor = array();
            $variacaograu = array();
            $variacaolado = array();
            $variacaoquantidade = array();
            $variacaosku = array();
            $variacaoean = array();
            $variacaoprice = array();
            $variacaolistprice = array();
            $variacaoimagem = array();
            if ($comvar != "on") {
                if ($this->postClean('sizevar',TRUE) == "on") {
                    $variacaotamanho =  $this->postClean('T',TRUE);
                }
                if ($this->postClean('colorvar',TRUE) == "on") {
                    $variacaocor =  $this->postClean('C',TRUE);
                }
                if ($this->postClean('voltvar',TRUE) == "on") {
                    $variacaovoltagem =  $this->postClean('V',TRUE);
                }
                if ($this->postClean('saborvar',TRUE) == "on") {
                    $variacaosabor =  $this->postClean('sb',TRUE);
                }
                if ($this->postClean('grauvar',TRUE) == "on") {
                    $variacaograu =  $this->postClean('gr',TRUE);
                }
                if ($this->postClean('ladovar',TRUE) == "on") {
                    $variacaolado =  $this->postClean('ld',TRUE);
                }
                $variacaoquantidade = $this->postClean('Q',TRUE);
                $variacaosku = $this->postClean('SKU_V',TRUE);
                $variacaoean = $this->postClean('EAN_V',TRUE);
                $variacaoprice = $this->postClean('PRICE_V',TRUE);
                $variacaolistprice = $this->postClean('LIST_PRICE_V',TRUE);
                $variacaoimagem = $this->postClean('IMAGEM',TRUE);
            }
            $this->data['variacaotamanho'] = $variacaotamanho;
            $this->data['variacaocor'] = $variacaocor;
            $this->data['variacaovoltagem'] = $variacaovoltagem;
            $this->data['variacaosabor'] = $variacaosabor;
            $this->data['variacaograu'] = $variacaograu;
            $this->data['variacaolado'] = $variacaolado;
            $this->data['variacaoquantidade'] = $variacaoquantidade;
            $this->data['variacaosku'] = $variacaosku;
            $this->data['variacaoean'] = $variacaoean;
            $this->data['variacaoprice'] = $variacaoprice;
            $this->data['variacaolistprice'] = $variacaolistprice;
            $this->data['variacaoimagem'] = $variacaoimagem;
            $this->data['mykits'] = $this->model_products->getProductsKitFromProductItem($product_id);
            $this->data['myorders'] = $this->model_orders->getOrdersByProductItem($product_id);
            $this->data['require_ean'] = $require_ean;
            $this->data['invalid_ean'] = $this->lang->line('application_ean') . ' inválido';

            if ($Preco_Quantidade_Por_Marketplace == 1) {
                $this->data['products_marketplace'] = $this->model_products_marketplace->getAllDataByProduct($product_id);
            } else {
                $this->data['products_marketplace'] = array();
            }
            
            $this->data['product_data']['name'] = $product_data['name'];
            $this->data['product_data']['description'] = $product_data['description'];
            $this->data['flavor_active'] = $this->model_settings->getFlavorActive();
            if(empty($this->data['flavor_active'] ?? '')){
                $this->data['flavor_active'] = null;
            }

            $this->data['degree_active'] = $this->model_settings->getDegreeActive();
            if(empty($this->data['degree_active'] ?? '')){
                $this->data['degree_active'] = null;
            }

            $this->data['side_active'] = $this->model_settings->getSideActive();
            if(empty($this->data['side_active'] ?? '')){
                $this->data['side_active'] = null;
            }

            $this->render_template($this->editProductView, $this->data);
        }
    }

    /*
     * It removes the data from the database
     * and it returns the response into the json format
     */
    public function remove()
    {
        if (!in_array('deleteProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $product_id = $this->postClean('product_id',TRUE);

        $response = array();
        if ($product_id) {
            $delete = $this->model_products->remove($product_id);
            if ($delete == true) {
                get_instance()->log_data('Products', 'remove', $product_id, "I");

                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_remove_product');
            }
        } else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page_again');
        }

        echo json_encode($response);
    }

    public function load()
    {
        if (!in_array('createProduct', $this->permission) && in_array('disablePrice', $this->permission))
            {redirect('products/loadCatalog', 'refresh');}
        if (!in_array('createProduct', $this->permission) && !in_array('updateProduct', $this->permission))
            {redirect('dashboard', 'refresh');}
        if (!in_array('createProduct', $this->permission) && !in_array('updateProduct', $this->permission))
            {redirect('dashboard', 'refresh');}
        redirect('productsLoadByCSV', 'refresh');

        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter)
            $sellerCenter = $settingSellerCenter['value'];

        $this->data['page_title'] = $this->lang->line('application_upload_products');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!ini_get("auto_detect_line_endings")) // arquivo lido em um computador Macintosh
                ini_set("auto_detect_line_endings", '1');

            if (!is_null($this->postClean("import"))) { // selecionou o arquivo e será validado

                //                if (!$this->postClean('validate_file') && $_FILES['product_upload']['type'] != "application/vnd.ms-excel") {
                //                if (!$this->postClean('validate_file')) {
                //                    $this->session->set_flashdata('error', 'Formato de arquivo inválido, adicione um arquivo CSV.');
                //                    redirect('products/load', 'refresh');
                //                }

                if (!$this->postClean('validate_file',TRUE)) {
                    $dirPathTemp = "assets/files/product_upload/";
                    if (!is_dir($dirPathTemp)) mkdir($dirPathTemp);
                    $upload_file = $this->upload_file();
                } else
                    $upload_file = $this->postClean('validate_file',TRUE);

                if (!$upload_file) {
                    $this->session->set_flashdata('error', $this->data['upload_msg']);
                    redirect('products/load', 'refresh');
                }


                $csv = Reader::createFromPath($upload_file); // lê o arquivo csv
                $csv->setDelimiter(';'); // separados de colunas
                $csv->setHeaderOffset(0); // linha do header

                $stmt   = new Statement();
                $dados  = $stmt->process($csv);

                $this->load->library('UploadProducts'); // carrega lib de upload de imagens
                $arrRetorno         = array();
                $arrRetornoSku      = array();
                $newFileWithError   = array();
                $arrRetornoImage    = array();
                $qtdErros           = 0;
                $arrChaves          = array(
                    'ID da Loja'            => array('field' => 'lojaProduto', 'ignored' => false),
                    'Sku do Parceiro'       => array('field' => 'skuProduto', 'ignored' => false),
                    'Nome do Item'          => array('field' => 'nomeProduto', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Preco de Venda'        => array('field' => 'precoProduto', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Preco de Lista'        => array('field' => 'precoLista', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Quantidade em estoque' => array('field' => 'estoque', 'ignored' => false),
                    'Fabricante'            => array('field' => 'fabricante', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'SKU no fabricante'     => array('field' => 'skuFabricante', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Categoria'             => array('field' => 'categoria', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'EAN'                   => array('field' => 'ean', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Peso Liquido em kgs'   => array('field' => 'pesoLiquido', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Peso Bruto em kgs'     => array('field' => 'pesoBruto', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Largura em cm'         => array('field' => 'largura', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Altura em cm'          => array('field' => 'altura', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Profundidade em cm'    => array('field' => 'profundidade', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'NCM'                   => array('field' => 'ncm', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Origem do Produto _ Nacional ou Estrangeiro' => array('field' => 'origemProduto', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Garantia em meses'         => array('field' => 'garantia', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Prazo Operacional em dias' => array('field' => 'prazoOperacional', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Produtos por embalagem' => array('field' => 'itemPerPackage', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Descricao do Item _ Informacoes do Produto' => array('field' => 'descricaoProduto', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Imagens'                   => array('field' => 'imagens', 'ignored' => $sellerCenter == 'somaplace' ? true : false),
                    'Status(1=Ativo|2=Inativo|3=Lixeira)' => array('field' => 'status', 'ignored' => $sellerCenter == 'somaplace' ? true : false)
                );
                $canCreate = $sellerCenter == 'conectala';

                $origens = array(); // Monto o array de origens de produtos
                for ($i = 0; $i <= 8; $i++)
                    $origens[$i] = array('codigo' => (string)$i, 'nome' => $this->lang->line("application_origin_product_" . $i));

                // 0 = validacao
                // 1 = inclusão, ignorando os produtos com erro
                // 2 = inclusão, ignorando os produtos com erro e mostrar botão para baixar com error
                // 3 = baixar nova planilha de produtos com erro
                $tipoImportacao = $this->postClean('typeImport',TRUE);

                // percorre chaves para ver se está faltando algum campo
                $colunaFaltante = false;
                $colunasFaltantes = array();
                if ($dados->count() > 0) {
                    $verificaDado = $dados->fetchOne();
                    foreach (array_keys($arrChaves) as $chave) {
                        if (!isset($verificaDado[$chave])) {
                            array_push($colunasFaltantes, $chave);
                            $colunaFaltante = true;
                        }
                    }
                    $colunasFaltantes = implode(', ', $colunasFaltantes);
                }

                foreach ($dados as $linha => $dado) {
                    $linha++; // sempre pular o header, então adicionar 1(uma) linha
                    $arrRetorno[$linha] = array();
                    $arrProdutoExistente[$linha] = false;
                    $arrVariacao[$linha] = false;
                    $arrProdutoIntegrado[$linha] = false;
                    $novoProduto = true;
                    $variacao = false;
                    $produtoIntegrado = false;
                    $arrVerificaImages = array();

                    foreach ($arrChaves as $field => $fieldIgnored){
                        ${$fieldIgnored['field'] . '_ignored'} = $fieldIgnored['ignored'];
                    }
                    $empresaProduto     = null;
                    $lojaProduto        = !isset($dado['ID da Loja']) ? null : filter_var($this->detectUTF8(trim($dado['ID da Loja'])), FILTER_SANITIZE_STRING);
                    $skuProduto         = !isset($dado['Sku do Parceiro']) ? null : filter_var($this->detectUTF8(trim($dado['Sku do Parceiro'])), FILTER_SANITIZE_STRING); // OBRIGATÓRIO
                    $nomeProduto        = !isset($dado['Nome do Item']) ? null : filter_var($this->detectUTF8(trim($dado['Nome do Item'])), FILTER_SANITIZE_STRING);
                    $precoProduto       = !isset($dado['Preco de Venda']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Preco de Venda'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $precoLista         = !isset($dado['Preco de Lista']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Preco de Lista'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $estoque            = !isset($dado['Quantidade em estoque']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Quantidade em estoque'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $fabricante         = !isset($dado['Fabricante']) ? null : filter_var($this->detectUTF8(trim($dado['Fabricante'])), FILTER_SANITIZE_STRING);
                    $skuFabricante      = !isset($dado['SKU no fabricante']) ? null : filter_var($this->detectUTF8(trim($dado['SKU no fabricante'])), FILTER_SANITIZE_STRING);
                    $categoria          = !isset($dado['Categoria']) ? null : filter_var($this->detectUTF8(trim($dado['Categoria'])), FILTER_SANITIZE_STRING);
                    $ean                = !isset($dado['EAN']) ? null : filter_var(trim($dado['EAN']), FILTER_SANITIZE_STRING);
                    $pesoLiquido        = !isset($dado['Peso Liquido em kgs']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Peso Liquido em kgs'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $pesoBruto          = !isset($dado['Peso Bruto em kgs']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Peso Bruto em kgs'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $largura            = !isset($dado['Largura em cm']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Largura em cm'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $altura             = !isset($dado['Altura em cm']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Altura em cm'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $profundidade       = !isset($dado['Profundidade em cm']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Profundidade em cm'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $ncm                = !isset($dado['NCM']) ? null : filter_var(preg_replace('/[^\d\+]/', '', trim($dado['NCM'])), FILTER_SANITIZE_NUMBER_INT);
                    $origemProduto      = !isset($dado['Origem do Produto _ Nacional ou Estrangeiro']) ? null : filter_var(trim($dado['Origem do Produto _ Nacional ou Estrangeiro']), FILTER_SANITIZE_STRING);
                    $garantia           = !isset($dado['Garantia em meses']) ? null : filter_var(trim($dado['Garantia em meses']), FILTER_SANITIZE_NUMBER_INT);
                    $prazoOperacional   = !isset($dado['Prazo Operacional em dias']) ? null : filter_var(trim($dado['Prazo Operacional em dias']), FILTER_SANITIZE_NUMBER_INT);
                    $items_per_package  = !isset($dado['Produtos por embalagem']) ? null : filter_var(trim($dado['Produtos por embalagem']), FILTER_SANITIZE_NUMBER_INT);
                    $descricaoProduto   = !isset($dado['Descricao do Item _ Informacoes do Produto']) ? null : $this->detectUTF8(strip_tags_products(trim($dado['Descricao do Item _ Informacoes do Produto']), $this->allowable_tags));
                    $imagens            = !isset($dado['Imagens']) ? null : filter_var(trim($dado['Imagens']), FILTER_SANITIZE_STRING);
                    $status             = !isset($dado['Status(1=Ativo|2=Inativo|3=Lixeira)']) ? null : filter_var(trim($dado['Status(1=Ativo|2=Inativo|3=Lixeira)']), FILTER_SANITIZE_NUMBER_INT);
                    $unidade            = 'UN';
                    $imagemPrimaria     = '';
                    $pastaImagem        = '';

                    $arrRetornoSku[$linha] = $skuProduto;

                    // se todas as colunas da linhas estiverem em branco, vou ignorar
                    $linhaEmBranco = true;
                    foreach ($dado as $line){
                        if (trim($line) != '') {$linhaEmBranco = false;}
                    }
                    if ($linhaEmBranco) {
                        unset($arrRetorno[$linha]);
                        continue; // ignoro linha em branco
                    }

                    $lojas = $this->model_stores->getStoresForExport();
                    // informou um código ou nome para a loja
                    if ($lojaProduto && !$lojaProduto_ignored) {
                        foreach ($lojas as $loja) { // percorre as lojas para encontrar a loja informada
                            // caso for a loja, recuperar o código da loja e da empresa
                            if ($lojaProduto == $loja['codigo'] || strtoupper($lojaProduto) == strtoupper($loja['nome'])) {
                                $lojaProduto    = $loja['codigo'];
                                $empresaProduto = $loja['company_id'];
                                break;
                            }
                        }
                    }

                    // Informou uma loja, mas não foi encontrada
                    if ($lojaProduto && !$empresaProduto && !$lojaProduto_ignored) {array_push($arrRetorno[$linha], 'A loja informada não foi encontrada.');}

                    // empresa ainda não definida, define como a do usuário autenticado
                    if (!$empresaProduto) {$empresaProduto = $this->session->userdata('usercomp');}

                    // ainda não definido o código da loja
                    if (!$lojaProduto && !$lojaProduto_ignored) {
                        // se usuário gerencia apenas um única loja, defino a loja do usuário
                        if ($this->session->userdata('userstore') != 0) {$lojaProduto = (int)$this->session->userdata('userstore');}
                        else {
                            // Esse usuário gerencia mais de uma loja
                            // deverá informar para qual lojar pretente
                            // fazer a importação
                            $lojas = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
                            if (count($lojas) > 1) {array_push($arrRetorno[$linha], 'Não foi informado para qual loja pretende fazer a importação.');}
                            // Se não tem linha da loja então pega a primeira loja
                            $lojaProduto = (int)$lojas[0]['id'];
                        }
                    }

                    // não foi informado uma loja e não encontrou uma, também não encontrou empresa
                    if ((!$lojaProduto || !$empresaProduto) && !$lojaProduto_ignored) {
                        array_push($arrRetorno[$linha], 'Não foi possível localizar a loja ou empresa.');
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    // loja recuperado não é um código numérico
                    if (!is_numeric($lojaProduto) && !$lojaProduto_ignored) {
                        array_push($arrRetorno[$linha], 'Não foi possível localizar a loja.');
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    // usuário tem permissão de gerenciar essa loja
                    if (in_array($empresaProduto, $this->data['filters'])) {
                        // loja não pertence da empresa
                        if (!$this->model_stores->CheckStores($empresaProduto, $lojaProduto))  array_push($arrRetorno[$linha], 'Loja não pertence a Empresa.');
                    } else array_push($arrRetorno[$linha], 'Empresa não encontrada.');

                    if ($skuProduto && !$skuProduto_ignored) {
                        $dataProduct = $this->model_products->getProductComplete($skuProduto, $empresaProduto, $lojaProduto);
                        if ($dataProduct) {
                            $novoProduto = false;
                            $arrProdutoExistente[$linha] = true;
                            $produtoIntegrado = $this->model_integrations->getPrdIntegration($dataProduct['id']) && $this->session->userdata('usercomp') != 1 ? true : false;
                            $arrProdutoIntegrado[$linha] = $produtoIntegrado;
                        } else {
                            $dataVariant = $this->model_products->getVariantsBySkuAndStore($skuProduto, $lojaProduto);
                            if ($dataVariant) {
                                $variacao = true;
                                $novoProduto = false;
                                $arrVariacao[$linha] = true;
                            }
                        }
                    } elseif (!$skuProduto && !$skuProduto_ignored) array_push($arrRetorno[$linha], 'Código SKU precisa ser informado.');

                    if ($novoProduto && !$canCreate) {
                        array_push($arrRetorno[$linha], 'Não foi possível localizar o produto.');
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    if ($novoProduto && $colunaFaltante)
                        array_push($arrRetorno[$linha], 'Para cadastrado de produto é preciso informar todas as colunas, somente para atualização é opcional. Colunas faltantes: ' . $colunasFaltantes);

                    // valida nome do produto
                    if (!$produtoIntegrado && !$variacao && $nomeProduto !== null && $nomeProduto == '' && !$nomeProduto_ignored) array_push($arrRetorno[$linha], 'Nome do item precisa ser informado.');

                    // valida se o nome do produto possui até '$this->product_length_name' caracteres
                    if (!$produtoIntegrado && !$variacao && strlen($nomeProduto) > $this->product_length_name) {
                        array_push($arrRetorno[$linha], 'Nome do item precisa ser de até ' . $this->product_length_name . ' caracteres.');
                    }

                    // valida preço de venda
                    if (!$variacao && $precoProduto !== null && $precoProduto <= 0 && !$precoProduto_ignored) array_push($arrRetorno[$linha], 'Preço de venda precisa ser maior que 0(zero).');

                    // valida estoque, consulta de é uma alteração a analisar se existe variação
                    // verifica variações caso seja atualização
                    if (!$novoProduto && $skuProduto && !$estoque_ignored) {
                        if (!$variacao) {
                            $variants = $this->model_products->getVariantsForSkuAndStore($skuProduto, $lojaProduto);
                            if (count($variants) > 0) {
                                $estoque = 0;
                                foreach ($variants as $var) $estoque += $var['qty'];
                            }
                        }
                    }

                    // consulta fabricante, se não existir cadastrar
                    if (!$produtoIntegrado && !$variacao && $fabricante !== null && !$fabricante_ignored) {
                        if ($fabricante == "") array_push($arrRetorno[$linha], 'Fabricante precisa ser informado.');
                        else {
                            $getBrandByName = $this->model_brands->getBrandbyName($fabricante);
                            $getBrandById = $this->model_brands->getBrandData($fabricante);
                            if (!$getBrandByName && !$getBrandById) {
                                $fabricante = '["' . $this->model_brands->create(array('name' => $fabricante, 'active' => 1)) . '"]';
                            } else {
                                if ($getBrandByName) $fabricante = '["' . $getBrandByName . '"]';
                                else $fabricante = '["' . $fabricante . '"]';
                            }
                        }
                    }

                    // consulta categoria
                    if (!$produtoIntegrado && !$variacao && $categoria !== null && !$categoria_ignored) {
                        if ($categoria == "") array_push($arrRetorno[$linha], 'Categoria precisa ser informada.');
                        else {
                            $getCategoryByName = $this->model_category->getCategorybyName($categoria);
                            $getCategoryById = $this->model_category->getCategoryData($categoria);
                            if (!$getCategoryByName && !$getCategoryById) array_push($arrRetorno[$linha], 'Categoria informada não encontrada.');
                            else {
                                if ($getCategoryByName) $categoria = '["' . $getCategoryByName . '"]';
                                else $categoria = '["' . $categoria . '"]';
                            }
                        }
                    }

                    // valida código EAN
                    if (!$produtoIntegrado && $ean !== null && !$this->model_products->ean_check($ean) && !$ean_ignored) array_push($arrRetorno[$linha], 'Código EAN inválido.');

                    // valida peso líquido
                    if (!$produtoIntegrado && !$variacao && $pesoLiquido !== null && $pesoLiquido <= 0 && !$pesoLiquido_ignored) array_push($arrRetorno[$linha], 'Peso liquido do produto precisa ser maior que 0(zero).');

                    // valida peso bruto
                    if (!$produtoIntegrado && !$variacao && $pesoBruto !== null && $pesoBruto <= 0 && !$pesoBruto_ignored) array_push($arrRetorno[$linha], 'Peso bruto do produto precisa ser maior que 0(zero).');

                    // valida largura
                    // if ($largura !== null && $largura < 11 && !$largura_ignored) array_push($arrRetorno[$linha], 'Largura do produto menor que o mínimo 11.');
                    if (!$produtoIntegrado && !$variacao && $largura !== null && $largura <= 0 && !$largura_ignored) array_push($arrRetorno[$linha], 'Largura do produto precisa ser maior que 0(zero).');

                    // valida altura
                    // if ($altura !== null && $altura < 2 && !$altura_ignored) array_push($arrRetorno[$linha], 'Altura do produto menor que o mínimo 2.');
                    if (!$produtoIntegrado && !$variacao && $altura !== null && $altura <= 0 && !$altura_ignored) array_push($arrRetorno[$linha], 'Altura do produto precisa ser maior que 0(zero).');

                    // valida profundidade
                    // if ($profundidade !== null && $profundidade < 16 && !$profundidade_ignored) array_push($arrRetorno[$linha], 'Profundidade do produto menor que o mínimo 16.');
                    if (!$produtoIntegrado && !$variacao && $profundidade !== null && $profundidade <= 0 && !$profundidade_ignored) array_push($arrRetorno[$linha], 'Profundidade do produto precisa ser maior que 0(zero).');

                    // valida NCM
                    if (!$variacao && $ncm !== null && ($ncm != '' && (strlen($ncm) != 8 || !is_numeric($ncm))) && !$ncm_ignored) array_push($arrRetorno[$linha], 'Código NCM inválido.');

                    // valida origem do produto
                    if (!$variacao && $origemProduto !== null && $origemProduto_ignored) {
                        $existeOrigem = false;
                        foreach ($origens as $origem) {
                            if ($origemProduto == $origem['codigo'] || strtoupper($origemProduto) == strtoupper($origem['nome'])) {
                                $existeOrigem = true;
                                break;
                            }
                        }
                        if (!$existeOrigem) array_push($arrRetorno[$linha], 'Origem do produto inválido.');
                    }

                    // valida garantia
                    if (!$produtoIntegrado && !$variacao && $garantia !== null && $garantia < 0 && !$garantia_ignored) array_push($arrRetorno[$linha], 'Garantia do produto não pode ser um número negativo.');

                    // valida prazo operacional
                    if (!$produtoIntegrado && !$variacao && $prazoOperacional !== null && $prazoOperacional < 0 && !$prazoOperacional_ignored) array_push($arrRetorno[$linha], 'Prazo operacional do produto não pode ser um número negativo.');

                    // valida descrição
                    if (!$produtoIntegrado && !$variacao && $descricaoProduto !== null && $descricaoProduto == '' && !$descricaoProduto_ignored) array_push($arrRetorno[$linha], 'Descrição do produto precisa ser informada.');

                    // valida se a descrição do produto possui até 2000 caracteres
                    if (!$produtoIntegrado && !$variacao && strlen($descricaoProduto) > 2000) {
                        array_push($arrRetorno[$linha], 'Descrição do item precisa ser de até 2000 caracteres.');
                    }

                    if (!$produtoIntegrado && !$variacao && $imagens !== null && $novoProduto && (count(explode(",", $imagens)) > 4) && !$imagens_ignored) array_push($arrRetorno[$linha], 'É possível cadastrar apenas 4 imagens.');
                    if (!$produtoIntegrado && !$variacao && $imagens !== null && (count(explode(",", $imagens)) <= 4) && !$imagens_ignored) {

                        $verificaImagens = explode(",", $imagens);
                        if (empty($verificaImagens[0])) unset($verificaImagens[0]);
                        // percorre as imagens para verificar
                        foreach ($verificaImagens as $verificaImagem) {
                            array_push($arrVerificaImages, $verificaImagem);
                            if (!$this->uploadproducts->checkRemoteFile($verificaImagem))
                                array_push($arrRetorno[$linha], 'Não conseguimos acessa a imagem: ' . $verificaImagem);
                        }
                    }

                    // valida status
                    if (!$variacao && $status !== null && $status != 1 && $status != 2 && $status != 3 && !$status_ignored) array_push($arrRetorno[$linha], 'Status do produto precisa ser informado como 1, 2 ou 3.');

                    // consulta unidade
                    if (!$produtoIntegrado && !$variacao && $unidade !== null) {
                        $getAttribute = $this->model_attributes->getValueAttr($unidade);
                        if (!$getAttribute) array_push($arrRetorno[$linha], 'Unidade não encontrada.');
                        else $unidade = '["' . $getAttribute . '"]';
                    }

                    // validar imagem somente de for para inclusão para não deixar a importação muito lenta
                    if ($tipoImportacao == 0) {
                        if (count($arrRetorno[$linha])) $qtdErros++;
                    } elseif (($tipoImportacao == 1 || $tipoImportacao == 2) && !count($arrRetorno[$linha])) { // tudo certo para inserir ou atualizar o produto
                        $temImagem = false;
                        $arrImagensExiste = array();
                        $arrUrlImagensExiste = array();
                        $expImagens = explode(",", $imagens);

                        // diretório onde será salva a imagem
                        $serverpath = $_SERVER['SCRIPT_FILENAME'];
                        $pos = strpos($serverpath, 'assets');
                        $serverpath = substr($serverpath, 0, $pos);
                        $targetDir = $serverpath . 'assets/images/product_image/';
                        $imagensIguais = true;
                        $dirImage = '';

                        // se for novo, vai gerar uma nova pasta para as imagens do produto
                        if ($novoProduto) {
                            $dirImage = get_instance()->getGUID(false);
                            $targetDir .= $dirImage;
                            if (!file_exists($targetDir)) mkdir($targetDir);
                        } elseif (!$variacao) {
                            // já existe o produto, recupera o nome da pasta das imagens
                            $dirImage = $dataProduct['image'];
                            if (!$produtoIntegrado && !$imagens_ignored) {
                                $targetDir .= $dirImage;

                                // percorro as imagens enviadas para ver se preciso atualizar
                                foreach ($arrVerificaImages as $verificaImagem) {
                                    // recupera o nome da imagem
                                    $expNomeImagemVerifica = explode("/", $verificaImagem);
                                    $nomeImagemVerifica = array_pop($expNomeImagemVerifica);
                                    $expNomeImagemVerifica = explode(".", $nomeImagemVerifica);
                                    // se exitir a imagens eu defino que as imagens não são iguais
                                    if (!file_exists($targetDir . '/' . $expNomeImagemVerifica[0] . '.jpg')) $imagensIguais = false;
                                    else {
                                        array_push($arrImagensExiste, $expNomeImagemVerifica[0] . '.jpg'); // adiciona ao array a imagens que já existe e não preciso mexer
                                        array_push($arrUrlImagensExiste, $verificaImagem); // adiciona ao array a imagens que já existe e não preciso mexer
                                    }
                                }
                                if ($this->uploadproducts->countImagesDir($dirImage) != count($expImagens)) $imagensIguais = false;
                            }
                        }

                        if (!$produtoIntegrado && !$variacao && !$imagens_ignored) {
                            $pastaImagem = $dirImage; // pasta da imagem
                            if (empty($expImagens[0])) unset($expImagens[0]);

                            // percorre as imagens para fazer upload
                            if ($imagens !== null && ($novoProduto || !$imagensIguais)) {
                                if (!$novoProduto)  // demove todas as imagens
                                    $this->uploadproducts->deleteImagesDir($dirImage, $arrImagensExiste);
                                if (count($expImagens) != $this->uploadproducts->countImagesDir($dirImage)) {
                                    foreach ($expImagens as $imagem) {
                                        //verifica se não precisa inserir novamente a imagem
                                        if (in_array($imagem, $arrUrlImagensExiste)) continue;

                                        $imagem = trim(str_replace(" ", "%20", $imagem)); // alterar espaços por %20
                                        $upload = $this->uploadproducts->sendImageForUrl("{$targetDir}/", $imagem);
                                        if ($upload['success'] == false) {
                                            array_push($arrRetornoImage[$linha], $upload['data']);
                                            break;
                                        }
                                        $temImagem = true;
                                    }
                                }
                            }

                            // ocorreu algum problema na importação das imagens
                            if (isset($arrRetornoImage[$linha]) && count($arrRetornoImage[$linha])) continue;
                        }

                        // define a imagem primária
                        $imagemPrimaria = !$variacao ? $this->uploadproducts->getPrimaryImageDir($dirImage, $dataProduct['product_catalog_id'] ?? null) : null;

                        // Monta array para importação
                        $arrReplace = array();
                        if (!$precoProduto_ignored && $precoProduto !== null) $arrReplace['price'] = $precoProduto;
                        if (!$estoque_ignored && $estoque !== null) $arrReplace['qty'] = $estoque;
                        if (!$ean_ignored && $ean !== null) $arrReplace['EAN'] = $ean;

                        if ($variacao) { // Variação
                            $this->model_products->updateVar($arrReplace, $dataVariant['prd_id'], $dataVariant['variant']); // atualiza a variação
                            // atualizar estoque
                            $estoque = 0;
                            foreach ($this->model_products->getProductVariants($dataVariant['prd_id'], '') as $var_upd)
                                $estoque += (int)$var_upd['qty'];

                            $this->model_products->update(array('qty' => $estoque), $dataVariant['prd_id']);
                        } else { // Produto
                            // serão informados apenas se for um novo produto
                            if ($novoProduto && $empresaProduto !== null && $novoProduto) $arrReplace['company_id'] = $empresaProduto;
                            if ($novoProduto && $lojaProduto !== null && $novoProduto) $arrReplace['store_id'] = $lojaProduto;
                            if ($novoProduto && $skuProduto !== null && $novoProduto) $arrReplace['sku'] = $skuProduto;
                            if ($novoProduto && $pastaImagem !== null && $novoProduto) $arrReplace['image'] = $pastaImagem;
                            if ($nomeProduto !== null) $arrReplace['name'] = $nomeProduto;
                            if (!$produtoIntegrado && !$fabricante_ignored && $fabricante !== null) $arrReplace['brand_id'] = $fabricante;
                            if (!$produtoIntegrado && !$skuFabricante_ignored && $skuFabricante !== null) $arrReplace['codigo_do_fabricante'] = $skuFabricante;
                            if (!$produtoIntegrado && !$categoria_ignored && $categoria !== null) $arrReplace['category_id'] = $categoria;
                            if (!$produtoIntegrado && !$pesoLiquido_ignored && $pesoLiquido !== null) $arrReplace['peso_liquido'] = $pesoLiquido;
                            if (!$produtoIntegrado && !$pesoBruto_ignored && $pesoBruto !== null) $arrReplace['peso_bruto'] = $pesoBruto;
                            if (!$produtoIntegrado && !$largura_ignored && $largura !== null) $arrReplace['largura'] = $largura;
                            if (!$produtoIntegrado && !$altura_ignored && $altura !== null) $arrReplace['altura'] = $altura;
                            if (!$produtoIntegrado && !$profundidade_ignored && $profundidade !== null) $arrReplace['profundidade'] = $profundidade;
                            if (!$ncm_ignored && $ncm !== null) $arrReplace['NCM'] = $ncm;
                            if (!$origemProduto_ignored && $origemProduto !== null) $arrReplace['origin'] = $origemProduto;
                            if (!$produtoIntegrado && !$garantia_ignored && $garantia !== null) $arrReplace['garantia'] = $garantia;
                            if (!$produtoIntegrado && !$prazoOperacional_ignored && $prazoOperacional !== null) $arrReplace['prazo_operacional_extra'] = $prazoOperacional;
                            if ($descricaoProduto !== null) $arrReplace['description'] = $descricaoProduto;
                            if (!$status_ignored && $status !== null) $arrReplace['status'] = $status;
                            if (!$produtoIntegrado && $unidade !== null) $arrReplace['attribute_value_id'] = $unidade;
                            if (!$produtoIntegrado && !$imagens_ignored && $imagemPrimaria !== null) $arrReplace['principal_image'] = $imagemPrimaria === '' ? null : $imagemPrimaria;

                            if (!$produtoIntegrado && !$novoProduto) {
                                if ($dataProduct['category_id'] == '[""]' && ($categoria_ignored || $categoria === null)) $arrReplace['situacao'] = 1; // não tem categoria = incompleto
                                elseif ($imagemPrimaria == '') $arrReplace['situacao'] = 1; // não tem imagem = incompleto
                                else $arrReplace['situacao'] = 2; // tem categoria e imagem = completo
                            } elseif (!$produtoIntegrado) $arrReplace['situacao'] = $imagemPrimaria == '' ? 1 : 2; // novo produto, tem imagem = completo

                            if ($novoProduto) {
                                $create = $this->model_products->create($arrReplace); // cria o produto
                                $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($arrReplace, $create);
                            } else {
                                $this->model_products->update($arrReplace, $dataProduct['id']); // atualiza o produto
                            }
                        }
                    } elseif ($tipoImportacao == 3) {
                        if (count($arrRetorno[$linha])) array_push($newFileWithError, $dado);
                    }
                    // validação de preço de lista
                    if ($precoProduto > $precoLista){
                        array_push($arrRetorno[$linha], 'O preço de venda não pode ser menor que o preço de lista.');
                    }
                }

                if ($tipoImportacao == 0) {
                    $this->data['validate_finish']          = $arrRetorno;
                    $this->data['validate_finish_prd_ext']  = $arrProdutoExistente;
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                    $this->data['qty_errors']               = $qtdErros;
                    $this->data['validate_finish_var_ext']  = $arrVariacao;
                    $this->data['validate_finish_inte_ext'] = $arrProdutoIntegrado;
                } elseif ($tipoImportacao == 1) {
                    $this->data['validate_finish']          = $arrRetornoImage;
                    $this->data['validate_finish_prd_ext']  = array();
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                } elseif ($tipoImportacao == 2) {
                    $this->data['validate_finish']          = $arrRetorno;
                    $this->data['validate_finish_prd_ext']  = array();
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                } elseif ($tipoImportacao == 3) {
                    $newCsv = Writer::createFromString("");
                    //                    $newCsv->setOutputBOM(Reader::BOM_UTF8); // converte para UTF8
                    //                    $newCsv->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8'); // converte de ISO-8859-15 para UTF8
                    $encoder = (new CharsetConverter())->outputEncoding('utf-8');
                    $newCsv->addFormatter($encoder);
                    $newCsv->setDelimiter(';'); // demiliter de cada coluna
                    $newCsv->insertOne(array_keys($arrChaves)); // cabeçalho
                    $newCsv->insertAll($newFileWithError); // linhas
                    $newCsv->output('Erros-ConectaLa_Produtos_' . date('Y-m-d-H-i-s') . '.csv'); // arquivo de saida
                    die;
                }
                $this->data['tipo_importacao'] = $tipoImportacao;
                $this->data['validate_file'] = $upload_file;
            }
            if (!is_null($this->postClean("noerrors"))) {
                $upload_file = $this->postClean('upload_file',TRUE);
                $this->data['upload_point'] = 3;
            }
            if (!is_null($this->postClean("witherrors"))) {
                $upload_file = $this->postClean('upload_file',TRUE);
                $this->data['upload_point'] = 4;
            }
        } else {
            $this->data['upload_point'] = 1;
            $upload_file = $this->lang->line('messages_nofile');
        }
        $this->data['upload_file'] = trim($upload_file);

        $this->render_template('products/load', $this->data);
    }

    public function loadCatalog()
    {
        if (in_array('createProduct', $this->permission) || !in_array('disablePrice', $this->permission))
            redirect('products/load', 'refresh');

        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter)
            $sellerCenter = $settingSellerCenter['value'];

        if (!in_array('createProduct', $this->permission) && !in_array('updateProduct', $this->permission))
            redirect('dashboard', 'refresh');

        $this->data['page_title'] = $this->lang->line('application_upload_products');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!ini_get("auto_detect_line_endings")) // arquivo lido em um computador Macintosh
                ini_set("auto_detect_line_endings", '1');

            if (!is_null($this->postClean("import"))) { // selecionou o arquivo e será validado

                if (!$this->postClean('validate_file',TRUE)) {
                    $dirPathTemp = "assets/files/product_upload/";
                    if (!is_dir($dirPathTemp)) mkdir($dirPathTemp);
                    $upload_file = $this->upload_file();
                } else
                    $upload_file = $this->postClean('validate_file',TRUE);

                if (!$upload_file) {
                    $this->session->set_flashdata('error', $this->data['upload_msg']);
                    redirect('products/loadCatalog', 'refresh');
                }

                $csv = Reader::createFromPath($upload_file); // lê o arquivo csv
                $csv->setDelimiter(';'); // separados de colunas
                $csv->setHeaderOffset(0); // linha do header

                $stmt   = new Statement();
                $dados  = $stmt->process($csv);

                $this->load->library('UploadProducts'); // carrega lib de upload de imagens
                $arrRetorno         = array();
                $arrRetornoSku      = array();
                $newFileWithError   = array();
                $arrRetornoImage    = array();
                $qtdErros           = 0;
                $arrChaves          = array(
                    'ID da Loja'            => array('field' => 'lojaProduto', 'ignored' => false),
                    'Sku do Parceiro'       => array('field' => 'skuProduto', 'ignored' => false),
                    'Quantidade em estoque' => array('field' => 'estoque', 'ignored' => false),
                    'EAN'                   => array('field' => 'ean', 'ignored' => false),
                    'Catalogo'              => array('field' => 'catalog', 'ignored' => false),
                );

                // 0 = validacao
                // 1 = inclusão, ignorando os produtos com erro
                // 2 = inclusão, ignorando os produtos com erro e mostrar botão para baixar com error
                // 3 = baixar nova planilha de produtos com erro
                $tipoImportacao = $this->postClean('typeImport',TRUE);

                // percorre chaves para ver se está faltando algum campo
                $colunaFaltante = false;
                $colunasFaltantes = array();
                if ($dados->count() > 0) {
                    $verificaDado = $dados->fetchOne();
                    foreach (array_keys($arrChaves) as $chave) {
                        if (!isset($verificaDado[$chave])) {
                            array_push($colunasFaltantes, $chave);
                            $colunaFaltante = true;
                        }
                    }
                    $colunasFaltantes = implode(', ', $colunasFaltantes);
                }

                foreach ($dados as $linha => $dado) {
                    $linha++; // sempre pular o header, então adicionar 1(uma) linha
                    $arrRetorno[$linha] = array();
                    $arrProdutoExistente[$linha] = false;
                    $arrVariacao[$linha] = false;
                    $arrProdutoIntegrado[$linha] = false;
                    $novoProduto = true;
                    $variacao = false;
                    $produtoIntegrado = false;
                    $arrVerificaImages = array();

                    $empresaProduto     = null;
                    $skuProduto         = !isset($dado['Sku do Parceiro']) ? null : filter_var($this->detectUTF8(trim($dado['Sku do Parceiro'])), FILTER_SANITIZE_STRING); // OBRIGATÓRIO
                    $lojaProduto        = !isset($dado['ID da Loja']) ? null : filter_var($this->detectUTF8(trim($dado['ID da Loja'])), FILTER_SANITIZE_STRING);
                    $nomeProduto        = !isset($dado['Nome do Item']) ? null : filter_var($this->detectUTF8(trim($dado['Nome do Item'])), FILTER_SANITIZE_STRING);
                    $estoque            = !isset($dado['Quantidade em estoque']) ? null : filter_var($this->validateNumberFloatCSV(trim($dado['Quantidade em estoque'])), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $ean                = !isset($dado['EAN']) ? null : filter_var(trim($dado['EAN']), FILTER_SANITIZE_STRING);
                    $catalogo           = !isset($dado['Catalogo']) ? null : filter_var($this->detectUTF8(trim($dado['Catalogo'])), FILTER_SANITIZE_STRING);

                    $arrRetornoSku[$linha] = $skuProduto;

                    // se todas as colunas da linhas estiverem em branco, vou ignorar
                    $linhaEmBranco = true;
                    foreach ($dado as $line)
                        if (trim($line) != '') $linhaEmBranco = false;

                    if ($linhaEmBranco) {
                        unset($arrRetorno[$linha]);
                        continue; // ignoro linha em branco
                    }

                    $lojas = $this->model_stores->getStoresForExport();
                    // informou um código ou nome para a loja
                    if ($lojaProduto) {
                        foreach ($lojas as $loja) { // percorre as lojas para encontrar a loja informada
                            // caso for a loja, recuperar o código da loja e da empresa
                            if ($lojaProduto == $loja['codigo'] || strtoupper($lojaProduto) == strtoupper($loja['nome'])) {
                                $lojaProduto    = $loja['codigo'];
                                $empresaProduto = $loja['company_id'];
                                break;
                            }
                        }
                    }

                    // Informou uma loja, mas não foi encontrada
                    if ($lojaProduto && !$empresaProduto) array_push($arrRetorno[$linha], 'A loja informada não foi encontrada.');

                    // empresa ainda não definida, define como a do usuário autenticado
                    if (!$empresaProduto) $empresaProduto = $this->session->userdata('usercomp');

                    // ainda não definido o código da loja
                    if (!$lojaProduto) {
                        // se usuário gerencia apenas um única loja, defino a loja do usuário
                        if ($this->session->userdata('userstore') != 0) $lojaProduto = (int)$this->session->userdata('userstore');
                        else {
                            // Esse usuário gerencia mais de uma loja
                            // deverá informar para qual lojar pretente
                            // fazer a importação
                            $lojas = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
                            if (count($lojas) > 1) array_push($arrRetorno[$linha], 'Não foi informado para qual loja pretende fazer a importação.');
                            // Se não tem linha da loja então pega a primeira loja
                            $lojaProduto = (int)$lojas[0]['id'];
                        }
                    }

                    // não foi informado uma loja e não encontrou uma, também não encontrou empresa
                    if ((!$lojaProduto || !$empresaProduto)) {
                        array_push($arrRetorno[$linha], 'Não foi possível localizar a loja ou empresa.');
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    // loja recuperado não é um código numérico
                    if (!is_numeric($lojaProduto)) {
                        array_push($arrRetorno[$linha], 'Não foi possível localizar a loja.');
                        array_push($newFileWithError, $dado);
                        $qtdErros++;
                        continue;
                    }

                    // usuário tem permissão de gerenciar essa loja
                    if (in_array($empresaProduto, $this->data['filters'])) {
                        // loja não pertence da empresa
                        if (!$this->model_stores->CheckStores($empresaProduto, $lojaProduto))  array_push($arrRetorno[$linha], 'Loja não pertence a Empresa.');
                    } else array_push($arrRetorno[$linha], 'Empresa não encontrada.');

                    if ($skuProduto) {
                        $dataProduct = $this->model_products->getProductComplete($skuProduto, $empresaProduto, $lojaProduto);
                        if ($dataProduct) {
                            $novoProduto = false;
                            $arrProdutoExistente[$linha] = true;
                            $produtoIntegrado = $this->model_integrations->getPrdIntegration($dataProduct['id']) && $this->session->userdata('usercomp') != 1 ? true : false;
                            $arrProdutoIntegrado[$linha] = $produtoIntegrado;
                        } else {
                            $dataVariant = $this->model_products->getVariantsBySkuAndStore($skuProduto, $lojaProduto);
                            if ($dataVariant) {
                                $variacao = true;
                                $novoProduto = false;
                                $arrVariacao[$linha] = true;
                            }
                        }
                    } elseif (!$skuProduto) array_push($arrRetorno[$linha], 'Código SKU precisa ser informado.');

                    if ($novoProduto && $colunaFaltante)
                        array_push($arrRetorno[$linha], 'Para cadastrado de produto é preciso informar todas as colunas, somente para atualização é opcional. Colunas faltantes: ' . $colunasFaltantes);

                    // valida estoque, consulta de é uma alteração a analisar se existe variação
                    // verifica variações caso seja atualização
                    if (!$novoProduto && $skuProduto) {
                        if (!$variacao) {
                            $variants = $this->model_products->getVariantsForSkuAndStore($skuProduto, $lojaProduto);
                            if (count($variants) > 0) {
                                $estoque = 0;
                                foreach ($variants as $var) $estoque += $var['qty'];
                            }
                        }
                    }

                    if ($catalogo !== null && $novoProduto) {
                        if (!is_numeric($catalogo)) {
                            $getCatalogo = $this->model_products_catalog->getCatalogByName($catalogo);
                            if (!$getCatalogo) array_push($arrRetorno[$linha], 'Catálogo informado não foi encontrado.');
                            else $catalogo = $getCatalogo['id'];
                        }
                    }

                    if ($catalogo !== null && $novoProduto && !$this->model_products_catalog->verifyStoreCatalog($catalogo, $lojaProduto))
                        array_push($arrRetorno[$linha], 'Loja não pode usar o catálogo.');

                    $product_catalog_id = 0;
                    // valida código EAN de algum catalogo
                    if ($ean !== null && $novoProduto && $catalogo !== null) {
                        $productByEan = $this->model_products_catalog->getProductsByEAN($ean);

                        if (!count($productByEan))
                            array_push($arrRetorno[$linha], 'Código EAN não corresponde a nenhum produto de catálogo.');
                        else {
                            foreach ($productByEan as $prd_ctl) {

                                if ($this->model_products_catalog->getProductByProductCatalogStoreId($prd_ctl['id'], $lojaProduto))
                                    array_push($arrRetorno[$linha], 'Código EAN já corresponde a um produto já cadastrado.');

                                $getCatalogId = $this->model_products_catalog->getCatalogsProductsByProductCatalogIdAndCatalogId($catalogo, $prd_ctl['id']);
                                if ($getCatalogId) {
                                    $product_catalog_id = $getCatalogId['product_catalog_id'];
                                    continue;
                                }
                            }
                            if ($product_catalog_id == 0)
                                array_push($arrRetorno[$linha], 'Código EAN não corresponde a nenhum produto de catálogo.');
                        }
                    }

                    // validar imagem somente de for para inclusão para não deixar a importação muito lenta
                    if ($tipoImportacao == 0) {
                        if (count($arrRetorno[$linha])) $qtdErros++;
                    } elseif (($tipoImportacao == 1 || $tipoImportacao == 2) && !count($arrRetorno[$linha])) { // tudo certo para inserir ou atualizar o produto

                        // Monta array para importação
                        $arrReplace = array();
                        if ($estoque !== null) $arrReplace['qty'] = $estoque;

                        // serão informados apenas se for um novo produto
                        if ($ean !== null && $novoProduto) $arrReplace['EAN'] = $ean;
                        if ($empresaProduto !== null && $novoProduto) $arrReplace['company_id'] = $empresaProduto;
                        if ($lojaProduto !== null && $novoProduto) $arrReplace['store_id'] = $lojaProduto;
                        if ($skuProduto !== null && $novoProduto) $arrReplace['sku'] = $skuProduto;

                        if ($novoProduto) {

                            $product_catalog = $this->model_products_catalog->getProductProductData($product_catalog_id);

                            $arrReplace = array(
                                'name' => $product_catalog['name'],
                                'sku' => $skuProduto,
                                'price' => $product_catalog['price'],
                                'qty' => $estoque,
                                'image' => 'catalog_' . $product_catalog['id'],
                                'principal_image' => $product_catalog['principal_image'],
                                'description' => $product_catalog['description'],
                                'attribute_value_id' => $product_catalog['attribute_value_id'],
                                'brand_id' => json_encode(array($product_catalog['brand_id'])),
                                'category_id' => json_encode(array($product_catalog['category_id'])),
                                'store_id' => $lojaProduto,
                                'status' => 1,
                                'EAN' => $product_catalog['EAN'],
                                'codigo_do_fabricante' => $product_catalog['brand_code'] ?? '',
                                'peso_liquido' => $product_catalog['net_weight'],
                                'peso_bruto' => $product_catalog['gross_weight'],
                                'largura' => $product_catalog['width'],
                                'altura' => $product_catalog['height'],
                                'profundidade' => $product_catalog['length'],
                                'garantia' => $product_catalog['warranty'],
                                'NCM' => $product_catalog['NCM'] ?? '',
                                'origin' => $product_catalog['origin'] ?? '',
                                'has_variants' => $product_catalog['has_variants'],
                                'company_id' => $empresaProduto,
                                'situacao' => 2,
                                'prazo_operacional_extra' => 0,
                                'product_catalog_id' => $product_catalog['id']
                            );

                            $this->model_products->create($arrReplace); // cria o produto
                        } else $this->model_products->update($arrReplace, $dataProduct['id']); // atualiza o produto
                    } elseif ($tipoImportacao == 3) {
                        if (count($arrRetorno[$linha])) array_push($newFileWithError, $dado);
                    }
                }

                if ($tipoImportacao == 0) {
                    $this->data['validate_finish']          = $arrRetorno;
                    $this->data['validate_finish_prd_ext']  = $arrProdutoExistente;
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                    $this->data['qty_errors']               = $qtdErros;
                    $this->data['validate_finish_var_ext']  = $arrVariacao;
                    $this->data['validate_finish_inte_ext'] = $arrProdutoIntegrado;
                } elseif ($tipoImportacao == 1) {
                    $this->data['validate_finish']          = $arrRetornoImage;
                    $this->data['validate_finish_prd_ext']  = array();
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                } elseif ($tipoImportacao == 2) {
                    $this->data['validate_finish']          = $arrRetorno;
                    $this->data['validate_finish_prd_ext']  = array();
                    $this->data['validate_finish_skus']     = $arrRetornoSku;
                } elseif ($tipoImportacao == 3) {
                    $newCsv = Writer::createFromString("");
                    //                    $newCsv->setOutputBOM(Reader::BOM_UTF8); // converte para UTF8
                    //                    $newCsv->addStreamFilter('convert.iconv.ISO-8859-15/UTF-8'); // converte de ISO-8859-15 para UTF8
                    $encoder = (new CharsetConverter())->outputEncoding('utf-8');
                    $newCsv->addFormatter($encoder);
                    $newCsv->setDelimiter(';'); // demiliter de cada coluna
                    $newCsv->insertOne(array_keys($arrChaves)); // cabeçalho
                    $newCsv->insertAll($newFileWithError); // linhas
                    $newCsv->output('Erros-ConectaLa_Produtos_' . date('Y-m-d-H-i-s') . '.csv'); // arquivo de saida
                    die;
                }
                $this->data['tipo_importacao'] = $tipoImportacao;
                $this->data['validate_file'] = $upload_file;
            }
            if (!is_null($this->postClean("noerrors"))) {
                $upload_file = $this->postClean('upload_file',TRUE);
                $this->data['upload_point'] = 3;
            }
            if (!is_null($this->postClean("witherrors"))) {
                $upload_file = $this->postClean('upload_file',TRUE);
                $this->data['upload_point'] = 4;
            }
        } else {
            $this->data['upload_point'] = 1;
            $upload_file = $this->lang->line('messages_nofile');
        }
        $this->data['upload_file'] = trim($upload_file);

        $this->render_template('products/load_somaplace', $this->data);
    }

    public function importAttributes()
    {
        if (!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_import');
        $this->data['page_now'] = 'importAttributes';

        $store_id = +$this->session->userdata['userstore'];
        $this->data['category'] = $this->model_category->getCategoriesByStoreId($store_id);

        $this->render_template('products/import_attributes', $this->data);
    }

    public function importCollections()
    {
        $collection_occ = $this->model_settings->getStatusbyName('collection_occ');
        if ($collection_occ != "1") {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_import');
        $this->data['page_now'] = 'importAttributes';

        $store_id = +$this->session->userdata['userstore'];
        $this->data['category'] = $this->model_category->getCategoriesByStoreId($store_id);

        $this->render_template('products/import_collections', $this->data);
    }

    public function generateCsvAttribute($category_id = null)
    {
        $store_id = +$this->session->userdata['userstore'];
        $company_id = $this->session->userdata['usercomp'];
        $category = $this->model_category->getCategoryData($category_id);
        $products = $this->model_products->getProductsByCategory($category_id);
        $attributes = $this->model_products->getAttributesProductsByCategory($category_id);

        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex(0);

        $line = 1;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, 'ID da Loja');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, 'ID do Produdo');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $line, 'SKU');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $line, 'ID da Categoria');
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $line, 'Categoria');

        $column = 5;
        $arr_pos_attributes = array();
        $arr_valor_atributes = array();
        $valor = "";
        foreach ($attributes as $attribute) {
            $label = $attribute['int_to'] . "_" . $attribute['nome'];

            if ($attribute['obrigatorio'] == 1) {
                $label = "*_".$label;
            }
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column, $line, $label);

            $column_id = $attribute['int_to'] . '_' . $attribute['category_id'] . '_' . $attribute['id_atributo'];
            $arr_pos_attributes[$column_id] = $column;

            if ($attribute['tipo'] == "list" && $attribute['valor'] != null) {
                $arr_valor_atributes[$column_id] = $attribute;
            }

            $column++;
        }

        foreach($products as $product) {
            $line++;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, $product['store_id']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $line, $product['id']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $line, $product['sku']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $line, $category['id']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $line, $category['name']);

            $produtosAtributosMarketplaces = $this->model_products->getProdutosAtributosMarketplaces($product['id']);
            foreach($produtosAtributosMarketplaces as $atributoMarketplaces) {
                $column_id = $atributoMarketplaces['int_to'] . '_' . $category['id'] . '_' . $atributoMarketplaces['id_atributo'];
                if (array_key_exists($column_id, $arr_pos_attributes)) {
                    $column_pos = $arr_pos_attributes[$column_id];
                    if (array_key_exists($column_id, $arr_valor_atributes)){
                        $valor  = $this->getValueAtribute($arr_valor_atributes, $atributoMarketplaces['valor']);
                    }
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column_pos, $line, $valor);
                }
            }
        }

        $filename = $company_id == 1 ? 'all_stores_' : ($store_id == 0 ? "company_{$company_id}_" : "store_{$store_id}_");
        $filename .= "_attributes_category_". $category['id'] . "_" . date("Y-m-d-H-i-s") . ".xlsx";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function getValueAtribute($array_values, $valor_atributo)
    {
        $valor = "";
        foreach ($array_values as $atributo) {
            foreach (json_decode($atributo['valor']) as $value) {
                if ($atributo['int_to'] == 'VIA' && $value->udaValueId == $valor_atributo) {
                    $valor = $value->udaValue;
                    break;
                }
                else if ($atributo['int_to'] == 'ML' && $value->id == $valor_atributo) {
                    $valor = $value->name;
                    break;
                }
                else if ($atributo['int_to'] == 'NovoMundo' && $value->FieldValueId == $valor_atributo) {
                    $valor = $value->Value;
                    break;
                }
                else if ($value->FieldValueId == $valor_atributo) {
                    $valor = $value->Value;
                    break;
                }
            }
            break;
        }
        return $valor;
    }

    public function only_verify()
    {
        $upload_file = $this->get_file_on_upload();
        if (!$upload_file) {
            $this->session->set_flashdata('error', $this->lang->line('messages_imported_queue_error'));
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        }
        $user = $this->model_users->getUserById($this->session->userdata['id']);
        $new_csv_data = [
            'upload_file' => $upload_file,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_email' => $user['email'],
            'usercomp' => $user['company_id'],
        ];
        $is_create = $this->model_csv_to_verifications_phases->create($new_csv_data);
        if ($is_create) {
            $this->session->set_flashdata('success', $this->lang->line('messages_imported_queue_sucess'));
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_imported_queue_error'));
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        }
    }

    public function upload_attributes_file()
    {
        $config['upload_path'] = 'assets/files/product_upload';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'xls|xlsx';
        $config['max_size'] = '100000';
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('phase_upload')) {
            $error = $this->upload->display_errors();
            $this->data['upload_msg'] = $this->lang->line('messages_invalid_file');
            $this->data['upload_msg'] = $error;
            $this->session->set_flashdata('error', $this->lang->line('messages_product_attr_imported_queue_error'));
			$this->session->set_flashdata('error', $error);
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['phase_upload']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            $data_imported = array(
                'store_id' => $this->session->userdata['userstore'],
                'path' => $path,
                'name_original' => $_FILES['phase_upload']['name'],
                'email' => $this->session->userdata['email']
            );
            $this->model_csv_import_attributes_products->insertCsvImported($data_imported);
            $this->session->set_flashdata('success', $this->lang->line('messages_product_attr_imported_queue_sucess'));
            redirect(self::PRODUCTS_ATTR_IMPORT_ROUTE, 'refresh');
        }
    }

    public function GetAttributeId($linha, &$msg)
    {
        $attribute_id = "[";
        $valids = $this->data['valids'];
        foreach ($valids as $key => $val) {
            $keym = strtolower($key);
            if (array_key_exists(trim($keym), $linha)) {
                if (array_key_exists(trim($linha[$keym]), $valids[$key])) {
                    $attribute_id .= '"' . $valids[$key][trim($linha[$keym])] . '";';
                } else {
                    $msg .= "Campo " . $key . " Inválido. ";
                }
            } else {
                $msg .= "Coluna " . $key . " Inválida. ";
            }
        }
        $attribute_id .= "]";
        return $attribute_id;
    }

    /*function CheckProductLoadData($linha,&$msg,&$data, $uploadImage) {
        $colunas = array("empresa", "loja", "sku", "nome", "unidade", "preco_venda", "qtd", "imagens", "descricao", "fabricante", "categoria", "EAN", "cod_fabricante", "peso_liquido", "peso_bruto", "largura", "altura", "profundidade", "garantia", "NCM", "origem_produto");
        $check = array(  "AB",      "AB",   "ABX", "AB",   "AB",       "AB",          "NB",  "A",       "AB",        "AB",         "AB",        "NB",  "AB",             "NB",           "NB",         "NB",      "NB",     "NB",           "NB",       "NB",  "ABX",       "NB");
        $colunas = array("Empresa", "ID da Loja", "Sku do Parceiro", "Nome do Item", "Unidade", "Preco de Venda", "Quantidade em estoque", "Imagens", "Descricao do Item _ Informacoes do Produto", "Fabricante", "Categoria", "EAN", "SKU no fabricante", "Peso Liquido em kgs", "Peso Bruto em kgs", "Largura em cm", "Altura em cm", "Profundidade em cm", "Garantia em meses", "NCM", "Origem do Produto _ Nacional ou Estrangeiro", "Prazo Operacional em dias", "Status1Ativo2Inativo" );
        $check = array(  "AB",      "AB",         "ABX",             "AB",           "AB",      "AB",             "NB",                    "A",       "AB",                                         "AB",         "AB",        "AN",   "A",                "NB",                  "NB",                "NB",            "NB",           "NB",                 "NB",                "NB",  "ABX",                                         "NB",                       "AB");
        // removido $attribs = array("unidade","frete grátis");
        $attribs = array();
        $erroImage = array(false);
        $ok = true;
        $col = 1;
        $valAc = '';
        $catCodigo = '';
        foreach ($linha as $key => $val) {
            $valAc.=trim($val);
        }
        if ($valAc == '') {
            $data ='';
            return $ok;  // ignoro linha em branco
        }
        
        $origens = Array();  // Monto o array de origens de produtos
        for ($i=0; $i<=8; $i++) {
            $origens[$i] = array('codigo' => (string)$i, 'nome' => $this->lang->line("application_origin_product_".$i));
        }
        
        $lojas = $this->model_stores->getStoresForExport();
        if (isset($linha['ID da Loja'])) {
            foreach($lojas as $loja) {
                if (($linha['ID da Loja'] == $loja['codigo'])  || (strtoupper($linha['ID da Loja']) == strtoupper($loja['nome']))) {
                    $linha['ID da Loja'] = $loja['codigo'];
                    $linha['Empresa'] = $loja['company_id'];
                    break;
                }
            }
        }
        // Leio as categorias
        $categorias = $this->model_category->getCategoryDataForExport();
        // leio os fabricantes
        $fabricantes = $this->model_brands->getBrandForExport();
        
        if (!isset($linha['Empresa'])) {
            $linha['Empresa'] = $this->session->userdata('usercomp');
        } elseif (trim($linha['Empresa'])=='') {
            $linha['Empresa'] = $this->session->userdata('usercomp');
        }
        if ($this->data['userstore'] != 0) {   // Se está setada a store, o produto só pode ser desta store
            $linha['ID da Loja'] =$this->data['userstore'];
        } else {
            if (!isset($linha['ID da Loja'])) {  // Se não tem linha da loja então pega a primeira loja
                $lojas = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
                $linha['ID da Loja'] = $lojas[0]['id'];
            } elseif (trim($linha['ID da Loja'])=='') {
                $lojas = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
                $linha['ID da Loja'] = $lojas[0]['id'];
            }
        }
        
        $valids = $this->data['valids'];
        if ((isset($linha['Sku do Parceiro'])) && (isset($linha['Empresa'])) && (isset($linha['ID da Loja']))) {
            if ((trim($linha['Sku do Parceiro'])!="") && (trim($linha['Empresa'])!="") && (trim($linha['ID da Loja']!=""))) {
                if (in_array(trim($linha['Empresa']), $this->data['filters'])) {
                    if (!$this->model_stores->CheckStores(trim($linha['Empresa']),trim($linha['ID da Loja']))) {
                        $ok = false;
                        $msg .= "(Loja não pertence a Empresa.)";
                    }
                } else {
                    $ok = false;
                    $msg .= "(Empresa Inválida.)";
                }
                $existe = $this->model_products->getProductComplete(str_replace("/", "-", $linha['Sku do Parceiro']), $linha['Empresa'], $linha['ID da Loja']);
                if ($existe) { $data = $existe; }
            } else {
                $existe = false;
            }
        } else {
            $existe = false;
        }


        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $serverpath = substr($serverpath,0,$pos);
        $targetDir = $serverpath . 'assets/images/product_image/';
        $dirImage = get_instance()->getGUID(false);
        $targetDir .= $dirImage;
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        $temImagem = false;
        if (isset($linha['Imagens'])) {
            if (trim($linha['Imagens'])!='') {

                $images = explode(",", $linha['Imagens']);

                if(!$existe && (count($images) > 4)){
                    $erroImage = array(true, "É possível enviar apenas 4 imagens");
                } else {

                    foreach ($images as $image) {
                        $upload = $this->sendImageForUrl($targetDir . '/', trim(str_replace(" ", "%20", $image))); // alterar espaços por %20
                        if ($upload['success'] == false) {
                            $erroImage = array(true, $upload['data']);
                            break;
                        }
                        $temImagem = true;
                    }
					if ($temImagem) {
						$principal_image = '';
						$fotos = scandir(FCPATH . 'assets/images/product_image/' . $dirImage);
		                foreach($fotos as $foto) {
		                    if (($foto!=".") && ($foto!="..") && ($foto!="")) {
		                    	if ($principal_image == '') {
		                    		$principal_image = baseUrlPublic('assets/images/product_image/'.$dirImage).'/'.$foto;
		                    		break;
		                    	}
		                    }
		                }
		                $data['principal_image'] = $principal_image;
					}
                }
                
            }
        }

        $linha['Imagens'] = $dirImage;

        foreach ($linha as $key => $val) {
            if ($col++>0) {
                //echo '$key:'.$key.'<br>';
                //echo '$val:'.$val.'<br>';
                $attrib_ok = "";
                $brand = "";
                $ean = "";
                $categ = "";
                $unidade = "";
                if(in_array(trim($key),$colunas)) {
                    if (in_array($key,$attribs)) {
                        if (array_key_exists(trim($linha[$key]),$valids[$key])) {
                            $attrib_ok = $this->GetAttributeId($linha,$msg);
                        } else {
                            $msg .= "(Valor do Campo ".$key." Invalido.)";
                            $ok = false;
                        }
                    } else {
                        $nc = array_search(trim($key),$colunas);
                        if (substr($check[$nc],0,1)=="N") {
                            if ((substr($check[$nc],0,2)=="NB") && (trim($linha[$key])=="")) {
                                if ((!$existe) || (($existe) && ($check[$nc]=="NBX"))) {
                                    $msg .= "(Falta valor OBRIGATÓRIO em: ".$key.")";
                                    $ok = false;
                                }
                            }
                            if ($ok && (!$this->fmtNum($linha[$key])) && ($linha[$key] !='0')) {
                                $msg .= "(Valor NÃO NUMÉRICO em: ".$key." = ".$linha[$key].")";
                                $ok = false;
                            }
                        }
                        if (substr($check[$nc],0,1)=="A") {
                            if ((substr($check[$nc],0,2)=="AB") && (trim($linha[$key])=="")) {
                                if ((!$existe) || (($existe) && ($check[$nc]=="ABX"))) {
                                    $msg .= "(Falta valor OBRIGATÓRIO em: ".$key.")";
                                    $ok = false;
                                }
                            }
                        }
                        if ($key == "Fabricante") {
                            
                            $achei= false;
                            foreach($fabricantes as $fabricante) {
                                if (($this->detectUTF8($linha['Fabricante']) == $fabricante['codigo'])  || (strtoupper($this->detectUTF8($linha['Fabricante'])) == strtoupper($fabricante['nome']))) {
                                    $linha['Fabricante'] =$fabricante['nome'];
                                    $brand = '["'.$fabricante['codigo'].'"]';
                                    $achei= true;
                                    break;
                                }
                            }
                            //if (!$this->model_brands->getBrandbyName(utf8_encode($linha['Fabricante']))) {
                            if (!$achei) {
                                // Criar fabricante
                                $sqlBrand = $this->model_brands->create(array('name' => $linha['Fabricante'], 'active' => 1));

                                if($sqlBrand) $brand = '["'.$sqlBrand.'"]';
                                else {
                                    $msg .= "(Valor do Campo " . $key . "(" . $linha['Fabricante'] . ") Invalido.)";
                                    $ok = false;
                                }
                            }
                        }
                        if ($key == "Categoria") {
                            $achei= false;
                            foreach($categorias as $categoria) {
                                if (($this->detectUTF8($linha['Categoria']) == $categoria['codigo'])  || (strtoupper($this->detectUTF8($linha['Categoria'])) == strtoupper($categoria['nome']))) {
                                    $linha['Categoria'] = $categoria['nome'];
                                    $achei= true;
                                    $catCodigo =$categoria['codigo'];
                                    $categ = '["'.$categoria['codigo'].'"]';
                                    break;
                                }
                            }
                            // if (!$this->model_category->getCategorybyName(utf8_encode($linha['Categoria']))) {
                            if (!$achei) {
                                $msg .= "(Valor do Campo ".$key." invalido ou não encontrado.) - ". $this->detectUTF8($linha['Categoria']);
                                $ok = false;
                            }
                        }
                        if ($key == "Unidade") {
                            if (!$this->model_attributes->getValueAttr($linha['Unidade'])) {
                                $msg .= "(Valor do Campo ".$key." Invalido.)";
                                $ok = false;
                            } else {
                                $unidade = '["'.$this->model_attributes->getValueAttr($linha['Unidade']).'"]';
                            }
                        }
                        if ($key == "EAN") {
                            if (!$this->model_products->ean_check($linha['EAN'])) {
                                $msg .= "(Valor do Campo ".$key." ".$linha['EAN']." Inválido.)";
                                $ok = false;
                            } else {
                                $ean = $this->model_products->ean_check($linha['EAN']);
                            }
                        }
                        if ($key == "NCM") {
                            $ncm = preg_replace('/[^\d\+]/', '',$linha['NCM']);
                            if ((strlen($ncm) != 8) || (!is_numeric($ncm))) {
                                $msg .= "(Valor do Campo ".$key." Invalido.)";
                                $ok = false;
                            }
                        }
                        if ($key == "Origem do Produto _ Nacional ou Estrangeiro") {
                            
                            $achei= false;
                            foreach($origens as $origem) {
                                if (($linha['Origem do Produto _ Nacional ou Estrangeiro'] == $origem['codigo'])  || (strtoupper($linha['Origem do Produto _ Nacional ou Estrangeiro']) == strtoupper($origem['nome']))) {
                                    $linha['Origem do Produto _ Nacional ou Estrangeiro'] = $origem['codigo'];
                                    $achei= true;
                                    break;
                                }
                            }
                            if (!$achei) {
                                $msg .= "(Valor do Campo ".$key." Invalido.)".print_r($linha['Origem do Produto _ Nacional ou Estrangeiro'],true);
                                $ok = false;
                            }
                        }
						if ($key == "Largura em cm") {
                            if ((int)$linha[$key] < 11) {
                                $msg .= "(Valor do Campo ".$key." menor que o mínimo 11.)";
                                $ok = false;
                            }
                        }
						if ($key == "Altura em cm") {
                            if ((int)$linha[$key] < 2) {
                                $msg .= "(Valor do Campo ".$key." menor que o mínimo 2.)";
                                $ok = false;
                            }
                        }
						if ($key == "Profundidade em cm") {
                            if ((int)$linha[$key] < 16) {
                                $msg .= "(Valor do Campo ".$key." menor que o mínimo 16.)";
                                $ok = false;
                            }
                        }
						if($key == "Imagens" && $erroImage[0]){
                            $msg .= "(Coluna ".$key." Inválida. Valor:".$erroImage[1].")";
                            $ok = false;
                        }
                        if ($key == "Status1Ativo2Inativo") {
                            if ((int)$linha[$key] != 1 && (int)$linha[$key] != 2) {
                                $msg .= "(Valor do Campo Status(1=Ativo|2=Inativo|3=Lixeira) deve ser 1 ou 2.)";
                                $ok = false;
                            }
                        }
                    }
                } else {
                    if($key == "Status1Ativo2Inativo") $key = "Status(1=Ativo|2=Inativo|3=Lixeira)";
                    $msg .= "(Coluna ".$key." Inválida. Valor:".$val.")";
                    $ok = false;
                }
                
                if ($ok) {
                    if ($key=='Nome do Item') {
                        $data['name'] = $this->detectUTF8($linha['Nome do Item']);
                    } elseif ($key=='Sku do Parceiro') {
                        $data['sku'] = str_replace("/", "-", $linha['Sku do Parceiro']);
                    } elseif ($key=='Preco de Venda') {
                        $data['price'] = $this->fmtNum($linha['Preco de Venda']);
                    } elseif ($key=='Quantidade em estoque') {
                        $qty = $linha['Quantidade em estoque'];

                        // verifica variações caso seja atualização
                        if(isset($data['sku']) && isset($data['store_id'])) {
                            $variants = $this->model_products->getVariantsForSkuAndStore($data['sku'], $data['store_id']);
                            if(count($variants) > 0) $qty = 0;
                            foreach ($variants as $var) $qty += $var['qty'];
                        }

                        $data['qty'] = $qty;
                    } elseif ($key=='Imagens') {
                        $data['image'] = $linha['Imagens'];
                    } elseif ($key=='Descricao do Item _ Informacoes do Produto') {
                        $data['description'] = $this->detectUTF8($linha['Descricao do Item _ Informacoes do Produto']);
                    } elseif ($brand!="") {
                        $data['brand_id'] = $brand;
                    } elseif ($categ!="") {
                        $data['category_id'] = $categ;
                    } elseif ($key=='ID da Loja') {
                        $data['store_id'] = $linha['ID da Loja'];
                    } elseif ($key=='EAN') {
                        $data['EAN'] = $linha['EAN'];
                    } elseif ($key=='SKU no fabricante') {
                        $data['codigo_do_fabricante'] = $linha['SKU no fabricante'];
                    } elseif ($key=='Peso Liquido em kgs') {
                        $data['peso_liquido'] = $linha['Peso Liquido em kgs'];
                    } elseif ($key=='Peso Bruto em kgs') {
                        $data['peso_bruto'] = $linha['Peso Bruto em kgs'];
                    } elseif ($key=='Largura em cm') {
                        $data['largura'] = $linha['Largura em cm'];
                    } elseif ($key=='Altura em cm') {
                        $data['altura'] = $linha['Altura em cm'];
                    } elseif ($key=='Profundidade em cm') {
                        $data['profundidade'] = $linha['Profundidade em cm'];
                    } elseif ($key=='Garantia em meses') {
                        $data['garantia'] = $linha['Garantia em meses'];
                    } elseif ($key=='Prazo Operacional em dias') {
                        $data['prazo_operacional_extra'] = $linha['Prazo Operacional em dias'];
                    } elseif ($key=='NCM') {
                        $data['NCM'] = preg_replace('/[^\d\+]/', '',$linha['NCM']);
                    } elseif ($key=='Origem do Produto _ Nacional ou Estrangeiro') {
                        $data['origin'] = $linha['Origem do Produto _ Nacional ou Estrangeiro'];
                    } elseif($key=='Unidade'){
                        $data['attribute_value_id'] = $unidade;
                    } elseif($key=='Empresa'){
                        $data['company_id'] = $linha['Empresa'];
                    } elseif($key=='Status1Ativo2Inativo'){
                        $data['status'] = $linha['Status1Ativo2Inativo'];
                    }
                }
            }
        }
        
        
        if ($uploadImage) {
            $data['situacao'] = '2';
            if ($temImagem == false) {
                $data['situacao'] = '1';	 // Falta informações
            }
            if (!array_key_exists('category_id', $data)) {
                $data['situacao'] = '1';
            } elseif (trim ($data['category_id'])=='') {
                $data['situacao'] = '1';
            } else {
                // Tem categoria então ve se tem campos a mais para o ML e marca como Falta Informações
                $campos = $this->pegaCamposMKTdaMinhaCategoria($catCodigo,'ML');
                if (count($campos)>0){
                    $data['situacao'] = '1';
                }
                // Tem categoria então marca a loja se o tipo de volume mudou
                $categoria = $this->model_category->getCategoryData($catCodigo);
                if (!is_null($categoria['tipo_volume_id'])) {
                    $datastorestiposvolumes = array(
                        'store_id' => $data['store_id'],
                        'tipos_volumes_id' => $categoria['tipo_volume_id'],
                                'status' => 1,
                        );
                        $this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
                    }
                    
                }
                if (!array_key_exists('brand_id', $data)) {
                    $data['situacao'] = '1';
                } elseif (trim ($data['brand_id'])=='') {
                    $data['situacao'] = '1';
                }
                if (!array_key_exists('prazo_operacional_extra',$data)) {
                    $data['prazo_operacional_extra'] =0;
                }
               $data['status'] = '1'; // força o Ativo
        }
        return $ok;
    }*/

    public function UTF8($string)
    {
        if (utf8_encode(utf8_decode($string)) == $string) {
            return $string;
        } else {
            return utf8_encode($string);
        }
    }

    public function allocate()
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //get_instance()->log_data('Products','index','-');

        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);
        $this->data['filters'] = $this->model_reports->getFilters('products');
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('products/allocate', $this->data);
    }
    public function mktselect()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if ((!is_null($this->postClean('mkt'))) && ($this->postClean('mkt') != "0")) {
            $mkt = $this->postClean('mkt');
            if (!is_null($this->postClean('id'))) {
                get_instance()->log_data('Products', 'SendMKT', json_encode($_POST), "I");
                $ids = $this->postClean('id');
                if (!is_null($this->postClean('select'))) {
                    foreach ($ids as $k => $v) {
                        $mktData = $this->model_integrations->getIntegrationsData($mkt);
                        $product = $this->model_products->getProductData(0, $id);
                        list($id, $cpy) = explode("|", $v);
                        $prd = array(
                            'int_id' => $mkt,
                            'prd_id' => $id,
                            'company_id' => $cpy,
                            'store_id' => $product['store_id'],
                            'date_last_int' => '',
                            'status' => 1,
                            'status_int' => 0,
                            'int_type' => 13,        // BLING FORÇADO
                            'int_to' => $mktData['name'],
                        );
                        $this->model_integrations->setProductToMkt($prd);
                    }
                }
                if (!is_null($this->postClean('deselect'))) {
                    foreach ($ids as $k => $v) {
                        list($id, $cpy) = explode("|", $v);
                        $prd = array(
                            'status' => 0,
                            'user_id' => $this->session->userdata['id']
                        );
                        $this->model_integrations->unsetProductToMkt($mkt, $id, $cpy, $prd);
                    }
                }
            }
        }
        unset($_POST);
        $this->data['filters'] = $this->model_reports->getFilters('products');
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('products/allocate', $this->data);
    }

    function fmtNum($num, $padrao = "US")
    {    // Ou BR
        $temp = str_replace(",", "", $num);
        $temp = str_replace(".", "", $temp);
        if (is_numeric($temp)) {
            $num = str_replace(",", ".", $num);
            $ct = false;
            while (!$ct) {
                $temp = str_replace(".", "", $num, $cnt);
                if ($cnt < 2) {
                    $ct = true;
                } else {
                    $pos = strpos($num, ".");
                    $num = substr($num, 0, $pos) . substr($num, $pos + 1);
                    $ct = false;
                }
            }
            return $num;
        } else {
            return false;
        }
    }

    function pegaCamposMLdaMinhaCategoria($idcat)
    {
        $result = $this->model_atributos_categorias_marketplaces->getCategoryMkt($idcat);
        $idMkt = $result['id_mkt'];

        $result = $this->model_atributos_categorias_marketplaces->getCategoryMktML($idMkt);
        $idCatML = $result['id_loja'];

        // ricardo ML 
        $result = $this->model_categorias_marketplaces->getCategoryMktplace('ML', $idcat);
        $idCatML = $result['category_marketplace_id'];
        $result = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaML($idCatML);

        return $result;
    }

    function pegaCamposMKTdaMinhaCategoria($idcat, $int_to, $idprd = null)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $idcat);
        $idCatML = ($result) ? $result['category_marketplace_id'] : null;
        $enriched = false;
        if ($idprd) {
            $productCategoryMkt = $this->model_products_category_mkt->getCategoryEnriched($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
                $enriched = true;
            }
        }
        $category_mkt = $this->model_categorias_marketplaces->getCategoryByMarketplace($int_to, $idCatML);
        $result = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML, $int_to);

        return [$result, $category_mkt, $enriched];
    }

	function verifyVariantsAtCategoryMarketplace($idcat, $int_to, $variant_name, $idprd = null)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $idcat);
        $idCatML = ($result) ? $result['category_marketplace_id'] : null;
        if ($idprd) {
            $productCategoryMkt = $this->model_products_category_mkt->getCategoryEnriched($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
            } 
        }
		return [$result, $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKTVariant($idCatML, $int_to, $variant_name)];
    }

    function camposPorCategorias()
    { // chamado por AJAX
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $idcat = $this->input->get('idcat');
        //echo 'Peguei = '.$idcat;

        echo json_encode($this->pegaCamposMLdaMinhaCategoria($idcat), JSON_UNESCAPED_UNICODE);
    }

    public function produtosIntegracao()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //get_instance()->log_data('Products','index','-');

        // $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->data['page_now'] = 'products_integration';
        $this->data['page_title'] = $this->lang->line('application_products_integration');
        $this->render_template('products/produtosintegracao', $this->data);
    }

    public function fetchProdutosIntegracaoData()
    {
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        //get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 3 letras
                $procura = " AND (pi.int_to like '%" . $busca['value'] . "%' OR pi.skumkt like '%" . $busca['value'] . "%' OR c.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR" .
                    $procura .= " pi.skubling like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%'  OR p.sku like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('pi.int_to', 'p.sku', 'pi.skubling', 'pi.skumkt', 'p.name', 'c.name', 's.name');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        $data = $this->model_products->getProdutosIntegracao($ini, $sOrder, $procura);

        $filtered = $this->model_products->getCountProdutosIntegracao($procura);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;
            // echo $value['id_integration']."|".$value['atributo'].'|'.$value['categoria_id']."\n";
            $status = '<span class="label-danger">-</span>';

            $category_id = json_decode($value['category_id']);
            $category_id = $category_id[0];
            //	$category = $this->model_category->getCategoryData($value['categoria']);
            $catName = '<em style="color:red">' . $value['categoria'] . '</em>';

            $categoryLinked = $this->model_category->getCategoryLinked($category_id, $value['int_to']);
            if (!empty($categoryLinked)) {
                $catName = '<em style="color:blue">' . $value['categoria'] . '</em>' . ' ==> ' . $categoryLinked['id_loja'];
            }

            if (is_null($value['skumkt']) || (trim($value['skumkt']) == '')) {
                $produto = '<b style="color:red">' . $value['produto'] . '</b>';
            } else {
                $produto = '<b style="color:blue">' . $value['produto'] . '</b>';
            }
            $skumkt = $value['skumkt'];

            if ((($value['int_to'] == 'ML') || ($value['int_to'] == 'MAGALU')) && ($value['skumkt'] == '00')) {
                $skumkt = '<span class="label label-danger" data-toggle="tooltip" title="Falta Integrar com o ML">' . $value['skumkt'] . '</span>';
                $produto = '<b style="color:red">' . $value['produto'] . '</b>';
            }
            /*
             if ($value['usado'] != 0) {
             $status ='<span class="label-success">*</span>';
             $catstring = '';
             $catlocais=$this->model_atributos_categorias_marketplaces->getCategoriaLocal($value['categoria_id']);
             foreach ($catlocais as $catlocal) {
             $catstring .= '<b >'.$catlocal['name']."</b> | ";
             }
             $catstring = substr($catstring,0,-3);
             //	$catName='<span  data-toggle="tooltip" data-html="true" title="'.$catstring.'">'.$value['categoria'].'</span>';
             
             $catName = '<em style="color:blue">'.$value['categoria'].'</em>'. ' ==> '. $catstring;
             }
             */

            $result[$key] = array(
                $value['int_to'],
                $value['sku'],
                $value['skubling'],
                $skumkt,
                $produto,
                $catName,
                $value['loja'],

            );
        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_products->getCountProdutosIntegracao(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function getCodsArrayFilter($query, $campo_id)
    {

        $arr_in = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $value) {
                array_push($arr_in, $value[$campo_id]);
            }
            $where_in = implode(',', $arr_in);
        } else {
            $where_in = null;
        }

        return $where_in == null ? 0 : $where_in;
    }


    public function sendImageForUrl($caminho, $fileUrl)
    {
        try {
            list($width_orig, $height_orig, $tipo) = getimagesize($fileUrl);
            $fileSize = strlen(file_get_contents($fileUrl));
        } catch (Exception $e) {
            return array('success' => false, 'data' => "A imagem tem que ser um URL de imagem válida. URL: {$fileUrl}");
        }

        $width      = $width_orig;
        $height     = $height_orig;
        $resize     = false; // min = imagem muito pequena vai redimensionar para o tamanho mínimo, | max = imagem muito grande vai redimensionar para o tamanho máximo | false = não precisa redimensionar
        $nameImage  = md5(microtime()) . md5($fileUrl); // Define novo nome para a imagem

        // Verifica limites de 800x800 a 1200x1200
        if ($width_orig < 800 || $height_orig < 800) $resize = 'min';
        elseif ($width_orig > 1200 || $height_orig > 1200) $resize = 'max';

        // Precisa redimensionar
        if ($resize !== false) {
            // largura maior que altura
            if ($width > $height) {
                if ($resize == "min") {
                    $width = (800 / $height) * $width;
                    $height = 800;
                } else if ($resize == "max") {
                    $height = (1200 / $width) * $height;
                    $width = 1200;
                }
            }
            // altura maior que largura
            elseif ($height > $width) {
                if ($resize == "min") {
                    $height = (800 / $width) * $height;
                    $width = 800;
                } else if ($resize == "max") {
                    $width = (1200 / $height) * $width;
                    $height = 1200;
                }
            } else {
                $width = $resize == "min" ? 800 : 1200;
                $height = $resize == "min" ? 800 : 1200;
            }

            //Caso não consiga redimensionar propocional entre 800x800 e 1200x1200, vai ser preciso distorcer a imagem
            if ($width < 800)   $width  = 800;
            if ($height < 800)  $height = 800;
            if ($width > 1200)  $width  = 1200;
            if ($height > 1200) $height = 1200;
        }
        $quality = 100;
        if ($fileSize > 700000) { // se a imagem é grande, abaixo a qualidade dela que diminiu bastente o tamanho 
            $quality = 75;
        }
        try {
            $novaimagem = imagecreatetruecolor($width, $height);
            switch ($tipo) {
                    // gif
                case 1:
                    $origem = imagecreatefromgif($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
                    break;

                    // jpg
                case 2:
                    $origem = imagecreatefromjpeg($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
                    break;

                    // png
                case 3:
                    /*
                    imagesavealpha($novaimagem, true);
                    $cor_fundo = imagecolorallocatealpha($novaimagem, 0, 0, 0, 127);
                    imagefill($novaimagem, 0, 0, $cor_fundo);

                    $origem = imagecreatefrompng($fileUrl);
                    imagecopyresampled($novaimagem, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagepng($novaimagem, $caminho . $nameImage . '.png');
                    
					 * 
					 */
                    $origem = imagecreatefrompng($fileUrl);
                    $imageTmp = imagecreatetruecolor(imagesx($origem), imagesy($origem));
                    imagefill($imageTmp, 0, 0, imagecolorallocate($imageTmp, 255, 255, 255));
                    imagealphablending($imageTmp, TRUE);
                    imagecopy($imageTmp, $origem, 0, 0, 0, 0, imagesx($origem), imagesy($origem));

                    imagecopyresampled($novaimagem, $imageTmp, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                    imagejpeg($novaimagem, $caminho . $nameImage . '.jpg', $quality);
                    imagedestroy($imageTmp);
                    break;
                default:
                    return array('success' => false, 'data' => "Tipo de imagem não suportado!");
            }

            imagedestroy($novaimagem);
            imagedestroy($origem);
        } catch (Exception $e) {
            return array('success' => false, 'data' => $e->getMessage());
        }

        return array('success' => true);
    }

    public function productsNotCorreios()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //get_instance()->log_data('Products','index','-');

        // $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->data['page_title'] = $this->lang->line('application_products_not_post_office');
        $this->render_template('products/productsnotcorreios', $this->data);
    }

    public function fetchProductsNotCorreios()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        //get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 3 letras
                $procura = " AND (p.id like '%" . $busca['value'] . "%' OR sku like '%" . $busca['value'] . "%' ";
                $procura .= " OR s.name like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' ";
                $procura .= " OR peso_bruto like '%" . $busca['value'] . "%' OR largura like '%" . $busca['value'] . "%' ";
                $procura .= " OR price like '%" . $busca['value'] . "%' ";
                $procura .= " OR altura like '%" . $busca['value'] . "%' OR profundidade like '%" . $busca['value'] . "%' ) ";
            }
        }
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'id', 's.name', 'sku', 'p.name', 'CAST(peso_bruto AS DECIMAL(12,2))', 'CAST(peso_cubico AS DECIMAL(12,2))', 'CAST(largura AS DECIMAL(12,2))', 'CAST(altura AS DECIMAL(12,2))', 'CAST(profundidade AS DECIMAL(12,2))', 'CAST(soma AS DECIMAL(12,2))', 'CAST(price AS DECIMAL(12,2))', 'date_update', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        $data = $this->model_products->getProductsNotCorreios($ini, $sOrder, $procura, $length);
        $filtered = $this->model_products->getCountProductsNotCorreios($procura);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;
            $value['peso_cubico'] = ceil($value['peso_cubico']);
            if ($value['peso_bruto'] > 30) {
                $value['peso_bruto'] =  '<b style="color:red">' . $value['peso_bruto'] . '</b>';
            }
            if ($value['peso_cubico'] > 30) {
                $value['peso_cubico'] =  '<b style="color:red">' . $value['peso_cubico'] . '</b>';
            }
            if ($value['largura'] > 105) {
                $value['largura'] =  '<b style="color:red">' . $value['largura'] . '</b>';
            }
            if ($value['altura'] > 105) {
                $value['altura'] =  '<b style="color:red">' . $value['altura'] . '</b>';
            }
            if ($value['profundidade'] > 105) {
                $value['profundidade'] =  '<b style="color:red">' . $value['profundidade'] . '</b>';
            }
            if ($value['soma'] > 315) {
                $value['soma'] = '<b style="color:red">' . $value['soma'] . '</b>';
            }
            $buttons = '';
            if (in_array('updateProduct', $this->permission)) {
                $buttons .= '<a target="__blank" href="' . base_url('products/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }
            $linkid =     '<a target="__blank" href="' . base_url('products/update/' . $value['id']) . '" >' . $value['id'] . '</a>';
            $result[$key] = array(
                $value['id'],
                $linkid,
                $value['store'],
                $value['sku'],
                $value['name'],
                $value['peso_bruto'],
                $value['peso_cubico'],
                $value['largura'],
                $value['altura'],
                $value['profundidade'],
                $value['soma'],
                $value['price'],
                $value['date_update'],
                $buttons,
            );
        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_products->getCountProductsNotCorreios(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function orderimages()
    {
        $path = 'assets/images/product_image/';
        $parameters = $this->postClean('params');
        $fileIndex = 1; // Número inicial para renomeação dos arquivos
        $stacks = $parameters['stack'];
        $mainFolder = explode('/', $stacks[0]['key'])[0];

        // Permite ainda a organização via Bucket até que todos os produtos tenham sido migrados.
        if (!$this->postClean('onBucket')) {
            $folderFiles = scandir($path . $mainFolder);
            $path = substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], 'index.php')) . 'assets/images/product_image/';
            foreach ($stacks as $stack) {
                $folderAndFile = explode('/', $stack['key']);
                $fileName = $folderAndFile[1];
                $newFileName = sprintf('%03d', $fileIndex) . $fileName; // Adiciona numeração inicial

                // Verifica se o arquivo existe na pasta
                if (in_array($fileName, $folderFiles)) {
                    $result = rename($path . $mainFolder . '/' . $fileName, $path . $mainFolder . '/' . $newFileName);
                    if (!$result) {
                        echo "Erro ao renomear o arquivo: $fileName\n";
                    }
                }

                $fileIndex++; // Incrementa o número para o próximo arquivo
            }
        } else {
            // Percorre cada stack.
            foreach ($stacks as $key => $stack) {
                // Verifica se o objeto existe.
                if ($this->bucket->objectExists($path . $stack['key'])) {
                    // Monta o novo nome da imagem como o nome anterior, alterando a primeira letra para ordenar.
                    $image_name = $key . substr(basename($stack['key']), 1);
                    $dir = dirname($stack['key']);
                    $this->bucket->renameObject($path . $stack['key'], $path . $dir . '/' . $image_name);
                }
            }
        }
    }

    public function markproductasok()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if (!is_null($this->postClean('id'))) {
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_products->markAsNotCorreios($id, $this->session->userdata('id'));
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    // $this->model_orders->updatePaidStatus->updatePaidStatus($id,$this->product_length_name);
                }
            }
        }

        redirect('products/productsNotCorreios', 'refresh');
    }

    public function updateQty()
    {
        $prod_id = $this->postClean('id',TRUE);
        $old_qty = $this->postClean('old_qty',TRUE);
        $new_qty = $this->postClean('new_qty',TRUE);

        if (!is_numeric($new_qty)) return;

        $data = [
            'qty' => $new_qty
        ];

        if ($new_qty != $old_qty) {
            $data['stock_updated_at'] = date('Y-m-d H:i:s');
        }

        $saved = $this->model_products->update($data, $prod_id);

        if ($saved) {
            $user = $this->session->userdata();
            $log = [
                'user_id'    => $user['id'],
                'user_name'  => $user['username'],
                'product_id' => $prod_id,
                'old_qty'    => $old_qty,
                'new_qty'    => $new_qty
            ];
            $this->log_data(__CLASS__, __FUNCTION__, json_encode($log), 'I');
        }
    }

    public function updatePrice()
    {
        $prod_id   = $this->postClean('id',TRUE);
        $old_price = $this->postClean('old_price',TRUE);
        $new_price = $this->postClean('new_price',TRUE);

        if (!is_numeric($old_price) || !is_numeric($new_price)) {
            $this->output->set_status_header(422);
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Os valores de preço devem ser numéricos válidos.'
            ]));

            return;
        }

        if (substr_count($new_price, '.') > 1) {
            $new_price = substr_replace(str_replace('.', '', $new_price), '.', -2, 0);
        }

        $data = [
            'price' => $new_price
        ];

        $saved = $this->model_products->update($data, $prod_id);

        if ($saved) {
            $user = $this->session->userdata();
            $log = [
                'user_id'    => $user['id'],
                'user_name'  => $user['username'],
                'product_id' => $prod_id,
                'old_price'  => $old_price,
                'new_price'  => $new_price
            ];
            $this->log_data(__CLASS__, __FUNCTION__, json_encode($log), 'I');

            $this->output->set_output(json_encode([
                'success' => true,
                'message' => 'Preço do produto atualizado com sucesso.'
            ]));
        }
    }

    public function productsApprove()
    {
        if (!in_array('doProductsApproval', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['stores_filter'] = $this->model_integrations->getStoresProductsNeedApprovel();
        $this->data['page_title'] = $this->lang->line('application_products_approval');
        $this->data['names_marketplaces'] = $this->model_integrations->getIntegrationsContecalaNames('0');
        $this->data['categories'] = $this->model_category->getActiveCategroy(1);
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['marketplaces'] = $this->model_integrations->getIntegrations();
        $this->data['setting_validate_completed_sku_marketplace'] = $this->model_settings->getValueIfAtiveByName('validate_completed_sku_marketplace');
        $this->render_template('products/productsapprove', $this->data);
    }

    public function fetchProductsApproval()
    {
        ob_start();
        if (!in_array('doProductsApproval', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];
        $busca = $postdata['search'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) { // Garantir no minimo 3 letras
                $procura = " AND ( 
                    p.sku like '%" . trim($busca['value']) . "%' 
                    OR p.name like '%" . trim($busca['value']) . "%'  
                    OR s.name like '%" . trim($busca['value']) . "%'
                    OR c.name like '%" . trim($busca['value']) . "%'
                    OR pi.approved like '%" . trim($busca['value']) . "%'
                    OR p.date_update like '%" . trim($busca['value']) . "%'
                ) ";
            } else {
                return;
            }
        } else {

            if (!empty($postdata['category'])) {
                if (is_array($postdata['category'])) {
                    $categories = $postdata['category'];
                    $procura .= " AND (";
                    foreach ($categories as $category) {
                        $procura .=  " p.category_id = '[".'"'.$category.'"'."]' OR ";
                    }
                    $procura = substr($procura, 0, (strlen($procura) - 3));
                    $procura .= ") ";
                }

            }
            if (!empty($postdata['sku'])) {
                $procura .= " AND p.sku like '%" . trim($postdata['sku']) . "%' ";
            }
            if (!empty($postdata['nome'])) {
                $procura .= " AND p.name like '%" . trim($postdata['nome']) . "%' ";
            }

            if (!empty($postdata['int_to'])) {
                if (is_array($postdata['int_to'])) {
                    $int_tos = $postdata['int_to'];
                    $procura .= " AND (";
                    foreach ($int_tos as $int_to) {
                        $procura .= "pi.int_to = '" . $int_to . "' OR ";
                    }
                    $procura = substr($procura, 0, (strlen($procura) - 3));
                    $procura .= ") ";
                }
            }

            if (!empty($postdata['lojas'])) {
                if (is_array($postdata['lojas'])) {
                    $lojas = $postdata['lojas'];
                    $procura .= " AND (";
                    foreach ($lojas as $loja) {
                        $procura .= "s.id = " . (int)$loja . " OR ";
                    }
                    $procura = substr($procura, 0, (strlen($procura) - 3));
                    $procura .= ") ";
                }
            }

            if (!empty($postdata['status'])) {
                $procura .= " AND pi.approved = " . trim($postdata['status']);
            }
            if (!empty($postdata['completo'])) {
                $procura .= " AND p.situacao = " . trim($postdata['completo']);
            }
            if (!empty($postdata['estoque'])) {
                switch ((int)$postdata['estoque']) {
                    case 1:
                        $procura .= " AND p.qty > 0 ";
                        break;
                    case 2:
                        $procura .= " AND p.qty <= 0 ";
                        break;
                }
            }

            if (!empty($postdata['operador']) && $postdata['operadorvalor'] >= "0") {
                switch ($postdata['operador']) {
                    case 1:
                        $procura .= ' AND s.service_charge_value = '.$postdata['operadorvalor'];
                        break;
                    case 2:
                        $procura .= ' AND s.service_charge_value > '.$postdata['operadorvalor'];
                        break;
                    case 3:
                        $procura .= ' AND s.service_charge_value < '.$postdata['operadorvalor'];
                        break;
                    case 4:
                        $procura .= ' AND s.service_charge_value <= '.$postdata['operadorvalor'];
                        break;
                    case 5:
                        $procura .= ' AND s.service_charge_value >= '.$postdata['operadorvalor'];
                        break;
                    default:
                        $procura = '';
                        break;
                }
            }
        }
        $sOrder = "";
        if (!empty($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('','', 'p.sku', 'p.name', 'p.category_id', 's.name', 'pi.approved', 'pi.int_to', 'p.date_update', 'p.qty', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $completed_sku_marketplace = $postdata['completed_sku_marketplace'] ?? '';
        if ($completed_sku_marketplace !== '') {
            if ($completed_sku_marketplace == 1) {
                $procura .= ' AND (csss.skumkt IS NOT NULL OR pi.skumkt IS NOT NULL) ';
            } else {
                $procura .= ' AND (csss.skumkt IS NULL AND pi.skumkt IS NULL) ';
            }
        }

        $result = array();

        $data = $this->model_integrations->getProductsNeedApproval($ini, $sOrder, $procura, $length);
        $filtered = $this->model_integrations->getProductsNeedApprovalCount($procura);

        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_integrations->getProductsNeedApprovalCount($procura);
        }

        foreach ($data as $key => $value) {
            $remove = ['[',']','"'];
            $category = str_replace($remove,'',$value['category_id']);
            if (!empty($category)) {
                $categoryName = $this->model_category->getCategoryData($category);
                if (isset($categoryName['name'])) {
                    $categoryName = $categoryName['name'];
                }
            }
            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            $buttons = '<button onclick="changeIntegrationApproval(event,\'' . $value['sku']  . '\',\'' . $value['id'] . '\',\'' . $value['prd_id'] . '\',\'1\',\'' . $value['approved'] . '\',\'' . $value['int_to'] . '\')" class="btn btn-success" data-toggle="tooltip" title="' . $this->lang->line('application_approve') . '"><i class="fas fa-thumbs-up"></i></button>';
            $buttons .= '<button onclick="changeIntegrationApproval(event,\'' . $value['sku'] . '\',\'' . $value['id'] . '\',\'' . $value['prd_id'] . '\',\'2\',\'' . $value['approved'] . '\',\'' . $value['int_to'] . '\')" class="btn btn-danger" data-toggle="tooltip" title="' . $this->lang->line('application_disapprove') . '"><i class="fas fa-thumbs-down"></i></button>';
            $buttons .= '<button onclick="changeIntegrationApproval(event,\'' . $value['sku'] . '\',\'' . $value['id'] . '\',\'' . $value['prd_id'] . '\',\'3\',\'' . $value['approved'] . '\',\'' . $value['int_to'] . '\')" class="btn btn-primary" data-toggle="tooltip" title="' . $this->lang->line('application_mark_as_in_approval') . '"><i class="fas fa-thumbtack"></i></button>';

            if ($value['approved'] == 1) {
                $statusApproval = '<span id="statusApproval_' . $value['id'] . '" class="label label-success">' . mb_strtoupper($this->lang->line('application_approved'), 'UTF-8') . '</span>';
            } elseif ($value['approved'] == 2) {
                $statusApproval = '<span id="statusApproval_' . $value['id'] . '" class="label label-danger">' . mb_strtoupper($this->lang->line('application_disapproved'), 'UTF-8') . '</span>';
            } elseif ($value['approved'] == 3) {
                $statusApproval = '<span id="statusApproval_' . $value['id'] . '" class="label label-primary">' . mb_strtoupper($this->lang->line('application_approval'), 'UTF-8') . '</span>';
            } elseif ($value['approved'] == 4) {
                $statusApproval = '<span id="statusApproval_' . $value['id'] . '" class="label label-danger">' . mb_strtoupper($this->lang->line('application_rejected'), 'UTF-8') . '</span>';
                $buttons = '';
            }

            $linkid = '<a target="__blank" href="' . base_url('products/update/' . $value['prd_id']) . '" >' . $value['sku'] . '</a>';

            $result[$key] = array(
                $value['sku'] . "|" . $value['id'] . "|" . $value['prd_id'] . "|" . $value['approved']. "|" . $value['int_to'],
//                $value['id'],
                $img,
                $linkid,
                $value['name'],
                $categoryName,
                $value['store'],
//                $value['comissao'] . '%',
//                $value['date_update'],
                $statusApproval,
                $value['int_to'],
                date('d/m/Y H:i:s', strtotime($value['date_update'])),
                $value['qty'],
                $buttons,
            );
        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        ob_clean();
        echo json_encode($output);
    }

    public function markProductsApproval()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        if (!in_array('doProductsApproval', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $payload = [];
        $request = $this->postClean();

        if (!in_array('doProductsApproval', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($request['id'])) {
            $ids = $request['id'];
            if ($request['identify'] == 'approve_product') {
                foreach ($ids as $idtmp) {
                    $idexp = explode("|", $idtmp);
                    $this->model_integrations->updatePrdToIntegrationByTrusteeship(['approved' => 1], $idexp[1]);
                    $this->model_products->update(array('date_update' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)), $idexp[2]);
                }
            }
            if ($request['identify'] == 'disapprove_product') {
                $data = ['approved' => 2];
                foreach ($ids as $idtmp) {
                    $idexp = explode("|", $idtmp);
                    $payload[] = [
                        'product_id' => $idexp[2],
                        'int_to'     => $idexp[4],
                        'comment'    => $request['comment_error'],
                        'sku'        => $idexp[0],
                    ];
                    //$this->model_integrations->updatePrdToIntegrationByTrusteeship($data, $idexp[1], array(), $idexp[0]);
                    $this->model_products->update(array('date_update' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)), $idexp[2]);
                    $this->errorReason($data, $idexp[1], $request, $payload);
                }
            }
            if ($request['identify'] == 'on_approval_product') {
                foreach ($ids as $idtmp) {
                    $idexp = explode("|", $idtmp);
                    $this->model_integrations->updatePrdToIntegrationByTrusteeship(['approved' => 3], $idexp[1]);
                    $this->model_products->update(array('date_update' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)), $idexp[2]);
                }
            }
            $this->log_data('Integrations', "$log_name/Product_approval", json_encode($request));
        }

        redirect('products/productsApprove', 'refresh');
    }

    public function checkProductWithoutSkuMkt()
    {
        $validate_completed_sku_marketplace = $this->model_settings->getValueIfAtiveByName('validate_completed_sku_marketplace');

        if (!$validate_completed_sku_marketplace) {
            return '';
        }

        $products = array_map(function($item){
                if (is_numeric($item)) {
                    return $item;
                }
                $item = explode('|', $item);
                return $item[2];
            }, $this->postClean('products'));

        $check = $this->model_control_sync_skuseller_skumkt->checkSkuWithoutSkumkt($products);

        return $this->output->set_output($check ? (
            count($this->postClean('products')) > 1 ?
                'Existem produtos sem sku do marketplace preenchdio, caso seja necessário preencher, verifique os skus selecionados para preencher. <div class="d-flex justify-content-center"><button class="btn btn-primary" onclick="checkCheckbox(event);">Visualização rápida</button></div>' :
                'O produto está sem sku do marketplace preenchdio, caso seja necessário preencher, verifique os skus selecionados para preencher. <div class="d-flex justify-content-center"><button class="btn btn-primary" onclick="checkCheckbox(event);">Visualização rápida</button></div>'
        ) : '');
    }

    public function changeIntegrationApproval()
    {
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $request = $this->postClean();
        $id = $this->postClean('id',TRUE);
        $prd_id = $this->postClean('prd_id',TRUE);
        $approve = $this->postClean('approve',TRUE);
        $old_approve = $this->postClean('old_approve',TRUE);
        $int_to = $this->postClean('int_to',TRUE);

        if($old_approve != 2 && $approve != 2){
            if ($old_approve == $approve) { // se não mudou nada, faz nada
                return;
            }
        }

        $data = array(
            'approved' => $approve,
        );

        $payload = [];
        $payload[] = [
            'product_id' => $request['prd_id'],
            'int_to'     => $request['int_to'],
            'comment'    => $request['comment_error'] ?? null,
            'sku'        => isset($request['sku']) ? $request['sku'] : null,
        ];

        if($data['approved'] == 2){
            $this->errorReason($data, $id, $request, $payload );
        }else{
            $this->model_integrations->updatePrdToIntegrationByTrusteeship($data, $id);
        }

        $this->model_products->update(array('date_update' => date('Y-m-d H:i:s')), $prd_id);

        $this->log_data('Integrations', "$log_name/Product_approval", json_encode($request));
    }

    private function validateNumberFloatCSV($num)
    {
        $replace = strpos($num, ',');

        if ($replace !== false) $num = str_replace(",", ".", $num);

        $validDecimal = substr_count($num, '.');
        if ($validDecimal > 1) {
            $countDecimal = 0;
            $newNum = '';
            for ($i = 0; $i < strlen($num); $i++) {
                if ($num[$i] == '.') {
                    $countDecimal++;
                    if ($countDecimal != $validDecimal) continue;
                }
                $newNum .= $num[$i];
            }
            $num = $newNum;
        }

        return (float)$num;
    }

    public function fetchProductsByCategoryData($idcat)
    {
        ob_start();
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $busca = $postdata['search'];
        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 3) {  // Garantir no minimo 3 letras
                $procura = " AND (p.id = '" . $busca['value'] . "' OR s.name like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR p.sku like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('p.id', 'p.sku', 'p.name', 's.name');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        $data = $this->model_products->getProductsByCategoryData($idcat, $ini, $procura, $sOrder, $length);

        $filtered = $this->model_products->getProductsByCategoryCount($idcat, $procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_products->getProductsByCategoryCount($idcat);
        }

        foreach ($data as $key => $value) {
            $result[$key] = array(
                $value['id'],
                '<a target="__blank" href="' . base_url('products/update/' . $value['id']) . '" >' . $value['sku'] . '</a>',
                $value['name'],
                $value['loja'],
            );
        }
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }

    public function errorsTransformation()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('products/errorsTransformation', $this->data);
    }

    public function fetchProductDataError()
    {
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $busca = $postdata['search'];

        if ($busca['value']) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $this->data['ordersfilter'] .= " AND ( p.sku like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR p.id like '%" . $busca['value'] . "%')";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'p.sku', 'p.name', 'CAST(p.price AS DECIMAL(12,2))', 'CAST(p.qty AS UNSIGNED)', 's.name', 'p.id', '', '');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
            $this->data['orderby'] = $sOrder;
        }


        $result = array();
        if (isset($this->data['ordersfilter'])) {
            $filtered = $this->model_products->getProductErrorsCount($this->data['ordersfilter']);
            //get_instance()->log_data('Products','fetchfilter',print_r($this->data['ordersfilter'],true));
        } else {
            $filtered = 0;
        }

        $data = $this->model_products->getProductErrorData($ini, $length);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;
            $buttons = '';

            $buttons .= ' <button type="button" class="btn btn-default viewError" prd-id="' . $value['id'] . '"><i class="fa fa-eye"></i></button>';

            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            $is_kit = '';
            if ($value['is_kit'] == 1) {
                $is_kit = '<br><span class="label label-warning">Kit</span>';
            }

            $link_id = "<a href='" . base_url('products/update/' . $value['id']) . "'>" . $value['sku'] . ' ' . $is_kit . "</a>";
            $price = $this->formatprice($value['price']);
            $read_only = (trim($value['has_variants']) ? 'disabled data-toggle="tooltip" data-placement="top" title="Produto com variação" data-container="body"' : '');

            $result[$key] = array(
                $img,
                $link_id,
                $value['name'],
                'R$ ' . number_format($value['price'], 2, ',', '.'),
                $value['qty'],
                $value['loja'],
                $value['id'],
                $buttons
            );
        } // /foreach
        if ($filtered == 0) {
            $filtered = $i;
        }
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_products->getProductErrorsCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        echo json_encode($output);
    }

    public function getErrosTransformationAjax()
    {

        $prd_id = (int)$this->postClean('prd_id',TRUE);

        if (!$prd_id) {
            echo json_encode(array('success' => false, 'data' => "Não foi possível recuperar os dados desse erro."));
            exit();
        }

        $errors = $this->model_products->viewErrosTransformation($prd_id);

        if (!$errors) {
            echo json_encode(array('success' => false, 'data' => "Não foi possível recuperar os dados desse erro."));
            exit();
        }

        $arrErrors = array();
        foreach ($errors as $error)
            array_push($arrErrors, array(
                'message'   => $error['message'],
                'mkt'       => $error['int_to'],
                'step'      => $error['step'],
                'date'      => date('d/m/Y H:i', strtotime($error['date_create']))
            ));

        echo json_encode(array('success' => true, 'data' => $arrErrors));
    }

    public function verifyWords()
    {
        $request = [
            'name'          => trim($this->postClean('name',TRUE)) ? $this->postClean('name',TRUE) : null,
            'description'   => trim($this->postClean('description',TRUE, false, false)) ? $this->postClean('description',TRUE, false, false) : null,
            'sku'           => trim($this->postClean('sku',TRUE)) ? $this->postClean('sku',TRUE) : null,
            'store_id'      => trim($this->postClean('store',TRUE)) ? $this->postClean('store',TRUE) : null,
            'category_id'   => trim($this->postClean('category',TRUE)) ? (preg_replace('/[^0-9]/', '', $this->postClean('category',TRUE)) ?? null) : null,
            'brand_id'      => trim($this->postClean('brand',TRUE)) ? (preg_replace('/[^0-9]/', '', $this->postClean('brand',TRUE)) ?? null) : null
        ];

        if ($this->postClean('product',TRUE))
            $request['id'] = $this->postClean('product',TRUE);

        $response = $this->blacklistofwords->getBlockProduct($request);
        if ($this->session->userdata('usercomp') == 1)
            $response['request'] = $request; // add request do form para rebug

        echo json_encode(
            array(
                'blocked' => $response['blocked'],
                'data' => $response['data'] ?? null,
                'request' => $response['request'] ?? null,
                'data_row' => $this->session->userdata('usercomp') == 1 ? ($response['data_row'] ?? null) : null
            )
        );
    }

    public function checkEANpost()
    {
        ob_start();
        if (!in_array('createProduct', $this->permission) && !in_array('updateProducts', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $ean = $this->postClean('ean');
        $product_id = $this->postClean('product_id');
        $store_id = $this->postClean('store_id');
        $verify_ean = true;
        $msg = '';
        $label_ean = $this->lang->line('application_ean');
        $require_ean = ($this->model_settings->getStatusbyName('products_require_ean') == 1);

        if (trim($ean) == '') {
            if ($require_ean) {  // obrigatório mas não preencheu
                $msg = $this->lang->line('application_invalid_ean');
                ob_clean();
                echo json_encode(array('success' => false, 'message' => 'O campo ' . $label_ean . ' é obrigatório.'));
                return;
            } else { // não é obrigatorio e não está preenhcido
                ob_clean();
                echo json_encode(array('success' => true, 'message' => ''));
                return;
            }
        }


        if ($verify_ean) {
            if (!$this->checkEan($ean)) {  // verifica se a formataçao do ean é invalida e já retorna erro. 
                $msg = $this->lang->line('application_ean') . ' ' . $ean . ' inválido.';
                ob_clean();
                echo json_encode(array('success' => false, 'message' => $msg));
                return;
            }
        }

        if ($product_id == '0') {
            $product_id = null;
        }

        $id  = $this->model_products->VerifyEanUnique($ean, $store_id, $product_id);

        if ($id) { // verifico se está repetido 
            $msg =  $this->lang->line('application_ean') . ' ' . $ean . ' já cadastrado. Id=' . $id;
        }
        $ok = ($id === false);
        ob_clean();
        echo json_encode(array('success' => $ok, 'message' => $msg));
        return;
    }

    public function getImagesVariant()
    {
        ob_start();
        $upload_image = $this->postClean('tokenimagem');
        $is_on_bucket = $this->postClean('onBucket');
        $ln1 = array();
        $ln2 = array();
        if (!$is_on_bucket) {
            $fotos = array();
            if (is_dir(FCPATH . 'assets/images/product_image/' . $upload_image)) {
                $fotos = scandir(FCPATH . 'assets/images/product_image/' . $upload_image);
            }
            foreach ($fotos as $foto) {
                if (($foto != ".") && ($foto != "..") && ($foto != "") && (!is_dir(FCPATH . 'assets/images/product_image/' . $upload_image . "/" . $foto))) {
                    array_push($ln1, base_url('assets/images/product_image/' . $upload_image . '/' . $foto));
                    array_push($ln2, ['width' => "120px", 'key' => $upload_image . '/' . $foto]);
                }
            }
        } else {
            // Prefixo de url para buscar a imagem.
            $asset_prefix = "assets/images/product_image/" . $upload_image . "/";

            // Busca as imagens do produto no bucket.
            $listObjects = $this->bucket->listObjects($asset_prefix);
            // Caso tenha dado certo, busca o conteudo.
            if ($listObjects['success']) {
                // Percorre cada elemento e verifica se não é imagem de variação.
                foreach ($listObjects['contents'] as $key => $image_data) {
                    // Busca o url da imagem no bucket.
                    $img_key = $this->bucket->getAssetUrl($image_data['Key'],false);

                    // Recupera apenas o final da URL.
                    $only_key = strstr($image_data['Key'],$upload_image);
                    array_push($ln1, $img_key);
                    array_push($ln2, ['width' => "120px", 'key' => $only_key]);
                }
            }
        }
        ob_end_clean();
        echo json_encode(array('success' => true, 'ln1' => $ln1, 'ln2' => $ln2));
    }

    public function orderImagesVariant()
    {
        if (!in_array('createProduct', $this->permission)  &&  !in_array('updateProducts', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $path =  'assets/images/product_image/';
        $parameters = $this->postClean('params');
        $stacks = $parameters['stack'];

        if (!$this->postClean('onBucket')) {
            $path = FCPATH . $path;
            foreach ($stacks as $key => $stack) {
                $path_this = pathinfo($stack['key']);
                if (file_exists($path . $stack['key'])) {
                    $result = rename($path . $stack['key'], $path . $path_this['dirname'] . "/" . $key . substr($path_this['basename'], 1));
                }
            }
        } else {
            // Percorre cada stack.
            foreach ($stacks as $key => $stack) {
                // Verifica se o objeto existe.
                if ($this->bucket->objectExists($path . $stack['key'])) {
                    // Monta o novo nome da imagem como o nome anterior, alterando a primeira letra para ordenar.
                    $image_name = $key . substr(basename($stack['key']), 1);
                    $dir = dirname($stack['key']);
                    $this->bucket->renameObject($path . $stack['key'], $path . $dir . '/' . $image_name);
                }
            }
        }
    }

    /**
     * Remove uma imagem de um produto no bucket.
     * Utilizado apenas para bucket, se for no local é utilizado um plugin.
     */
    public function removeImageProduct()
    {
        ob_start();
        if (!in_array('createProduct', $this->permission)  &&  !in_array('updateProducts', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $path = 'assets/images/product_image/';
        $key = $this->postClean('key');
        if ($key) {
            $path .= $key;
            $this->bucket->deleteObject($path);
        }
        ob_end_clean();
        echo json_encode(array());
    }

    public static function deleteDir($dirPath)
    {
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public function getProductsAjaxByStore()
    {

        $store = $this->postClean('store',TRUE);

        if (!$this->model_stores->CheckStores($this->session->userdata('usercomp'), $store)) {
            echo json_encode([]);
            die;
        }

        $prods = $this->model_products->getProductsByStore($store, 0, 1000);
        $prods = array_filter($prods, function ($prod) {
            return $prod['status'] != Model_products::DELETED_PRODUCT;
        });
        echo json_encode($prods);
    }

    public function getVariantAjaxByProduct()
    {
        $product = $this->postClean('product',TRUE);

        if (!$this->model_products->verifyProductsOfStore($product)) {
            echo json_encode([]);
            die;
        }

        echo json_encode([
            'product' => $this->model_products->getProductData(0, $product),
            'var' => $this->model_products->getVariants($product)
        ]);
    }

    public function getVariantAjaxByVariant()
    {
        if (!in_array('createOrder', $this->permission) || !in_array(ENVIRONMENT, array('development', 'local'))) {
            redirect('dashboard', 'refresh');
        }

        $product = $this->postClean('product',TRUE);
        $variant = $this->postClean('variant',TRUE);

        echo json_encode($this->model_products->getVariants($product, $variant));
    }
    public function sentOmnilogic()
    {
        $hours = $this->model_settings->getValueIfAtiveByName('hour_for_omnilogic_wait');
        if (!$hours) {
            $this->session->set_flashdata('error', $this->lang->line('messages_omnilogic_err_dont_set_param'));
            redirect('dashboard', 'refresh');
        }
        if (!in_array('manageProductsOmnilogicSent', $this->permission))
            redirect('dashboard', 'refresh');
        $this->session->unset_userdata('ordersfilter');
        unset($this->data['ordersfilter']);
        $this->data['filters'] = $this->model_reports->getFilters('products');
        $this->data['page_title'] = $this->lang->line('application_manage_products_omnilogic_sent');
        $this->render_template('products/sentOmnilogic', $this->data);
    }
	
    public function fetchProductDatasentOmnilogic($isMkt = false)
    {
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];
        $more = [];
        $hours = $this->model_settings->getValueIfAtiveByName('hour_for_omnilogic_wait');
        if (!$hours) {
            $hours = 24;
        } else {
            $hours = intval($hours);
        }
        // dd($postdata['order']);
        $order_dir = $postdata['order'][0]['dir'];
        $order_by = 'p.name';
        switch ($postdata['order'][0]['column']) {
            case 0:
                $order_by = 'c.name';
                break;
            case 1:
                $order_by = 'p.omnilogic_date_sent';
                break;
            case 2:
                $order_by = 's.name';
                break;
            case 3:
                $order_by = 'p.sku';
                break;
            case 4:
                $order_by = 'p.name';
                break;
            case 5:
                $order_by = 'p.id';
                break;
        }
        if (trim($postdata['sku'])) {
            $more['p.sku']=$postdata['sku'];
        }
        if (trim($postdata['product'])) {
            $more['p.name']=$postdata['product'];
            // $more = array_merge($more, ['p.name' => $postdata['product']]);
        }
        if (trim($postdata['buscar_categoria'])) {
            $more['c.name']=$postdata['buscar_categoria'];
            // $more = array_merge($more, ['c.name' => $postdata['buscar_categoria']]);
        }
        if (trim($postdata['buscar_por_loja'])) {
            $more['s.name']=$postdata['buscar_por_loja'];
            // $more = array_merge($more, ['s.name' => $postdata['buscar_por_loja']]);
        }
        $dataAll = $this->model_products->get_product_for_omnilogic($hours, [], $order_by, $order_dir);
        $data = $this->model_products->get_product_for_omnilogic($hours, $more, $order_by, $order_dir);
        $result = [];
        foreach ($data as $key => $value) {
            $actions_buttons = '<a href="' . base_url('products/requestResentOminilog/') . $value['id'] . '"  type="button" class="btn btn-default"><i class="fa fa-upload" aria-hidden="true"></i></a>';
            $result[$key] = array(
                $value['c_name'],
                is_null($value['omnilogic_date_sent']) ? '' : date('d/m/Y', strtotime($value['omnilogic_date_sent'])),
                $value['s_name'],
                $value['sku'],
                $value['name'],
                '<a href="' . base_url('products/update/') . $value['id'] . '">' . $value['id'] . '</a>',
                $actions_buttons
            );
            // <a href="http://conectla.local/products/update/49">Sku10214 </a>
        } // /foreach
        $output = array(
            "draw" => $draw,
            "recordsTotal" => count(($dataAll)),
            "recordsFiltered" => count($data),
            "data" => $result
        );
        // get_instance()->log_data('Products','fetchProductData',print_r($result,true),'E');
        echo json_encode($output);
        //echo json_last_error();

    }
    public function requestResentOminilog($id)
    {
        if (!in_array('manageProductsOmnilogicSent', $this->permission))
            redirect('dashboard', 'refresh');
        $data = ['prd_id' => $id, 'int_to' => 'conectala_NM'];
        $this->log_data('Products.php', 'requestResentOminilog', json_encode($data), 'I');
        $insert = $this->model_queue_products_notify_omnilogic->create($data);
        if ($insert) {
            $this->session->set_flashdata('success', $this->lang->line('messages_omnilogic_success_request'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_omnilogic_err_request'));
        }
        redirect('products/sentOmnilogic', 'refresh');
    }

    public function getVtexIntegrations()
    {
        $integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
        $intto = array();
		$nameintto = array();
        foreach ($integrations as  $integration) {
            if ($integration['active'] == 1) {
            	if ((strpos($integration['auth_data'], 'X_VTEX_API_AppKey') > 0) 
					|| $integration['int_to'] == 'SH'
                    || $integration['int_to'] == 'Zema'
            		|| $integration['int_to'] == 'GPA'
                    || $integration['mkt_type'] == 'wake'){
            		$intto[] = $integration['int_to'];
					$nameintto[$integration['int_to']] = $integration['name'];
				}
			}
		}
        return array('int_to' => $intto, 'name' => $nameintto);
    }


    public function log_products_view($prd_id = null)
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		if (is_null($prd_id)) {
			redirect('dashboard', 'refresh');
		}
		$this->data['prd_id'] = $prd_id;
        $this->render_template('products/log_products_view', $this->data);
    }
	
	public function fetchLogProductsData()
    {

        $prd_id = $this->postClean('prd_id',TRUE);
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $busca = $postdata['search'];

		$procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $procura .= " AND ( id like '%" . $busca['value'] . "%' OR username like '%" . $busca['value'] . "%' OR change like '%" . $busca['value'] . "%' OR date_update like '%" . $busca['value'] . "%')";
            }
        }

        if (trim($postdata['username'])) {
            $username= '\'%' . $postdata['username'] . '%\'';
            $procura .= " AND username LIKE $username ";
        }
		
		if (trim($postdata['dateupdate'])) {
            $dateupdate = '\'%' . $postdata['dateupdate'] . '%\'';
            $procura .= " AND date_update LIKE $dateupdate ";
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
            	$direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('date_update', 'id', 'CAST(price AS DECIMAL(12,2))', 'CAST(qty AS UNSIGNED)', 'username',  );
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        $data = $this->model_log_products->getLogProductsData($prd_id, $procura, $sOrder, $ini, $length);
		$rectotal = $this->model_log_products->getLogProductsDataCount($prd_id);
		$filtered = $rectotal;
		if ($procura !='') {
			$filtered = $this->model_log_products->getLogProductsDataCount($prd_id, $procura);
		}
		
        foreach ($data as $key => $value) {
            $result[$key] = array(
            	$value['date_update'] ,
            	$value['id'] ,
                $this->formatprice($value['price']),
                $value['qty'] ,
                $value['username'] ,
                $value['change'] ,
                
            );
        } 
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $rectotal,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
		
        echo json_encode($output);

    }

	public function sendToMarketplace()
    {
		if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $prd_id = $this->postClean('prd_id',TRUE);
        $int_to = $this->postClean('int_to',TRUE);

        $data = array(
            'status' => 0,
            'prd_id' => $prd_id,
            'int_to' => $int_to,
        );

        $saved = $this->model_queue_products_marketplace->create($data);
		
		$response_array['status'] = 'error';
		if ($saved) {
			$response_array['status'] = 'success';
		}
		echo json_encode($response_array);
    }
	
	public function log_integration_marketplace($int_to=null , $prd_id = null, $offset = 0)
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		if (is_null($prd_id) || is_null($int_to)) {
			redirect('dashboard', 'refresh');
		}
		$limit = 10; 
		if ((int)$offset < 0) $offset=0;
		$this->data['int_to'] = $int_to;
		$this->data['prd_id'] = $prd_id;
		
		$logs = $this->model_log_integration_product_marketplace->getLogByIntToPrdId($int_to, $prd_id, $offset, $limit);
		
		foreach($logs as $key => $log) {
			$logs[$key]['sentjson'] = $this->prettyPrint($log['sent']);
			$logs[$key]['responsejson'] = $this->prettyPrint($log['response']);
            if (is_null(json_decode($log['sent']))) {
                $logs[$key]['sent'] = json_encode(array($log['sent']));
            }
            if (is_null(json_decode($log['response']))) {
                $logs[$key]['response'] = json_encode(array($log['response']));
            }
		}
		$this->data['logs'] = $logs;
		$bb = (int)$offset - $limit;
		$ff = (int)$offset + $limit;
		$this->data['btnRec'] = (($offset == 0) || ($bb < 0)) ? false :  base_url('products/log_integration_marketplace').'/'.$int_to.'/'.$prd_id.'/'.$bb;
		$this->data['btnFwd'] = (count($logs) < $limit) ? false :  base_url('products/log_integration_marketplace').'/'.$int_to.'/'.$prd_id.'/'.$ff.'';

        $this->render_template('products/log_integration_marketplace', $this->data);
    }
	
	function prettyPrint( $json )
	{
	    $result = '';
	    $level = 0;
	    $in_quotes = false;
	    $in_escape = false;
	    $ends_line_level = NULL;
	    $json_length = strlen( $json );
	
	    for( $i = 0; $i < $json_length; $i++ ) {
	        $char = $json[$i];
	        $new_line_level = NULL;
	        $post = "";
	        if( $ends_line_level !== NULL ) {
	            $new_line_level = $ends_line_level;
	            $ends_line_level = NULL;
	        }
	        if ( $in_escape ) {
	            $in_escape = false;
	        } else if( $char === '"' ) {
	            $in_quotes = !$in_quotes;
	        } else if( ! $in_quotes ) {
	            switch( $char ) {
	                case '}': case ']':
	                    $level--;
	                    $ends_line_level = NULL;
	                    $new_line_level = $level;
	                    break;
	
	                case '{': case '[':
	                    $level++;
	                case ',':
	                    $ends_line_level = $level;
	                    break;
	
	                case ':':
	                    $post = "&nbsp;";
	                    break;
	
	                case " ": case "\t": case "\n": case "\r":
	                    $char = "";
	                    $ends_line_level = $new_line_level;
	                    $new_line_level = NULL;
	                    break;
	            }
	        } else if ( $char === '\\' ) {
	            $in_escape = true;
	        }
	        if( $new_line_level !== NULL ) {
	            $result .= "<br>".str_repeat( "&nbsp;&nbsp;&nbsp;&nbsp;", $new_line_level );
	        }
	        $result .= $char.$post;
	    }
	
	    return $result;
	}

    public function SendOmnilogicEnrichCategories()
    {
        if (!in_array('enrichProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $product = $this->postClean('product',TRUE);
        $category = $this->postClean('category',TRUE);

        $query = $this->model_queue_products_notify_omnilogic->create(['prd_id' => $product]);

        if($query) {
            $this->session->set_flashdata('success', $this->lang->line('messages_send_omnilogic_enrich_categories_success'));
            return true;
        }

        $this->session->set_flashdata('error', $this->lang->line('messages_send_omnilogic_enrich_categories_error'));
        return false;
    }

    public function updateStatus($products = [])
    {
        $post_data = !empty($products) ? $products : json_decode($this->postClean('data', true), true);
        $products = $post_data['products'] ?? [];
        $productsIds = [];
        foreach ($products as $product) {
            $product_id = $product['id'];
            $product_status = $product['status'];
            if (!in_array($product_status, [
                Model_products::ACTIVE_PRODUCT,
                Model_products::INACTIVE_PRODUCT
            ])) {
                continue;
            }
            $productsIds[$product_status][] = $product_id;
        }

        $total = count($products);
        $updated = 0;
        $username = $this->session->userdata('email');
        try {
            foreach ($productsIds as $status => $productsId) {
                $updated += $this->model_products->updateStatus($productsId, $status);
                $log_products_array = array (
                    'prd_id' 	=> $productsId[0], 
                    'qty' 		=> '-',
                    'price' 	=> '-',
                    'username' 	=> $username,
                    'change' 	=> 'Alterado: new data\n{ status:"'.$status.'"}'
                ); 
                if(isset($log_products_array)){
                    $this->model_log_products->create($log_products_array); // grava log na model_log_products, para ser visualizado na tela log_products_view
                }
            }

            if ($total != $updated) {
                header("HTTP/1.1 420");
                echo json_encode([
                    'errors' => [
                        str_replace(
                        '{total}',
                        $total,
                        str_replace(
                            '{updated}',
                            $updated,
                            $this->lang->line('application_updated_status_products')
                        )
                        )
                    ],
                ]);
                return;
            }
        } catch (Throwable $e) {
            header("HTTP/1.1 500");
            echo json_encode(['errors' => [$this->lang->line('application_system_error')]]);
            return;
        }

        echo json_encode([
            'total' => $total,
            'updated' => $updated,
            'message' => $this->lang->line('application_products_status_updated')
        ]);
        return;
    }

    public function moveToTrash($products = [])
    {
        if (!in_array('moveProdTrash', $this->permission)) {
            header("HTTP/1.1 420");
            echo json_encode([
                'errors' => [$this->lang->line('messages_not_permission_move_to_trash')]
            ]);
            return;
        }

        $post_data = !empty($products) ? $products : json_decode($this->postClean('data', true), true);
        $products = $post_data['products'] ?? [];

        $retorno = $this->deleteProduct->moveToTrash($products);
        if (isset($retorno['errors'])) {
            header("HTTP/1.1 420");
        } else {
            $this->session->set_flashdata('success', $this->lang->line('message_products_moved_to_trash'));
            $retorno['redirect'] = base_url() . 'products';
        }
        echo json_encode($retorno);
        return;
    }

    protected function setProdFormData($prodId)
    {
        $product_data = $this->model_products->getProductData(0, $prodId);
        $this->data['sellercenter_name'] = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        $this->data['product_length_name'] = $this->product_length_name;
        $this->data['product_length_description'] = $this->product_length_description;
        $this->data['product_length_sku'] = $this->product_length_sku;
        $this->data['displayPriceByVariation'] = $this->model_settings->getStatusbyName('price_variation');
        $this->data['disableBrandCreationbySeller'] = $this->model_settings->getValueIfAtiveByName('disable_brand_creation_by_seller');
        $this->data['usergroup'] = $this->session->userdata('group_id');
        $this->data['storeCanUpdateProduct'] = true;
        $this->data['idCategory_id'] = onlyNumbers($product_data['category_id']);

        $this->data['name_cat'] = $this->model_category->getcategoryName($this->data['idCategory_id']);
        if ($this->model_category->getcategoryBlock($this->data['idCategory_id']) == 1) {
            $this->data['campoBlock'] = $campoBlock = "readonly";
            $this->data['days_cross_docking'] = $this->model_category->getcategoryDays_cross_docking($this->data['idCategory_id']);
        } else {
            $this->data['campoBlock'] = $campoBlock = "";
        }

        $this->data['origins'] = [
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        ];

        $attribute_data = $this->model_attributes->getActiveAttributeData('products');
        $attributes_final_data = [];
        foreach ($attribute_data as $k => $v) {
            $attributes_final_data[$k]['attribute_data'] = $v;
            $value = $this->model_attributes->getAttributeValueData($v['id']);
            $attributes_final_data[$k]['attribute_value'] = $value;
        }

        $this->data['attributes'] = $attributes_final_data;
        $this->data['brands'] = $this->model_brands->getActiveBrands();
        $this->data['category'] = $this->model_category->getActiveCategroy();

        $this->data['stores'] = $this->model_stores->getMyCompanyStores($product_data['company_id']);

        $this->data['notAdmin'] = !($this->data['usercomp'] == '1');
        $this->data['product_data'] = $this->security->xss_clean($product_data);

        $this->data['product_data']['with_variation'] = 0;
        if ($product_data['has_variants'] != "") {
            $this->data['product_data']['with_variation'] = 1;
            $product_variants = $this->model_products->getProductVariants($prodId, $product_data['has_variants']);
        } else {
            $product_variants['numvars'] = 0;
        }

        $this->data['product_data']['id'] = 0;
        $this->data['product_data']['copy_prod_data'] = 1;
        $this->data['product_data']['status'] = Model_products::ACTIVE_PRODUCT;
        //$this->data['product_data']['sku'] = DeleteProduct::normalizeTrashSku($this->data['product_data']['sku']);
        $this->data['product_data']['sku'] = '';

        // Busca a chave antiga da imagem, se o produto estava no bucket e o url original do produto.
        $image_dir = 'assets/images/product_image/';
        $old_image_key = $this->data['product_data']['image'];
        $onBucket = $this->data['product_data']['is_on_bucket'];

        // Neste caso apenas precisamos da base para a chave no bucket.
        $originalProdPath = $onBucket ? ($image_dir . $old_image_key) : UploadProducts::getImagePath($old_image_key);

        // Cria um novo GUID para o diretório.
        $newProdPath = $this->getGUID(false);
        
        // Gera o novo path.
        $newImagePath = $image_dir . $newProdPath;
        if (!$onBucket) {

            // Cria uma transferência para migrar os arquivos do disco para o bucket.
            $manager = $this->bucket->createTransfer($originalProdPath, $newImagePath);

            // Realiza a transerência.
            try {
                $manager->transfer();
                $this->data['product_data']['is_on_bucket'] = 1;
            } catch (Exception $e) {
                // Nada acontece, apenas não insere como se estivesse no bucket.
            }

            $this->data['product_data']['principal_image'] = str_replace(
                $this->data['product_data']['image'],
                $newProdPath,
                $this->data['product_data']['principal_image']
            );

            // Busca o URL das imagens do bucket.
            $newImages = $this->bucket->listObjectsUrl($newImagePath);

            if ($newImages['success']) {
                // Percorre cada imagem e adiciona o URL dela.
                foreach ($newImages['contents'] as $key => $value) {
                    $product_data['images']['files'][] = $value['Key'];
                }
            }
        } else {

            // Estamos passando os dois paths a partir da paste base de imagens, lá será inserido o sellercenter e feita a cópia.
            $this->bucket->copyMany($originalProdPath, $newImagePath);

            // Altera a chave da imagem principal.
            $this->data['product_data']['principal_image'] = str_replace(
                $this->data['product_data']['image'],
                $newProdPath,
                $this->data['product_data']['principal_image']
            );

            // Busca o URL das imagens do bucket.
            $newImages = $this->bucket->listObjectsUrl($newImagePath);

            if ($newImages['success']) {
                // Percorre cada imagem e adiciona o URL dela.
                foreach ($newImages['contents'] as $key => $value) {
                    $product_data['images']['files'][] = $value['Key'];
                }
            }
        }
        $situacao = !empty($this->data['product_data']['sku']) ? 2 : 1;
        $situacao = $situacao == 2 && !empty(json_decode($product_data['category_id'])) ? 2 : 1;
        $situacao = $situacao == 2 && !empty(json_decode($product_data['brand_id'])) ? 2 : 1;
        $situacao = $situacao == 2 && !empty($product_data['images']) ? 2 : 1;
        foreach ($product_variants as $k => $variant) {
            if(!isset($variant['id'])) {continue;}
            $product_variants[$k]['id'] = 0;
            $product_variants[$k]['qty'] = 0;
            $product_variants[$k]['status'] = Model_products::ACTIVE_PRODUCT;

            $product_variants[$k]['sku'] = '';
            $situacao = $situacao == 2 && !empty($product_variants[$k]['sku']) ? 2 : $situacao;
        }
        $this->data['product_data']['image'] = $newProdPath;
        $this->data['product_data']['image_original'] = $originalProdPath;
        $this->data['product_data']['image_new'] = $newImagePath;
        $this->data['product_variants'] = $product_variants;

        $this->data['product_data']['situacao'] = $situacao;
        $this->data['product_data']['competitiveness'] = false;
        $this->data['errors_transformation'] = [];
        $this->data['products_marketplace'] = [];
        $this->data['campaigns'] = [];
        $this->data['mykits'] = [];
        $this->data['myorders'] = [];
        $this->data['variacaotamanho'] = [];
        $this->data['variacaocor'] = [];
        $this->data['variacaovoltagem'] = [];
        $this->data['variacaosabor'] = [];
        $this->data['variacaoquantidade'] = [];
        $this->data['variacaosku'] = [];
        $this->data['variacaoean'] = [];
        $this->data['variacaoprice'] = [];
        $this->data['variacaoimagem'] = [];
        $this->data['variacaolistprice'] = [];
        $this->data['require_ean'] = ($this->model_settings->getStatusbyName('products_require_ean') == 1);
        $this->data['invalid_ean'] = $this->lang->line('application_ean') . ' inválido';
        $this->data['flavor_active'] = $this->model_settings->getFlavorActive();
        $this->data['degree_active'] = $this->model_settings->getDegreeActive();
        $this->data['side_active'] = $this->model_settings->getSideActive();
        if(empty($this->data['flavor_active'] ?? '')){
            $this->data['flavor_active'] = null;
        }
        if(empty($this->data['degree_active'] ?? '')){
            $this->data['degree_active'] = null;
        }
        if(empty($this->data['side_active'] ?? '')){
            $this->data['side_active'] = null;
        }

        $this->data['integrations'] = [];
    }

    public function copy($prodId)
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }

        $this->setProdFormData($prodId);


        $product_data = $this->model_products->getProductData(0, $prodId);
        $this->data['idCategory_id'] = onlyNumbers($product_data['category_id']);
        $this->data['days_cross_docking'] = $this->model_category->getcategoryDays_cross_docking($this->data['idCategory_id']); //seleciono na model


        if ($this->data['product_data']['situacao'] == 1) {
            $this->session->set_flashdata('error', $this->lang->line('messages_product_missing_information'));
        } else {
            $this->session->set_flashdata('success', $this->lang->line('messages_complete_registration'));
        }
        $this->render_template($this->editProductView, $this->data);
    }

    public function fetchProductDataNew()
    {
        if (!in_array('viewProduct', $this->permission)) {
            echo json_encode(['error' => $this->lang->line('application_dont_permission')]);
            return;
        }
        $this->editablePrice = $this->canModifyPrice();
        $this->percPriceCatalog = $this->hasPercPriceCatalog();

        $params = $this->postClean(NULL, TRUE);
        list($prodResults, $nroRegisters) = $this->fetchProductListByDataTableParams($params);
        $products = array_map(function ($prod) {
            $prod = $this->wrapperSkuToTableList($prod);
            $prod = $this->wrapperPriceToTableList($prod);
            $prod = $this->wrapperStockToTableList($prod);
            $prod = $this->wrapperMarketPlaceToTableList($prod);
            $prod = $this->wrapperImageToTableList($prod);
            $prod = $this->wrapperStatusAndSituationToTableList($prod);
            //$prod = $this->wrapperCollectionToTableList($prod);
            return $prod;
        }, $prodResults);

        $totalPages = $nroRegisters > 0 ? (int)($nroRegisters / (int)$params['length']) : 0;
        $page = (int)($params['start'] > 0 ? ((int)$params['length'] / (int)($params['start'])) : 0);
        $return = [
            'draw' => (int)$params['draw'],
            'data' => $products,
            'pagination' => [
                'page' => ceil($page),
                'per_page' => (int)$params['length'],
                'total_pages' => $totalPages,
                'filtered_items' => $nroRegisters,
                'total_items' => $nroRegisters,
            ],
            'recordsTotal' => $nroRegisters,
            'recordsFiltered' => $nroRegisters,
        ];
        echo json_encode($return);
    }

    protected function fetchProductListByDataTableParams(array $params = []): array
    {
        $limit = $params['length'] > 0 ? $params['length'] : 20;
        $offset = max($params['start'], 0);
        if (isset($params['search']) && is_array($params['search'])) {
            $params['search'] = current(array_values($params['search']));
        }

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $params['company_id'] = $this->data['usercomp'];
            } else {
                $params['store_id'] = $this->data['userstore'];
            }
        }

        $orderBy = [];
        if (!empty($params['order'] ?? [])) {
            $field = $params['columns'][$params['order'][0]['column']]['name'] ?? null;
            $field = !empty($field) ? $field : 'name';
            $direction = strtoupper($params['order'][0]['dir'] ?? 'asc');
            $orderBy = [
                $field => $direction == 'ASC' ? 'ASC' : 'DESC'
            ];
        }
        if (trim($params['with_stock'] ?? '') == '') {
            unset($params['with_stock']);
        }
        if (trim($params['is_kit'] ?? '') == '') {
            unset($params['is_kit']);
        }
        $nroRegisters = $this->model_products->countGetProductsByCriteria($params);
        $prodResults = $this->model_products->getProductsToDisplayByCriteria($params, $offset, $limit, $orderBy);
        return [$prodResults, $nroRegisters];
    }

    protected function canModifyPrice(): bool
    {
        if (in_array('disablePrice', $this->permission)) {
            return false;
        }
        return !((bool)$this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price'));
    }

    protected function hasPercPriceCatalog(): bool
    {
        $percPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_percentage_update_price_catalog');
        $daysPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_days_update_price_catalog');
        if (($daysPriceCatalogSetting['status'] ?? 2) == 2 || ($percPriceCatalogSetting['status'] ?? 2) == 2) {
            return false;
        }
        return (bool)$percPriceCatalogSetting['value'];
    }

    protected function wrapperSkuToTableList($prod)
    {
        $url = base_url("products/update/{$prod['id']}");
        $isKit = $prod['is_kit'] == 1 ? '<br><span class="label label-warning">Kit</span>' : '';
        $prod['sku'] = "<a href='{$url}'>{$prod['sku']}</a>{$isKit}";
        return $prod;
    }

    protected function wrapperPriceToTableList($prod)
    {
        $colorAlertPrice = '';
        $directionAlertPrice = '';
        if ($this->percPriceCatalog && $prod['qty'] > 0) {
            $productWithChangedPrice = $this->model_products_catalog->getProductWithChangedPrice($prod['product_catalog_id']);
            if ($productWithChangedPrice) {
                $colorAlertPrice = 'style="color: red"';
                $directionAlertPrice = $productWithChangedPrice['old_price'] > $productWithChangedPrice['new_price']
                    ? '&nbsp;<i class="fas fa-arrow-down" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu redução."></i>'
                    : '&nbsp;<i class="fas fa-arrow-up" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu aumento."></i>';
            }
        }
        $readyOnly = $this->editablePrice ? '' : 'disabled';
        $viewPrice = number_format($prod['price'], 2, ',', '.');
        $currency = $this->session->userdata('currency') ?? 'R$';
        $prod['price'] = "<div class='input-group'>
            <span class=\"input-group-addon\" id=\"basic-addon1\">{$currency}</span>
            <input type='text' class='form-control text-center' {$readyOnly} onchange='this.value=changePrice({$prod['id']}, {$prod['price']}, this.value, this)' onfocus='this.value={$prod['price']}' onKeyUp='this.value=formatPrice(this.value)' value='{$viewPrice}' size='7' {$colorAlertPrice} " . ($prod['is_kit'] != 1 ? '' : 'disabled') . "/>{$directionAlertPrice}
        </div>";
        return $prod;
    }

    protected function wrapperStockToTableList($prod)
    {
        $qtyStatus = '';
        if ($prod['qty'] <= 10 && $prod['qty'] > 0) {
            $qtyStatus = '<span class="label label-warning">' . lang('application_low') . '</span>';
        } else if ($prod['qty'] <= 0) {
            $qtyStatus = '<span class="label label-danger">' . lang('application_out_stock') . '</span>';
        }
        $readOnly = (trim($prod['has_variants']) ? 'disabled data-toggle="tooltip" data-placement="top" title="Produto com variação" data-container="body"' : '');
        $prod['stock'] = "<input type='text' class='form-control text-center' {$readOnly} onchange='changeQty({$prod['id']}, {$prod['qty']}, this.value)' onKeyPress='return digitos(event, this)' value='{$prod['qty']}' size='3' /> {$qtyStatus}";

        return $prod;
    }

    protected function wrapperMarketPlaceToTableList($prod)
    {
        if (empty($prod['marketplaces'])) return $prod;

        $marketplaces = [];
        $integrations = $this->model_integrations->getIntegrationsProduct($prod['id'], 1);
        foreach ($integrations ?? [] as $v) {
            if ($v['rule']) {
                $ruleBlock = [];
                $ruleId = is_numeric($v['rule']) ? (array)$v['rule'] : json_decode($v['rule']);
                foreach ($ruleId as $ruleBlockId) {
                    $rule = $this->model_blacklist_words->getWordById($ruleBlockId);
                    if ($rule) {
                        $ruleBlock[] = strtoupper(str_replace('"', "'", $rule['sentence']));
                    }
                }
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" data-html="true" title="' . implode('<br><br>', $ruleBlock) . '">' . $v['int_to'] . '</span>';
            } elseif ($v['errors'] == 1) {
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_errors_tranformation'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 0) {
                $marketplaces[] = '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 1) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_waiting_to_be_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 2) {
                $marketplaces[] = '<span class="label label-primary" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 11) {
                $over = $this->model_integrations->getPrdBestPrice($prod['EAN']);
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_higher_price'), 'UTF-8') . ' (' . $over . ')">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 12) {
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_higher_price'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 13) {
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_higher_price'), 'UTF-8') . ' ">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 14) {
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_release'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 20) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 21) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 22) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 23) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 24) {
                $marketplaces[] = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 90) {
                $marketplaces[] = '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_inactive'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 91) {
                $marketplaces[] = '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_no_logistics'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } elseif ($v['status_int'] == 99) {
                $marketplaces[] = '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            } else {
                $marketplaces[] = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper(lang('application_product_out_of_stock'), 'UTF-8') . '">' . $v['int_to'] . '</span>';
            }
        }

        $prod['marketplaces'] = implode('&nbsp;', $marketplaces);
        return $prod;
    }

    protected function wrapperImageToTableList($prod)
    {
        $prod['image'] = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($prod['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
        if ((!is_null($prod['principal_image'])) && !empty(trim($prod['principal_image']))) {
            $prod['image'] = '<img src="' . $prod['principal_image'] . '" alt="' . utf8_encode(substr($prod['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
        }
        return $prod;
    }

    protected function wrapperStatusAndSituationToTableList($prod)
    {
        $langStatus = ($prod['status'] ?? 0) == Model_products::ACTIVE_PRODUCT ? 'active' : (($prod['status'] ?? 0) == Model_products::BLOCKED_PRODUCT ? 'under_analysis' : 'inactive');
        $statusDesc = lang("application_{$langStatus}");

        $stClass = ($prod['situacao'] ?? 0) == Model_products::INCOMPLETE_SITUATION ? 'warning' : 'success';
        $stAttr = '';
        if (($prod['status'] ?? 0) == Model_products::BLOCKED_PRODUCT) {
            $stClass = 'danger';
            $stAttr = 'data-toggle="tooltip" title="' . lang('application_product_has_prohibited_word') . '"';
        }
        $prod['status'] = "<span class=\"label label-{$stClass}\" {$stAttr}>{$statusDesc}</span>";
        $langSituation = ($prod['situacao'] ?? 0) == Model_products::COMPLETE_SITUATION ? 'complete' : 'incomplete';
        $situationDesc = lang("application_{$langSituation}");
        $stClass = ($prod['situacao'] ?? 0) == Model_products::COMPLETE_SITUATION ? 'success' : 'danger';
        $prod['situation'] = "<span class=\"label label-{$stClass}\">{$situationDesc}</span>";
        return $prod;
    }

    protected function wrapperCollectionToTableList($prod)
    {
        if ($prod['collections'] ?? '') {
            $collectionIds = explode(',', str_replace(['[', ']', '"'], '', $prod['collections']));
            $colections = $this->model_products->getCatalogColections($collectionIds);
            $prod['collections'] = implode(', ', array_column($colections, 'name'));
        }
        return $prod;
    }

    public function saveImageProduct(): CI_Output
    {
        if (!$this->postClean('onBucket')) {
            $targetDir = getSourcePath('assets/images/product_image');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0775);
            }
            if (isset($_FILES['fileBlob']) && isset($_POST['uploadToken'])) {
                $token = $this->postClean('uploadToken');
                $splitedToken = explode("/", $token);
                if (count($splitedToken) > 1) {
                    $targetDir .= "/" . $splitedToken[0];
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0775);
                    }
                    $targetDir .= "/" . $splitedToken[1];
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0775);
                    }
                } else {
                    $targetDir .= "/" . $token;
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0775);
                    }
                }

                $file       = $_FILES['fileBlob']['tmp_name'];  // the path for the uploaded file chunk
                $index      =  $this->postClean('chunkIndex');          // the current file chunk index

                $upload = $this->uploadproducts->sendImageForUrl($targetDir, $file, true);
                if ($upload['success']) {
                    $zoomUrl = base_url('/assets/images/product_image/' . $token . "/" . $upload['path']);
                    $fileSize = filesize(getSourcePath('assets/images/product_image/' . $token . "/" . $upload['path']));
                    return $this->output->set_output(json_encode([
                        'chunkIndex'            => $index,         // the chunk index processed
                        'initialPreview'        => $zoomUrl, // the thumbnail preview data (e.g. image)
                        'initialPreviewConfig'  => [
                            [
                                'type'      => 'image',                         // check previewTypes (set it to 'other' if you want no content preview)
                                'caption'   => $upload['path'],                 // caption
                                'key'       => $token . "/" . $upload['path'],   // keys for deleting/reorganizing preview
                                'fileId'    => $upload['path'],                 // file identifier
                                'size'      => $fileSize,                       // file size
                                'zoomData'  => $zoomUrl,                        // separate larger zoom data
                                'token'     => $token,                            // token (file subdir)
                            ]
                        ],
                        'append' => true
                    ]));
                } else {
                    return $this->output->set_output(json_encode([
                        'error'     => $upload['data'],
                        'errorkeys' => [$index],
                        'details'   => $upload
                    ]));
                }
            }
        } else {
            // Diretório base para a imagem.
            $targetDir = 'assets/images/product_image';

            // Verifica se o uploadToken e o arquivo estão setados.
            if (isset($_FILES['fileBlob']) && isset($_POST['uploadToken'])) {
                // Busca o token.
                $token = $this->postClean('uploadToken');
                $splitedToken = explode("/", $token);
                // Quebra o token em partes para criar o diretório de destino.
                if (count($splitedToken) > 1) {
                    $targetDir .= "/" . $splitedToken[0];
                    $targetDir .= "/" . $splitedToken[1];
                } else {
                    $targetDir .= "/" . $token;
                }
                $file       = $_FILES['fileBlob']['tmp_name'];  // the path for the uploaded file chunk
                $index      =  $this->postClean('chunkIndex');          // the current file chunk index

                // Realiza o envio da imagem para o bucket.
                $upload = $this->uploadproducts->sendImageForBucket($targetDir, $file, true);
                if ($upload['success']) {
                    // O retorno é diferente da função de envio de imagem por URL, retornando o path completo da imagem no bucket.
                    $zoomUrl = $upload['path'];
                    $fileSize = $this->bucket->getObjectSize($targetDir.'/'.$upload['key']);
                    $imageName = basename($upload['path']);
                    return $this->output->set_output(json_encode([
                        'chunkIndex'            => $index,         // the chunk index processed
                        'initialPreview'        => $zoomUrl, // the thumbnail preview data (e.g. image)
                        'initialPreviewConfig'  => [
                            [
                                'type'      => 'image',                         // check previewTypes (set it to 'other' if you want no content preview)
                                'caption'   => $imageName,                 // caption
                                'key'       => $token . "/" . $imageName,   // keys for deleting/reorganizing preview
                                'fileId'    => $imageName,                 // file identifier
                                'size'      => $fileSize['size'],                       // file size
                                'zoomData'  => $zoomUrl,                        // separate larger zoom data
                                'token'     => $token,                            // token (file subdir)
                            ]
                        ],
                        'append' => true
                    ]));
                } else {
                    return $this->output->set_output(json_encode([
                            'error'     => $upload['data'],
                            'errorkeys' => [$index],
                            'details'   => $upload
                        ]));
                }
            }
        }


        return $this->output->set_output(json_encode([
            'error' => 'No file found'
        ]));
    }

    public function viewFast()
    {
        @$request = (array) json_decode($this->input->get('listview', TRUE)[0]);
        if(!$request){
            redirect('products/productsApprove', 'refresh');
        }
        $this->data['listview2'] = $request;

        $prev = $this->input->get('prev',TRUE);
        $next = $this->input->get('next',TRUE);

        $pagination = 0;
        if($prev && !$next){
            $pagination = $prev-1;
        } elseif (!$prev && $next) {
            $pagination = $next;
        } elseif (!$prev && !$next) {
            $pagination = 0;
        }

        $procura = '';

        $product = $request['productsList'][$pagination];
        $total = count($request['productsList']);

        $result = explode("|", $product);
        $procura .= " AND pi.prd_id = $result[2]";

        $this->data['page_title'] = 'Visualização rápida';
        $products = $this->model_integrations->getProductsRepare($procura);
        $this->data['products'] = $products;
        $this->data['products']['pagination'] = $pagination;
        $this->data['products']['totalOfProducts'] = $total;
        $this->data['category_name'] = $this->model_category->getCategoryData(onlyNumbers($products['category_id']))['name'] ?? '';
        $this->data['brand_name'] = $this->model_brands->getBrandData(onlyNumbers($products['brand_id']))['name'] ?? '';
        $this->data['product_is_publised'] = true;
        $int_to = $products['int_to'];
        $prd_id = $products['prd_id'];
        $variations = $this->model_products->getVariantsByProd_id($prd_id);
        $integrations_settings = $this->model_integrations_settings->getIntegrationSettingsbyIntto($int_to);
        $this->data['setting_validate_completed_sku_marketplace'] = $this->model_settings->getValueIfAtiveByName('validate_completed_sku_marketplace') && $integrations_settings['skumkt_default'] == 'sequential_id';

        $skus_seller_to_marketplace = array();
        if ($this->data['setting_validate_completed_sku_marketplace']) {
            $this->data['product_is_publised'] = $this->model_integrations->checkIfExistProductPublishedByPrdAndIntto($prd_id, $int_to);
            $product = $this->model_products->getProductData(0, $prd_id);

            $sequential_skumkts = $this->model_control_sequential_skumkts->getByPrdVariantIntTo($prd_id, null, $int_to);
            $skumkt = '';
            if (!$sequential_skumkts) {
                $sync_skuseller_skumkt = $this->model_control_sync_skuseller_skumkt->getByStoreSkuIntTo($product['store_id'], $products['sku'], $int_to);
                if ($sync_skuseller_skumkt) {
                    $skumkt = $sync_skuseller_skumkt['skumkt'];
                }
            } else {
                $skumkt = $sequential_skumkts['id'];
            }

            $skus_seller_to_marketplace[$products['sku']] = $skumkt;

            foreach ($variations as $variation) {
                $sequential_skumkts = $this->model_control_sequential_skumkts->getByPrdVariantIntTo($prd_id, $variation['variant'], $int_to);
                $skumkt = '';
                if (!$sequential_skumkts) {
                    $sync_skuseller_skumkt = $this->model_control_sync_skuseller_skumkt->getByStoreSkuIntTo($product['store_id'], $variation['sku'], $int_to);
                    if ($sync_skuseller_skumkt) {
                        $skumkt = $sync_skuseller_skumkt['skumkt'];
                    }
                } else {
                    $skumkt = $sequential_skumkts['id'];
                }

                $skus_seller_to_marketplace[$variation['sku']] = $skumkt;
            }
        }
        $this->data['skus_seller_to_marketplace'] = $skus_seller_to_marketplace;
        $this->data['variations'] = $variations;

        $this->render_template('products/fast_view', $this->data);
    }

    public function updateDataViewFast(): CI_Output
    {
        $sku_mkt_prd = $this->postClean('sku_mkt_prd');
        $sku_mkt_var = $this->postClean('sku_mkt_var');
        $prd_id      = $this->postClean('prd_id');
        $int_to      = $this->postClean('int_to');
        $product     = $this->model_products->getProductData(0, $prd_id);

        $product_is_publised = $this->model_integrations->checkIfExistProductPublishedByPrdAndIntto($prd_id, $int_to);

        $integrations_settings= $this->model_integrations_settings->getIntegrationSettingsbyIntto($int_to);

        if (empty($integrations_settings)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => "Não localizado a configuração do Marketplace $int_to"
            )));
        }

        if ($integrations_settings['skumkt_default'] !== 'sequential_id') {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => "Configuração do marketplace não está definida com padrão sequêncial"
            )));
        }

        if ($product_is_publised) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => 'Produto já publicado, não pode ser alterado.'
            )));
        }

        if (!is_null($sku_mkt_prd)) {
            if (!is_numeric($sku_mkt_prd)) {
                return $this->output->set_output(json_encode(array(
                    'success' => false,
                    'message' => "O sku do marketplace deve ser numérico."
                )));
            }

            if ($sku_mkt_prd >= $integrations_settings['skumkt_sequential_initial_value']) {
                return $this->output->set_output(json_encode(array(
                    'success' => false,
                    'message' => "O sku do marketplace não deve ser maior ou igual que o valor inicial definido na configuração do marketplace. Valor inicial: {$integrations_settings['skumkt_sequential_initial_value']}"
                )));
            }

            if ($this->model_control_sync_skuseller_skumkt->checkSkuAvaibility($product['store_id'], $product['sku'], $int_to, $sku_mkt_prd)) {
                return $this->output->set_output(json_encode(array(
                    'success' => false,
                    'message' => "O sku do marketplace já está em uso."
                )));
            }

            $this->model_control_sync_skuseller_skumkt->remove($product['store_id'], $product['sku'], $int_to);

            $this->model_control_sync_skuseller_skumkt->create(array(
                'store_id'   => $product['store_id'],
                'company_id' => $product['company_id'],
                'skuseller'  => $product['sku'],
                'skumkt'     => $sku_mkt_prd,
                'int_to'     => $int_to
            ));
        } else if (!is_null($sku_mkt_var)) {
            foreach ($sku_mkt_var as $variant => $skumkt_var) {
                if (!is_numeric($skumkt_var)) {
                    return $this->output->set_output(json_encode(array(
                        'success' => false,
                        'message' => "O sku do marketplace[#$variant: $skumkt_var] deve ser numérico."
                    )));
                }

                if ($skumkt_var >= $integrations_settings['skumkt_sequential_initial_value']) {
                    return $this->output->set_output(json_encode(array(
                        'success' => false,
                        'message' => "O sku do marketplace[#$variant: $skumkt_var] não deve ser maior ou igual que o valor inicial definido na configuração do marketplace. Valor inicial: {$integrations_settings['skumkt_sequential_initial_value']}"
                    )));
                }

                $product_var = $this->model_products->getVariants($prd_id,$variant);
                if ($product_var) {
                    if ($this->model_control_sync_skuseller_skumkt->checkSkuAvaibility($product['store_id'], $product_var['sku'], $int_to, $skumkt_var)) {
                        return $this->output->set_output(json_encode(array(
                            'success' => false,
                            'message' => "O sku do marketplace[#$variant: $skumkt_var] já está em uso."
                        )));
                    }
                    
                    $this->model_control_sync_skuseller_skumkt->remove($product['store_id'], $product_var['sku'], $int_to);
                    $this->model_control_sync_skuseller_skumkt->create(array(
                        'store_id'   => $product['store_id'],
                        'company_id' => $product['company_id'],
                        'skuseller'  => $product_var['sku'],
                        'skumkt'     => $skumkt_var,
                        'int_to'     => $int_to
                    ));
                }
            }
        }

        return $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => $this->lang->line('messages_successfully_updated')
        )));
    }

    public function errorReason($data, $id, $request, $payload)
    {
        if(!empty($payload)){

            $error_id = [];

            if (isset($request['check_image'])) {
                $error_id[] = 1;
            }
            if (isset($request['check_categeory'])) {
                $error_id[] = 2;
            }
            if (isset($request['check_dimensions'])) {
                $error_id[] = 3;
            }
            if (isset($request['check_price'])) {
                $error_id[] = 4;
            }
            if (isset($request['check_description'])) {
                $error_id[] = 5;
            }

            $products = [];
            foreach($error_id as $error) {
                foreach($payload as $key => $p){
                    $products[] = array_merge($p,[
                        'error_id' => $error,
                    ]);
                }
            }
            return $saved = $this->model_integrations->updatePrdToIntegrationByTrusteeship($data, $id, $products, $error_id);
        }
    }

    public function listImage()
    {
        $postdata = $this->postClean(NULL, TRUE);
        $remove = $postdata['remove'];
        $folder_name = 'assets/images/product_image/' . $postdata['listimages'] . '/';
        @$images = scandir($folder_name);
        $output = '';

        if (false !== $images) {
            $output .= '<div class="col-md-2" style="background: #367fa9;color: white;float: left;position: absolute;z-index: 5;line-height: 1.5;width: 113px;padding: 3px 5px;margin-left: 9px;">Imagem principal</div>';
            foreach ($images as $key => $image) {
                if ('.' != $image && '..' != $image) {
                    $extension = pathinfo($image, PATHINFO_EXTENSION);
                    if ($extension == 'jpg' || $extension == 'jpeg') {
                        $output .= '
                            <li class="" ondrop="saveOrder()" >
                                <div>
                                    <div class="">
                                        <a href="' . base_url($folder_name) . $image . '" target="_blank" >
                                           <img data-dz-thumbnail data-path="' . $postdata['listimages'] . '/' . $image . '"  data-key="' . ($key - 2) . ',' . '" src="' . base_url($folder_name) . $image . '" class="img-thumbnail" width="100" height="100" />                                                
                                        </a>
                                    </div>' .
                            (($remove == "true") ? '<button type="button" class="btn btn-link" onclick="removeimage(this)" data-id="' . $image . '" style="color: red;font-size: 15px;margin-left: -10px;"><i class="fa fa-close"></i></button>' : '')
                            . '<br>
                                </div>
                            </li>  
                        ';
                    }
                }
            }
            echo $output;
        }
    }
    public function getLimiteImage()
    {
        $getLimitImage = $this->Model_settings->getSettingDatabyName('limite_imagens_aceitas_api');
        if($getLimitImage && $getLimitImage['status'] == 1){
            echo $limitImage = (int) $getLimitImage['value'];
            return $limitImage = (int) $getLimitImage['value'];
        }else{
            echo $limitImage = 5;
            return $limitImage = 5;
        }
    }

    public function getCategoriesByStoreProduct($store_id): CI_Output
    {
        $store_id = explode('-', $store_id);

        $categories = $this->model_products->getCategoriesByStoreProduct($store_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($categories));
    }
}
