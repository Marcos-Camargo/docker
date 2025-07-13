<?php
/*
 SW Serviços de Informática 2019
 
 Controller do Dashboard
 
 */

use GuzzleHttp\Utils;

require 'system/libraries/Vendor/autoload.php';

class Dashboard extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_dashboard');
        
        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_users');
        $this->load->model('model_stores');
        $this->load->model('model_company');
        $this->load->model('model_log_history');
        $this->load->model('model_blingultenvio');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_campaigns');
        $this->load->model('model_settings');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_products_catalog');

        $this->load->library('Tunalibrary');

        //Starting Tuna integration library
        $this->integration = new Tunalibrary();

    }
    
    /*
     * It only redirects to the manage category page
     * It passes the total product, total paid orders, total users, and total stores information
     into the frontend.
     */
    
    public function testetuna($idPedido, $valorEstorno = null){

        $order = $this->model_orders->getOrdersData(0, $idPedido);

        $retorno = $this->integration->geracancelamentotuna($order, $valorEstorno);

        dd($retorno);


    }

    
    public function index()
    {
        $user_id = $this->session->userdata('id');
        $is_admin = ($user_id == 1) ? true :false;
        
        $usercomp = $this->session->userdata('usercomp');
        $usergroup = $this->session->userdata('group_id');
        $this->data['usercomp'] = $usercomp;
        $this->data['usergroup'] = $usergroup;
        $this->data['permissions'] = $this->permission;
        $more = " company_id = ".$usercomp;
        $moreProdInt = " company_id = ".$usercomp;

        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');        
        $this->data['dashboard_conecta']  = $this->model_settings->getStatusbyName('enable_dashboard_box');

        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }

        $dashSellerIndex = null;
        $dashMonitoring  = null;
        $settingDashSellerIndex = $this->model_settings->getSettingDatabyName('metabase_dashboard_seller_index');
        $settingDashMonitoring = $this->model_settings->getSettingDatabyName('metabase_dashboard_monitoring');

        if ($settingDashSellerIndex && $settingDashSellerIndex['status'] == 1) {
            $dashSellerIndex = $settingDashSellerIndex['value'];
        }

        if ($settingDashMonitoring && $settingDashMonitoring['status'] == 1) {
            $dashMonitoring = $settingDashMonitoring['value'];
        }
        
        // campos removidos dos dashboards         
        $this->data['total_products_campaigns'] =  0;
	    $this->data['total_orders_pending_action'] = 0;
        $this->data['total_orders_canceled'] = 0; 
        $this->data['total_products_high_stock'] = 0;
        $this->data['total_orders_last_post_day'] = 0;
        $this->data['total_products_low_stock'] = 0;
        $this->data['total_products_published'] = 0;
		$this->data['total_paid_orders'] = 0;

        $this->data['total_produtos_sem_integracao'] = 0;
        $this->data['total_produtos_sem_categoria_bling'] = 0;
        $this->data['total_lojas_sem_terminar_cadastro_fr'] = 0;
        $this->data['total_campos_sem_integracao_bling'] = 0;
        $this->data['total_novas_categorias_lojas'] = 0;
        $this->data['total_categorias_cadastrar_FR_expiradas'] = 0;

		$this->data['total_products'] = $this->model_products->countTotalProducts();
		$this->data['total_products_active'] = $this->model_products->countTotalProductsActive();
		$this->data['total_products_incomplet'] = $this->model_products->countTotalProductsIncomplet();
		$this->data['total_products_without_stock'] = $this->model_products->countTotalProductsWithoutStock();
		$this->data['total_products_out_price'] = $this->model_products->getProductsOutOfPrice();
		$this->data['total_orders_waiting_invoice'] = $this->model_orders->getOrderWaitingInvoice();
		$this->data['total_order_awaiting_collection'] = $this->model_orders->getOrderAwaitingCollection();
		$this->data['total_orders_in_transport'] = $this->model_orders->getOrdersInTransport();
		$this->data['total_orders_delivered'] = $this->model_orders->getOrderDelivered();
		$this->data['total_orders'] = $this->model_orders->getOrdersCount();
		$this->data['total_orders_delayed_post'] = $this->model_orders->getOrderDelayedPost();

        $productsChangedPrice = $sellerCenter == 'somaplace' ? $this->model_products_catalog->getProductsWithChangedPrice(true) : false;
        $this->data['total_products_with_changed_price'] = $productsChangedPrice ?? 0;

        $this->data['total_stores'] = $this->model_stores->countTotalStores();
        $this->data['total_stores_active'] = $this->model_stores->countTotalStoresActive();

		if(in_array('admDashboard', $this->permission)) {
			$this->data['total_users'] = $this->model_users->countTotalUsers();
			$this->data['total_companies'] = $this->model_company->countTotalCompanies();       	
			$this->data['total_erros_batch'] =0;
			$this->data['total_pedidos_sem_frete'] = $this->model_orders->getOrdersSemFreteCount();
       		$this->data['total_pedidos_entregues_marcar_mkt'] = $this->model_orders->getOrdensFreteEntregueCount();
		}      

        $stores = $this->model_stores->getStoresId();

        $this->data['metabase_graph'] = '';
        $this->data['metabase_graph_seller_index'] = '';
        if ($dashMonitoring) {
            $sendStore = $usercomp == 1 && !is_numeric($dashMonitoring);
            if (is_numeric($dashMonitoring)) {
                $dashMonitoring_adm     = (int)$dashMonitoring;
                $dashMonitoring_seller  = (int)$dashMonitoring;
            }
            else {
                $dashMonitoring         = json_decode($dashMonitoring);
                $dashMonitoring_adm     = (int)$dashMonitoring->admin;
                $dashMonitoring_seller  = (int)$dashMonitoring->seller;
            }
            $this->data['metabase_graph'] = $this->getMetabase('dashboard', $usercomp == 1 ? $dashMonitoring_adm : $dashMonitoring_seller, $sendStore ? array() : array('store_id' => $stores));
        }
        if ($dashSellerIndex) {
            $sendStore = $usercomp == 1 && !is_numeric($dashSellerIndex);
            if (is_numeric($dashSellerIndex)) {
                $dashSellerIndex_adm     = (int)$dashSellerIndex;
                $dashSellerIndex_seller  = (int)$dashSellerIndex;
            }
            else {
                $dashSellerIndex         = json_decode($dashSellerIndex);
                $dashSellerIndex_adm     = (int)$dashSellerIndex->admin;
                $dashSellerIndex_seller  = (int)$dashSellerIndex->seller;
            }
            $this->data['metabase_graph_seller_index'] = $this->getMetabase('dashboard', $usercomp == 1 ? $dashSellerIndex_adm : $dashSellerIndex_seller, $sendStore ? array() : array('store_id' => $stores));
        }

        if (is_file(APPPATH.'views/dashboard/'.$sellerCenter . '.php')) {
            $this->render_template('dashboard/'.$sellerCenter, $this->data);
        }
        else {
            $this->render_template('dashboard/default', $this->data);
        }

        
    }
    
    public function systemHealth()
    {

        include(APPPATH.'config/database.php');
        $this->data['usercomp'] =  $this->session->userdata('usercomp');
        $this->data['usergroup'] = $this->session->userdata('group_id');
        $this->data['permissions'] = $this->permission;

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        else {
            redirect('dashboard', 'refresh');
        }
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_system_health');

        $this->data['sellercenter'] = $sellerCenter;

        $this->data['events_month'] = [];
        if (isset($db['monitor'])){
            $this->load->model('model_monitor_events');
            $this->data['events_month'] = $this->model_monitor_events->getEventsBySellercenterValidity($sellerCenter, ENVIRONMENT, date('mY'));
        }

        get_instance()->load->library('Queue/QueueManager');
        get_instance()->load->model('model_oci_queues');

        $queues = get_instance()->model_oci_queues->findAll();
        $this->data['events_oci_queue'] = [];
        if ($queues){

            foreach ($queues as $queue){

                $itens = QueueManager::getQueueStatus($queue['display_name']);
                if ($itens){
                    $this->data['events_oci_queue'] = array_merge($this->data['events_oci_queue'], $itens);
                }

            }

        }

        $this->render_template('dashboard/systemhealth', $this->data);
        
    }

    public function controlPanel()
    {
    
        $this->load->model('model_monitor_events');
        
        $this->data['usercomp'] =  $this->session->userdata('usercomp');
        $this->data['usergroup'] = $this->session->userdata('group_id');
        $this->data['permissions'] = $this->permission;

        if ( $this->data['usergroup'] != 1) {
            redirect('dashboard', 'refresh');
        }

        if (substr($this->session->userdata('email'),-17) != '@conectala.com.br') {
            redirect('dashboard', 'refresh');
        }
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        else {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_system_health');

        $this->data['sellercenter'] = $sellerCenter;
        $this->data['events_month'] = $this->model_monitor_events->getEventsByValidity(date('mY'));

        $this->render_template('dashboard/controlpanel', $this->data);

    }
}