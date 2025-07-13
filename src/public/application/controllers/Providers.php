<?php
/*
SW Serviços de Informática 2019

Controller de Fornecedores

*/

use Firebase\JWT\JWT;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_DB_query_builder $db
 * @property CI_Session $session
 * @property CI_Form_validation $form_validation
 * @property CI_Config $config
 *
 * @property Model_providers $model_providers
 * @property Model_stores $model_stores
 * @property Model_company $model_company
 * @property Model_billet $model_billet
 * @property Model_integrations $model_integrations
 *
 * @property JWT $jwt
 */

class Providers extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_providers');

        $this->load->model('model_providers');
        $this->load->model('model_stores');
        $this->load->model('model_company');
        $this->load->model('model_billet');
        $this->load->model('model_integrations');

        $this->load->library('JWT');
    }

    /**
    * It only redirects to the manage providers page
    */
    public function index()
    {
        // Verifica se tem permissão
        if(!in_array('viewProviders', $this->permission))
            redirect('dashboard', 'refresh');

        $result = $this->model_providers->getProviderData();

        $this->data['results'] = $result;
        $this->render_template('providers/index', $this->data);
    }

    /**
    * Fetches the orders data from the orders table
    * this function is called from the datatable ajax function
    */
    public function fetchProvidersData()
    {
        $result = array('data' => array());

        $data = $this->model_providers->getProviderData();

        foreach ($data as $key => $value) {

            // button
            $buttons = '';

//            if(in_array('viewProviders', $this->permission)) {
//                $buttons .= '<a target="__blank" href="'.base_url('providers/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
//            }

            if(in_array('updateProviders', $this->permission)) {
                $buttons .= ' <a href="'.base_url('providers/update/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i></a>';
            }

            $result['data'][$key] = array(
                $value['id'],
                $value['razao_social'],
                $value['name'],
                $value['address'].", ".$value['addr_num'].", ".$value['addr_compl'].", ".$value['addr_neigh'],
                $this->formatPhone($value['phone']),
                $value['active'] == 1 ? '<span class="label label-success">Ativo</span>': '<span class="label label-danger">Inativo</span>',
                $buttons
            );
        } // /foreach

        echo json_encode($result);
    }

    /*
    * It redirects to the company page and displays all the company information
    * It also updates the company information into the database if the
    * validation for each input field is successfully valid
    */
    public function create()
    {
        // Verifica se tem permissão
        if(!in_array('createProviders', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $getProviderCNPJ = $this->model_providers->getProviderDataForCnpj($this->postClean('cnpj'));
        if ($getProviderCNPJ) {
            $this->session->set_flashdata('error', $this->lang->line('application_error_shipping_company_cnpj'));
            redirect('providers/create', 'refresh');
        }
        
        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['company_list'] = $this->model_company->getAllCompanyData();
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
        $this->form_validation->set_rules('phone', $this->lang->line('application_phone'), 'trim|required');
        $this->form_validation->set_rules('cnpj', $this->lang->line('application_cnpj'), 'trim|required');

        if ($this->postClean('exempted') != "1") {
            $this->form_validation->set_rules('txt_insc_estadual', $this->lang->line('application_iest'), 'trim|required|callback_checkInscricaoEstadual['.$this->postClean("addr_uf").']');
            $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        }

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required|valid_email');

        $this->data['marketplaces'] = $this->model_integrations->get_integrations_list();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['stores_by_provider'] = array();
       
        if ($this->form_validation->run() == TRUE) {
            // true case

            $startdt = new DateTime('now'); // setup a local datetime
            $start_format = $startdt->format('Y-m-d H:i:s');

            $data = array(
                'data_inclusao'     => $start_format,
                'data_alteracao'    => '',

                //'active'            => $this->postClean('active') ? 1 : 0,
                'active'            => $this->postClean('active') ? 1 : 0,
                'active_token_api'  => $this->postClean('active_token_api') ? 1 : 0,
                'token_api'         => $this->postClean('token_api'),
                'name'              => $this->postClean('name'),
                'razao_social'      => $this->postClean('raz_soc'),
                'phone'             => onlyNumbers($this->postClean('phone')),
                'cnpj'              => $this->postClean('cnpj'),

                'responsible_name'  => $this->postClean('responsible_name'),
                'responsible_email' => $this->postClean('responsible_email'),
                'responsible_cpf'   => $this->postClean('responsible_cpf'),
                
				'tracking_web_site'  => $this->postClean('tracking_web_site'),
				
                'responsible_oper_name'  => $this->postClean('responsible_oper_name'),
                'responsible_oper_email' => $this->postClean('responsible_oper_email'),
                'responsible_oper_cpf'   => $this->postClean('responsible_oper_cpf'),
                
                'responsible_finan_name'  => $this->postClean('responsible_finan_name'),
                'responsible_finan_email' => $this->postClean('responsible_finan_email'),
                'responsible_finan_cpf'   => $this->postClean('responsible_finan_cpf'),
                
                'tipo_fornecedor'       => $this->postClean('slc_tipo_provider'),
                'observacao'            => $this->postClean('txt_observacao'),

                'marketplace'           => empty($this->postClean('marketplace', true)) ? null : $this->postClean('marketplace', true),

                'zipcode'           => onlyNumbers($this->postClean('zipcode')),
                'address'           => $this->postClean('address'),
                'addr_num'          => $this->postClean('addr_num'),
                'addr_compl'        => $this->postClean('addr_compl'),
                'addr_neigh'        => $this->postClean('addr_neigh'),
                'addr_city'         => $this->postClean('addr_city'),
                'addr_uf'           => $this->postClean('addr_uf'),

                'bank'              => $this->postClean('bank'),
                'agency'            => $this->postClean('agency'),
                'account_type'      => $this->postClean('account_type'),
                'account'           => $this->postClean('account'),
                
                'regiao_entrega'    => $this->postClean('txt_regiao_entrega'),
                'regiao_coleta'     => $this->postClean('txt_regiao_coleta'),
                'tempo_coleta'      => $this->postClean('txt_tempo_coleta'),
                'fluxo_fin'         => $this->postClean('txt_fluxo_fin'),
                'credito'           => $this->postClean('slc_val_credito'),
                'val_credito'       => $this->postClean('txt_val_credito'),
                'ship_min'          => $this->postClean('slc_val_ship_min'),
                'val_ship_min'      => $this->postClean('txt_val_ship_min'),
                'qtd_min'           => $this->postClean('slc_qtd_min'),
                'val_qtd_min'       => $this->postClean('txt_qtd_min'),
                'tipo_pagamento'    => $this->postClean('slc_tipo_pagamento'),
                'store_id'          => $this->postClean('slc_store'),
                'company_id'        => $this->postClean('slc_company'),
                'tipo_produto'      => $this->postClean('txt_tipo_produto'),
                'insc_estadual'     => $this->postClean('exempted') == "1" ? "0" : $this->postClean('txt_insc_estadual'),
                'slc_tipo_cubage'   => $this->postClean('slc_tipo_cubage') == "FreteCubadoSim" ? 1 : 0,
                'cubage_factor'     => $this->postClean('cubage_factor') == "" ? null : $this->postClean('cubage_factor'),
                'ad_valorem'        => $this->postClean('ad_valorem') == "" ? null : $this->postClean('ad_valorem') ,
                'gris'              => $this->postClean('gris') == "" ? null : $this->postClean('gris'),
                'toll'              => $this->postClean('toll') == "" ? null : $this->postClean('toll'),
                'shipping_revenue'  => $this->postClean('shipping_revenue') == "" ? null : $this->postClean('shipping_revenue')
            );

            $this->db->trans_begin();
            $insert = $this->model_providers->create($data);

            if (!empty($this->postClean('stores'))) {
                $this->model_stores->updateStoresByStores($this->postClean('stores'), array('provider_id' => $insert));
            }

            if ($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('providers/', 'refresh');
            }

            $this->db->trans_commit();

            if($insert == true) {
                if ($this->postClean('active_token_api')) {
                    $data['token_api'] = $this->createTokenAPI($insert, $this->postClean('responsible_email'));
                    $this->model_providers->update($data, $insert);
                }
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('providers/', 'refresh');
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('providers/create', 'refresh');
            }
        }
        else {
            $this->data['page_title'] = $this->lang->line('application_register_provider');
            if (!is_null($this->postClean('crcli'))) {
                $this->session->set_flashdata('error', 'Erro no fomulário:');
            }
            $this->render_template('providers/create', $this->data);
        }
    }



    /*
    * It redirects to the provider page and displays all the provider information
    * It also updates the provider information into the database if the
    * validation for each input field is successfully valid
    */
    public function update($id)
    {

        // Verifica se tem permissão
        if(!in_array('updateProviders', $this->permission))
            redirect('dashboard', 'refresh');

        // Verifica se o fornecedor existe
        if($this->model_providers->getProviderData($id) === null)
            redirect('providers', 'refresh');

        if($this->postClean('cnpj')) {
            $getProviderCNPJ = $this->model_providers->getProviderDataForCnpj($this->postClean('cnpj'));
       
            if ($getProviderCNPJ && $getProviderCNPJ['id'] != $id) {
                $this->session->set_flashdata('error', $this->lang->line('application_error_shipping_company_cnpj'));
                redirect('providers/', 'refresh');
            }
        }
        

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['company_list'] = $this->model_company->getAllCompanyData();
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
        $this->form_validation->set_rules('phone', $this->lang->line('application_phone'), 'trim|required');
        $this->form_validation->set_rules('cnpj', $this->lang->line('application_cnpj'), 'trim|required');

        if ($this->postClean('exempted') != "1") {
            $this->form_validation->set_rules('txt_insc_estadual', $this->lang->line('application_iest'), 'trim|required|callback_checkInscricaoEstadual['.$this->postClean('addr_uf').']');
            $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        }

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'trim|required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'trim|required|valid_email');

        $fields = Array();
        if (!is_null($this->postClean('crcli'))) {
            $fields['active']               = $this->postClean('active') ? 1 : 0;
            $fields['active_token_api']     = $this->postClean('active_token_api') ? 1 : 0;
            $fields['token_api']            = $this->postClean('token_api');
            $fields['name']                 = $this->postClean('name');
            $fields['razao_social']         = $this->postClean('raz_soc');
            $fields['phone']                = $this->postClean('phone');
            $fields['cnpj']                 = $this->postClean('cnpj');

            $fields['responsible_name']     = $this->postClean('responsible_name');
            $fields['responsible_email']    = $this->postClean('responsible_email');
            $fields['responsible_cpf']      = $this->postClean('responsible_cpf');

            $fields['zipcode']              = $this->postClean('zipcode');
            $fields['address']              = $this->postClean('address');
            $fields['addr_num']             = $this->postClean('addr_num');
            $fields['addr_compl']           = $this->postClean('addr_compl');
            $fields['addr_neigh']           = $this->postClean('addr_neigh');
            $fields['addr_city']            = $this->postClean('addr_city');
            $fields['addr_uf']              = $this->postClean('addr_uf');

            $fields['bank']                 = $this->postClean('bank');
            $fields['agency']               = $this->postClean('agency');
            $fields['account_type']         = $this->postClean('account_type');
            $fields['account']              = $this->postClean('account');
            
            $fields['tracking_web_site']     = $this->postClean('tracking_web_site');
			
            $fields['responsible_oper_name']     = $this->postClean('responsible_oper_name');
            $fields['responsible_oper_email']    = $this->postClean('responsible_oper_email');
            $fields['responsible_oper_cpf']      = $this->postClean('responsible_oper_cpf');
            
            $fields['responsible_finan_name']    = $this->postClean('responsible_finan_name');
            $fields['responsible_finan_email']   = $this->postClean('responsible_finan_email');
            $fields['responsible_finan_cpf']     = $this->postClean('responsible_finan_cpf');
            
            $fields['tipo_fornecedor']           = $this->postClean('slc_tipo_provider');
            $fields['observacao']                = $this->postClean('txt_observacao');

            $fields['marketplace']           = empty($this->postClean('marketplace')) ? null : $this->postClean('marketplace');
            
            $fields['regiao_entrega']    = $this->postClean('txt_regiao_entrega');
            $fields['regiao_coleta']     = $this->postClean('txt_regiao_coleta');
            $fields['tempo_coleta']      = $this->postClean('txt_tempo_coleta');
            $fields['fluxo_fin']         = $this->postClean('txt_fluxo_fin');
            $fields['credito']           = $this->postClean('slc_val_credito');
            $fields['val_credito']       = $this->postClean('txt_val_credito');
            $fields['ship_min']          = $this->postClean('slc_val_ship_min');
            $fields['val_ship_min']      = $this->postClean('txt_val_ship_min');
            $fields['qtd_min']           = $this->postClean('slc_qtd_min');
            $fields['val_qtd_min']       = $this->postClean('txt_qtd_min');
            $fields['tipo_pagamento']    = $this->postClean('slc_tipo_pagamento');
            $fields['store_id']          = $this->postClean('slc_store');
            $fields['company_id']        = $this->postClean('slc_company');
            $fields['tipo_produto']      = $this->postClean('txt_tipo_produto');
            $fields['insc_estadual']     = $this->postClean('txt_insc_estadual');
            $fields['slc_tipo_cubage']   = $this->postClean('slc_tipo_cubage');
            $fields['cubage_factor']     = $this->postClean('cubage_factor');
            $fields['ad_valorem']        = $this->postClean('ad_valorem');
            $fields['gris']              = $this->postClean('gris');
            $fields['toll']              = $this->postClean('toll');
            $fields['shipping_revenue']  = $this->postClean('shipping_revenue');
        }
        $this->data['fields'] = $fields;

        $this->data['marketplaces'] = $this->model_integrations->get_integrations_list();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['stores_by_provider'] = array_map(function($store){
            return $store['id'];
        }, $this->model_stores->getStoresByProvider($id));

        if ($this->form_validation->run() == TRUE) {
            // true case

            $startdt = new DateTime('now'); // setup a local datetime
            $start_format = $startdt->format('Y-m-d H:i:s');

            $data = array(
                'data_alteracao'    => $start_format,

                'active'            => $this->postClean('active') ? 1 : 0,
                'active_token_api'  => $this->postClean('active_token_api') ? 1 : 0,
                // 'token_api'         => $this->postClean('token_api'),
                'name'              => $this->postClean('name'),
                'razao_social'      => $this->postClean('raz_soc'),
                'phone'             => onlyNumbers($this->postClean('phone')),
                'cnpj'              => $this->postClean('cnpj'),
				
				'tracking_web_site'  => $this->postClean('tracking_web_site'),
				
                'responsible_name'  => $this->postClean('responsible_name'),
                'responsible_email' => $this->postClean('responsible_email'),
                'responsible_cpf'   => $this->postClean('responsible_cpf'),
                
                'responsible_oper_name'  => $this->postClean('responsible_oper_name'),
                'responsible_oper_email' => $this->postClean('responsible_oper_email'),
                'responsible_oper_cpf'   => $this->postClean('responsible_oper_cpf'),
                
                'responsible_finan_name'  => $this->postClean('responsible_finan_name'),
                'responsible_finan_email' => $this->postClean('responsible_finan_email'),
                'responsible_finan_cpf'   => $this->postClean('responsible_finan_cpf'),
                
                'tipo_fornecedor'       => $this->postClean('slc_tipo_provider',TRUE),
                'observacao'            => $this->postClean('txt_observacao',TRUE),

                'marketplace'           => empty($this->postClean('marketplace', true)) ? null : $this->postClean('marketplace', true),

                'zipcode'           => onlyNumbers($this->postClean('zipcode',TRUE)),
                'address'           => $this->postClean('address',TRUE),
                'addr_num'          => $this->postClean('addr_num',TRUE),
                'addr_compl'        => $this->postClean('addr_compl',TRUE),
                'addr_neigh'        => $this->postClean('addr_neigh',TRUE),
                'addr_city'         => $this->postClean('addr_city',TRUE),
                'addr_uf'           => $this->postClean('addr_uf',TRUE),

                'bank'              => $this->postClean('bank',TRUE),
                'agency'            => $this->postClean('agency',TRUE),
                'account_type'      => $this->postClean('account_type',TRUE),
                'account'           => $this->postClean('account',TRUE),

                'regiao_entrega'    => $this->postClean('txt_regiao_entrega'),
                'regiao_coleta'     => $this->postClean('txt_regiao_coleta'),
                'tempo_coleta'      => $this->postClean('txt_tempo_coleta'),
                'fluxo_fin'         => $this->postClean('txt_fluxo_fin'),
                'credito'           => $this->postClean('slc_val_credito'),
                'val_credito'       => $this->postClean('txt_val_credito'),
                'ship_min'          => $this->postClean('slc_val_ship_min'),
                'val_ship_min'      => $this->postClean('txt_val_ship_min'),
                'qtd_min'           => $this->postClean('slc_qtd_min'),
                'val_qtd_min'       => $this->postClean('txt_qtd_min'),
                'tipo_pagamento'    => $this->postClean('slc_tipo_pagamento'),
                'store_id'          => $this->postClean('slc_store'),
                'company_id'        => $this->postClean('slc_company'),
                'tipo_produto'      => $this->postClean('txt_tipo_produto'),
                'insc_estadual'     => $this->postClean('exempted') == "1" ? "0" : $this->postClean('txt_insc_estadual'),
                'slc_tipo_cubage'   => $this->postClean('slc_tipo_cubage') == "FreteCubadoSim" ? 1 : 0,
                'cubage_factor'     => $this->postClean('cubage_factor') == "" ? null : $this->postClean('cubage_factor'),
                'ad_valorem'        => $this->postClean('ad_valorem') == "" ? null : $this->postClean('ad_valorem') ,
                'gris'              => $this->postClean('gris') == "" ? null : $this->postClean('gris'),
                'toll'              => $this->postClean('toll') == "" ? null : $this->postClean('toll'),
                'shipping_revenue'  => $this->postClean('shipping_revenue') == "" ? null : $this->postClean('shipping_revenue')
            );

            $this->db->trans_begin();

            if ($this->postClean('active_token_api')) {
                $data['token_api'] = $this->createTokenAPI($id, $this->postClean('responsible_email'));
				if ($fields['token_api'] == null || $fields['token_api'] == '') {
					$refresh = true;
				}
            }

            $update = $this->model_providers->update($data, $id);
            $this->model_stores->updateStoresByProvider($id);

            if (!empty($this->postClean('stores'))) {
                $this->model_stores->updateStoresByStores($this->postClean('stores'), array('provider_id' => $id));
            }

            if ($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('providers/update/'.$id, 'refresh');
            }

            $this->db->trans_commit();
            
            if($update == true) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                if (isset($refresh)) {
                    redirect('providers/update/'.$id, 'refresh');
                }
                redirect('providers/', 'refresh');
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('providers/update/'.$id, 'refresh');
            }
        }
        else {
            $provider_data = $this->model_providers->getProviderData($id);
            $providerToSeller = $this->model_providers->getProviderToSellerData($id);
            //dd($providerToSeller);
            $this->data['provider_data'] = $provider_data;
            // SW - Log Update
            get_instance()->log_data('Fornecedores','edit before',json_encode($provider_data),"I");
            if (!is_null($this->postClean('crcli'))) {
                $this->session->set_flashdata('error', 'Form Errors:');
            }
            else{
                $fields = Array();
                $fields['idProvider']           = $id;
                $fields['active']               = $provider_data['active'];
                $fields['active_token_api']     = $provider_data['active_token_api'];
                $fields['token_api']            = $provider_data['token_api'];
                $fields['cnpj']                 = $provider_data['cnpj'];
                $fields['name']                 = $provider_data['name'];
                $fields['razao_social']         = $provider_data['razao_social'];
                $fields['phone']                = $this->formatPhone($provider_data['phone']);

                $fields['responsible_name']     = $provider_data['responsible_name'];
                $fields['responsible_cpf']      = $provider_data['responsible_cpf'];
                $fields['responsible_email']    = $provider_data['responsible_email'];
                
				$fields['tracking_web_site']     = $provider_data['tracking_web_site'];
				
                $fields['responsible_oper_name']     = $provider_data['responsible_oper_name'];
                $fields['responsible_oper_cpf']      = $provider_data['responsible_oper_cpf'];
                $fields['responsible_oper_email']    = $provider_data['responsible_oper_email'];
                
                $fields['responsible_finan_name']     = $provider_data['responsible_finan_name'];
                $fields['responsible_finan_cpf']      = $provider_data['responsible_finan_cpf'];
                $fields['responsible_finan_email']    = $provider_data['responsible_finan_email'];
                
                $fields['tipo_fornecedor']          = $provider_data['tipo_fornecedor'];
                $fields['observacao']               = $provider_data['observacao'];

                $fields['marketplace']              = $provider_data['marketplace'];
                
                $fields['zipcode']              = $this->formatCep($provider_data['zipcode']);
                $fields['address']              = $provider_data['address'];
                $fields['addr_num']             = $provider_data['addr_num'];
                $fields['addr_compl']           = $provider_data['addr_compl'];
                $fields['addr_neigh']           = $provider_data['addr_neigh'];
                $fields['addr_city']            = $provider_data['addr_city'];
                $fields['addr_uf']              = $provider_data['addr_uf'];

                $fields['bank']                 = $provider_data['bank'];
                $fields['agency']               = $provider_data['agency'];
                $fields['account_type']         = $provider_data['account_type'];
                $fields['account']              = $provider_data['account'];
                
                
                $fields['regiao_entrega']    = $provider_data['regiao_entrega'];
                $fields['regiao_coleta']     = $provider_data['regiao_coleta'];
                $fields['tempo_coleta']      = $provider_data['tempo_coleta'];
                $fields['fluxo_fin']         = $provider_data['fluxo_fin'];
                $fields['credito']           = $provider_data['credito'];
                $fields['val_credito']       = $provider_data['val_credito'];
                $fields['ship_min']          = $provider_data['ship_min'];
                $fields['val_ship_min']      = $provider_data['val_ship_min'];
                $fields['qtd_min']           = $provider_data['qtd_min'];
                $fields['val_qtd_min']       = $provider_data['val_qtd_min'];
                $fields['tipo_pagamento']    = $provider_data['tipo_pagamento'];
                $fields['store_id']          = $provider_data['store_id'];
                $fields['company_id']        = $providerToSeller['company_id'] ?? null;
                $fields['tipo_produto']      = $provider_data['tipo_produto'];
                $fields['insc_estadual']     = $provider_data['insc_estadual'];
                $fields['slc_tipo_cubage']   = $provider_data['slc_tipo_cubage'];
                $fields['cubage_factor']     = $provider_data['cubage_factor'];
                $fields['ad_valorem']        = $provider_data['ad_valorem'];
                $fields['gris']              = $provider_data['gris'];
                $fields['toll']              = $provider_data['toll'];
                $fields['shipping_revenue']  = $provider_data['shipping_revenue'];

                $this->data['fields'] = $fields;
            }
            $this->data['page_title'] = $this->lang->line('application_update_provider');
            $teste = $this->data;
            $this->render_template('providers/edit', $this->data);
        }
    }

    public function listindicacao(){
        
        // Verifica se tem permissão
        if(!in_array('viewProviders', $this->permission))
            redirect('dashboard', 'refresh');
            
        $this->data['transportadoras'] = $this->model_providers->getProviderData();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        
        $this->render_template('providers/listindicacao', $this->data);
        
        
    }

    function checkInscricaoEstadual($ie, $uf) 
    {
        $ok = ValidatesIE::check($ie, $uf);
        if (!$ok) {
            $this->form_validation->set_message('checkInscricaoEstadual', '{field} inválida.');
		}
		return $ok;
	}
    
    public function fetchIndicacaoGridDataTransp($transportadora = null, $loja = null)
    {

        $result = array('data' => array());
        
        $data = $this->model_providers->getProvidersIndifcacao(null, $transportadora, $loja);
        
        foreach ($data as $key => $value) {
            
            $buttons = '';
            
            if(in_array('updateProviders', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="editarIndicacao(\''.$value['id_pi'].'\')"><i class="fa fa-pencil"></i></button>';
                $buttons .= ' <button type="button" class="btn btn-default" onclick="excluirIndicacao(\''.$value['id_pi'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }
            $ativo = '';
            
            if($value['ativo'] == "1"){
                $ativo = "Sim";
            }else{
                $ativo = "Não";
            }
            
            $result['data'][$key] = array(
                $value['id_pi'],
                $value['ship_company'],
                $value['loja'],
                $value['percentual_desconto']."%",
                $ativo,
                $buttons
            );
        }
        
        echo json_encode($result);
    }
    
    public function salvarindicacaotransp(){
        
        $inputs = $this->postClean();

        if($inputs['slc_transportadora_new'] <> "" && $inputs['slc_loja_new'] <> "" && $inputs['txt_desconto'] <> "" ){
            $data = $this->model_providers->salvaIndicacaoTransp($inputs);
            if($data){
                echo "0;Indicação cadastrada com sucesso";
            }else{
                echo "1;Erro ao cadastrar indicação.";
            }
        }
        
        
    }
    
    public function removeindicacao(){
        
        $inputs = $this->postClean();

        if($inputs['txt_hdn_id_remove'] <> "" ){
            $data = $this->model_providers->removerIndicacaoTransp($inputs);
            if($data){
                echo "0;Indicação excluida com sucesso";
            }else{
                echo "1;Erro ao excluir indicação.";
            }
        }
        
    }
    
    public function editindicacao($id = null){
        
        // Verifica se tem permissão
        if(!in_array('viewProviders', $this->permission))
            redirect('dashboard', 'refresh');
            
            $this->data['transportadoras'] = $this->model_providers->getProviderData();
            $this->data['stores'] = $this->model_stores->getActiveStore();
            
            $valores = $this->model_providers->getProvidersIndifcacao($id);

            $this->data['valores'] = $valores[0];
            
            $this->render_template('providers/editindicacao', $this->data);
            
            
    }
    
    public function editarindicacaotransp(){
        
        $inputs = $this->postClean();
        
        if($inputs['txt_hdn_id'] <> "" && $inputs['slc_transportadora'] <> "" && $inputs['slc_loja'] <> "" && $inputs['txt_desconto'] <> "" ){
            
            if(array_key_exists("active",$inputs)){
                $inputs['txt_ativo'] = "1";
            }else{
                $inputs['txt_ativo'] = "0";
            }
            
            $data = $this->model_providers->editarIndicacaoTransp($inputs);
            if($data){
                echo "0;Indicação editada com sucesso";
            }else{
                echo "1;Erro ao editar indicação.";
            }
        }
        
    }

    public function createTokenAPI($provider_id, $email)
    {
        $key = $this->config->config['encryption_key'];

        $payload = array(
            "provider_id" => $provider_id,
            "email"       => $email
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return $this->jwt->encode($payload, $key);
    }

    function validaCnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        
        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;

        // Verifica se todos os digitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj))
            return false;	

        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;

        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}