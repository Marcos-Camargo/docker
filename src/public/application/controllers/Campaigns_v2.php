<?php /** @noinspection DuplicatedCode */
/** @noinspection JSVoidFunctionReturnValueUsed */
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUnused */

use App\Libraries\Enum\CampaignSegment;
use App\Libraries\Enum\CampaignStatus;
use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\ComissioningType;
use App\Libraries\Enum\ComissionRuleEnum;
use App\Libraries\Enum\DiscountTypeEnum;
use App\Libraries\Enum\GridCampaign;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_integrations $model_integrations
 * @property Model_category $model_category
 * @property Model_settings $model_settings
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_campaigns_v2_categories $model_campaigns_v2_categories
 * @property Model_campaigns_v2_marketplaces $model_campaigns_v2_marketplaces
 * @property Model_campaigns_v2_stores $model_campaigns_v2_stores
 * @property Model_campaigns_v2_products $model_campaigns_v2_products
 * @property Model_campaigns_v2_elegible_products $model_campaigns_v2_elegible_products
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_campaigns_v2_payment_methods $model_campaigns_v2_payment_methods
 * @property Model_vtex_payment_methods $model_vtex_payment_methods
 * @property Model_vtex_trade_policy $model_vtex_trade_policy
 * @property Model_campaigns_v2_vtex_campaigns $model_campaigns_v2_vtex_campaigns
 * @property Model_commissionings $model_commissionings
 * @property Model_campaigns_v2_trade_policies $model_campaigns_v2_trade_policies
 * @property Model_commissioning_products $model_commissioning_products
 * @property Model_commissioning_trade_policies $model_commissioning_trade_policies
 * @property Model_commissioning_categories $model_commissioning_categories
 * @property Model_commissioning_brands $model_commissioning_brands
 * @property Model_commissioning_stores $model_commissioning_stores
 * @property VtexCampaigns $vtexcampaigns
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Security $security
 * @property CI_Parser $parser
 * @property CI_Lang $lang
 */
class Campaigns_v2 extends Admin_Controller
{

    private $tempProductsPrices = [];
    private $tempProductsVariantsPrices = [];
    private $tempProductsPricesMarketplace = [];
    private $tempProductsVariantsPricesMarketplace = [];
    private $productsSetDateUpdated = [];

