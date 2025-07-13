<?php
/*
SW Serviços de Informática 2019

Controller de Fornecedores

*/
defined('BASEPATH') OR exit('No direct script access allowed');


class Shippingrules extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_shipping_company');

        $this->load->model('Model_shipping_company');
        $this->load->model('model_stores');
        $this->load->model('model_billet');

        $this->load->library('JWT');

        $this->load->helper('download');
    }

    /**
    * It only redirects to the manage providers page
    */
    public function index()
    {
       // Verifica se tem permissão
        if(!in_array('viewProviders', $this->permission))
            redirect('dashboard', 'refresh');

        // $this->session->userdata['id']
        if($this->session->userdata['usercomp'] != 1){
            if(!is_null($this->session->userdata['id']) && !empty($this->session->userdata['id']))
                $result = $this->Model_shipping_company->getProviderData($this->session->userdata['id']);
        }else{
            $result = $this->Model_shipping_company->getProviderData();
        }       
       
        $this->data['results'] = $result;
        $this->render_template('shipping_rules/list', $this->data);
    }
    
    private function getStates() {
        $states = array(
            "AC-C" => "Acre - Capital",
            "AC-I" => "Acre - Interiior",
            "AC-A" => "Acre - Ambos",
            "AL-C" => "Alagoas - Capital",
            "AL-I" => "Alagoas - Interior",
            "AL-A" => "Alagoas - Ambos",
            "AP-C" => "Amapá - Capital",
            "AP-I" => "Amapá - Interior",
            "AP-A" => "Amapá - Ambos",
            "AM-C" => "Amazonas - Capital",
            "AM-I" => "Amazonas - Interior",
            "AM-A" => "Amazonas - Ambos",
            "BA-C" => "Bahia - Capital",
            "BA-I" => "Bahia - Interior",
            "BA-A" => "Bahia - Ambos",
            "CE-C" => "Ceará - Capital",
            "CE-I" => "Ceará - Interior",
            "CE-A" => "Ceará - Ambos",
            "DF-C" => "Distrito Federal - Capital",
            "DF-I" => "Distrito Federal - Interior",
            "DF-A" => "Distrito Federal - Ambos",
            "ES-C" => "Espírito Santo - Capital",
            "ES-I" => "Espírito Santo - Interior",
            "ES-A" => "Espírito Santo - Ambos",
            "GO-C" => "Goiás - Capital",
            "GO-I" => "Goiás - Interior",
            "GO-A" => "Goiás - Ambos",
            "MA-C" => "Maranhão - Capital",
            "MA-I" => "Maranhão - Interior",
            "MA-A" => "Maranhão - Ambos",
            "MT-C" => "Mato Grosso - Capital",
            "MT-I" => "Mato Grosso - Interior",
            "MT-A" => "Mato Grosso - Ambos",
            "MS-C" => "Mato Grosso do Sul - Capital",
            "MS-I" => "Mato Grosso do Sul - Interior",
            "MS-A" => "Mato Grosso do Sul - Ambos",
            "MG-C" => "Minas Gerais - Capital",
            "MG-I" => "Minas Gerais - Interior",
            "MG-A" => "Minas Gerais - Ambos",
            "PA-C" => "Pará - Capital",
            "PA-I" => "Pará - Interior",
            "PA-A" => "Pará - Ambos",
            "PB-C" => "Paraíba - Capital",
            "PB-I" => "Paraíba - Interior",
            "PB-A" => "Paraíba - Ambos",
            "PR-C" => "Paraná - Capital",
            "PR-I" => "Paraná - Interior",
            "PR-A" => "Paraná - Ambos",
            "PE-C" => "Pernambuco - Capital",
            "PE-I" => "Pernambuco - Interior",
            "PE-A" => "Pernambuco - Ambos",
            "PI-C" => "Piauí - Capital",
            "PI-I" => "Piauí - Interior",
            "PI-A" => "Piauí - Ambos",
            "RJ-C" => "Rio de Janeiro - Capital",
            "RJ-I" => "Rio de Janeiro - Interior",
            "RJ-A" => "Rio de Janeiro - Ambos",
            "RN-C" => "Rio Grande do Norte - Capital",
            "RN-I" => "Rio Grande do Norte - Interior",
            "RN-A" => "Rio Grande do Norte - Ambos",
            "RS-C" => "Rio Grande do Sul - Capital",
            "RS-I" => "Rio Grande do Sul - Interior",
            "RS-A" => "Rio Grande do Sul - Ambos",
            "RO-C" => "Rondônia - Capital",
            "RO-I" => "Rondônia - Interior",
            "RO-A" => "Rondônia - Ambos",
            "RR-C" => "Roraima - Capital",
            "RR-I" => "Roraima - Interior",
            "RR-A" => "Roraima - Ambos",
            "SC-C" => "Santa Catarina - Capital",
            "SC-I" => "Santa Catarina - Interior",
            "SC-A" => "Santa Catarina - Ambos",
            "SP-C" => "São Paulo - Capital",
            "SP-I" => "São Paulo - Interior",
            "SP-A" => "São Paulo - Ambos",
            "SE-C" => "Sergipe - Capital",
            "SE-I" => "Sergipe - Interior",
            "SE-A" => "Sergipe - Ambos",
            "TO-C" => "Tocantins - Capital",
            "TO-I" => "Tocantins - Interior",
            "TO-A" => "Tocantins - Ambos"            
        );

        return $states;
    }

    public function create()
    {
        $result = array();
        $this->data['states'] = $this->getStates();
        $this->data['shippingCompany'] = $this->Model_shipping_company->getProviderData();

        $this->data['results'] = $result;
        $this->render_template('shipping_rules/create', $this->data);
    }

    public function saverules() {
        echo "<pre>";
        var_dump($_POST);
    }
    /**
    * Fetches the orders data from the orders table
    * this function is called from the datatable ajax function
    */
    public function fetchShippingRulesData()
    {
        $result = array('data' => array());
        
        // if($this->session->userdata['usercomp'] == 1){
        //     $data = $this->Model_shipping_company->getProviderData();
        // }else{
        //     if(!is_null($this->session->userdata['id']) && !empty($this->session->userdata['id'])){
        //         $data = $this->Model_shipping_company->getProviderDataUserSellerId($this->session->userdata['id']);
        //     }
        // }

        // foreach ($data as $key => $value) {
        //     $buttons = '';

        //     if($this->session->userdata['usercomp'] != 1){
        //         $buttons .= ' <a href="'.base_url('providers/updatesimplified/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i></a>';    
        //     }else{
        //         $buttons .= ' <a href="'.base_url('providers/update/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i></a>';
        //     }

        //     $updateButton = '<input id="toggle-event" type="checkbox" data-toggle="toggle">';

        //     $result['data'][$key] = array(
        //         $value['id'],
        //         $value['razao_social'],
        //         $value['name'],                
        //         $value['active'] == 1 ? '<input type="checkbox" checked onchange="updateStatus('. $value['id'] .',$(this))">' : '<input type="checkbox" onchange="updateStatus('. $value['id'] .',$(this))">',
        //         $value['tabela'] = '<a href="'.base_url('shippingcompany/tableshippingcompany/'.$value['id']).'" class="btn btn-default"><i class="fa fa-edit"></i> Configurar tabela</a>',
        //         $buttons
        //     );
        // }  

        echo json_encode($result);
    
}

    public function tableshippingcompany($id)
    {
        // $result = $this->Model_shipping_company->getProviderData();        
        $this->data['shipping_company_id'] = $id;
        $this->render_template('shipping_company/tablelist', $this->data);
    }

    public function tablelist($id)
    {
        
        $result = array('data' => array());
        $data = $this->Model_shipping_company->getTableConfigShipping($id, (int)$this->session->userdata['id']);
        
        foreach($data as $key => $value) {

            $action  = '<a href="'.base_url('shippingcompany/tabledelete/'.$value['id_file'].'/' . $id .'').'" class="btn btn-danger"><i class="fa fa-trash"></i></a>';
            if($value['status']) {
                $action .= ' <button onclick="updateStatusTable('.$value['id_file'].',0);" class="btn btn-success"><i class="fa  fa-check-circle"></i></button>';
            } else {
                $action .= ' <button onclick="updateStatusTable('.$value['id_file'].',1);" class="btn btn-warning"><i class="fa  fa-ban"></i></button>';
            }
            
            $result['data'][] = array(
                 $value['id_file']
                ,'<a target="_blank" href="'.base_url('shippingcompany/download/'.$value['id_file']).'">'.$value['filename'].'</a>'
                ,$value['dt_start_v']
                ,$value['dt_end_v']
                ,$value['dt_create_file']
                ,$action
            );
        }
       
        echo json_encode($result);
    }
   
     
    public function updateStatusShippigRule()
    {
       
        // $data = array (
        //      'status' => (int)$_POST['status']
        //     ,'idSeller' => $this->session->userdata['id']
        //     ,'idTransportadora' => $_POST['id_transportadora']
        // );
        
        // $result = $this->Model_shipping_company->updateStatusShippingCompany($data);
        
        // if($result) {
        //     $msg = "Transportadora Alterada com sucesso.";
        // }
        echo json_encode(array('success' => '', 'message' => $msg));
        return;

    }
    
}