    private $productsAutoRejectedMotive = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->load->model('model_integrations');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_category');
        $this->load->model('model_products');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_categories');
        $this->load->model('model_campaigns_v2_marketplaces');
        $this->load->model('model_campaigns_v2_stores');
        $this->load->model('model_campaigns_v2_products');
        $this->load->model('model_campaigns_v2_elegible_products');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_campaigns_v2_vtex_campaigns');
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->load->model('model_campaigns_v2_occ_campaigns');
        }
        $this->load->model('model_campaigns_v2_payment_methods');
        $this->load->model('model_campaigns_v2_trade_policies');
        $this->load->model('model_vtex_payment_methods');
        $this->load->model('model_vtex_trade_policy');
        $this->load->model('model_settings');
        $this->load->model('model_commissioning_products');
        $this->load->model('model_commissioning_trade_policies');
        $this->load->model('model_commissioning_categories');
        $this->load->model('model_commissioning_brands');
        $this->load->model('model_commissioning_stores');
        $this->load->library('checkCommissioningChanges');

        $this->load->library('parser');
        $this->load->library('excel');

    }

    public function index()
    {

        if (!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_campaigns_v2');

        $this->data['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');


        $userstore = $this->session->userdata('userstore');
        $compId = $this->session->userdata('usercomp');
        $allowRender = true;
        if ($userstore || $compId != 1) {

            $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();
            if (!$usersStores){
                $allowRender = false;
            }

        }

        if ($allowRender){
            if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") {
                $this->data['page_title'] = $this->lang->line('application_manage_campaigns_v2_1');

                $this->data['data']['page_now_selected'] = 'campaigns_v2_title_manage_campaigns';

                $this->render_template('campaigns_v2/index_v2_1', $this->data);
            } else {
                $this->render_template('campaigns_v2/index', $this->data);
            }
        }else{
            $this->render_template('campaigns_v2/index_disabled', $this->data);
        }


        //Debug
        if ($this->model_settings->getValueIfAtiveByName('enable_debug_campaigns')) {
            $this->output->enable_profiler(true);
        }
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }


    public function dashboard()
    {
        if (!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $userstore = $this->session->userdata('userstore');

        $this->data['page_title'] = $this->lang->line('application_campaigns_v2_title_dashboard');
        $this->data['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');

        $this->data['data']['page_now_selected'] = 'campaigns_v2_title_dashboard';

        // CARDS
        $active_campaigns = $this->model_campaigns_v2->getCardActiveCampaigns();
        $this->data['card_active_campaigns'] = $active_campaigns ?? 0;
        $this->data['card_end_this_month'] = $this->model_campaigns_v2->getCardEndThisMonth();
        $this->data['card_adherence'] = $this->model_campaigns_v2->getCardAdherence();
        $this->data['card_products'] = $this->model_campaigns_v2->getCardProducts($userstore);
        $this->data['card_approval'] = $this->model_campaigns_v2->getCardApproval();
        $this->data['card_revenue'] = $this->model_campaigns_v2->getCardRevenue($userstore);

        if ($this->data['card_revenue'] <= 0) {
            $this->data['card_revenue'] = '0';
        }

        //graficos
        //top 10 -> nao vai puxar dados para o front, vai criar dados nas tabelas
        $this->createDashboardGraphData();

        $this->render_template('campaigns_v2/dashboard', $this->data);
    }


    public function createDashboardGraphData()
    {
        //primeiro criando o grafico de top 10 sellers
        //nao esquecer de converter estas funcoes para seller posteriormente
        $this->model_campaigns_v2->graphTopSellers();
        $this->model_campaigns_v2->graphTopProducts(100);
    }


    public function revenues()
    {
        if (!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_campaigns_v2_title_revenues');

        $this->data['data']['page_now_selected'] = 'campaigns_v2_title_revenues';
        $this->data['total_revenues'] = $this->model_campaigns_v2->getCardRevenue($this->session->userdata('userstore'));

        $this->render_template('campaigns_v2/revenues', $this->data);


        //Debug
        if ($this->model_settings->getValueIfAtiveByName('enable_debug_campaigns')) {
            $this->output->enable_profiler(true);
        }
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }


    public function detail(int $id): void
    {

        if (!in_array('viewCampaigns', $this->permission)) {
            exit();
        }

        $this->data['campaign'] = $this->model_campaigns_v2->getCampaignById($id);
        $this->data['categories'] = $this->model_campaigns_v2_categories->getByCampaignId($id);
        $this->data['marketplaces'] = $this->model_campaigns_v2_marketplaces->getByCampaignId($id);

        $this->parser->parse('campaigns_v2/modal_detail', $this->data);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    public function elegible_products(int $id)
    {

        if (!in_array('viewCampaigns', $this->permission)) {
            //@todo colocar em json
            exit('Permission denied');
        }

        $userStore = $this->session->userdata('userstore');
        if ($userStore && !$this->model_campaigns_v2->storeIsInCampaign($id, $userStore)) {
            //@todo colocar em json
            exit(lang('application_you_not_joined_selected_campaign'));
        }

        $page = $this->input->get('page'); // Página atual
        $limit = $this->input->get('limit'); // Limite de registros por página

        $offset = ($page - 1) * $limit; // Calcular offset

        $productsElegible = $this->model_campaigns_v2_elegible_products->getByCampaignIdPaginated($id, $limit, $offset);

        $productsIds = [];
        $productVariantIds = [];
        $groupById = true;
        foreach ($productsElegible['products'] as $productElegible) {
            $productsIds[] = $productElegible['product_id'];
            //@todo quando remover flag, deixar apenas o isset, resto fica tudo igual como na linha anterior e a próxima
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($productElegible['prd_variant_id'])) {
                $productVariantIds[] = $productElegible['prd_variant_id'];
                $groupById = false;
            }
        }

        $productsElegible['products'] = $this->model_products->searchProductsToMassiveImportCampaign($productsIds, $groupById, $productVariantIds);

        foreach ($productsElegible['products'] as $key => &$product) {

            $prd_variant_id = null;
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['prd_variant_id'])) {
                $prd_variant_id = $product['prd_variant_id'];
            }

            $productElegible = $this->model_campaigns_v2_elegible_products->getProductByCampaignId(
                $id,
                $product['id'],
                $prd_variant_id
            );

            if (!$productElegible){
                unset($productsElegible['products'][$key]);
                continue;
            }

            //O preço já vem em product, não tem necessidade da query em questão, quando remover essa ff, pode remover tudo
            if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                $productPrice = $this->model_products->getPrice($product['id']);
                $product['price'] = $productPrice;
            }
            $product['maximum_share_sale_price'] = $productElegible['maximum_share_sale_price'] ?? 0;
            $product['discount_type'] = $productElegible['discount_type'];
            $product['discount_percentage'] = $productElegible['discount_percentage'];
            $product['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
            $product['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
            $product['fixed_discount'] = $productElegible['fixed_discount'];
            $product['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
            $product['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];

            //teste
            $product['comission_rule'] = '';
            $product['new_comission'] = '';
            $product['comission_rebate'] = '';

        }

        echo json_encode($productsElegible, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    }

    /**
     * Produtos na campanha
     * @param  int  $id
     */
    public function products(int $id): void
    {

        if (!in_array('viewCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $userStore = $this->session->userdata('userstore');
        if ($userStore && !$this->model_campaigns_v2->storeIsInCampaign($id, $userStore)) {
            $this->session->set_flashdata('error', lang('application_you_not_joined_selected_campaign'));
            redirect('campaigns_v2', 'refresh');
        }

        $this->data['pageinfo'] = $this->data['pageinfo'] ?? 'application_products_in_campaign';
        $this->data['page_title'] = lang($this->data['pageinfo']);

        $campaign = $this->generateCampaignEditData($id, false, false, false);

        $categories = [];
        if ($campaign['categories']) {
            foreach ($campaign['categories'] as $categoryId) {
                $categories[] = $this->model_category->getCategoryData($categoryId);
            }
        }

        $valorSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['sellercenter'] = $valorSellerCenter['value'];

        $payment_gateway_id = $this->model_settings->getSettingDatabyName('payment_gateway_id');
        $this->data['payment_gateway_id'] = $payment_gateway_id['value'];

        $this->data['stores'] = json_encode($campaign['stores']);

        $this->data['seller_type'] = $campaign['seller_type'];

        $this->data['marketplaces'] = $this->model_integrations->getAllDistinctIntTo();
        $this->data['array_stores'] = $this->model_campaigns_v2_stores->getByCampaignId($id);
        $this->data['categories'] = json_encode($categories);

        $campaignTypesBlacklist = [];
        if ($this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            $campaignTypesBlacklist[] = CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;
        }
        $this->data['campaign_types'] = CampaignTypeEnum::generateList($campaignTypesBlacklist);
        $this->data['discount_types'] = DiscountTypeEnum::generateList();
        $this->data['comission_rules'] = json_encode(ComissionRuleEnum::generateList());
        $this->data['segments'] = json_encode(CampaignSegment::generateList());
        $this->data['page'] = 'products';

        // SENTRY ID: 582
        $campaignEndDate = null;
        if (!empty($campaign['end_date']) && !empty($campaign['end_time'])) {
            try {
                $campaignEndDate = new DateTime($campaign['end_date'].' '.$campaign['end_time'].':00');
            } catch (Exception $e) {
                // Log the error or handle it as appropriate
                $campaignEndDate = null;
            }
        }

        $this->data['campaign_expired'] = ($campaignEndDate instanceof DateTime) ? ($campaignEndDate->format(DATETIME_INTERNATIONAL) <= dateNow()->format(DATETIME_INTERNATIONAL)) : true;

        //Registro padrão
        $this->data['entry'] = json_encode($campaign);
        $this->data['campaign'] = $campaign;
        $this->data['userstore'] = $userStore;
        $this->data['campaign_expired'] = $campaignEndDate <= dateNow()->format(DATETIME_INTERNATIONAL);

        $this->data['allow_insert_products'] = false;
        if ($campaign['active']
            && !$this->data['campaign_expired']
            && ((!in_array($campaign['campaign_type'], [
                        CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                    ]) && ($userStore || $this->data['usercomp'] != 1))
                || in_array($campaign['campaign_type'], [
                    CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                ]) && !$userStore && $this->data['only_admin'] && $this->data['usercomp'] == 1)) {
            $this->data['allow_insert_products'] = true;
        }

        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');

        $this->data['payment_methods_options'] = "0";
        $this->data['trade_policies_options'] = "0";
        $this->data['payment_methods_mktplaces'] = [];
        $this->data['trade_policies_mktplaces'] = [];
        $this->data['use_payment_methods'] = false;
        $this->data['use_trade_policies'] = false;

        $paymentMethodsVtex = $this->model_campaigns_v2_vtex_campaigns->getCampaignV2PaymentMethods($campaign['id']);
        $allowedPaymentMethods = [];
        foreach ($paymentMethodsVtex as $paymentMethodVtex) {
            $allowedPaymentMethods[] = $paymentMethodVtex['method_id'];
        }

        $tradePoliciesVtex = $this->model_campaigns_v2_vtex_campaigns->getCampaignV2TradePolicies($campaign['id']);
        $allowedTradePolicies = [];
        foreach ($tradePoliciesVtex as $tradePolicyVtex) {
            $allowedTradePolicies[] = $tradePolicyVtex['trade_policy_id'];
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->data['vtex_marketplaces'] = [];
            $this->data['occ_marketplaces'] = [];
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            if ($this->data['marketplaces']){
                foreach ($this->data['marketplaces'] as $marketplace){
                    $integrations = $this->model_integrations->getIntegrationsByIntTo($marketplace['int_to']);
                    if (strstr($integrations[0]['auth_data'], 'oraclecloud.com')){
                        $this->data['occ_marketplaces'][] = $marketplace['int_to'];
                    }else{
                        $this->data['vtex_marketplaces'][] = $marketplace['int_to'];
                    }
                }
                $this->getPaymentMethods($allowedPaymentMethods);
            }
        }else{
            //@todo pode remover
            $this->getVtexPaymentMethods($allowedPaymentMethods);
        }

        $this->getVtexTradePolicies($allowedTradePolicies);

        if (!$paymentMethodsVtex) {
            $this->data['use_payment_methods'] = false;
        }
        if (!$tradePoliciesVtex) {
            $this->data['use_trade_policies'] = false;
        }
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            if ($campaign['vtex_campaign_update'] > 0 || $campaign['occ_campaign_update'] > 0) {
                if ($campaign['vtex_campaign_update'] > 0){
                    $lastVtexCampaign = $this->model_campaigns_v2_vtex_campaigns->getLastCampaignByCampaignId($campaign['id']);
                }
                if ($campaign['occ_campaign_update'] > 0){
                    $lastVtexCampaign = $this->model_campaigns_v2_occ_campaigns->getLastCampaignByCampaignId($campaign['id']);
                }
                $this->data['last_vtex_campaign'] = $lastVtexCampaign;
            }
        }else{

            //@todo pode remover
            if ($campaign['vtex_campaign_update'] > 0) {
                if ($campaign['vtex_campaign_update'] > 0){
                    $lastVtexCampaign = $this->model_campaigns_v2_vtex_campaigns->getLastCampaignByCampaignId($campaign['id']);
                }
                $this->data['last_vtex_campaign'] = $lastVtexCampaign;
            }

        }

        $this->data['allow_add_products_by_deadline'] = $this->model_campaigns_v2->campaignIsAbleToJoinByDateLimit($campaign);

        $this->render_template('campaigns_v2/create', $this->data);

        //Debug
        if ($this->model_settings->getValueIfAtiveByName('enable_debug_campaigns')) {
            $this->output->enable_profiler(true);
        }

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    /**
     * @param  int  $campaignId
     * @param  bool  $clearDate
     * @param  bool  $getProducts
     * @param  bool  $isClonePage
     * @return array|null
     */
    public function generateCampaignEditData(
        int $campaignId,
        bool $clearDate = true,
        bool $getProducts = true,
        bool $isClonePage = false
    ): ?array {

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $marketplaces = $this->model_campaigns_v2_marketplaces->getByCampaignId($campaignId);
        $categories = $this->model_campaigns_v2_categories->getByCampaignId($campaignId);
        $stores = $this->model_campaigns_v2_stores->getByCampaignId($campaignId);

        $products = [];
        $productsElegible = [];

        if ($getProducts) {
            $products = $this->model_campaigns_v2_products->getProductsByCampaign($campaignId);
        } elseif ($isClonePage) {
            $productsElegible = $this->model_campaigns_v2_elegible_products->getByCampaignId($campaignId);
        }

        if ($clearDate) {

            $campaign['start_date'] = dateNow()->format(DATE_INTERNATIONAL);
            $campaign['start_time'] = '00:00';
            $campaign['end_date'] = '';
            $campaign['end_time'] = '00:05';

        } else {

            $startDate = $campaign['start_date'];
            $endDate = $campaign['end_date'];

            $campaign['start_date'] = dateFormat($startDate, DATE_INTERNATIONAL);
            $campaign['start_time'] = dateFormat($startDate, 'H:i');
            $campaign['end_date'] = dateFormat($endDate, DATE_INTERNATIONAL);
            $campaign['end_time'] = dateFormat($endDate, 'H:i');

        }

        $campaign['products'] = [];
        $campaign['elegible_products'] = [];
        $campaign['add_elegible_products'] = [];
        if ($getProducts && $products) {

            foreach ($products as $product) {
                $campaign['products'][] = [
                    'id' => $product['product_id'],
                    'store' => $product['store_name'],
                    'store_id' => $product['store_id'],
                    'sku' => $product['sku'],
                    'name' => $product['product_name'],
                    'price' => $product['price'],
                    'qty' => $product['qty'],
                    'campaign' => [],
                    'discount_type' => $product['discount_type'] ?? null,
                    'discount_percentage' => $product['discount_percentage'] ?? null,
                    'fixed_discount' => $product['fixed_discount'] ?? null,
                    'comission_rule' => $product['comission_rule'] ?? null,
                    'new_comission' => $product['new_comission'] ?? null,
                    'rebate_value' => $product['rebate_value'] ?? null,
                    'active' => (bool) $product['active'],
                    'approved' => (bool) $product['approved'],
                    'product_price' => $product['product_price'] ?? null,
                    'product_promotional_price' => $product['product_promotional_price'] ?? null,
                    'maximum_share_sale_price' => $product['maximum_share_sale_price'] ?? null,
                ];
            }

        } elseif ($productsElegible) {

            $productsIds = [];
            foreach ($productsElegible as $productElegible) {
                $productsIds[] = $productElegible['product_id'];
            }
            $products = $this->model_products->searchProductsToMassiveImportCampaign($productsIds, true);

            $campaign['elegible_products'] = $products;

        }

        $campaign['categories'] = [];
        if ($categories) {
            foreach ($categories as $category) {
                $campaign['categories'][] = $category['category_id'];
            }
        }
        $campaign['stores'] = [];
        if ($stores) {

            foreach ($stores as $store) {
                $campaign['stores'][] = (string) $store['store_id'];
            }

        }

        $campaign['marketplaces'] = [];
        if ($marketplaces) {
            foreach ($marketplaces as $marketplace) {
                $campaign['marketplaces'][] = $marketplace['int_to'];
            }
        }

        if ($campaign['products']) {

            foreach ($campaign['products'] as &$product) {

                $prd_variant_id = null;
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['prd_variant_id'])) {
                    $prd_variant_id = $product['prd_variant_id'];
                }

                $productElegible = $this->model_campaigns_v2_elegible_products->getProductByCampaignId(
                    $campaignId,
                    $product['id'],
                    $prd_variant_id
                );
                $productPrice = $this->model_products->getPrice($product['id']);
                $product['price'] = $productPrice;
                $product['maximum_share_sale_price'] = $productElegible['maximum_share_sale_price'];
                $product['discount_type'] = $productElegible['discount_type'];
                $product['discount_percentage'] = $productElegible['discount_percentage'];
                $product['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
                $product['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                $product['fixed_discount'] = $productElegible['fixed_discount'];
                $product['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
                $product['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];

            }

        }

        if ($campaign['elegible_products']) {

            foreach ($campaign['elegible_products'] as &$product) {

                $productElegible = $this->model_campaigns_v2_elegible_products->getProductByCampaignId($campaignId,
                    $product['id']);
                $productPrice = $this->model_products->getPrice($product['id']);
                $product['price'] = $productPrice;
                $product['maximum_share_sale_price'] = $productElegible['maximum_share_sale_price'];
                $product['discount_type'] = $productElegible['discount_type'];
                $product['discount_percentage'] = $productElegible['discount_percentage'];
                $product['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
                $product['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                $product['fixed_discount'] = $productElegible['fixed_discount'];
                $product['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
                $product['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];

            }

        }

        if ($campaign['elegible_products']) {

            foreach ($campaign['elegible_products'] as &$product) {

                $productElegible = $this->model_campaigns_v2_elegible_products->getProductByCampaignId($campaignId,
                    $product['id']);
                $productPrice = $this->model_products->getPrice($product['id']);
                $product['price'] = $productPrice;
                $product['maximum_share_sale_price'] = $productElegible['maximum_share_sale_price'];
                $product['discount_type'] = $productElegible['discount_type'];
                $product['discount_percentage'] = $productElegible['discount_percentage'];
                $product['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
                $product['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                $product['fixed_discount'] = $productElegible['fixed_discount'];
                $product['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
                $product['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];

            }

        }

        $this->payment_methods_active = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->occ_payment_methods_active = $this->model_settings->getValueIfAtiveByName('allow_occ_campaign_payment_method');
            $campaign['paymentMethods'] = [];
        }

        if ($this->payment_methods_active || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $this->occ_payment_methods_active)) {

            $this->data['use_payment_methods'] = true;
            $this->data['payment_methods_options'] = "0";
            $this->data['payment_methods_mktplaces'] = [];

            $paymentMethodsInCampaign = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($campaign['id']);
            $allowedPaymentMethods = [];
            foreach ($paymentMethodsInCampaign as $paymentMethodInCampaign) {
                $allowedPaymentMethods[] = $paymentMethodInCampaign['method_id'];
            }

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                $this->getPaymentMethods($allowedPaymentMethods);
            }else{
                $this->getVtexPaymentMethods($allowedPaymentMethods);
            }

            if (strlen($this->data['payment_methods_options']) > 1) {
                $selected_payment_methods = json_decode($this->data['payment_methods_options'], true);
                $selected_payment_methods = reset($selected_payment_methods);

                if ($selected_payment_methods) {
                    foreach ($selected_payment_methods as $method) {
                        $campaign['paymentMethods'][] = $method['method_id'];
                    }
                }
            }
        }

        $this->trade_policies_active = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        $campaign['tradePolicies'] = [];

        if ($this->trade_policies_active) {

            $this->data['use_trade_policies'] = true;
            $this->data['trade_policies_options'] = "0";
            $this->data['trade_policies_mktplaces'] = [];

            $tradePoliciesInCampaign = $this->model_campaigns_v2_trade_policies->getCampaignV2TradePolicies($campaign['id']);
            $allowedTradePolicies = [];
            foreach ($tradePoliciesInCampaign as $tradePolicyInCampaign) {
                $allowedTradePolicies[] = $tradePolicyInCampaign['trade_policy_id'];
            }

            $this->getVtexTradePolicies($allowedTradePolicies);

            if (strlen($this->data['trade_policies_options']) > 1) {
                $selected_trade_policies = json_decode($this->data['trade_policies_options'], true);
                $selected_trade_policies = reset($selected_trade_policies);

                if ($selected_trade_policies) {
                    foreach ($selected_trade_policies as $trade_policy) {
                        $campaign['tradePolicies'][] = $trade_policy['trade_policy_id'];
                    }
                }
            }
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $campaign['is_owner'] = ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$this->session->userdata('userstore') && ($campaign['vtex_campaign_update'] > 0 || $campaign['occ_campaign_update'] > 0))
                ||
                ($campaign['seller_type'] > 0
                    && $this->checkPermissionUserCampaign($campaign, $this->session->userdata('userstore'),
                        $this->data['usercomp'])
                    && $this->model_campaigns_v2->canShowButtonCampaignToAdmin($campaign['id'],
                        $this->session->userdata['id']));
        }else{
            //@todo pode excluir
            $campaign['is_owner'] = ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$this->session->userdata('userstore') && $campaign['vtex_campaign_update'] > 0)
                ||
                ($campaign['seller_type'] > 0
                    && $this->checkPermissionUserCampaign($campaign, $this->session->userdata('userstore'),
                        $this->data['usercomp'])
                    && $this->model_campaigns_v2->canShowButtonCampaignToAdmin($campaign['id'],
                        $this->session->userdata['id']));
        }

        return $campaign;

    }

    /**
     * @todo atualizar
     * @param  array  $allowedPaymentMethods
     * @return void
     */
    protected function getPaymentMethods(array $allowedPaymentMethods = [])
    {

        $integrations = [];

        $payment_methods_options = [];

        if ($this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method')) {

            $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0);

            if ($market_places) {

                $this->data['use_payment_methods'] = true;

                foreach ($market_places as $key => $market_place) {

                    if (is_array($market_place)) {

                        $integrations[$key] = $market_place;
                        $integrations[$key]['auth_data'] = json_decode($market_place['auth_data'], true);

                    }

                }

                $payment_methods_options_array = $this->model_vtex_payment_methods->vtexGetPaymentMethods();

                if ($payment_methods_options_array) {
                    foreach ($payment_methods_options_array as $payment_methods_options_item) {
                        if ($allowedPaymentMethods && in_array($payment_methods_options_item['method_id'],
                                $allowedPaymentMethods)) {
                            $payment_methods_options[$payment_methods_options_item['int_to']][] = $payment_methods_options_item;
                        }
                    }
                }
            }

        }

        if ($this->model_settings->getValueIfAtiveByName('allow_occ_campaign_payment_method')) {

            $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0);

            if ($market_places) {

                $this->data['use_payment_methods'] = true;

                foreach ($market_places as $key => $market_place) {

                    if (is_array($market_place)) {

                        $integrations[$key] = $market_place;
                        $integrations[$key]['auth_data'] = json_decode($market_place['auth_data'], true);

                    }

                }

                $payment_methods_options_array = $this->model_vtex_payment_methods->vtexGetPaymentMethods();

                if ($payment_methods_options_array) {
                    foreach ($payment_methods_options_array as $payment_methods_options_item) {
                        if ($allowedPaymentMethods && in_array($payment_methods_options_item['method_id'],
                                $allowedPaymentMethods)) {
                            $payment_methods_options[$payment_methods_options_item['int_to']][] = $payment_methods_options_item;
                        }
                    }
                }
            }

        }

        $this->data['payment_methods_mktplaces'] = $integrations ?: array();
        $this->data['payment_methods_options'] = $payment_methods_options ? json_encode($payment_methods_options) : '[]';

    }

    protected function getVtexPaymentMethods(array $allowedPaymentMethods = [])
    {

        //@todo remover quando remover a feature oep-1443-campanhas-occ

        $integrations = [];

        $payment_methods_options = [];

        if ($this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method')) {

            $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0);

            if ($market_places) {

                $this->data['use_payment_methods'] = true;

                foreach ($market_places as $key => $market_place) {

                    if (is_array($market_place)) {

                        $integrations[$key] = $market_place;
                        $integrations[$key]['auth_data'] = json_decode($market_place['auth_data'], true);

                    }

                }

                $payment_methods_options_array = $this->model_vtex_payment_methods->vtexGetPaymentMethods();

                if ($payment_methods_options_array) {
                    foreach ($payment_methods_options_array as $payment_methods_options_item) {
                        if ($allowedPaymentMethods && in_array($payment_methods_options_item['method_id'],
                                $allowedPaymentMethods)) {
                            $payment_methods_options[$payment_methods_options_item['int_to']][] = $payment_methods_options_item;
                        }
                    }
                }
            }

        }

        $this->data['payment_methods_mktplaces'] = $integrations ?: array();
        $this->data['payment_methods_options'] = $payment_methods_options ? json_encode($payment_methods_options) : '[]';

    }

    protected function getVtexTradePolicies(array $allowedTradePolicies = [])
    {

        $vtex_integrations = [];

        $trade_policy_options = [];

        if ($this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies')) {

            $market_places = $this->model_integrations->getIntegrationsbyStoreIdActive(0);

            if ($market_places) {

                $this->data['use_trade_policies'] = true;

                foreach ($market_places as $key => $market_place) {

                    if (is_array($market_place)) {

                        $vtex_integrations[$key] = $market_place;
                        $vtex_integrations[$key]['auth_data'] = json_decode($market_place['auth_data'], true);
                        $registerIntegrations = $this->model_integrations->getRegisterIntegration($market_place['id']);
                        if (isset($registerIntegrations['tradesPolicies'])) {
                            $vtex_integrations[$key]['trade_policies'] = json_decode($registerIntegrations['tradesPolicies']);
                        }

                    }

                }

                $vtex_trade_policies_options_array = $this->model_vtex_trade_policy->vtexGetTradePolicies();

                if ($vtex_trade_policies_options_array) {
                    foreach ($vtex_trade_policies_options_array as $vtex_trade_policies_option) {
                        if ($allowedTradePolicies && in_array($vtex_trade_policies_option['trade_policy_id'],
                                $allowedTradePolicies)) {
                            foreach ($vtex_integrations as $key => $vtex_integration) {

                                if (isset($vtex_integration['trade_policies']) && in_array($vtex_trade_policies_option['trade_policy_id'],
                                        $vtex_integration['trade_policies'])) {
                                    $trade_policy_options[$vtex_integration['int_to']][] = $vtex_trade_policies_option;
                                }

                            }
                        }
                    }
                }
            }

        }

        $this->data['trade_policies_mktplaces'] = $vtex_integrations ?: array();
        $this->data['trade_policies_options'] = $trade_policy_options ? json_encode($trade_policy_options) : '[]';

    }

    public function checkPermissionUserCampaign($campaign, $userstore, $compId)
    {
        $storeIsInCampaign = $this->model_campaigns_v2->storeIsInCampaign($campaign['id'], $userstore);
        $companyIsInCampaign = $this->model_campaigns_v2->companyIsInCampaign($campaign['id']);

        if ($userstore == 0 && $compId > 0 && $campaign['seller_type'] == 2 && ($campaign['store_seller_campaign_owner'] == $compId)) {
            return true;
        }
        if (($userstore > 0 && $campaign['store_seller_campaign_owner'] == $userstore && $campaign['seller_type'] == 1)) {
            return true;
        }

        return false;
    }

    public function active_campaigns($revenue = false)
    {
        ob_start();
        $postdata = $this->postClean(null, true);

        $revenue = (isset($postdata['revenue'])) ?? false;

        $userstore = $this->session->userdata('userstore');
        $compId = $this->session->userdata('usercomp');
        $sellerIndex = null;
        $comission = null;

        if ($userstore || $compId != 1) {

            $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();

            $sellerIndexes = [];
            foreach ($usersStores as $userstoreId) {

                $sellerIndex = $this->model_stores->getSellerIndex(['store_id' => $userstoreId]);
                if (isset($sellerIndex[0]['seller_index'])){
                    $sellerIndexes[] = $sellerIndex[0]['seller_index'];
                }

            }

            // SENTRY ID: 389
            $sellerIndex = 0;
            if (!empty($sellerIndexes)){
                $sellerIndex = max($sellerIndexes);
            }

            //Comissão só conseguimos realizar filtro se estiver logado como loja, não pode ser empresa
            if ($userstore) {
                $store = $this->model_stores->getStoreById($userstore);
                $comission = $store['service_charge_value'];
            }

        }

        if ($revenue) {
            $data = $this->model_campaigns_v2->getRevenues($postdata, $userstore, $sellerIndex, $comission);
            $recordsTotal = $this->model_campaigns_v2->getRevenues($postdata, $userstore, $sellerIndex, $comission,
                true)[0]['total'];
            $itens = $this->generateRevenueData($data, GridCampaign::ACTIVE, $postdata);
        } else {
            $data = $this->model_campaigns_v2->getActiveCampaigns($postdata, $userstore, $sellerIndex, $comission);
            $recordsTotal = $this->model_campaigns_v2->countTotalActiveCampaigns($postdata, $userstore, $sellerIndex,
                $comission);
            $itens = $this->generateReturnDataCampaignFromArrayResult($data, GridCampaign::ACTIVE, $revenue);

        }


        /*
            braun hack FIN-802
            vou fazer uma limpeza no array, ao inves de mexer no metodo
            pq ainda pode ser que os dados das colunas sejam discutidos novamente
        */
        if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") {
            foreach ($itens as $key => $val) {
                if (!$postdata['dashboard_list']) {
                    unset($itens[$key]['marketplace_takes_over']);
                    unset($itens[$key]['merchant_takes_over']);
                    unset($itens[$key]['deadline_for_joining']);
                    unset($itens[$key]['download_selected_itens_by_seller']);
                    unset($itens[$key]['download_approved_by_marketplace']);
                    unset($itens[$key]['download_not_approved_by_marketplace']);
                    unset($itens[$key]['has_products_pending']);
                }
            }
        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    private function generateRevenueData($data, $gridCampaign, $postdata)
    {
        foreach ($data as $key => $value) {
            if ($postdata['dashboard_list'] == '1') {
                $title = lang('application_show_campaign_products_details');
                $data[$key]['marketplace_takes_over'] = '<a title="'.$title.'" href="'.base_url('campaigns_v2/products/'.$value['id']).'" class="text-center">'.lang('application_show_view').'</a>';
                $data[$key]['merchant_takes_over'] = '<a title="'.$title.'" href="'.base_url('campaigns_v2/products/'.$value['id']).'" class="text-center">'.lang('application_show_view').'</a>';
            }

            $data[$key]['start_date'] = datetimeBrazil($value['start_date']);
            $data[$key]['end_date'] = datetimeBrazil($value['end_date']);
//			$data[$key]['action'] = $this->generateActionButtons($value, $gridCampaign);
            $data[$key]['action'] = '<a href="'.base_url('campaigns_v2/products/'.$value['id']).'" target="_blank" class="btn btn-wider-1 btn-outline-primary">Ver detalhes</a>';
            $data[$key]['campaign_type'] = CampaignTypeEnum::getDescription($value['campaign_type']);
            $data[$key]['active'] = ($value['active'] == 0) ? lang('application_active') : lang('application_inactive');
        }

        return $data;
    }

    private function generateReturnDataCampaignFromArrayResult(
        array $data,
        string $gridCampaign,
        $revenue = false
    ): array {

        $result = [];

        foreach ($data as $key => $value) {

            $marketplace_takes_over = '';
            $merchant_takes_over = '';

            if ($value['segment'] == CampaignSegment::PRODUCT && !$revenue) {

                $title = lang('application_show_campaign_products_details');
                $marketplace_takes_over = '<a title="'.$title.'" href="'.base_url('campaigns_v2/products/'.$value['id']).'" class="text-center">'.lang('application_show_view').'</a>';
                $merchant_takes_over = '<a title="'.$title.'" href="'.base_url('campaigns_v2/products/'.$value['id']).'" class="text-center">'.lang('application_show_view').'</a>';

            } else {
                if ($value['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {
                    if ($value['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $marketplace_takes_over = $value['marketplace_discount_percentual'].'%';
                        $merchant_takes_over = $value['seller_discount_percentual'].'%';
                    } else {
                        $marketplace_takes_over = money($value['marketplace_discount_fixed']);
                        $merchant_takes_over = money($value['seller_discount_fixed']);
                    }
                } elseif ($value['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {
                    if ($value['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $marketplace_takes_over = '0%';
                        $merchant_takes_over = $value['discount_percentage'].'%';
                    } else {
                        $marketplace_takes_over = 'R$ 0,00';
                        $merchant_takes_over = money($value['fixed_discount']);
                    }
                } elseif ($value['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {
                    if ($value['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $marketplace_takes_over = $value['discount_percentage'].'%';
                        $merchant_takes_over = '0%';
                    } else {
                        $marketplace_takes_over = money($value['fixed_discount']);
                        $merchant_takes_over = 'R$ 0,00';
                    }
                }
            }

            if ($value['active']) {
                if ($gridCampaign == GridCampaign::MY_CAMPAIGNS) {
                    $campaignStatus = CampaignStatus::getDescription(CampaignStatus::ADHERED);
                } elseif ($value['start_date'] > dateNow()->format(DATETIME_INTERNATIONAL)) {
                    $campaignStatus = CampaignStatus::getDescription(CampaignStatus::SCHEDULE);
                } elseif ($value['end_date'] < dateNow()->format(DATETIME_INTERNATIONAL)) {
                    $campaignStatus = CampaignStatus::getDescription(CampaignStatus::EXPIRED);
                } else {
                    $campaignStatus = CampaignStatus::getDescription(CampaignStatus::AVAILABLE);
                }
            } else {
                $campaignStatus = CampaignStatus::getDescription(CampaignStatus::INACTIVE);
            }

            $approved = lang('application_waiting');
            if ($value['approved'] == 1) {
                $approved = lang('application_approved');
            } elseif ($value['approved'] == 2) {
                $approved = lang('application_repproved');
            }

            $result[$key] = [
                'id' => $value['id'],
                'name' => $value['name'],
                'start_date' => datetimeBrazil($value['start_date']),
                'end_date' => datetimeBrazil($value['end_date']),
                'action' => $this->generateActionButtons($value, $gridCampaign),
                'campaign_type' => CampaignTypeEnum::getDescription($value['campaign_type']),
                'marketplace_takes_over' => $marketplace_takes_over,
                'merchant_takes_over' => $merchant_takes_over,
                'deadline_for_joining' => $value['deadline_for_joining'] ? datetimeBrazil($value['deadline_for_joining']) : '',
                'status' => $campaignStatus,
                'download_selected_itens_by_seller' => '',
                'download_approved_by_marketplace' => '',
                'download_not_approved_by_marketplace' => '',
                'approved' => $approved,
            ];

            if ($value['highlight']) {
                $title = lang('application_remove_highlight_campaign');
                $icon = ' <i class="fa fa-trophy" style="color: gold;"></i> ';
                $result[$key]['name'] = $icon.'&nbsp;'.$result[$key]['name'];
            }

            $checkCampaignOwnerAdmin = $this->model_campaigns_v2->canShowButtonCampaignToAdmin($value['id'],
                $this->session->userdata['id']);

            if (($this->data['only_admin']
                    && $this->data['usercomp'] == 1
                    && !$this->session->userdata('userstore')
                    && ($value['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $value['occ_campaign_update'] > 0)))
                ||
                ($value['seller_type'] > 0
                    && $this->checkPermissionUserCampaign($value, $this->session->userdata('userstore'),
                        $this->data['usercomp'])
                    && $checkCampaignOwnerAdmin)) {

                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                    if ($value['vtex_campaign_update'] > 0){
                        $lastVtexCampaign = $this->model_campaigns_v2_vtex_campaigns->getLastCampaignByCampaignId($value['id']);
                    }
                    if ($value['occ_campaign_update'] > 0){
                        $lastVtexCampaign = $this->model_campaigns_v2_occ_campaigns->getLastCampaignByCampaignId($value['id']);
                    }

                }else{
                    //@todo pode remover
                    $lastVtexCampaign = $this->model_campaigns_v2_vtex_campaigns->getLastCampaignByCampaignId($value['id']);
                }
                $this->data['last_vtex_campaign'] = $lastVtexCampaign;

                if ($value['ds_vtex_campaign_creation']) {
                    $icon = ' <i class="fas fa-exclamation-circle" style="color: red;" title="Erro no cadastro com Marketplace"></i> ';
                    $result[$key]['name'] = $icon.'&nbsp;'.$result[$key]['name'];
                } elseif (!$lastVtexCampaign) {
                    $title = lang('application_campaign_vtex_status_message_in_process');
                    $icon = ' <i class="fas fa-spinner fa-spin" title="'.$title.'"></i> ';
                    $result[$key]['name'] = $icon.'&nbsp;'.$result[$key]['name'];
                }

            }

            if (!in_array($value['campaign_type'], [
                    CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                ]) && (($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$this->session->userdata('userstore')) || $gridCampaign != GridCampaign::ACTIVE)) {
                $result[$key]['download_selected_itens_by_seller'] = '<a class="text-center" href="'.base_url("campaigns_v2/download_selected_itens_by_seller/{$value['id']}").'">xls</a>';
                $result[$key]['download_approved_by_marketplace'] = '<a class="text-center" href="'.base_url("campaigns_v2/download_approved_by_marketplace/{$value['id']}").'">xls</a>';
                $result[$key]['download_not_approved_by_marketplace'] = '<a class="text-center" href="'.base_url("campaigns_v2/download_not_approved_by_marketplace/{$value['id']}").'">xls</a>';
            }

            if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$this->session->userdata('userstore')) {
                //Se é negociação marketplace ou redução de comissão/rebate, nunca vai precisar de aprovação
                if (in_array($value['campaign_type'],
                    [CampaignTypeEnum::MARKETPLACE_TRADING, CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE])) {
                    $result[$key]['has_products_pending'] = lang('application_no');
                } else {
                    $result[$key]['has_products_pending'] = $this->model_campaigns_v2_products->campaignHasProductsInAnalisys($value['id']) ? lang('application_yes') : lang('application_no');
                }
            }

        }

        return $result;

    }

    /**
     * @param  array  $campaign
     * @param  string  $gridCampaign
     * @return string
     */
    private function generateActionButtons(array $campaign, string $gridCampaign): string
    {

        $userstore = $this->session->userdata('userstore');

        $compId = $this->session->userdata('usercomp');

        $companyIsInCampaign = $this->model_campaigns_v2->companyIsInCampaign($campaign['id']);

        $buttons = [];

        //Somente admin pode clonar
        if (in_array('createCampaigns', $this->permission)) {

            if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
                $title = lang('application_clone_campaign');
                $buttons[] = ' <a class="btn btn-light m-2 btn-w222" href="'.base_url('campaigns_v2/clone/'.$campaign['id']).'"><i class="fa fa-copy"></i> &nbsp;'.$title.'</a>';

                if ($campaign['highlight']) {
                    $title = lang('application_remove_highlight_campaign');
                    $icon = '<span class="fa-stack fa-xs">
                              <i class="fa fa-trophy fa-stack-1x"></i>
                              <i class="fa fa-ban fa-stack-2x text-danger"></i>
                            </span>';
                    $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/highlight/'.$campaign['id']).'">'.$icon.' &nbsp;'.$title.'</a>';
                } else {
                    $title = lang('application_highlight_campaign');
                    $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/highlight/'.$campaign['id']).'"><i class="fa fa-trophy"></i> &nbsp;'.$title.'</a>';
                }
            }
        }

        $title = lang('application_show_campaign');
        $buttons[] = ' <a class="dropdown-item222 btn btn-light m-2" onclick="return showCampaignDetail('.$campaign['id'].')" data-toggle="modal" data-target="#detailModal"><i class="fa fa-tags "></i> &nbsp;'.$title.'</a>';

        //Admin sempre ve, lojista só ve no minhas campanhas e se está participando da campanha
        if (($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) || $this->model_campaigns_v2->storeIsInCampaign($campaign['id'],
                $userstore) || $companyIsInCampaign) {
            $title = lang('application_show_campaign_products');
            $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/products/'.$campaign['id']).'"><i class="fa fa-boxes"></i> &nbsp;'.$title.'</a>';
        }

        if (($userstore || $this->data['usercomp'] != 1) && $this->model_campaigns_v2->campaignIsAbleToJoinByDateLimit($campaign)) {
            if ($gridCampaign == GridCampaign::ACTIVE) {
                $title = lang('application_join_campaign');
                $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/join/'.$campaign['id']).'"><i class="fa fa-sign-in"></i> &nbsp;'.$title.'</a>';
            }
        }

        if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore && !in_array($campaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $title = lang('application_download_file');
            $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/download_file/'.$campaign['id']).'"><i class="fa fa-arrow-alt-circle-down"></i> &nbsp;'.$title.'</a>';
        }

        $checkCampaignOwnerAdmin = $this->model_campaigns_v2->canShowButtonCampaignToAdmin($campaign['id'],
            $this->session->userdata['id']);

        if (($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore)) {
            if ($campaign['active']) {
                $title = lang('application_inactivate_campaign');
                $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/inactivate/'.$campaign['id']).'"><i class="fa fa-stop-circle"></i> &nbsp;'.$title.'</a>';
//            } else {
//                $title = lang('application_activate_campaign');
//                $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/activate/'.$campaign['id']).'"><i class="fa fa-play-circle"></i> &nbsp;'.$title.'</a>';
            }
        } elseif ($campaign['seller_type'] > 0 && $this->checkPermissionUserCampaign($campaign, $userstore,
                $compId) && $checkCampaignOwnerAdmin) {
            if ($campaign['active']) {
                $title = lang('application_inactivate_campaign');
                $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/inactivate/'.$campaign['id']).'"><i class="fa fa-stop-circle"></i> &nbsp;'.$title.'</a>';
//            } else {
//                $title = lang('application_activate_campaign');
//                $buttons[] = ' <a class="btn btn-light m-2" href="'.base_url('campaigns_v2/activate/'.$campaign['id']).'"><i class="fa fa-play-circle"></i> &nbsp;'.$title.'</a>';
            }
        }

        if (in_array('approveCampaignCreation', $this->permission)
            && $campaign['active']
            && $campaign['approved'] == 0) {
            $title = lang('application_approve');
            $buttons[] = ' <a class="btn btn-light m-2" style="width: 100%; text-align:left;" href="'.base_url('campaigns_v2/approve/'.$campaign['id']).'"><i class="fas fa-check-circle"></i> &nbsp;'.$title.'</a>';

            $title = lang('application_disapprove');
            $buttons[] = ' <a class="btn btn-light m-2" style="width: 100%; text-align:left;" href="'.base_url('campaigns_v2/repprove/'.$campaign['id']).'"><i class="fas fa-times-circle"></i> &nbsp;'.$title.'</a>';
        }

        $button = '
                <div class="btn-group">
                  <button type="button" class="btn btn-wider-1 btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-list-ul"></i> &nbsp;'.lang('application_actions').'
                    </button>
                  <div class="dropdown-menu dropdown-menu-right">'.
            implode('', $buttons)
            .'</div>
                </div>
                    ';

        return $button;

    }

    public function inactivate(int $campaignId)
    {

        ignore_user_abort(true);
        ini_set('memory_limit', '1096M');
        set_time_limit(0);

        $this->db->trans_begin();

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        if ($campaign['seller_type'] > 0) {
            $userstore = $this->session->userdata('userstore');
            $compId = $this->session->userdata('usercomp');
            $companyIsInCampaign = $this->model_campaigns_v2->companyIsInCampaign($campaign['id']);
            $storeIsInCampaign = $this->model_campaigns_v2->storeIsInCampaign($campaign['id'], $userstore);
            if (($campaign['store_seller_campaign_owner'] != $userstore
                    && $campaign['store_seller_campaign_owner'] != $compId)
                && !$companyIsInCampaign
                && !$storeIsInCampaign) {
                redirect('campaigns_v2', 'refresh');
            }
        } else //Usuário não tem acesso
        {
            if (!$this->data['only_admin'] || $this->data['usercomp'] != 1 || $this->session->userdata('userstore')) {
                redirect('campaigns_v2', 'refresh');
            }
        }

        //If campaign type is vtex, first we need to inactivate in vtex
        if (($campaign['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0))) {
            if ($campaign['vtex_campaign_update'] > 0){
                $this->load->library('VtexCampaigns');
                $this->vtexcampaigns->archiveUnarchiveCampaign($campaign['id'], false);
            }
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0){
                $this->load->library('OccCampaigns');
                $this->occcampaigns->archiveUnarchiveCampaign($campaign['id']);
            }
        }

        $this->model_campaigns_v2->update(['active' => 0], $campaignId);
        $this->model_campaigns_v2_products->desactivateProductsByCampaign($campaignId);

        if ($campaign['b2w_type'] == 0) {
            $this->setDateUpdateAllProductsInCampaign($campaignId);
        }

        $this->session->set_flashdata('success', lang('application_message_inactivated_campaign'));

        $this->db->trans_commit();

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        redirect('campaigns_v2', 'refresh');

    }

    public function setDateUpdateAllProductsInCampaign(int $campaignId): void
    {
        $products = $this->model_campaigns_v2_products->getProductsByCampaign($campaignId);
        if ($products) {
            foreach ($products as $product) {
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                    $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $campaignId,
                                $product['product_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0,
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }else{

                    $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $campaignId,
                                $product['product_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }
            }
        }
    }

    public function migrate_discounts_temp()
    {

        if (!isset($this->session->get_userdata()['migrate_discounts_temp'])) {

            $this->session->set_tempdata('migrate_discounts_temp', true, 99999);

            $this->model_campaigns_v2->migrate_discounts_temp($this->model_settings->getSettingDatabyName('sellercenter')['value']);

            exit('Pronto');

        }

        exit('Já rodado');

    }

//    public function activate(int $campaignId)
//    {
//
//        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
//        if (!$campaign) {
//            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
//            redirect('campaigns_v2', 'refresh');
//        }
//
//        if ($campaign['seller_type'] > 0) {
//            $userstore = $this->session->userdata('userstore');
//            $compId = $this->session->userdata('usercomp');
//            $companyIsInCampaign = $this->model_campaigns_v2->companyIsInCampaign($campaign['id']);
//            $storeIsInCampaign = $this->model_campaigns_v2->storeIsInCampaign($campaign['id'], $userstore);
//            if (($campaign['store_seller_campaign_owner'] != $userstore
//                    && $campaign['store_seller_campaign_owner'] != $compId)
//                && !$companyIsInCampaign
//                && !$storeIsInCampaign) {
//                redirect('campaigns_v2', 'refresh');
//            }
//        } else //Usuário não tem acesso
//        {
//            if (!$this->data['only_admin'] || $this->data['usercomp'] != 1 || $this->session->userdata('userstore')) {
//                redirect('campaigns_v2', 'refresh');
//            }
//        }
//
//        //If campaign type is vtex, first we need to inactivate in vtex
//        if ($campaign['vtex_campaign_update'] > 0 || $campaign['occ_campaign_update'] > 0) {

//            if ($campaign['vtex_campaign_update'] > 0){
    //            $this->load->library('VtexCampaigns');
    //            $this->vtexcampaigns->archiveUnarchiveCampaign($campaign['id']);
//            }

//            if ($campaign['occ_campaign_update'] > 0){
//                $this->load->library('OccCampaigns');
//                $this->occcampaigns->archiveUnarchiveCampaign($campaign['id']);
//            }
//        }
//
//        $this->model_campaigns_v2->update(['active' => 1], $campaignId);
//
//        if ($campaign['b2w_type'] == 0) {
//            $this->setDateUpdateAllProductsInCampaign($campaignId);
//        }
//
//        $this->session->set_flashdata('success', lang('application_message_activated_campaign'));
//
//        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
//            saveSlowQueries();
//        }
//
//        redirect('campaigns_v2', 'refresh');
//
//    }

    public function approve(int $campaignId)
    {

        if (!in_array('approveCampaignCreation', $this->permission)) {
            redirect('campaigns_v2', 'refresh');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        //If campaign type is vtex, first we need to inactivate in vtex
        if ($campaign['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0)) {
            if ($campaign['vtex_campaign_update'] > 0){
                $this->load->library('VtexCampaigns');
                $this->vtexcampaigns->archiveUnarchiveCampaign($campaign['id'], $campaign['active'], true);
            }
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0){
                $this->load->library('OccCampaigns');
                $this->occcampaigns->archiveUnarchiveCampaign($campaign['id']);
            }
        }

        $this->model_campaigns_v2->update(['approved' => 1], $campaignId);

        if ($campaign['b2w_type'] == 0) {
            $this->setDateUpdateAllProductsInCampaign($campaignId);
        }

        $this->session->set_flashdata('success', lang('application_message_approved_campaign'));

        redirect('campaigns_v2', 'refresh');

    }

    public function repprove(int $campaignId)
    {

        if (!in_array('approveCampaignCreation', $this->permission)) {
            redirect('campaigns_v2', 'refresh');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        //If campaign type is vtex, first we need to inactivate in vtex
        if ($campaign['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0)) {
            if ($campaign['vtex_campaign_update'] > 0){
                $this->load->library('VtexCampaigns');
                $this->vtexcampaigns->archiveUnarchiveCampaign($campaign['id'], $campaign['active'], false);
            }
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0){
                $this->load->library('OccCampaigns');
                $this->occcampaigns->archiveUnarchiveCampaign($campaign['id']);
            }
        }

        $this->model_campaigns_v2->update(['approved' => 2], $campaignId);

        if ($campaign['b2w_type'] == 0) {
            $this->setDateUpdateAllProductsInCampaign($campaignId);
        }

        $this->session->set_flashdata('success', lang('application_message_repproved_campaign'));

        redirect('campaigns_v2', 'refresh');

    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function download_file(int $campaignId): void
    {

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign || in_array($campaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        //Usuário não tem acesso
        if (!$this->data['only_admin'] || $this->data['usercomp'] != 1 || $this->session->userdata('userstore')) {
            redirect('campaigns_v2', 'refresh');
        }

        $storesIds = [];
        $categoriesIds = [];
        $productsIds = [];

        if (
            (!in_array($campaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) &&
            $campaign['segment'] == CampaignSegment::PRODUCT) {

            $products = $this->model_campaigns_v2_elegible_products->getByCampaignId($campaignId);
            foreach ($products as $product) {
                $productsIds[] = $product['product_id'];
            }

        } elseif ($campaign['segment'] == CampaignSegment::STORE) {

            $stores = $this->model_campaigns_v2_stores->getByCampaignId($campaignId);

            foreach ($stores as $store) {
                $storesIds[] = $store['store_id'];
            }

        } elseif ($campaign['segment'] == CampaignSegment::CATEGORY) {

            $categories = $this->model_campaigns_v2_categories->getByCampaignId($campaignId);

            foreach ($categories as $category) {
                $categoriesIds[] = $category['category_id'];
            }

        }

        //Se está filtrando por loja específica, só podemos mostrar de uma loja
        if (isset($_GET['store']) && $_GET['store']) {
            $storesIds = [(int) $_GET['store']];
        }

        $products = $this->model_products->searchProductsToCampaign(
            '',
            $campaign['participating_comission_from'],
            $campaign['participating_comission_to'],
            $campaign['product_min_value'],
            $campaign['product_min_quantity'],
            $campaign['min_seller_index'],
            $storesIds,
            $categoriesIds,
            $productsIds,
            0
        );

        if (!$products) {
            $this->session->set_flashdata('error', lang('application_no_product_selected'));
            redirect('campaigns_v2', 'refresh');
        }

        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex();

        $line = 1;

        $column = 0;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, lang('application_product_sku'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_product_id'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_product_name'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_campaign_segment_category'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_campaign_segment_seller'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_product_price_before_campaign'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_product_price_in_campaign'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            DiscountTypeEnum::getDescription($campaign['discount_type']));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_stock'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_gmv_potential'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_inventory_coverage'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_item_sold_last_30_days'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_approved'));
        if ($campaign['b2w_type'] == 1) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_sku_marketplace'));
        }

        foreach ($products as $product) {

            $categoryName = null;
            if ($product['category_id']) {
                $categoryName = $this->model_category->getCategoryData($product['category_id']);
            }

            //Calculando o valor na promoção
            $product['product_price'] = $product['price'];
            $product['discount_type'] = $campaign['discount_type'];
            $product['discount_percentage'] = $campaign['discount_percentage'];
            $product['fixed_discount'] = $campaign['fixed_discount'];
            $this->calculateProductPriceByDiscountRule($product);

            if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                $discountValue = $product['discount_percentage'].'%';
            } else {
                $discountValue = money($product['fixed_discount']);
            }

            $gmvLast30Days = $this->model_products->getGmvLast30Days($product['id']);
            $productInCampaign = $this->model_campaigns_v2_products->getProductInCampaign($campaignId, $product['id']);

            $line++;

            $column = 0;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, (string)$product['sku']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$product['id']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['name']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                $categoryName ? $categoryName['name'] : '');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$product['store']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$product['price']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                (string)$product['product_promotional_price']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$discountValue);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$product['qty']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                (string)($product['product_promotional_price'] * $product['qty']));
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                (string)($gmvLast30Days / $product['qty']));
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$gmvLast30Days);
            if ($productInCampaign) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(
                    ++$column,
                    $line,
                    $productInCampaign['approved'] ? lang('application_yes') : lang('application_no'));
            } else {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                    lang('application_product_not_added_to_campaign'));
            }

            if ($campaign['b2w_type'] == 1) {
                $prd_to_integration = $this->model_products->getProductsToIntegrationByIdIntTo($product['id'],
                    $productInCampaign['int_to']);
                $prd_to_integrationValue = $prd_to_integration ? $prd_to_integration['skumkt'] : '';
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, (string)$prd_to_integrationValue);
            }

        }

        $filename = "campaign_v2_".date("Y-m-d-H-i-s").".xlsx";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    public function calculateProductPriceByDiscountRule(array &$product): void
    {

        //Se só está adicionando nas elegíveis, não tem preço ainda
        if (!isset($product['product_price'])) {
            return;
        }

        if ($product['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {
            $product['product_promotional_price'] = $product['product_price'] - $product['fixed_discount'];
        }

        if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
            $price = $product['product_price'];
            $percentualDiscount = $product['discount_percentage'];
            $product['product_promotional_price'] = $price - ($price * $percentualDiscount / 100);
            $product['product_promotional_price'] = roundDecimalsDown($product['product_promotional_price']);
        }

    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function download_products_in_campaign(int $campaignId): void
    {

        // Validações iniciais
        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign || in_array($campaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        if (!$this->data['only_admin'] || $this->data['usercomp'] != 1 || $this->session->userdata('userstore')) {
            redirect('campaigns_v2', 'refresh');
        }

        // Configurações iniciais
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        // Iniciar buffer de saída
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        // Configurar headers
        $filename = "campaign_v2_".date("Y-m-d-H-i-s").".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        // Abrir stream de saída
        $output = fopen('php://output', 'w');

        // Escrever BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Escrever cabeçalhos
        fputcsv($output, [
            lang('application_product_id'),
            lang('application_product_name'),
            lang('application_campaign_segment_category'),
            lang('application_campaign_segment_seller'),
            lang('application_product_price_before_campaign'),
            lang('application_product_price_in_campaign'),
            DiscountTypeEnum::getDescription($campaign['discount_type']),
            lang('application_stock'),
            lang('application_gmv_potential'),
            lang('application_inventory_coverage'),
            lang('application_item_sold_last_30_days'),
            lang('commision_hierarchy'),
            lang('application_commission'),
            lang('application_status'),
        ]);

        // Processar e enviar em lotes
        $batchSize = 100;
        $offset = 0;

        while (true) {
            $products = $this->model_campaigns_v2_products->getProductsBatch($campaignId, $offset, $batchSize);

            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                $gmvLast30Days = $this->model_products->getGmvLast30Days($product['product_id']);

                // Calculando o valor na promoção
                $product['product_price'] = $product['price'];
                $product['discount_type'] = $campaign['discount_type'];
                $product['discount_percentage'] = $campaign['discount_percentage'];
                $product['fixed_discount'] = $campaign['fixed_discount'];
                $this->calculateProductPriceByDiscountRule($product);

                $discountValue = ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL)
                    ? $product['discount_percentage'].'%'
                    : money($product['fixed_discount']);

                if ($product['removed']) {
                    $status = lang('application_deleted');
                    if ($product['auto_removed']) {
                        $status = lang('reproved_by_commision_hierarchy');
                    }
                } elseif ($product['approved']) {
                    $status = lang('application_approved');
                } else {
                    $status = lang('application_waiting');
                }

                // Escrever linha
                fputcsv($output, [
                    $product['product_id'],
                    $product['product_name'],
                    $product['category_name'] ?? '',
                    $product['store_name'],
                    number_format($product['price'], 2, ',', '.'),
                    number_format($product['product_promotional_price'], 2, ',', '.'),
                    $discountValue,
                    $product['qty'],
                    number_format($product['product_promotional_price'] * $product['qty'], 2, ',', '.'),
                    number_format($gmvLast30Days / ($product['qty'] ?: 1), 2, ',', '.'),
                    number_format($gmvLast30Days, 2, ',', '.'),
                    ComissioningType::getName($product['commision_hierarchy']),
                    $product['percentual_commision'],
                    $status
                ]);

                // Enviar buffer para o cliente
                if (ob_get_length()) {
                    ob_flush();
                    flush();
                }
            }

            $offset += $batchSize;
            unset($products);
            gc_collect_cycles();
        }

        // Fechar stream
        fclose($output);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit();
    }


    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download_approved_by_marketplace(int $campaignId): void
    {
        $this->generateXlsProducts($campaignId, true);
    }

    /**
     * @param  int  $campaignId
     * @param  bool  $onlyApproved
     * @param  bool  $onlyNotApproved
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    private function generateXlsProducts(
        int $campaignId,
        bool $onlyApproved = false,
        bool $onlyNotApproved = false
    ): void {
        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $userstore = $this->session->userdata('userstore');
        if ($userstore && !$this->model_campaigns_v2->storeIsInCampaign($campaignId, $userstore)) {
            $this->session->set_flashdata('error', lang('application_you_not_joined_selected_campaign'));
            redirect('campaigns_v2', 'refresh');
        }

        $products = $this->model_campaigns_v2_products->getProductsByCampaign($campaignId, $userstore, $onlyApproved,
            $onlyNotApproved);

        if (!$products) {
            $this->session->set_flashdata('error', lang('application_no_product_selected'));
            redirect('campaigns_v2', 'refresh');
        }

        $primeiroProduto = $products[0];

        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex();

        $line = 1;

        $column = 0;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, lang('application_marketplace'));
        if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_campaign_segment_seller'));
        }
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_product_id'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_product_name'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_campaign_segment_category'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_product_price_before_campaign'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            lang('application_product_price_in_campaign'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
            DiscountTypeEnum::getDescription($primeiroProduto['discount_type']));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_stock'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_gmv_potential'));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, lang('application_approved'));
        if ($campaign['b2w_type'] == 1) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_sku_marketplace'));
        }

        if ($onlyNotApproved && $this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')){
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_marketplace_comission'));
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_hierarchy_level'));
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                lang('application_campaign_participation'));
        }

        foreach ($products as $product) {

            $categoryName = null;
            if ($product['category_id']) {
                $categoryName = $this->model_category->getCategoryData($product['category_id']);
            }

            $line++;

            $column = 0;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, $product['int_to']);
            if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['store_name']);
            }
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['product_id']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['product_name']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                $categoryName ? $categoryName['name'] : '');
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['price']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                $product['product_promotional_price']);

            if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                $discountValue = $product['discount_percentage'].'%';
            } else {
                $discountValue = money($product['fixed_discount']);
            }

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $discountValue);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['qty']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                (string)($product['product_promotional_price'] * $product['qty']));
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line,
                $product['approved'] ? 'Sim' : 'Não');

            if ($campaign['b2w_type'] == 1) {
                $prd_to_integration = $this->model_products->getProductsToIntegrationByIdIntTo($product['product_id'],
                    $product['int_to']);
                $prd_to_integrationValue = $prd_to_integration ? $prd_to_integration['skumkt'] : '';
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $prd_to_integrationValue);
            }

            if ($onlyNotApproved && $this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')){
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['percentual_commision'].'%');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, ComissioningType::getName($product['commision_hierarchy']));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(++$column, $line, $product['percentual_from_commision'].'%');
            }

        }

        $filename = "selected_itens_campaign_v2_".date("Y-m-d-H-i-s").".xlsx";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download_not_approved_by_marketplace(int $campaignId): void
    {
        $this->generateXlsProducts($campaignId, false, true);
    }

    /**
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download_selected_itens_by_seller(int $campaignId): void
    {

        $this->generateXlsProducts($campaignId);

    }

    public function expired_campaigns(): void
    {
        ob_start();
        $postdata = $this->postClean(null, true);

        $userstore = $this->session->userdata('userstore');

        $data = $this->model_campaigns_v2->getExpiredCampaigns($postdata, $userstore);
        $recordsTotal = $this->model_campaigns_v2->countTotalExpiredCampaigns($postdata, $userstore);

        $itens = $this->generateReturnDataCampaignFromArrayResult($data, GridCampaign::EXPIRED);

        // braun hack FIN-802
        if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") {
            foreach ($itens as $key => $val) {
                if (!$postdata['dashboard_list']) {
//            $itens[$key]['files'] = '';
//            $itens[$key]['action'] = '';
                    unset($itens[$key]['marketplace_takes_over']);
                    unset($itens[$key]['merchant_takes_over']);
                    unset($itens[$key]['deadline_for_joining']);
                    unset($itens[$key]['download_selected_itens_by_seller']);
                    unset($itens[$key]['download_approved_by_marketplace']);
                    unset($itens[$key]['download_not_approved_by_marketplace']);
                    unset($itens[$key]['has_products_pending']);
                }
            }
        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    public function my_campaigns(): void
    {
        ob_start();
        $postdata = $this->postClean(null, true);

        $userstores = $this->model_stores->getMyCompanyStoresArrayIds();
        $usersStoresString = implode(',', $userstores);

        $data = $this->model_campaigns_v2->getMyCampaigns($postdata, $usersStoresString);
        $recordsTotal = $this->model_campaigns_v2->countTotalMyCampaigns($postdata, $usersStoresString);

        $itens = $this->generateReturnDataCampaignFromArrayResult($data, GridCampaign::MY_CAMPAIGNS);

        // braun hack FIN-802
        if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") {
            foreach ($itens as $key => $val) {
                if (!$postdata['dashboard_list']) {
//            $itens[$key]['files'] = '';
//            $itens[$key]['action'] = '';
                    unset($itens[$key]['marketplace_takes_over']);
                    unset($itens[$key]['merchant_takes_over']);
                    unset($itens[$key]['deadline_for_joining']);
                    unset($itens[$key]['download_selected_itens_by_seller']);
                    unset($itens[$key]['download_approved_by_marketplace']);
                    unset($itens[$key]['download_not_approved_by_marketplace']);
                    unset($itens[$key]['has_products_pending']);
                }
            }
        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    public function clone(int $campaignId): void
    {

        $this->data['pageinfo'] = 'application_clone_campaign';
        $this->data['page_title'] = lang($this->data['pageinfo']);

        $campaign = $this->generateCampaignEditData($campaignId, true, false, true);
        $campaign['id'] = null;
        $campaign['name'] .= ' (CLONE)';

        $this->createcampaigns($campaign);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    /**
     * Cadastro
     */
    public function createcampaigns(array $campaign = []): void
    {

        $userstore = $this->session->userdata('userstore');

        $getData = cleanArray($this->input->get(null, true));

        if ($getData) {
            // Permissão de campanha seller
            if (isset($getData['defaultType'])) {
                if ($getData['defaultType'] != 'sellerCampaign') {
                    redirect('campaigns_v2', 'refresh');
                } else {
                    if (!in_array('sellerCampaignCreation', $this->permission)) {
                        redirect('campaigns_v2', 'refresh');
                    }
                }
            } else {
                redirect('campaigns_v2', 'refresh');
            }

        } else {
            // Permissão de campanha ADM mktplace
            if (!in_array('createCampaigns', $this->permission)) {
                redirect('campaigns_v2', 'refresh');
            } else {

                if ($this->data['only_admin'] == 0) {
                    redirect('campaigns_v2', 'refresh');
                }

                if ($this->data['usercomp'] != 1) {
                    redirect('campaigns_v2', 'refresh');
                }

                /* if($this->session->userdata('userstore')){
                     redirect('campaigns_v2', 'refresh');
                 } */

            }

        }

        /*if ((isset($getData['defaultType']) && $getData['defaultType'] != 'sellerCampaign') && (!in_array('createCampaigns', $this->permission) && (!$this->data['only_admin'] || $this->data['usercomp'] != 1 || $this->session->userdata('userstore'))) ) {
            redirect('campaigns_v2', 'refresh');
        }

        if (((isset($getData['defaultType']) && $getData['defaultType'] == 'sellerCampaign') && !in_array('sellerCampaignCreation', $this->permission))){
            redirect('campaigns_v2', 'refresh');
        }*/

        $this->data['pageinfo'] = $this->data['pageinfo'] ?? 'application_add_campaign';

        $this->generateRenderDefaultData();

        if (!$campaign) {

            $marketplaces = [];
            if (count($this->data['marketplaces']) == 1) {
                $marketplaces[] = $this->data['marketplaces'][0]['int_to'];
            }

            //Tipo de campanha fixo da B2W
            $campaignType = '';
            $discount_type = null;
            $b2wType = false;
            $sellerType = 0;

            $campaign_type_enum = new ReflectionClass(CampaignTypeEnum::class);
            $campaign_types = $campaign_type_enum->getConstants();

            //braun hack FIN-802
            //if deve indicar se o paramentro enable_campaigns_v2_1 esta ativo ou nao
            //se SIM e nao foi pre-definido um tipo de campanha, reinicia o fluxo pela new_campaign ou campign_types (2.1)
            //caso contrário permite o acesso pois na versao 2 pode-se escolher na tela
            if (
                ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1")
                &&
                (!isset($getData['defaultType']) || !in_array($getData['defaultType'], $campaign_types))
            ) {
                redirect('campaigns_v2/newcampaign', 'refresh');
            }

            if (isset($getData['defaultType']) && in_array($getData['defaultType'], $campaign_types)) {
                $campaignType = $getData['defaultType'];
//                $campaignType = 'channel_funded_discount';
//                $campaignType = 'shared_discount';
//                $campaignType = 'merchant_discount';
//                $campaignType = 'commission_reduction_and_rebate';
//                $campaignType = 'marketplace_trading';
//                $discount_type = 'discount_percentage';
                //braun hack
            }
            if (isset($getData['defaultType']) && $getData['defaultType'] == 'b2wcampaign') {
                $campaignType = CampaignTypeEnum::SHARED_DISCOUNT;
                $discount_type = DiscountTypeEnum::PERCENTUAL;
                $b2wType = true;
            } elseif (isset($getData['defaultType']) && $getData['defaultType'] == 'sellerCampaign') {
                $campaignType = CampaignTypeEnum::MERCHANT_DISCOUNT;
                $sellerType = $userstore > 0 ? 1 : 2;
            }


            $campaign = [
                'id' => '',
                'b2w_type' => $b2wType,
                'name' => '',
                'start_date' => dateNow()->format(DATE_INTERNATIONAL),
                'start_time' => '00:00',
                'deadline_for_joining' => '',
                'end_date' => '',
                'end_time' => '00:05',
                'description' => '',
                'marketplaces' => $marketplaces,
                'campaign_type' => $campaignType,
                'discount_type' => $discount_type,
                'discount_percentage' => 0,
                'fixed_discount' => 0,
                'participating_comission_from' => 0,
                'participating_comission_to' => 0,
                'seller_discount_percentual' => 0,
                'seller_discount_fixed' => 0,
                'marketplace_discount_percentual' => 0,
                'marketplace_discount_fixed' => 0,
                'product_min_value' => 0,
                'product_min_quantity' => 0,
                'categories' => [],
                'stores' => [],
                'min_seller_index' => 1,
                'products' => [],
                'segment' => CampaignSegment::STORE,
                'comission_rule' => '',
                'new_comission' => '',
                'rebate_value' => '',
                'products_auto_approval' => 1,
                'paymentMethods' => [],
                'tradePolicies' => [],
                'elegible_products' => [],
                'add_elegible_products' => [],
            ];
            $campaign['seller_type'] = $sellerType;

        }

        $this->data['use_payment_methods'] = false;
        $this->data['use_trade_policies'] = false;
        $this->data['payment_methods_options'] = "0";
        $this->data['payment_methods_mktplaces'] = [];

        $paymentMethodsVtex = $this->model_vtex_payment_methods->vtexGetPaymentMethods();
        $allowedPaymentMethods = [];
        foreach ($paymentMethodsVtex as $paymentMethodVtex) {
            $allowedPaymentMethods[] = $paymentMethodVtex['method_id'];
        }

        $tradePoliciesVtex = $this->model_vtex_trade_policy->vtexGetTradePolicies();
        $allowedTradePolicies = [];
        foreach ($tradePoliciesVtex as $tradePolicyVtex) {
            $allowedTradePolicies[] = $tradePolicyVtex['trade_policy_id'];
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->getPaymentMethods($allowedPaymentMethods);
        }else{
            //@todo pode remover
            $this->getVtexPaymentMethods($allowedPaymentMethods);
        }
        $this->getVtexTradePolicies($allowedTradePolicies);

        $this->data['allow_insert_products'] = false;

        //Registro padrão
        $this->data['entry'] = json_encode($campaign);
        $this->data['campaign'] = $campaign;
        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $this->data['vtex_marketplaces'] = [];
            $this->data['occ_marketplaces'] = [];

            if ($this->data['marketplaces']){
                foreach ($this->data['marketplaces'] as $marketplace){
                    $integrations = $this->model_integrations->getIntegrationsByIntTo($marketplace['int_to']);
                    if (strstr($integrations[0]['auth_data'], 'oraclecloud.com')){
                        $this->data['occ_marketplaces'][] = $marketplace['int_to'];
                    }else{
                        $this->data['vtex_marketplaces'][] = $marketplace['int_to'];
                    }
                }
            }
        }

        if (!isset($campaignType)) {
            $campaignType = '';
        }
        $this->data['data']['page_now_selected'] = 'campaigns_v2_title_'.$campaignType;

        if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") {
            $this->render_template('campaigns_v2/create_2_1', $this->data);
        } else {
            $this->render_template('campaigns_v2/create', $this->data);
        }

        //Debug
        if ($this->model_settings->getValueIfAtiveByName('enable_debug_campaigns')) {
            $this->output->enable_profiler(true);
        }

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    private function generateRenderDefaultData(): void
    {

        $getData = cleanArray($this->input->get(null, true));

        $this->data['page_title'] = lang($this->data['pageinfo']);

        $valorSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['sellercenter'] = $valorSellerCenter['value'];

        $payment_gateway_id = $this->model_settings->getSettingDatabyName('payment_gateway_id');
        $this->data['payment_gateway_id'] = $payment_gateway_id['value'];

        //Tipo de campanha fixo da B2W
        if (isset($getData['defaultType']) && $getData['defaultType'] == 'b2wcampaign') {

            $this->data['marketplaces'] = [];
            $this->data['marketplaces'][] = ['int_to' => 'B2W', 'name' => 'ConectaLá B2W'];
        } else {

            $this->data['marketplaces'] = $this->model_integrations->getAllDistinctIntTo(0);
        }
        $this->data['stores'] = json_encode($this->model_stores->getActiveStore());
        $this->data['categories'] = json_encode($this->model_category->getActiveCategroy());
        $campaignTypesBlacklist = [];
        if ($this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            $campaignTypesBlacklist[] = CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;
        }
        $this->data['campaign_types'] = CampaignTypeEnum::generateList($campaignTypesBlacklist);
        if ($this->model_settings->getSettingDatabyName('negociacao_marketplace_campanha')['value'] == 0) {
            unset($this->data['campaign_types'][CampaignTypeEnum::MARKETPLACE_TRADING]);
        }
        if (isset($getData['defaultType']) && $getData['defaultType'] == 'sellerCampaign') {
            $this->data['campaign_types'] = [CampaignTypeEnum::MERCHANT_DISCOUNT => lang('application_'.CampaignTypeEnum::MERCHANT_DISCOUNT)];
        }
        $this->data['discount_types'] = DiscountTypeEnum::generateList();
        $this->data['comission_rules'] = json_encode(ComissionRuleEnum::generateList());
        $this->data['segments'] = json_encode(CampaignSegment::generateList());
        $this->data['page'] = '';
    }

    public function highlight(int $campaignId): void
    {

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $campaign['highlight'] = (int) !$campaign['highlight'];

        $this->model_campaigns_v2->update($campaign, $campaignId);

        if ($campaign['highlight']) {
            $this->session->set_flashdata('success', 'Campanha Destacada com Sucesso!');
        } else {
            $this->session->set_flashdata('success', 'Removido Destaque da Campanha com Sucesso!');
        }

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        redirect('campaigns_v2', 'refresh');

    }

    public function join(int $campaignId): void
    {

        //Admin não entra em campanha
        if ($this->data['only_admin'] && $this->data['usercomp'] == 1) {
            $this->session->set_flashdata('error',
                lang('application_campaign_message_you_cannot_join_campaign_as_admin'));
            redirect('campaigns_v2', 'refresh');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $userStores = $this->model_stores->getMyCompanyStoresArrayIds();

        foreach ($userStores as $userstore) {

            if (!$this->model_campaigns_v2->isStoreAllowedJoinCampaign($campaignId, $userstore)) {
                $this->session->set_flashdata('error',
                    lang('application_campaign_message_you_cannot_join_this_campaign'));
                redirect('campaigns_v2', 'refresh');
            }

            $this->model_campaigns_v2_stores->joinCampaign($campaignId, $userstore);

        }

        $this->session->set_flashdata('success', lang('application_campaign_successfull_joined_campaign'));

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        redirect('campaigns_v2/products/'.$campaignId, 'refresh');

    }

    /**
     * API para busca de produtos para a campanha para adição a lista de produtos elegíveis
     */
    public function searchProducts(): void
    {

        $searchString = xssClean($this->input->get('searchString'));
        $campaignId = xssClean($this->input->get('campaign_id'));

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $stores = $this->model_stores->getMyCompanyStoresArrayIds();

        $products = $this->model_products->searchProductsToCampaign(
            $searchString,
            $request['participating_comission_from'],
            $request['participating_comission_to'],
            $request['product_min_value'],
            $request['product_min_quantity'],
            $request['min_seller_index'],
            $stores,
            null,
            null,
            100,
            $campaignId
        );

        $newArrayProducts = [];

        if ($products) {

            foreach ($products as $product) {

                $product['campaign'] = [];

                $productPrice = $this->model_products->getPrice($product['id']);
                $product['price'] = $productPrice;
                $product['another_discount_campaign'] = false; //Deixar o ajax fazer isso quando adere o item, não precisa aqui, vai ter perda gigantesca de performance (aprox 2s por item ~100 * 2 = 200s)
//                $product['another_discount_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
//                    null,
//                    $product['id'],
//                    $request['marketplaces']
//                );
                $product['another_comission_rebate_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherComissionReductionRebateCampaign($product['id'],
                    $request['marketplaces']);
                $product['another_marketplace_trading_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherMarketplaceTradingCampaign($product['id']);
                $product['maximum_share_sale_price'] = null;
                $product['comission_rule'] = '';
                $product['new_comission'] = null;
                $product['rebate_value'] = null;
                if (in_array($request['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                    if ($request['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                        $product['comission_rule'] = ComissionRuleEnum::NEW_COMISSION;
                        $product['new_comission'] = $request[ComissionRuleEnum::NEW_COMISSION];
                    } else {
                        $product['comission_rule'] = ComissionRuleEnum::COMISSION_REBATE;
                        $product['rebate_value'] = $request['rebate_value'];
                    }
                } elseif (in_array($request['campaign_type'], [
                    CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT,
                    CampaignTypeEnum::SHARED_DISCOUNT
                ])) {
                    if ($request['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $product['discount_type'] = DiscountTypeEnum::PERCENTUAL;
                        $product['discount_percentage'] = max($request['discount_percentage'], 0);
                        if ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {
                            //Desconto Marketplace
                            $product['seller_discount_percentual'] = 0;
                            $product['marketplace_discount_percentual'] = max($request['discount_percentage'], 0);
                        } elseif ($request['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {
                            //Desconto seller
                            $product['seller_discount_percentual'] = max($request['discount_percentage'], 0);
                            $product['marketplace_discount_percentual'] = 0;
                        } else {
                            //Compartilhado
                            $product['seller_discount_percentual'] = max($request['seller_discount_percentual'], 0);
                            $product['marketplace_discount_percentual'] = max($request['marketplace_discount_percentual'],
                                0);
                        }
                    } else {
                        $product['discount_type'] = DiscountTypeEnum::FIXED_DISCOUNT;
                        $product['fixed_discount'] = $request['fixed_discount'];
                        $product['seller_discount_fixed'] = $request['seller_discount_fixed'];
                        $product['marketplace_discount_fixed'] = $request['marketplace_discount_fixed'];
                    }
                }

                $newArrayProducts[] = $product;

            }

        }

        header('Content-type: application/json');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit(json_encode($newArrayProducts));

    }

    /**
     * Ajax para listar os produtos da campanha
     * @param  int  $campaignId
     */
    public function products_in_campaign(int $campaignId): void
    {
        ob_start();
        $postdata = $this->postClean(null, true);
        $userstore = $this->postClean('searchByStore', true);
        if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
            $userstore = $this->session->userdata('userstore');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);

        $products = $this->model_campaigns_v2_products->findProductsInCampaign($postdata, $campaignId, false,
            $userstore);
        $recordsTotal = $this->model_campaigns_v2_products->findProductsInCampaign($postdata, $campaignId, true,
            $userstore);

        if ($products) {

            foreach ($products as &$product) {

                $product['action_value'] = $product['action_value'] > 0 ? money($product['action_value']) : false;
                $product['created_at'] = datetimeBrazil($product['created_at']);

                if ($product['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                    $product['comission'] = ComissionRuleEnum::getDescription(ComissionRuleEnum::NEW_COMISSION).': '.$product['new_comission'].'%';
                    $product['new_comission'] = $product['new_comission'].' %';
                } else {
                    $product['comission'] = ComissionRuleEnum::getDescription(ComissionRuleEnum::COMISSION_REBATE).' '.money($product['rebate_value']);
                    $product['rebate_value'] = money($product['rebate_value']);
                }

                if ($campaign['end_date'] <= dateNow()->format(DATETIME_INTERNATIONAL)) {
                    $product['approved'] = $product['approved'] ? lang('application_yes') : lang('application_no');
                    $product['active'] = $product['active'] ? lang('application_yes') : lang('application_no');
                } elseif ($product['removed']) {
                    $product['active'] = '<div class="label label-danger" title="'.$product['removed_description'].'">'.lang('application_deleted').'</div>';
                    $product['approved'] = '<div class="label label-danger" title="'.$product['removed_description'].'">'.lang('application_deleted').'</div>';
                } else {

                    if ($product['action_value'] || in_array($campaign['campaign_type'], [
                            CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                        ])) {

                        if (!in_array($campaign['campaign_type'], [
                            CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                        ])) {
                            if ($product['active']) {
                                $product['active'] = '<input type="checkbox" style=" min-height: 16px; margin: 0;" checked="checked" onclick="activateDesactivateProduct(this, \''.$campaignId.'\', \''.$product['campaign_product_key'].'\')">';
                            } else {
                                $product['active'] = '<input type="checkbox" style=" min-height: 16px; margin: 0;" onclick="activateDesactivateProduct(this, \''.$campaignId.'\', \''.$product['campaign_product_key'].'\')">';
                            }
                        }

                        //Somente admin pode aprovar/desaprovar
                        if (in_array('approveCampaignCreation', $this->permission) ) {
                            if ($product['approved']) {
                                $product['approved'] = '<input type="checkbox" style=" min-height: 16px; margin: 0;" checked="checked" onclick="aproveUnaproveProduct(this, \''.$campaignId.'\', \''.$product['campaign_product_key'].'\')">';
                            } else {
                                $product['approved'] = '<input type="checkbox" style=" min-height: 16px; margin: 0;" onclick="aproveUnaproveProduct(this, \''.$campaignId.'\', \''.$product['campaign_product_key'].'\')">';
                            }
                        }else{
                            $product['approved'] = $product['approved'] ? lang('application_yes') : lang('application_no');
                        }

                    } else {
                        $product['active'] = '<div class="label label-danger">'.lang('application_invalid_promotional_price').'</div>';
                        $product['approved'] = '<div class="label label-danger">'.lang('application_invalid_promotional_price').'</div>';
                    }

                }

                if (in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {

                    $promotionalPrice = $this->getCurrentProductPrice($product['id'], $product['int_to']);
                    $product['action_value'] = $promotionalPrice > 0 ? money($promotionalPrice) : null;

                }

                $participatingInCampaign = $product['active'] && $product['approved'] && !$product['removed'];
                if ($participatingInCampaign) {
                    $product['maximum_share_sale_price'] = money($product['maximum_share_sale_price']);
                    if ($campaign['campaign_type'] == CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE) {
                        $participatingInCampaign = $this->isProductParticipatingToComissionReductionAndRebate($product['id'],
                            $product['int_to']);
                    } elseif ($campaign['campaign_type'] == CampaignTypeEnum::MARKETPLACE_TRADING) {
                        $participatingInCampaign = $this->isProductParticipatingToMarketplaceTrading($product['id'],
                            $product['int_to']);
                    }
                }

                $product['participating'] = !$product['removed'] && $participatingInCampaign ? lang('application_yes') : lang('application_no');

                $product['product_price'] = money($product['product_price']);

                // Format variant information if feature flag is enabled
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                    $product['variant_info'] = '';
                    if (!empty($product['has_variants']) && !empty($product['variant_name'])) {
                        $variantTypes = explode(';', $product['has_variants']);
                        $variantValues = explode(';', $product['variant_name']);

                        $variantInfo = [];
                        for ($i = 0; $i < count($variantTypes) && $i < count($variantValues); $i++) {
                            $variantInfo[] = $variantTypes[$i] . ': ' . $variantValues[$i];
                        }

                        $product['variant_info'] = implode('; ', $variantInfo);
                    }
                }

                if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                    $product['discount'] = $product['discount_percentage'].'%';
                } else {
                    $product['discount'] = money($product['fixed_discount']);
                }

                $product['discount_type_name'] = '';
                if (!in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                    $product['discount_type_name'] = DiscountTypeEnum::getDescription($product['discount_type']);
                }

                $product['seller_discount'] = '';
                $product['marketplace_discount'] = '';

                if (in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {
                    if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $product['marketplace_discount'] = $product['marketplace_discount_percentual'].'%';
                    } else {
                        $product['marketplace_discount'] = money($product['marketplace_discount_fixed']);
                    }
                }
                if (in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])) {
                    if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $product['seller_discount'] = $product['seller_discount_percentual'].'%';
                    } else {
                        $product['seller_discount'] = money($product['seller_discount_fixed']);
                    }
                }

                $product['gmv_last_30_days'] = money($this->model_products->getGmvLast30Days($product['id']));

            }

        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal['count'],
            "recordsFiltered" => $recordsTotal['count'],
            "data" => $products,
        );

        ob_clean();
        header('Content-type: application/json');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit(json_encode($output));

    }

    /**
     * Retorna o valor atual do produto para o Determinado Marketplace
     */
    public function getCurrentProductPrice(int $productId, string $int_to): string
    {

        $int_to = utf8_encode($int_to);

        //Buscando o preço atual do produto para usar ele caso não tiver em outra promoção
        if (isset($this->tempProductsPrices[$productId])) {
            $productPrice = $this->tempProductsPrices[$productId];
        } else {
            $productPrice = $this->tempProductsPrices[$productId] = $this->model_products->getPrice($productId);
        }

        if (!isset($this->tempProductsPricesMarketplace[$productId])) {

            $productsPriceMarketplace = $this->model_products_marketplace->getAllDataByProduct($productId);
            if ($productsPriceMarketplace) {

                foreach ($productsPriceMarketplace as $productMarketplace) {
                    if ($productMarketplace['variant'] == '0') {
                        $price = $productMarketplace['price'] == '' || $productMarketplace['same_price'] ? $productPrice : $productMarketplace['price'];
                        $this->tempProductsPricesMarketplace[$productId][$productMarketplace['int_to']] = $price;
                    }
                }

            } else {
                $this->tempProductsPricesMarketplace[$productId][$int_to] = $productPrice;
            }

        }

        if (isset($this->tempProductsPricesMarketplace[$productId][$int_to])) {
            return $this->tempProductsPricesMarketplace[$productId][$int_to];
        }

        return $this->tempProductsPricesMarketplace[$productId][$int_to] = $productPrice;

    }

    public function getCurrentProductPriceVariant(int $productId, string $int_to, string $productVariantId = null): string
    {

        $int_to = utf8_encode($int_to);

        //Buscando o preço atual do produto para usar ele caso não tiver em outra promoção
        if (isset($this->tempProductsVariantsPrices[$productId][$productVariantId])) {
            $productPrice = $this->tempProductsVariantsPrices[$productId][$productVariantId];
        } else {
            $productPrice = $this->tempProductsVariantsPrices[$productId][$productVariantId] = $this->model_products->getVariant($productId, $productVariantId);
        }

        if (!isset($this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId])) {

            $productsPriceMarketplace = $this->model_products_marketplace->getAllDataByProduct($productId);
            if ($productsPriceMarketplace) {

                foreach ($productsPriceMarketplace as $productMarketplace) {
                    if ($productMarketplace['variant'] == $productPrice['variant']) {
                        $price = $productMarketplace['price'] == '' || $productMarketplace['same_price'] ? $productPrice['price'] : $productMarketplace['price'];
                        $this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId][$productMarketplace['int_to']] = $price;
                    }
                }

            } else {
                $this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId][$int_to] = $productPrice['price'];
            }

        }

        if (isset($this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId][$int_to])) {
            return $this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId][$int_to];
        }

        return $this->tempProductsVariantsPricesMarketplace[$productId][$productVariantId][$int_to] = $productPrice['price'];

    }

    public function isProductParticipatingToComissionReductionAndRebate(int $productId, string $int_to): bool
    {

        $productCampaignPrice = $this->getCurrentProductPrice($productId, $int_to);
        $productsComissionReduction = $this->model_campaigns_v2->getProductsCampaignWithComissionReductionRebate($productId,
            $int_to);

        //Se não possui nenhuma campanha do tipo redução de comissão e rebate, não está participando da campanha
        if (!$productsComissionReduction) {
            return false;
        }

        //Se o valor do produto sendo praticado for inferior ao preço de venda máximo da ação, está participando da campanha
        return $productCampaignPrice <= $productsComissionReduction['maximum_share_sale_price'];

    }

    public function isProductParticipatingToMarketplaceTrading(int $productId, string $int_to): bool
    {

        $productCampaignPrice = $this->getCurrentProductPrice($productId, $int_to);
        $productsComissionReduction = $this->model_campaigns_v2->getProductsCampaignWithMarketplaceTrading($productId,
            $int_to);

        //Se não possui nenhuma campanha do tipo redução de comissão e rebate, não está participando da campanha
        if (!$productsComissionReduction) {
            return false;
        }

        return true;

    }

    public function approve_all_products(int $campaignId): void
    {

        $userstore = $this->session->userdata('userstore');

        //Somente admin tem acesso ao aprovar todos
        if ($userstore || !$this->data['only_admin'] || $this->data['usercomp'] != 1) {
            $this->session->set_flashdata('error', lang('application_error_has_ocurred'));
            redirect('campaigns_v2', 'refresh');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $this->model_campaigns_v2_products->approveAllProducts($campaignId);

        if ($campaign['b2w_type'] == 0) {
            $products = $this->model_campaigns_v2_products->getProductsByCampaign($campaignId);
            foreach ($products as $product) {
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                    $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $campaignId,
                                $product['product_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0,
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }else{

                    //@todo pode remover
                    $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $campaignId,
                                $product['product_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }
            }
        }

        $this->session->set_flashdata('success', lang('application_all_products_successfull_aproved'));

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        redirect('campaigns_v2/products/'.$campaignId, 'refresh');

    }

    public function manage_products_status(int $campaignId, int $campaignProductKey): void
    {
        ob_start();
        $userstore = $this->session->userdata('userstore');

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        $active = $this->postClean('active', true);
        $approved = $this->postClean('approved', true);

        $data = [];
        if (!is_null($active)) {
            $data['active'] = $active;
        }
        //Segurança, somente admin pode aprovar/desaprovar
        if (!is_null($approved) && !$userstore && $this->data['only_admin'] && $this->data['usercomp'] == 1) {
            $data['approved'] = $approved;
        }

        $productCampaign = $this->model_campaigns_v2_products->getByPk($campaignId, $campaignProductKey);

        $error = false;

        if (!$productCampaign) {
            $error = true;
            $this->session->set_flashdata('error', lang('application_register_not_found'));
        }

        if ($productCampaign['product_promotional_price'] <= 0 && !in_array($campaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $error = true;
            $this->session->set_flashdata('error', lang('application_invalid_promotional_price'));
        }

        if ($error) {
            ob_clean();
            header('Content-type: application/json');
            if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
                saveSlowQueries();
            }
            exit(json_encode(['redirect' => site_url('campaigns_v2/products/'.$campaign['id'])]));
        }

        $this->model_campaigns_v2_products->changeProductStatus($data, $campaignId, $campaignProductKey);

        //Se está ativando o produto nessa campanha, tem que inativar das outras campanhas também
        if (!is_null($active) && $active == 1) {
            $productsCheck = $this->model_campaigns_v2_products->getProductParticipatingAnotherDiscountCampaign(
                $productCampaign['campaign_v2_id'],
                $productCampaign['product_id'],
                $productCampaign['int_to'],
                [],
                []
            );
            if ($productsCheck) {

                foreach ($productsCheck as $productCheck) {

                    $this->model_campaigns_v2_products->desactivateProduct(
                        $productCheck['campaign_v2_id'],
                        $productCheck['product_id'],
                        true,
                        $productCheck['int_to']
                    );

                }

            }
        }

        if ($campaign['b2w_type'] == 0) {
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                $this->model_products->setDateUpdatedProduct(
                $productCampaign['product_id'],
                null,
                __METHOD__,
                    array(
                        'int_to' => $productCampaign['int_to'],
                        'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                            $productCampaign['campaign_v2_id'],
                            $productCampaign['product_id'],
                            array($productCampaign['int_to']),
                            [],
                            [],
                            0,
                            0
                        ),
                        'price' => $productCampaign['product_promotional_price'],
                        'list_price' => $productCampaign['product_price']
                    )
                );

            }else{

                //@todo pode remover
                $this->model_products->setDateUpdatedProduct(
                $productCampaign['product_id'],
                null,
                __METHOD__,
                    array(
                        'int_to' => $productCampaign['int_to'],
                        'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                            $productCampaign['campaign_v2_id'],
                            $productCampaign['product_id'],
                            array($productCampaign['int_to']),
                            [],
                            [],
                            0
                        ),
                        'price' => $productCampaign['product_promotional_price'],
                        'list_price' => $productCampaign['product_price']
                    )
                );

            }
        }

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    /**
     * Rota ajax de adição de produtos
     */
    public function add_products(): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();

        $this->db->trans_begin();

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        if (!$request['products']) {
            $this->generateResponseObject(lang('application_no_product_selected'), 'danger');
        }

        //Buscando os dados atualizados da campanha na base
        $campaign = $this->model_campaigns_v2->getCampaignById($request['id']);
        $campaignCategories = $this->model_campaigns_v2_categories->getByCampaignId($request['id']);

        //Validando se está dentro do período de vigência
        if ($campaign['end_date'] <= dateNow()->format(DATETIME_INTERNATIONAL)) {
            $this->generateResponseObject(lang('application_you_cannot_add_product_in_expired_campaign'));
        }

        if (!$this->model_campaigns_v2->campaignIsAbleToJoinByDateLimit($campaign)) {
            $this->generateResponseObject('Não é possível adicionar novos produtos na campanha, pois o prazo para adesão a campanha já passou.',
                'danger');
        }

        $marketplaces = $this->model_campaigns_v2_marketplaces->getByCampaignId($request['id']);

        $products = [];
        foreach ($request['products'] as $requestProduct) {

            $productElegible = null;
            if ((!in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING]))
                && $campaign['segment'] == CampaignSegment::PRODUCT) {

                $prdVariantId = null;
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                    $prdVariantId = $requestProduct['prd_variant_id'];
                }

                $productElegible = $this->model_campaigns_v2_elegible_products->getProductByCampaignId(
                    $request['id'],
                    $requestProduct['id'],
                    $prdVariantId
                );

            }

            //Só podemos validar se o produto está na categoria informada como filtro ou não tiver nenhuma categoria
            if ($campaignCategories) {

                //Se não está em nenhuma categoria, não deixar inserir
                $productCategoryId = $this->model_products->getCategoryId($requestProduct['id']);

                $campaignCategory = $this->model_campaigns_v2_categories->getCategoryByCampaignIdProductId($campaign['id'],
                    $productCategoryId);
                if (!$campaignCategory) {
                    $this->generateResponseObject(
                        lang('application_this_product_is_not_allowed_to_participate_current_campaign').': '.$requestProduct['sku'],
                        'danger'
                    );
                }
            }

            foreach ($marketplaces as $marketplace) {

                $int_to = $marketplace['int_to'];

                $prdVariantId = null;
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $requestProduct['prd_variant_id']){
                    $productPrice = $this->getCurrentProductPriceVariant(
                        $requestProduct['id'],
                        $marketplace['int_to'],
                        $requestProduct['prd_variant_id']
                    );
                }else{
                    $productPrice = $this->getCurrentProductPrice($requestProduct['id'], $marketplace['int_to']);
                }

                if ($campaign['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                    $productCampaign = [
                        'campaign_v2_id' => $campaign['id'],
                        'product_id' => $requestProduct['id'],
                        'int_to' => $int_to,
                        'discount_type' => $campaign['discount_type'],
                        'comission_rule' => $campaign['comission_rule'],
                        'fixed_discount' => $campaign['fixed_discount'],
                        'discount_percentage' => $campaign['discount_percentage'],
                        'active' => 1,
                        'approved' => $campaign['products_auto_approval'],
                        'product_price' => $productPrice,
                        'new_comission' => $campaign['new_comission'],
                        'rebate_value' => $campaign['rebate_value'],
                        'marketplace_discount_percentual' => $campaign['discount_percentage'],
                        'marketplace_discount_fixed' => $campaign['marketplace_discount_fixed'],
                    ];
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                        $productCampaign['prd_variant_id'] = $requestProduct['prd_variant_id'];
                    }

                    if ($productElegible) {
                        $productCampaign['discount_type'] = $productElegible['discount_type'];
                        $productCampaign['fixed_discount'] = $productElegible['fixed_discount'];
                        $productCampaign['discount_percentage'] = $productElegible['discount_percentage'];
                        $productCampaign['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                        $productCampaign['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];
                    }

                    $products[] = $productCampaign;

                } elseif (in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])) {

                    $productCampaign = [
                        'campaign_v2_id' => $campaign['id'],
                        'product_id' => $requestProduct['id'],
                        'int_to' => $int_to,
                        'discount_type' => $campaign['discount_type'],
                        'discount_percentage' => $campaign['discount_percentage'],
                        'fixed_discount' => $campaign['fixed_discount'],
                        'comission_rule' => null,
                        'active' => 1,
                        'approved' => $campaign['products_auto_approval'],
                        'product_price' => $productPrice,
                        'new_comission' => null,
                        'rebate_value' => null,
                        'marketplace_discount_percentual' => $campaign['marketplace_discount_percentual'],
                        'marketplace_discount_fixed' => $campaign['marketplace_discount_fixed'],
                        'seller_discount_percentual' => $campaign['seller_discount_percentual'],
                        'seller_discount_fixed' => $campaign['seller_discount_fixed'],
                    ];
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                        $productCampaign['prd_variant_id'] = $requestProduct['prd_variant_id'];
                    }

                    if ($productElegible) {
                        $productCampaign['discount_type'] = $productElegible['discount_type'];
                        $productCampaign['fixed_discount'] = $productElegible['fixed_discount'];
                        $productCampaign['discount_percentage'] = $productElegible['discount_percentage'];
                        $productCampaign['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                        $productCampaign['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];
                        $productCampaign['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
                        $productCampaign['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
                    }

                    $products[] = $productCampaign;

                } elseif (in_array($campaign['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {

                    $productCampaign = [
                        'campaign_v2_id' => $campaign['id'],
                        'product_id' => $requestProduct['id'],
                        'int_to' => $int_to,
                        'active' => 1,
                        'approved' => 1,
                        'product_price' => $productPrice,
                        'maximum_share_sale_price' => $requestProduct['maximum_share_sale_price'] ?? null,
                        'comission_rule' => $requestProduct['comission_rule'],
                    ];

                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                        $productCampaign['prd_variant_id'] = $requestProduct['prd_variant_id'];
                    }

                    if ($requestProduct['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                        $productCampaign['new_comission'] = $requestProduct['new_comission'];
                    } else {
                        $productCampaign['rebate_value'] = $requestProduct['rebate_value'];
                    }

                    $products[] = $productCampaign;

                }

            }

        }

        $productsAutoRepproved = [];
        foreach ($products as $product) {
            if (!$this->addProductToCampaign($campaign, $product)) {
                $productsAutoRepproved[] = $product;
            }
        }

        $message = [];
        if ($this->productsSetDateUpdated) {
            $this->model_products->setDateUpdatedProducts($this->productsSetDateUpdated, null, __METHOD__);
            $message[] = lang('application_products_successfull_added_to_campaign');
        }

        if ($productsAutoRepproved) {
            $message[] = "Os seguintes produtos foram reprovados automaticamente:";
            $lines = '<ul>';
            foreach ($productsAutoRepproved as $productRepproved) {
                $lines .= "<li>Marketplace {$productRepproved['int_to']}, Produto ID: {$productRepproved['product_id']}, Motivo: {$this->productsAutoRejectedMotive[$product['int_to']][$product['product_id']]}</li>";
            }
            $lines .= "</ul>";
            $message[] = $lines;
            $this->session->set_flashdata('error', implode('<br>', $message));
        } else {
            if (!empty($message) && isset($message[0])) {
                $this->session->set_flashdata('success', !empty($message) && isset($message[0]) ? $message[0] : '');
            }
        }

        $this->db->trans_commit();
        ob_clean();
        header('Content-type: application/json');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit(json_encode(['redirect' => site_url('campaigns_v2/products/'.$campaign['id'])]));

    }

    public function add_new_elegible_products(): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();

        $this->db->trans_begin();

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $marketplaces = $this->model_campaigns_v2_marketplaces->getByCampaignId($request['id']);

        $products = [];
        if ($request['add_elegible_products'] && $request['segment'] == CampaignSegment::PRODUCT) {
            foreach ($request['add_elegible_products'] as $requestProduct) {

                $product = [
                    'product_id' => $requestProduct['id'],
                    'maximum_share_sale_price' => null,
                ];

                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($requestProduct['prd_variant_id'])) {
                    $product['prd_variant_id'] = $requestProduct['prd_variant_id'];
                }

                if (in_array($request['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {

                    $product['maximum_share_sale_price'] = $requestProduct['maximum_share_sale_price'] ?? null;

                    foreach ($marketplaces as $marketplace) {
                        $product['int_to'] = $marketplace['int_to'];
                        $product['approved'] = $product['active'] = 1;

                        //Se não definiu no produto, vamos usar da campanha
                        if (isset($requestProduct['comission_rule'])) {
                            $product['comission_rule'] = $requestProduct['comission_rule'];
                        } else {
                            $product['comission_rule'] = $request['comission_rule'];
                        }

                        //Se não foi preenchido no produto, vamos usar da campanha
                        if ($product['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {

                            if (isset($requestProduct['comission_rule']) && $requestProduct['new_comission'] > 0) {
                                $product['new_comission'] = $requestProduct['new_comission'];
                            } else {
                                $product['new_comission'] = $request['new_comission'];
                            }

                        } else {

                            if (isset($requestProduct['rebate_value']) && $requestProduct['rebate_value'] > 0) {
                                $product['rebate_value'] = $requestProduct['rebate_value'];
                            } else {
                                $product['rebate_value'] = $request['rebate_value'];
                            }

                        }

                        $products[] = $product;

                    }

                } else {

                    $product['discount_type'] = $requestProduct['discount_type'];

                    if ($requestProduct['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {

                        $product['fixed_discount'] = $requestProduct['fixed_discount'];

                        if ($request['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {

                            $product['seller_discount_fixed'] = $requestProduct['seller_discount_fixed'];
                            $product['marketplace_discount_fixed'] = $requestProduct['marketplace_discount_fixed'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                            $product['marketplace_discount_fixed'] = $requestProduct['marketplace_discount_fixed'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {
                            $product['seller_discount_fixed'] = $requestProduct['seller_discount_fixed'];
                        }

                    } else {

                        $product['discount_percentage'] = $requestProduct['discount_percentage'];

                        if ($request['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {

                            $product['seller_discount_percentual'] = $requestProduct['seller_discount_percentual'];
                            $product['marketplace_discount_percentual'] = $requestProduct['marketplace_discount_percentual'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                            $product['marketplace_discount_percentual'] = $requestProduct['marketplace_discount_percentual'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {

                            $product['seller_discount_percentual'] = $requestProduct['seller_discount_percentual'];

                        }
                    }

                    $products[] = $product;

                }

            }
        }

        if ($request['segment'] == CampaignSegment::PRODUCT && !$products) {
            $this->generateResponseObject(lang('application_no_product_selected'), 'danger');
        }

        foreach ($products as $product) {
            $product['campaign_v2_id'] = $request['id'];
            $this->addProductToCampaign($request, $product);
        }

        $lib = new \CheckCommissioningChanges();
        $lib->processCommissionings([$request['id']]);

        $this->db->trans_commit();

        ob_clean();
        header('Content-type: application/json');

        $this->session->set_flashdata('success', lang('application_products_successfull_added_to_campaign'));

        exit(json_encode(['redirect' => site_url('campaigns_v2/products/'.$request['id'])]));

    }

    /**
     * Caso for copiar daqui, mover para outro lugar para não duplicar código
     */
    private function generateResponseObject(string $message = '', string $type = 'success')
    {
        ob_start();
        header('Content-type: application/json');
        ob_clean();
        exit(json_encode(['type' => $type, 'message' => $message]));
    }

    /**
     * Funcionalidade final utilizada para adicionar um produto em uma campanha
     * Essa é a parte que todos os lugares chamam para adicionar, pois precisa passar pelas regras de negócio sempre
     * @param  array  $campaign
     * @param  array  $product
     */
    private function addProductToCampaign(array $campaign, array $product): bool
    {


        $productId = $product['product_id'];
        $productVariantId = $product['prd_variant_id'] ?? null;
        $campaignType = $campaign['campaign_type'];
        $intTo = $product['int_to'] ?? null;

        /**
         * Se está tentando colocar produto neste tipo de campanha e já está em outro, não permitiremos manter nos outros
         */
        if ($campaignType == CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE) {

            $productCheck = $this->model_campaigns_v2->getProductsCampaignWithComissionReductionRebate(
                $productId,
                $intTo,
                null,
                $productVariantId
            );
            if ($productCheck) {
                $this->model_campaigns_v2_products->desactivateProduct(
                    $productCheck['id'],
                    $productId,
                    false,
                    null,
                    $productVariantId
                );
            }

            $productStore = $this->model_products->getStore($productId);
            if (!$this->model_campaigns_v2->storeIsInCampaign($product['campaign_v2_id'], $productStore)) {
                $this->model_campaigns_v2_stores->joinCampaign($product['campaign_v2_id'], (int) $productStore);
            }

        } elseif ($campaignType == CampaignTypeEnum::MARKETPLACE_TRADING) {

            $productCheck = $this->model_campaigns_v2->getProductsCampaignWithMarketplaceTrading($productId, $intTo);
            if ($productCheck) {
                $this->model_campaigns_v2_products->desactivateProduct($productCheck['id'], $productId, false);
            }

            $productStore = $this->model_products->getStore($productId);
            if (!$this->model_campaigns_v2->storeIsInCampaign($product['campaign_v2_id'], $productStore)) {
                $this->model_campaigns_v2_stores->joinCampaign($product['campaign_v2_id'], (int) $productStore);
            }

        }

        //Calculando o preço final do produto
        if (isset($product['discount_type'])) {

            $product['product_promotional_price'] = null;

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                if ($product['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT && $product['fixed_discount'] <= 0) {
                    $this->productsAutoRejectedMotive[$product['int_to']][$product['product_id']] = "Desconto fixo inválido: {$product['fixed_discount']}";
                    return false;
                }

                if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL && $product['discount_percentage'] <= 0) {
                    $this->productsAutoRejectedMotive[$product['int_to']][$product['product_id']] = "Não é permito desconto total com valor zero, favor insira um desconto maior que zero ou remova o item da campanha";
                    return false;
                }
            }

            $this->calculateProductPriceByDiscountRule($product);

            //Verificando agora se o percentual deve realizar uma aprovação automática ou remoção do produto
            $max_percentual_auto_approve_products_campaign = $this->model_settings->getValueIfAtiveByName('max_percentual_auto_approve_products_campaign');
            $min_percentual_auto_repprove_products_campaign = $this->model_settings->getValueIfAtiveByName('min_percentual_auto_repprove_products_campaign');

            /**
             * Para entrar nas regras de aprovação/reprovação automático, ambos os parâmetros precisam estar configurados
             * int_to só vai vir na adesão de produtos
             */
            if (isset($product['int_to'])
                && $this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')
                && $max_percentual_auto_approve_products_campaign && $min_percentual_auto_repprove_products_campaign) {

                $comission = $this->model_commissionings->getComissionProduct(
                    $campaign,
                    $product['product_id'],
                    $campaign['start_date'],
                    $campaign['end_date'],
                    $product['int_to']
                );

                $product['percentual_commision'] = $comission['comission'];
                $product['commision_hierarchy'] = $comission['hierarchy'];

                $comission_value = ($product['product_price'] * $product['percentual_commision']) / 100;

                $marketplace_discount_value = 0;
                /**
                 * desconto percentual compartilhado
                 * desconto percentual marketplace
                 */
                if ($product['marketplace_discount_percentual']) {

                    $marketplace_discount_value = ($product['product_price'] * $product['marketplace_discount_percentual']) / 100;

                    //Desconto fixo custeado pelo canal
                } elseif ($campaign['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT
                    && $campaign['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {

                    $marketplace_discount_value = $product['fixed_discount'];

                    /**
                     * desconto fixo compartilhado
                     */
                } elseif ($campaign['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT
                    && $product['marketplace_discount_fixed']) {
                    $marketplace_discount_value = $product['marketplace_discount_fixed'];
                }

                $proportion_from_comission = ($marketplace_discount_value / $comission_value) * 100;
                $product['percentual_from_commision'] = $proportion_from_comission;

                //Por padrão, fica em aprovação
                $product['approved'] = 0;

                //A proporção é inferior ou igual ao minimo para auto aprovação, vamos marcar como aprovado
                if ($proportion_from_comission <= $max_percentual_auto_approve_products_campaign) {
                    $product['approved'] = 1;
                }
                //Se é reprovado automaticamente, parar por aqui e não fazer mais nada
                if ($proportion_from_comission >= $min_percentual_auto_repprove_products_campaign) {
                    $this->productsAutoRejectedMotive[$product['int_to']][$product['product_id']] = "$proportion_from_comission% da comissão de {$comission['comission']}% da comissão no período.";
                    return false;
                }

            }


        }

        if (in_array($campaignType,
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])
            || (isset($product['product_promotional_price']) && $product['product_promotional_price'] > 0)) {

            if (!in_array($campaignType,
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {

                $trade_policies = $this->model_campaigns_v2_trade_policies->getCampaignV2TradePoliciesIds($product['campaign_v2_id']);
                $paymentMethods = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($product['campaign_v2_id']);

                $productsCheck = $this->model_campaigns_v2_products->getProductParticipatingAnotherDiscountCampaign(
                    $product['campaign_v2_id'],
                    $productId,
                    $intTo,
                    $trade_policies,
                    $paymentMethods,
                    $productVariantId
                );
                if ($productsCheck) {

                    foreach ($productsCheck as $productCheck) {

                        $variantId = null;
                        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')
                        && isset($productCheck['prd_variant_id'])){
                            $variantId = $productCheck['prd_variant_id'];
                        }

                        $this->model_campaigns_v2_products->desactivateProduct(
                            $productCheck['campaign_v2_id'],
                            $productCheck['product_id'],
                            true,
                            $productCheck['int_to'],
                            $variantId
                        );

                    }

                }
            }

            //Cadastra o produto na nova campanha
            if (!$this->model_campaigns_v2_products->isProductParticipatingSameDiscountCampaign(
                $product['campaign_v2_id'], $productId, $intTo, $productVariantId))
            {
                $this->model_campaigns_v2_products->create($product);
                if ($campaign['b2w_type'] == 0) {
                    $this->productsSetDateUpdated[] = $productId;
                }
            }

        } else {

            if (!isset($product['int_to'])) {
                //Cadastra o produto na nova campanha
                unset($product['product_promotional_price']);
                $this->model_campaigns_v2_elegible_products->createOrUpdate($product);
            } else {
                if (isset($product['product_promotional_price']) && !$product['product_promotional_price']) {
                    $this->generateResponseObject('Não é possível adicionar um produto com 100% de desconto!',
                        'danger');
                }
            }
        }

        return true;

    }

    /**
     * OEP-1525 - Dilnei
     * @param  array  $campaign
     * @param  int  $product_id
     * @param  string  $dateStart
     * @param  string  $dateEnd
     * @param  string  $int_to
     * @return  array|null
     */
    private function getComissionProduct(
        array $campaign,
        int $product_id,
        string $dateStart,
        string $dateEnd,
        string $int_to
    ): array {

        $tradePolicies = [];
        $paymentMethods = [];
        if ($campaign['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0)) {
            $tradePolicies = $this->model_campaigns_v2_trade_policies->getCampaignV2TradePoliciesIds($campaign['id']);
            $paymentMethods = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($campaign['id']);
        }

        // commissioning_products
        $commissioning_products = $this->model_commissioning_products->getCommissionByProductAndDateRange(
            $int_to,
            $product_id,
            $paymentMethods,
            $tradePolicies,
            $dateStart,
            $dateEnd
        );

        if ($commissioning_products) {
            return [
                'comission' => $commissioning_products['comission'],
                'hierarchy' => ComissioningType::PRODUCT
            ];
        }

        $product_data = $this->model_products->getProductData(0, $product_id);
        $category_id = str_replace('"]', '', str_replace('["', '', $product_data['category_id']));
        $brand_id = str_replace('"]', '', str_replace('["', '', $product_data['brand_id']));
        $store_id = $product_data['store_id'];

        // commissioning_trade_policies - não precisa ser por produto.
        if ($tradePolicies) {
            $commissioning_trade_policies = $this->model_commissioning_trade_policies->getCommissionByTradePolicyAndStoreAndDateRangeAndIntTo(
                $tradePolicies,
                $dateStart,
                $dateEnd,
                $store_id,
                $int_to
            );
            if ($commissioning_trade_policies) {
                return [
                    'comission' => $commissioning_trade_policies['comission'],
                    'hierarchy' => ComissioningType::TRADE_POLICY
                ];
            }
        }

        // commissioning_categories
        if (!empty($category_id)) {
            $commissioning_categories = $this->model_commissioning_categories->getCommissionByCategoryDateRange(
                $store_id,
                $int_to,
                $category_id,
                $dateStart,
                $dateEnd
            );

            if ($commissioning_categories) {
                return [
                    'comission' => $commissioning_categories['comission'],
                    'hierarchy' => ComissioningType::CATEGORY
                ];
            }
        }

        // commissioning_brands
        if (!empty($brand_id)) {
            $commissioning_brands = $this->model_commissioning_brands->getCommissionByBrandAndDateRange(
                $int_to,
                $brand_id,
                $store_id,
                $dateStart,
                $dateEnd
            );

            if ($commissioning_brands) {
                return [
                    'comission' => $commissioning_brands['comission'],
                    'hierarchy' => ComissioningType::BRAND
                ];
            }
        }

        // commissioning_stores
        $commissioning_stores = $this->model_commissioning_stores->getCommissionDateRange(
            $int_to,
            $store_id,
            $dateStart,
            $dateEnd
        );

        if ($commissioning_stores) {
            return [
                'comission' => $commissioning_stores['comission'],
                'hierarchy' => ComissioningType::SELLER
            ];
        }

        $store_data = $this->model_stores->getStoresData($store_id);

        return [
            'comission' => $store_data['service_charge_value'],
            'hierarchy' => ComissioningType::STORE_REGISTER
        ];

    }

    public function teste_preco_campanha_marketplace(int $productId, string $int_to): void
    {
        $this->load->model('model_promotions');
        exit($this->model_promotions->getPriceProduct($productId, '999', $int_to));
    }

    public function validate_campaign_marketplace_trading_segment_stores(): void
    {
        ob_start();
        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        if (!$request['marketplaces']) {
            $this->generateResponseObject(lang('application_select_participating_marketplaces'), 'danger');
        }

        foreach ($request['marketplaces'] as $marketplace) {
            $marketplaces[] = ['int_to' => utf8_decode($marketplace)];
        }

        $data = [];
        $data['result'] = $this->model_campaigns_v2_products->anyStoreHasAnyProductInAnotherCampaignMarketplaceTrading($request['stores'],
            $marketplaces);

        ob_clean();
        header('Content-type: application/json');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit(json_encode($data));
    }

    /**
     * API para cadastro de campanhas
     */
    public function save_insert_edit(): void
    {

        ignore_user_abort(true);
        set_time_limit(0);

        ob_start();

        $this->db->trans_begin();

        $userstore = $this->session->userdata('userstore');
        $compId = $this->session->userdata('usercomp');
        $store_seller_campaign_owner = null;
        if ($compId != 1) {
            if ($userstore == 0) {
                $store_seller_campaign_owner = $compId;
            } else {
                $store_seller_campaign_owner = $userstore;
            }
        }

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $id = $request['id'] ?? null;

        //Montando os objetos para cadastrar na base
        $campaign = [];
        $campaign['id'] = $id;
        $campaign['b2w_type'] = $request['b2w_type'] ? 1 : 0;

        $campaign['seller_type'] = 0;
        if ($request['seller_type']) {
            $campaign['seller_type'] = 2;
            if ($userstore) {
                $campaign['seller_type'] = 1;
            }
        }
        $campaign['store_seller_campaign_owner'] = $store_seller_campaign_owner;

        $campaign['name'] = utf8_decode($request['name']);
        $campaign['start_date'] = $request['start_date'].' '.$request['start_time'].':00';
        $campaign['end_date'] = $request['end_date'].' '.$request['end_time'].':00';
        $campaign['description'] = utf8_decode($request['description']);
        $campaign['campaign_type'] = $request['campaign_type'];
        $campaign['min_seller_index'] = $request['min_seller_index'];
        $campaign['segment'] = $request['segment'];
        $campaign['discount_type'] = $campaign['discount_percentage'] = $campaign['fixed_discount'] = null;
        $campaign['products_auto_approval'] = $request['products_auto_approval'];
        if (in_array(
            $request['campaign_type'],
            [
                CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT,
                CampaignTypeEnum::SHARED_DISCOUNT,
                CampaignTypeEnum::MERCHANT_DISCOUNT,
            ])) {

            $campaign['discount_type'] = $request['discount_type'];
            $campaign['discount_percentage'] = $request['discount_percentage'];
            $campaign['fixed_discount'] = $request['fixed_discount'];

        } elseif (in_array($request['campaign_type'],
            [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $campaign['comission_rule'] = $request['comission_rule'];
            if ($campaign['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                $campaign['new_comission'] = $request['new_comission'];
            } else {
                $campaign['rebate_value'] = $request['rebate_value'];
            }
        }

        $campaign['participating_comission_from'] = $request['participating_comission_from'];
        $campaign['participating_comission_to'] = $request['participating_comission_to'];

        $campaign['deadline_for_joining'] = $request['deadline_for_joining'] ?: null;

        $marketplaces = [];
        $categories = [];
        $products = [];
        $stores = [];

        if (!$request['marketplaces']) {
            $this->generateResponseObject(lang('application_select_participating_marketplaces'), 'danger');
        }

        foreach ($request['marketplaces'] as $marketplace) {
            $marketplaces[] = ['int_to' => utf8_decode($marketplace)];
        }

        if (in_array($request['campaign_type'],
            [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {
            if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {

                $campaign['seller_discount_percentual'] = $request['seller_discount_percentual'];
                $campaign['marketplace_discount_percentual'] = $request['marketplace_discount_percentual'];

            } else {
                $campaign['seller_discount_fixed'] = $request['seller_discount_fixed'];
                $campaign['marketplace_discount_fixed'] = $request['marketplace_discount_fixed'];
            }
        }

        if ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {
            $campaign['participating_comission_from'] = $request['participating_comission_from'];
            $campaign['participating_comission_to'] = $request['participating_comission_to'];
        }

        if (in_array(
            $request['campaign_type'],
            [
                CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT,
                CampaignTypeEnum::SHARED_DISCOUNT,
                CampaignTypeEnum::MERCHANT_DISCOUNT
            ])) {
            $campaign['product_min_value'] = number_format($request['product_min_value'], 2, '.', '');
            $campaign['product_min_quantity'] = $request['product_min_quantity'];
        }

        /**
         * Segmentação: Produtos
         */
        $elegibleProducts = $request['elegible_products'] ?: $request['add_elegible_products'];
        if ($elegibleProducts && $request['segment'] == CampaignSegment::PRODUCT) {
            foreach ($elegibleProducts as $requestProduct) {

                $product = [
                    'product_id' => $requestProduct['id'],
                    'maximum_share_sale_price' => null,
                ];

                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                    $product['prd_variant_id'] = $requestProduct['prd_variant_id'];
                }

                if (in_array($request['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {

                    $product['maximum_share_sale_price'] = $requestProduct['maximum_share_sale_price'] ?? null;

                    foreach ($marketplaces as $marketplace) {
                        $product['int_to'] = $marketplace['int_to'];
                        $product['approved'] = $product['active'] = 1;

                        //Se não definiu no produto, vamos usar da campanha
                        if (isset($requestProduct['comission_rule'])) {
                            $product['comission_rule'] = $requestProduct['comission_rule'];
                        } else {
                            $product['comission_rule'] = $campaign['comission_rule'];
                        }

                        //Se não foi preenchido no produto, vamos usar da campanha
                        if ($product['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {

                            if (isset($requestProduct['comission_rule']) && $requestProduct['new_comission'] > 0) {
                                $product['new_comission'] = $requestProduct['new_comission'];
                            } else {
                                $product['new_comission'] = $campaign['new_comission'];
                            }

                        } else {

                            if (isset($requestProduct['rebate_value']) && $requestProduct['rebate_value'] > 0) {
                                $product['rebate_value'] = $requestProduct['rebate_value'];
                            } else {
                                $product['rebate_value'] = $campaign['rebate_value'];
                            }

                        }

                        $products[] = $product;

                    }

                } else {

                    $product['discount_type'] = $requestProduct['discount_type'];

                    if ($requestProduct['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {

                        $product['fixed_discount'] = $requestProduct['fixed_discount'];

                        if ($request['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {

                            $product['seller_discount_fixed'] = $requestProduct['seller_discount_fixed'];
                            $product['marketplace_discount_fixed'] = $requestProduct['marketplace_discount_fixed'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                            $product['marketplace_discount_fixed'] = $requestProduct['marketplace_discount_fixed'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {
                            $product['seller_discount_fixed'] = $requestProduct['seller_discount_fixed'];
                        }

                    } else {

                        $product['discount_percentage'] = $requestProduct['discount_percentage'];

                        if ($request['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {

                            $product['seller_discount_percentual'] = $requestProduct['seller_discount_percentual'];
                            $product['marketplace_discount_percentual'] = $requestProduct['marketplace_discount_percentual'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                            $product['marketplace_discount_percentual'] = $requestProduct['marketplace_discount_percentual'];

                        } elseif ($request['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {

                            $product['seller_discount_percentual'] = $requestProduct['seller_discount_percentual'];

                        }
                    }

                    $products[] = $product;

                }

            }
        }

        /**
         * Segmentação: Categorias
         */
        if ($request['categories'] && $request['segment'] == CampaignSegment::CATEGORY) {
            foreach ($request['categories'] as $category) {

                $categories[] = ['category_id' => $category];

            }
        }

        /**
         * Segmentação: Lojas
         */
        if ($request['stores'] && $request['segment'] == CampaignSegment::STORE) {
            foreach ($request['stores'] as $storeId) {

                $stores[$storeId] = [
                    'store_id' => $storeId,
                    'joined' => in_array($request['campaign_type'], [
                        CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING
                    ]) ? 1 : 0
                ];

            }
        }

        $this->validateCampaignData($campaign);

        if ($campaign['segment'] == CampaignSegment::STORE && !$stores) {
            $this->generateResponseObject(lang('application_campaign_stores_not_selected'), 'danger');
        }

        if ($campaign['segment'] == CampaignSegment::CATEGORY && !$categories) {
            $this->generateResponseObject(lang('application_campaign_categories_not_selected'), 'danger');
        }

        if ($campaign['segment'] == CampaignSegment::PRODUCT && !$products) {
            $this->generateResponseObject(lang('application_no_product_selected'), 'danger');
        }

        $campaign['approved'] = (int) in_array('approveCampaignCreation', $this->permission);

        //Salvando os registros
        $campaignId = $this->model_campaigns_v2->create($campaign);

        if ($campaign['segment'] == CampaignSegment::STORE) {
            foreach ($stores as $store) {
                $store['campaign_v2_id'] = $campaignId;
                if (!$this->model_campaigns_v2_stores->exists($campaignId, $store['store_id'])) {
                    $this->model_campaigns_v2_stores->create($store);
                }
            }
        } elseif ($campaign['segment'] == CampaignSegment::CATEGORY) {
            foreach ($categories as $category) {
                $category['campaign_v2_id'] = $campaignId;
                $this->model_campaigns_v2_categories->create($category);
            }
        } else {

            $productsAutoRepproved = [];
            foreach ($products as $product) {
                $product['campaign_v2_id'] = $campaignId;
                if (!$this->addProductToCampaign($campaign, $product)) {
                    $productsAutoRepproved[] = $product;
                }
            }

        }

        foreach ($marketplaces as $marketplace) {
            $marketplace['campaign_v2_id'] = $campaignId;
            $this->model_campaigns_v2_marketplaces->create($marketplace);
        }

        if ($request['paymentMethods']) {

            foreach ($request['paymentMethods'] as $method) {

                $payment_method = [
                    'int_to' => $request['marketplaces'][0],
                    'campaign_v2_id' => $campaignId,
                    'method_id' => $method
                ];

                $this->model_campaigns_v2_payment_methods->relatePaymenMethodToCampaignV2($payment_method);


            }

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                //Descobrindo se é vtex ou occ
                $integrations = $this->model_integrations->getIntegrationsByIntTo($request['marketplaces'][0]);
                if (strstr($integrations[0]['auth_data'], 'oraclecloud.com')){
                    //Setting current campaign as vtex_type
                    $this->model_campaigns_v2->update(['occ_campaign_update' => 2], $campaignId);
                }else{
                    //Setting current campaign as vtex_type
                    $this->model_campaigns_v2->update(['vtex_campaign_update' => 2], $campaignId);
                }
            }else{
                //Setting current campaign as vtex_type
                $this->model_campaigns_v2->update(['vtex_campaign_update' => 2], $campaignId);
            }

        }

        if ($request['tradePolicies']) {

            foreach ($request['tradePolicies'] as $trade_policy_id) {

                $trade_policy = [
                    'int_to' => $request['marketplaces'][0],
                    'campaign_v2_id' => $campaignId,
                    'trade_policy_id' => $trade_policy_id
                ];

                $this->model_campaigns_v2_trade_policies->relateTradePolicyToCampaignV2($trade_policy);

            }

            //Setting current campaign as vtex_type
            $this->model_campaigns_v2->update(['vtex_campaign_update' => 2], $campaignId);

        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            if (isset($productsAutoRepproved) && $productsAutoRepproved) {
                $message[] = "Os seguintes produtos não podem ser cadastrados:";
                $lines = '<ul>';
                foreach ($productsAutoRepproved as $productRepproved) {
                    $lines .= "<li>Produto ID: {$productRepproved['product_id']}, Motivo: {$this->productsAutoRejectedMotive[$product['int_to']][$product['product_id']]}</li>";
                }
                $lines .= "</ul>";
                $message[] = $lines;
                $this->db->trans_rollback();
            } else {
                $this->session->set_flashdata('success',
                    $id ? $this->lang->line('messages_successfully_updated') : $this->lang->line('messages_successfully_created'));
                $this->db->trans_commit();
            }
        }else{
            $this->session->set_flashdata('success',
                $id ? $this->lang->line('messages_successfully_updated') : $this->lang->line('messages_successfully_created'));
        }

        ob_clean();
        header('Content-type: application/json');
        if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            //@todo pode remover essa linha
            $this->db->trans_commit();
        }
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

            if (isset($productsAutoRepproved) && $productsAutoRepproved){
                exit(json_encode(['message' => implode('<br>', $message), 'type' => 'danger']));
            }else{
                exit(json_encode(['redirect' => site_url('campaigns_v2')]));
            }

        }else{
            exit(json_encode(['redirect' => site_url('campaigns_v2')]));
        }

    }

    /**
     * @param  array  $campaign
     */
    private function validateCampaignData(array $campaign): void
    {

        $this->form_validation->set_data($campaign);

        $this->form_validation->set_rules('name', $this->lang->line('application_name_campaign'), 'trim|required');
        $this->form_validation->set_rules('start_date', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('end_date', $this->lang->line('application_end_date'), 'trim|required');

        $this->form_validation->set_rules('campaign_type', $this->lang->line('application_campaign_type'),
            'trim|required');
        $this->form_validation->set_rules('segment', $this->lang->line('application_campaign_segment_by'),
            'trim|required');

        if ($campaign['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT) {

            if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {

                $this->form_validation->set_rules(
                    'seller_discount_percentual',
                    $this->lang->line('application_seller_discount'),
                    'trim|required'
                );
                $this->form_validation->set_rules(
                    'marketplace_discount_percentual',
                    $this->lang->line('application_marketplace_discount'),
                    'trim|required'
                );

                if ($campaign['seller_discount_percentual'] + $campaign['marketplace_discount_percentual'] != $campaign['discount_percentage']) {
                    $this->generateResponseObject(
                        lang('application_campaign_the_sum_seller_discount_marketplace_cannot_be_different_from_total_discount'),
                        'danger'
                    );
                }

            } else {

                $this->form_validation->set_rules(
                    'seller_discount_fixed',
                    $this->lang->line('application_seller_discount'),
                    'trim|required'
                );
                $this->form_validation->set_rules(
                    'marketplace_discount_fixed',
                    $this->lang->line('application_marketplace_discount'),
                    'trim|required'
                );

                if ($campaign['seller_discount_fixed'] + $campaign['marketplace_discount_fixed'] != $campaign['fixed_discount']) {
                    $this->generateResponseObject(
                        lang('application_campaign_the_sum_seller_discount_marketplace_cannot_be_different_from_total_discount'),
                        'danger'
                    );
                }

            }

        } elseif (in_array($campaign['campaign_type'],
            [CampaignTypeEnum::MERCHANT_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {

            if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                $this->form_validation->set_rules('discount_percentage',
                    $this->lang->line('application_discount_percentage'), 'trim|required');
            } else {
                $this->form_validation->set_rules('fixed_discount', $this->lang->line('application_fixed_discount'),
                    'trim|required');
            }

        } elseif (in_array($campaign['campaign_type'],
            [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
            $this->form_validation->set_rules('comission_rule', $this->lang->line('application_comission_rule'),
                'trim|required');
            if ($campaign['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                $this->form_validation->set_rules('new_comission', $this->lang->line('application_new_comission'),
                    'trim|required');
            } else {
                $this->form_validation->set_rules('rebate_value', $this->lang->line('application_comission_rebate'),
                    'trim|required');
            }
        }

        if (!$this->form_validation->run()) {
            $this->generateResponseObject(validation_errors(), 'danger');
        }

        if ($campaign['start_date'] >= $campaign['end_date']) {
            $this->generateResponseObject(lang('application_campaign_select_final_date_higher_than_start_date'),
                'danger');
        }
        if (!$campaign['start_date'] || strlen($campaign['start_date']) != 19 || !$campaign['end_date'] | strlen($campaign['end_date']) != 19) {
            $this->generateResponseObject("Data/hora início e data/hora fim são obrigatórios",'danger');
        }

        if ($campaign['deadline_for_joining'] && $campaign['deadline_for_joining'] > $campaign['end_date']) {
            $this->generateResponseObject(lang('application_invalid_deadline_for_joining'), 'danger');
        }

        $endDateTime = DateTime::createFromFormat(DATETIME_INTERNATIONAL, $campaign['end_date']);
        $endMinutes = $endDateTime->format('i');
        if (!in_array($endMinutes, [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55])) {
            $this->generateResponseObject(lang('application_please_select_date_from_options'), 'danger');
        }

    }

    /**
     * @throws Exception
     */
    public function upload_csv_stores()
    {
        ob_start();
        try {

            $rows = readTempCsv($_FILES['file']['tmp_name'], 0, ['ID da Loja']);

            $rowsReturn = [];
            foreach ($rows as $row) {
                $rowsReturn[] = ['ID' => $row['ID da Loja']];
            }

        } catch (Exception $exception) {
            $this->generateResponseObject($exception->getMessage(), 'danger');
        }

        ob_clean();
        header('Content-type: application/json');
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }
        echo json_encode($rowsReturn);

    }

    public function upload_csv_products()
    {
        ob_start();
        $postData = json_decode($this->postClean('entry'), true);

        try {

            $schema = ['ID do Produto', 'Tipo do Desconto', 'Desconto Seller', 'Desconto Marketplace'];
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                $schema[] = "ID da Loja";
                $schema[] = "SKU";
            }

            $rows = readTempCsv($_FILES['file']['tmp_name'], 1000, $schema);

            $products = [];

            foreach ($rows as &$row) {

                //Validações 1º
                if (!in_array($row['Tipo do Desconto'], ['Porcentagem', 'Fixo'])) {
                    throw new Exception("Informe o tipo do Desconto em todos os produtos");
                }

                // Validação para garantir que ou "ID do Produto" ou "ID da Loja" + "SKU" esteja preenchido quando a feature flag estiver habilitada
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                    if (empty($row['ID do Produto']) && (empty($row['ID da Loja']) || empty($row['SKU']))) {
                        throw new Exception("Informe o ID do Produto ou ID da Loja + SKU");
                    }
                } else {
                    if (empty($row['ID do Produto'])) {
                        throw new Exception("Informe o ID do Produto");
                    }
                }

                // Determina o identificador do produto para mensagens de erro
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                    $prodIdentifier = !empty($row['ID do Produto']) 
                        ? "produto id {$row['ID do Produto']}" 
                        : "produto com SKU {$row['SKU']} da loja {$row['ID da Loja']}";
                } else {
                    $prodIdentifier = "produto id {$row['ID do Produto']}";
                }

                if (
                    $postData['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT &&
                    ((!$row['Desconto Seller'] && $row['Desconto Seller'] !== "0") ||
                    (!$row['Desconto Marketplace'] && $row['Desconto Marketplace'] !== "0"))
                ) {
                    throw new Exception("Informe o desconto Seller e o Desconto Marketplace no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT && (!is_numeric($row['Desconto Seller']) || !is_numeric($row['Desconto Marketplace']))) {
                    throw new Exception("Informe somente números do desconto Seller e o Desconto Marketplace no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT && !$row['Desconto Seller']) {
                    throw new Exception("Informe o desconto Seller no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT && !is_numeric($row['Desconto Seller'])) {
                    throw new Exception("Informe o desconto Seller somente números no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT && $row['Desconto Marketplace']) {
                    throw new Exception("Informe somente o desconto Seller no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT && !$row['Desconto Marketplace']) {
                    throw new Exception("Informe o desconto Marketplace no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT && !is_numeric($row['Desconto Marketplace'])) {
                    throw new Exception("Informe o desconto Marketplace somente números no {$prodIdentifier}");
                }
                if ($postData['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT && $row['Desconto Seller'] > 0) {
                    throw new Exception("Informe somente o desconto Marketplace no {$prodIdentifier}");
                }
                // Validação para garantir que os IDs são numéricos
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                    if (!empty($row['ID do Produto']) && !is_numeric($row['ID do Produto'])) {
                        throw new Exception("Informe somente números no ID do Produto");
                    }
                    if (!empty($row['ID da Loja']) && !is_numeric($row['ID da Loja'])) {
                        throw new Exception("Informe somente números no ID da Loja");
                    }
                } else {
                    if ($postData['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT && !is_numeric($row['ID do Produto'])) {
                        throw new Exception("Informe somente números no produto id {$row['ID do Produto']}");
                    }
                }

                // Decide como buscar o produto com base nos dados fornecidos
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && empty($row['ID do Produto']) && !empty($row['ID da Loja']) && !empty($row['SKU'])) {
                    $product = $this->model_products->getProductToMassiveImportCampaign(null, $row['SKU'], $row['ID da Loja']);
                } else {
                    $product = $this->model_products->getProductToMassiveImportCampaign($row['ID do Produto']);
                }

                if ($row['Desconto Marketplace'] == "") {
                    $row['Desconto Marketplace'] = 0;
                }

                if ($row['Desconto Seller'] == "") {
                    $row['Desconto Seller'] = 0;
                }

                if ($product) {

                    $productsFound = [];

                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                        //Se está retornando um array de produtos, precisamos tratar como tal
                        if (!isset($product['id'])){
                            //Reconhecemos que veio um array
                            $productsFound = $product;
                        }else{
                            //Reconhecemos que é um produto só
                            $productsFound[] = $product;
                        }
                    }else{
                        //Mantemos como é, na remoção da flag pode remover sem problemas
                        $productsFound[] = $product;
                    }

                    foreach ($productsFound as $product) {
                        $product['another_discount_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                            null,
                            $product['id']
                        );
                        $product['another_comission_rebate_campaign'] = false;
                        $product['another_marketplace_trading_campaign'] = false;
                        $product['maximum_share_sale_price'] = null;

                        if ($row['Tipo do Desconto'] == 'Porcentagem') {

                            $row['Desconto Marketplace'] = number_format($row['Desconto Marketplace'], 0, '.', '');
                            $row['Desconto Seller'] = number_format($row['Desconto Seller'], 0, '.', '');

                            $product['discount_type'] = DiscountTypeEnum::PERCENTUAL;

                            if ($postData['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                                //Desconto Marketplace
                                $product['discount_percentage'] = $row['Desconto Marketplace'];
                                $product['seller_discount_percentual'] = 0;
                                $product['marketplace_discount_percentual'] = $row['Desconto Marketplace'];

                            } elseif ($postData['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {

                                //Desconto seller
                                $product['discount_percentage'] = $row['Desconto Seller'];
                                $product['seller_discount_percentual'] = $row['Desconto Seller'];
                                $product['marketplace_discount_percentual'] = 0;

                            } else {

                                //Compartilhado
                                $product['discount_percentage'] = $row['Desconto Seller'] + $row['Desconto Marketplace'];
                                $product['seller_discount_percentual'] = $row['Desconto Seller'];
                                $product['marketplace_discount_percentual'] = $row['Desconto Marketplace'];

                            }

                        } else {

                            $row['Desconto Marketplace'] = number_format($row['Desconto Marketplace'], 2, '.', '');
                            $row['Desconto Seller'] = number_format($row['Desconto Seller'], 2, '.', '');

                            $product['discount_type'] = DiscountTypeEnum::FIXED_DISCOUNT;

                            if ($postData['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT) {

                                //Desconto Marketplace
                                $product['fixed_discount'] = $row['Desconto Marketplace'];
                                $product['seller_discount_fixed'] = 0;
                                $product['marketplace_discount_fixed'] = $row['Desconto Marketplace'];

                            } elseif ($postData['campaign_type'] == CampaignTypeEnum::MERCHANT_DISCOUNT) {

                                //Desconto seller
                                $product['fixed_discount'] = $row['Desconto Seller'];
                                $product['seller_discount_fixed'] = $row['Desconto Seller'];
                                $product['marketplace_discount_fixed'] = 0;

                            } else {

                                /**
                                 * Compartilhado
                                 * @noinspection PhpWrongStringConcatenationInspection
                                 */
                                $product['fixed_discount'] = $row['Desconto Seller'] + $row['Desconto Marketplace'];
                                $product['seller_discount_fixed'] = $row['Desconto Seller'];
                                $product['marketplace_discount_fixed'] = $row['Desconto Marketplace'];

                            }

                        }

                        $products[] = $product;

                    }

                }

            }

        } catch (Exception $exception) {
            $this->generateResponseObject($exception->getMessage(), 'danger');
        }

        ob_clean();
        header('Content-type: application/json');
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        if (!$products) {
            $this->generateResponseObject("Nenhum produto disponível encontrado.", 'danger');
        }

        echo json_encode($products);

    }

    public function upload_csv_products_reduction_comission_rebate_campaign()
    {
        ob_start();
        $rowsReturn = [];
        $ids = [];
        try {

            $rows = readTempCsv($_FILES['file']['tmp_name'], 1000,
                ['ID do Produto', 'Preco Venda Maxima Acao', 'Nova Comissao', 'Rebate']);

            foreach ($rows as $row) {

                if ($row['Nova Comissao'] && $row['Rebate']) {
                    throw new Exception("Produto {$row['ID do Produto']} foi informado nova comissão {$row['Nova Comissao']} e rebate {$row['Rebate']}, por favor, informe apenas uma das colunas");
                }

                if (in_array($row['ID do Produto'], $ids)) {
                    throw new Exception("Produto {$row['ID do Produto']} já foi inserido na mesma planilha, não é possível que o produto participe de redução de comissão e rebate juntos.");
                }

                $ids[] = $row['ID do Produto'];

                if ($row['Nova Comissao'] && !is_numeric($row['Nova Comissao'])) {
                    throw new Exception("Produto {$row['ID do Produto']} não foi informado valores numéricos em Nova Comissão");
                }
                if ($row['Rebate'] && !is_numeric($row['Rebate'])) {
                    throw new Exception("Produto {$row['ID do Produto']} não foi informado valores numéricos em Rebate");
                }

                //Se não preencheu nada na planilha, vamos auto preencher com o que vier da grid gerais
                if (!$row['Rebate'] && !$row['Nova Comissao'] && $this->postClean('comission_rule')) {
                    if ($this->postClean('comission_rule') == ComissionRuleEnum::NEW_COMISSION) {
                        $row['Nova Comissao'] = $this->postClean('new_comission');
                    } else {
                        $row['Rebate'] = $this->postClean('comission_rebate');
                    }
                }

                $rowItem = [];
                $rowItem['ID'] = $row['ID do Produto'];
                $rowItem['Preco Venda Maxima Acao'] = $row['Preco Venda Maxima Acao'] ? number_format($row['Preco Venda Maxima Acao'],
                    2, '.', '') : '';
                $rowItem['Nova Comissao'] = $row['Nova Comissao'];
                $rowItem['Rebate'] = $row['Rebate'] ? number_format($row['Rebate'], 2, '.', '') : '';

                $rowsReturn[] = $rowItem;

            }

        } catch (Exception $exception) {
            $this->generateResponseObject($exception->getMessage(), 'danger');
        }

        $products = $this->model_products->searchProductsToMassiveImportCampaign($ids);

        if ($products) {
            foreach ($products as &$product) {
                $product['another_discount_campaign'] = false;
                $product['another_comission_rebate_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherComissionReductionRebateCampaign($product['id']);
                $product['another_marketplace_trading_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherMarketplaceTradingCampaign($product['id']);
                foreach ($rowsReturn as $rowProduct) {
                    if ($rowProduct['ID'] == $product['id']) {
                        $product['maximum_share_sale_price'] = $rowProduct['Preco Venda Maxima Acao'];
                        if ($rowProduct['Nova Comissao']) {
                            $product['comission_rule'] = ComissionRuleEnum::NEW_COMISSION;
                            $product['new_comission'] = number_format($rowProduct['Nova Comissao'], 2, '.', '');
                        } elseif ($rowProduct['Rebate']) {
                            $product['comission_rule'] = ComissionRuleEnum::COMISSION_REBATE;
                            $product['rebate_value'] = number_format($rowProduct['Rebate'], 2, '.', '');
                        } else {
                            $product['comission_rule'] = $this->postClean('comission_rule');
                            if ($product['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {
                                $product['new_comission'] = number_format($this->postClean('new_comission'), 2, '.',
                                    '');
                            } else {
                                $product['rebate_value'] = number_format($this->postClean('rebate_value'), 2, '.', '');
                            }
                        }
                    }
                }
            }
        }

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($products);
        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

    }

    public function upload_approvement_products(int $campaignId)
    {
        ob_start();
        //Se é usuário, não pode ter acesso
        $userStore = $this->session->userdata('userstore');
        if ($userStore || !$this->data['only_admin'] && $this->data['usercomp'] != 1) {
            redirect('campaigns_v2', 'refresh');
        }

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        try {

            $rows = readTempCsv($_FILES['file']['tmp_name'], 1000, ['ID do Produto']);

            $ids = [];
            foreach ($rows as $row) {
                $ids[] = $row['ID do Produto'];
            }

            $this->model_campaigns_v2_products->approveAllProducts($campaignId, $ids);

            $marketplaces = array_map(function ($item) {
                return $item['int_to'];
            }, $this->model_campaigns_v2_marketplaces->getByCampaignId($campaignId));

            if ($campaign['b2w_type'] == 0) {
                foreach ($ids as $product) {
                    if (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                        $active = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                            $campaignId,
                            $product,
                            $marketplaces,
                            [],
                            [],
                            0,
                            0
                        );

                    }else{

                        $active = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                            $campaignId,
                            $product,
                            $marketplaces,
                            [],
                            [],
                            0
                        );

                    }
                    foreach ($marketplaces as $marketplace) {
                        $productsCheck = $this->model_campaigns_v2_products->getProductParticipatingAnotherDiscountCampaign(
                            $campaignId,
                            $product,
                            $marketplace
                        );
                        $this->model_products->setDateUpdatedProduct($product, null, __METHOD__,
                            array(
                                'int_to' => $marketplace,
                                'active' => $active,
                                'price' => $productsCheck['product_promotional_price'],
                                'list_price' => $productsCheck['product_price']
                            )
                        );
                    }
                }
            }

            $this->session->set_flashdata('success', lang('application_products_successfull_approved'));

            ob_clean();
            header('Content-type: application/json');
            if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
                saveSlowQueries();
            }
            exit(json_encode(['redirect' => site_url('campaigns_v2/products/'.$campaign['id'])]));

        } catch (Exception $exception) {
            $this->generateResponseObject($exception->getMessage(), 'danger');
        }

    }

    public function upload_products_seller(int $campaignId)
    {

        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        if (!$campaign) {
            $this->session->set_flashdata('error', lang('application_campaign_not_fount'));
            redirect('campaigns_v2', 'refresh');
        }

        try {

            $rows = readTempCsv($_FILES['file']['tmp_name'], 1000, ['ID da Loja', 'ID do Produto', 'SKU']);

            $productsIds = [];
            $productVariantIds = [];

            foreach ($rows as $row) {

                if ($row['ID do Produto'] && ($row['ID da Loja'] || $row['SKU'])) {
                    throw new Exception("Produto {$row['ID do Produto']}, informar somente ID do Produto. Informado: ID da Loja: {$row['ID da Loja']}, SKU: {$row['SKU']}");
                }

                if ($row['ID do Produto'] && $row['ID da Loja'] && $row['SKU']) {
                    throw new Exception("Produto SKU {$row['SKU']}, informar somente SKU e ID da Loja");
                }

                if ($row['SKU'] && !$row['ID da Loja']) {
                    throw new Exception("Produto SKU {$row['SKU']}, informar ID da Loja");
                }

                $product = $this->model_products->getProductToMassiveImportCampaign(
                    (int) $row['ID do Produto'],
                    $row['SKU'],
                    (int) $row['ID da Loja']
                );

                if ($product) {

                    //Agora pode ser um array
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                        foreach ($product as $productRow){
                            $productVariantIds[] = $productRow['prd_variant_id'];
                            $productsIds[] = $productRow['id'];
                        }
                    }else{
                        $productsIds[] = $product['id'];
                    }

                }

            }

            if (!$productsIds) {
                throw new Exception("Nenhum dos produtos enviados por planilha estão disponíveis para adesão na campanha");
            }

            $this->searchProductsElegible($campaignId, $productsIds, 10000, $productVariantIds);

        } catch (Exception $exception) {
            $this->generateResponseObject($exception->getMessage(), 'danger');
        }

    }

    /**
     * Busca os produtos elegíveis para adicionar na campanha
     */
    public function searchProductsElegible(int $campaignId = null, array $productIds = [], $limit = 10000, $productVariantIds = []): void
    {
        ob_start();
        $searchString = xssClean($this->input->get('searchString'));

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $campaignId = (is_array($request) && isset($request['id'])) ? $request['id'] : $campaignId;

        $currentCampaign = $this->model_campaigns_v2->getCampaignById($campaignId);
        $marketplaces = $this->model_campaigns_v2_marketplaces->getByCampaignId($campaignId);
        $marketplacesIntTo = [];
        foreach ($marketplaces as $marketplace) {
            $marketplacesIntTo[] = $marketplace['int_to'];
        }

        $productsElegible = [];

        $categoriesIds = [];
        $storesIds = [];
        $productsIds = $productIds;

        if (!$productsIds &&
            (!in_array($currentCampaign['campaign_type'],
                [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) &&
            $currentCampaign['segment'] == CampaignSegment::PRODUCT) {

            $productsElegible = $this->model_campaigns_v2_elegible_products->getByCampaignId($currentCampaign['id']);

            if ($productsElegible) {
                foreach ($productsElegible as $product) {
                    $productsIds[] = $product['product_id'];
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['prd_variant_id'])) {
                        $productVariantIds[] = $product['prd_variant_id'];
                    }
                }
            }

        } elseif ($currentCampaign['segment'] == CampaignSegment::STORE) {

            $stores = $this->model_campaigns_v2_stores->getByCampaignId($currentCampaign['id']);
            foreach ($stores as $store) {
                if ($store['joined']) {
                    $storesIds[] = $store['store_id'];
                }
            }

        } elseif ($currentCampaign['segment'] == CampaignSegment::CATEGORY) {

            $categories = $this->model_campaigns_v2_categories->getByCampaignId($currentCampaign['id']);

            foreach ($categories as $category) {
                if (!$request['categories'] || $this->isCategoryIdInRequestStoreId($category['category_id'],
                        $request['categories'])) {
                    $categoriesIds[] = $category['category_id'];
                }
            }

        }

        //Se é um lojista logado, o filtro por loja será sempre da loja dele
        $storesIds = $this->model_stores->getMyCompanyStoresArrayIds();

        $products = $this->model_products->searchProductsToCampaign(
            $searchString,
            $currentCampaign['participating_comission_from'],
            $currentCampaign['participating_comission_to'],
            $currentCampaign['product_min_value'],
            $currentCampaign['product_min_quantity'],
            $currentCampaign['min_seller_index'],
            $storesIds,
            $categoriesIds,
            $productsIds,
            $limit,
            $currentCampaign['id'],
            $productVariantIds
        );

        $data = [];
        $data['message'] = '';

        if ($productsIds) {

            foreach ($productsIds as $productId) {

                $found = false;
                $productKey = null;
                foreach ($products as $key => $product) {
                    if ($product['id'] == $productId) {
                        $found = true;
                        $productKey = $key;
                        break;
                    }
                }

                // Get product variant ID if available
                $prd_variant_id = null;
                foreach ($products as $p) {
                    if ($p['id'] == $productId && \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($p['prd_variant_id'])) {
                        $prd_variant_id = $p['prd_variant_id'];
                        break;
                    }
                }

                if (!$found && $this->model_campaigns_v2_products->isProductInCampaign($currentCampaign['id'],
                        $productId, $prd_variant_id)) {
                    $data['message'] .= "Produto id: {$productId} já participante da campanha<br>";
                    $data['type'] = 'warning';
                }

            }

        }

        //If campaign segment is product, some product must be filtered out
        if (CampaignSegment::PRODUCT == $currentCampaign['segment']) {

            $newArrayProducts = [];
            foreach ($products as $product) {
                $prd_variant_id = null;
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['prd_variant_id'])) {
                    $prd_variant_id = $product['prd_variant_id'];
                }

                if ($this->model_campaigns_v2_elegible_products->getProductByCampaignId(
                    $currentCampaign['id'],
                    $product['id'],
                    $prd_variant_id
                )) {
                    $newArrayProducts[] = $product;
                } else {
                    $data['message'] .= "Produto id: {$product['id']} não participante da campanha<br>";
                    $data['type'] = 'warning';
                }
            }

            $products = $newArrayProducts;

        }

        $finalArrayProducts = [];

        if ($products) {
            foreach ($products as &$product) {

                // Get product variant ID if available
                $prd_variant_id = null;
                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($product['prd_variant_id'])) {
                    $prd_variant_id = $product['prd_variant_id'];
                }

                if ($this->model_campaigns_v2_products->isProductInCampaign($currentCampaign['id'], $product['id'], $prd_variant_id)){
                    continue;
                }

                $product['price'] = money($product['price']);

                $product['another_discount_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                    $campaignId,
                    $product['id'],
                    $marketplacesIntTo
                );

                $product['another_comission_rebate_campaign'] = false;
                $product['another_marketplace_trading_campaign'] = false;
                if ($this->data['only_admin'] && $this->data['usercomp'] == 1) {
                    $product['another_comission_rebate_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherComissionReductionRebateCampaign($product['id'],
                        $marketplacesIntTo);
                    $product['another_marketplace_trading_campaign'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherMarketplaceTradingCampaign($product['id']);
                }

                $product['comission_rule'] = '';
                $product['new_comission'] = '';
                $product['rebate_value'] = '';

                if (in_array($currentCampaign['campaign_type'],
                    [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                    $product['comission_rule'] = $currentCampaign['comission_rule'];
                    $product['rebate_value'] = $currentCampaign['rebate_value'];
                    $product['new_comission'] = $currentCampaign['new_comission'];
                }

                if ($productsElegible) {
                    foreach ($productsElegible as $productElegible) {
                        $found = $productElegible['product_id'] == $product['id'];
                        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
                            $found = $found && $productElegible['prd_variant_id'] == $product['prd_variant_id'];
                        }
                        if ($found) {
                            $product['discount_type'] = $productElegible['discount_type'];
                            $product['fixed_discount'] = $productElegible['fixed_discount'];
                            $product['discount_percentage'] = $productElegible['discount_percentage'];
                            $product['seller_discount_percentual'] = $productElegible['seller_discount_percentual'];
                            $product['seller_discount_fixed'] = $productElegible['seller_discount_fixed'];
                            $product['marketplace_discount_percentual'] = $productElegible['marketplace_discount_percentual'];
                            $product['marketplace_discount_fixed'] = $productElegible['marketplace_discount_fixed'];
                        }
                    }
                } else {
                    $product['discount_type'] = $currentCampaign['discount_type'];
                    $product['fixed_discount'] = $currentCampaign['fixed_discount'];
                    $product['discount_percentage'] = $currentCampaign['discount_percentage'];
                    $product['seller_discount_percentual'] = $currentCampaign['seller_discount_percentual'];
                    $product['seller_discount_fixed'] = $currentCampaign['seller_discount_fixed'];
                    $product['marketplace_discount_percentual'] = $currentCampaign['marketplace_discount_percentual'];
                    $product['marketplace_discount_fixed'] = $currentCampaign['marketplace_discount_fixed'];
                }

                $finalArrayProducts[] = $product;

            }
            $products = $finalArrayProducts;
        }

        if (!$products) {
            $this->generateResponseObject('Nenhum produto buscado está disponível para adesão na campanha', 'danger');
        }

        $data['products'] = $products;

        ob_clean();
        header('Content-type: application/json');

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        exit(json_encode($data));
    }

    private function isCategoryIdInRequestStoreId(int $categoryId, array $requestCategories = []): bool
    {

        //Se não está enviando nenhuma categoria, não está filtrando por categoria
        if (!$requestCategories) {
            return true;
        }

        foreach ($requestCategories as $requestCategory) {
            if (isset($requestCategory['id']) && $requestCategory['id'] == $categoryId || $requestCategory == $categoryId) {
                return true;
            }
        }

        return false;

    }

    public function productIsAnotherCampaign(int $product, string $marketplaces = '', string $tradePolicies = '', string $paymentMethods = '', string $campaignV2Id = null, string $prd_variant_id = null)
    {

        $marketplacesIntTo = [];
        if ($marketplaces) {
            $marketplacesIntTo = explode('|', urldecode($marketplaces));
        }

        $trade_policies_array = [];
        if ($tradePolicies && $tradePolicies != 'null') {
            $trade_policies_array = explode('|', urldecode($tradePolicies));
        }

        $payment_methods_array = [];
        if ($paymentMethods && $paymentMethods != 'null') {
            $payment_methods_array = explode('|', urldecode($paymentMethods));
        }

        $return = [];
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $prd_variant_id) {
            $return['exist'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                $campaignV2Id,
                $product,
                $marketplacesIntTo,
                $trade_policies_array,
                $payment_methods_array,
                null,
                null,
                $prd_variant_id
            );
            $return['b2w_exist'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherB2wCampaign($product, $prd_variant_id);
        } else {
            $return['exist'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                $campaignV2Id,
                $product,
                $marketplacesIntTo,
                $trade_policies_array,
                $payment_methods_array
            );
            $return['b2w_exist'] = $this->model_campaigns_v2_products->isProductParticipatingAnotherB2wCampaign($product);
        }

        $returnJson = json_encode($return);

        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')) {
            saveSlowQueries();
        }

        return $this->output->set_content_type('application/json')->set_output($returnJson);
    }

    public function arrayProductIsAnotherCampaign(string $marketplaces = '', string $tradePolicies = '', string $paymentMethods = '', string $campaignV2Id)
    {

        ignore_user_abort(true);
        set_time_limit(0);

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $marketplacesIntTo = [];
        if ($marketplaces) {
            $marketplacesIntTo = explode('|', urldecode($marketplaces));
        }

        $trade_policies_array = [];
        if ($tradePolicies && $tradePolicies != 'null') {
            $trade_policies_array = explode('|', urldecode($tradePolicies));
        }

        $payment_methods_array = [];
        if ($paymentMethods && $paymentMethods != 'null') {
            $payment_methods_array = explode('|', urldecode($paymentMethods));
        }

        $return = [];
        $return['exist'] = [];
        $return['b2w_exist'] = [];

        if ($request) {
            foreach ($request as $product) {

                $productId = $product;
                $productVariantId = null;

                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                    $productId = $product['id'];
                    $productVariantId = $product['prd_variant_id'];
                }

                if ($this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                    $campaignV2Id,
                    $productId,
                    $marketplacesIntTo,
                    $trade_policies_array,
                    $payment_methods_array,
                    null,
                    null,
                    $productVariantId
                )) {
                    $return['exist'][] = $product;
                }
                if ($this->model_campaigns_v2_products->isProductParticipatingAnotherB2wCampaign($productId)) {
                    $return['b2w_exist'][] = $product;
                }
            }
        }

        $returnJson = json_encode($return);

        saveSlowQueries();

        return $this->output->set_content_type('application/json')->set_output($returnJson);
    }

    public function newcampaign()
    {

        if (!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('campaign_v2_tutorial_title');

        $this->data['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');

        $userstore = $this->session->userdata('userstore');

        // 1 - determina se o usuario é seller ou marketplace
        if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
            if ($this->model_campaigns_v2->getCountCampaignsByStore() <= 0) {
                $this->render_template('campaigns_v2/tutorial_mktplace', $this->data);
            } else {
                $this->render_template('campaigns_v2/campaign_types', $this->data);
            }

        } else {
            $this->model_campaigns_v2->getMyCampaigns([], $userstore);

            if ($this->model_campaigns_v2->getCountCampaignsByStore() > 0) {
//                $this->render_template('campaigns_v2/tutorial_mktplace', $this->data);
            } else {
//                $this->render_template('campaigns_v2/campaign_types', $this->data);
            }
        }


//        $this->render_template('campaigns_v2/index', $this->data);
//
//        //Debug
//        if ($this->model_settings->getValueIfAtiveByName('enable_debug_campaigns')){
//            $this->output->enable_profiler(true);
//        }
//        if ($this->model_settings->getValueIfAtiveByName('save_slow_queries_campaigns')){
//            saveSlowQueries();
//        }
        /*
                exit;

                $this->data['teste'] = '';

                $userstore = $this->session->userdata('userstore');

                // 1 - determina se o usuario é seller ou marketplace
                if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore)
                {
                    if ($this->model_campaigns_v2->getCountCampaignsByStore() > 0)
                    {
        //                $this->render_template('campaigns_v2/tutorial_mktplace');
                        $this->render_template('campaigns_v2/create');
                    }

                }
                else
                {
                    $this->model_campaigns_v2->getMyCampaigns([], $userstore);
                }*/

    }

    public function campaigntypes()
    {
        if (!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('campaign_v2_campaign_type_title');

        $this->data['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->data['allow_create_campaigns_b2w_type'] = $this->model_settings->getValueIfAtiveByName('allow_create_campaigns_b2w_type');

        $userstore = $this->session->userdata('userstore');

        // 1 - determina se o usuario é seller ou marketplace
        if ($this->data['only_admin'] && $this->data['usercomp'] == 1 && !$userstore) {
            $this->render_template('campaigns_v2/campaign_types', $this->data);
        }
    }

}
