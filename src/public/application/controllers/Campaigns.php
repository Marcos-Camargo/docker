<?php
/*

*/
defined('BASEPATH') OR exit('No direct script access allowed');

class Campaigns extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_add_campaign');
        
        $this->load->model('model_products');
        $this->load->model('model_parametrosmktplace');
        $this->load->model('model_campaigns');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		
    }
    
    public function index()
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['filters'] = array (
            'filter_name' => '',
            'filter_marketplace' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1,4,5),
            'filter_type' => array(3),
        );
        $this->session->set_userdata(Array('campaignsfilter' => ''));
        $this->data['page_title'] = $this->lang->line('application_manage_campaigns');
        $this->render_template('campaigns/index', $this->data);
    }
    
    public function createcampaigns(){
        
        $marketplaces = $this->model_campaigns->getMarketplacesData();
        $campaign=$this->model_campaigns->getCampaignsData(0);
        
        $campanha['id'] = "";
        $campanha['lote'] = "";
        $campanha['campanha'] = "";
        $campanha['descricao'] = "";
        $campanha['marketplace'] = "";
        $campanha['data_inicio_campanha'] = "";
        $campanha['data_fim_campanha'] = "";
        $campanha['tipo_campanha'] = "";
        $campanha['taxa_reduzida_marketplace'] = "";
        $campanha['taxa_reduzida_seller'] = "";
        $campanha['tipo_pagamento'] = "";
        $campanha['total_percent_promocao'] = "";
        $campanha['total_percent_seller'] = "";
        $campanha['categoria_n1'] = "";
        $campanha['categoria_n2'] = "";
        $campanha['categoria_n3'] = "";
        $campanha['data_criacao'] = "";
        $campanha['data_alteracao'] = "";
        $campanha['status'] = "";
        
        $categoriaN1 = $this->model_promotions->getCategories(1);
        $categoriaN2 = $this->model_promotions->getCategories(2);
        $categoriaN3 = $this->model_promotions->getCategories(3);
        
        $this->data['categoriaN1'] = $categoriaN1;
        $this->data['categoriaN2'] = $categoriaN2;
        $this->data['categoriaN3'] = $categoriaN3;
        
        $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
        $this->data['campanha'] =$campanha;
        $this->data['marketplaces'] = $marketplaces;

        $valorSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['sellercenter'] = $valorSellerCenter['value'];

        $this->render_template('campaigns/createcampaigns', $this->data);
        
    }
    
    public function getproductscampanhatemp(){
        
        $result = array();
        
        $inputs = cleanArray($this->input->get());

        if($inputs['hdnLote'] <> ""){
            
            $ret = $this->model_campaigns->getprodutotemp($inputs);
            
            if($ret){
                
                foreach ($ret as $key => $value) {
                    
                    $buttons = "";
                
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="removerPedidoCampanha(\''.$value['product_id'].'\')"><i class="fa fa-trash"></i></button>';
                    
                    $result['data'][$key] = array(
                        $value['id'],
                        $inputs['slc_marketplace'],
                        $value['product_id'],
                        $value['sku'],
                        $value['nome'],
                        $buttons
                    );
                
                }
            }
            
            
        }
        
        if(empty($result)){
            $result['data'][0] = array("","","","","","");
        }
        
        echo json_encode($result);
        
    }
    
    public function addprodutotempcampanha(){
        
        $inputs = $this->postClean(NULL,TRUE);
        
        if($inputs['hdnLote'] <> "" && $inputs['buscaAuto'] <> "" && $inputs['txt_sku'] <> "" ){

            $ret = $this->model_campaigns->insertprodutotemp($inputs);
            
            if($ret){
                echo true;
            }else{
                echo false;
            }
        }
        
    }
    
    public function removerprodutotempcampanha(){
        
        $inputs = $this->postClean(NULL,TRUE);
        
        if($inputs['hdnLote'] <> "" && $inputs['produto'] <> ""){
            $ret = $this->model_campaigns->deleteprodutotemp($inputs);
            if($ret){
                echo true;
            }else{
                echo false;
            }
        }
        
    }
    
    public function salvarcampaigns(){
        
        $inputs = $this->postClean(NULL,TRUE);
        
        if (!array_key_exists("scl_tipo_pagamento", $inputs)) {
            $inputs['scl_tipo_pagamento'] = "";
        }
        
        if($inputs['hdnChamado'] == ""){
            $ret = $this->model_campaigns->cadastrarCampanha($inputs);
            if($ret){
                $ret2 = $this->model_campaigns->cadastraSKUs($inputs,$ret);
                if($ret2 == "1"){
                    if (array_key_exists("typepromo", $inputs)) {
                        if($inputs['typepromo'] == "1"){
                            $ret3 = $this->model_promotions->cadastrarPromocaoFromCampanha($inputs);
                            if($ret3){
                                $saida = "0;Campanha e promoção cadastrados com sucesso";
                            }else{
                                $saida = "1;Erro na geração da Campanha e Promoção";
                            }
                        }
                    }else{
                        $saida = "0;Campanha criada com sucesso";
                    }
                }else{
                    $saida = "1;Erro na geração da Campanha";
                }
            }else{
                $saida = "1;Erro na geração da Campanha";
            }
        }else{
            $ret = $this->model_campaigns->editaCampanha($inputs);
            
            if($ret){
                $ret2 = $this->model_campaigns->cadastraSKUs($inputs,$inputs['hdnChamado']);
                if($ret2){
                    $saida = "0;Campanha editado com sucesso";
                }else{
                    $saida = "1;Erro na edição da Campanha";
                }
            }else{
                $saida = "1;Erro na edição da Campanha";
            }
        }
        
        echo $saida;
        
    }
    
    public function buscarlistacampanha(){
        
        
        $result = array('data' => array());
        
        $inputs = cleanArray($this->input->get());
        
        $data = $this->model_campaigns->getCampanhasData(null);
        
        foreach ($data as $key => $value) {
            
            // button
            $buttons = '';
            $status = '';
            
			//  if(in_array('updateTTMkt', $this->permission)) {
            if(in_array('updateCampaigns', $this->permission)) {
                $buttons .= ' <a href="'.base_url('campaigns/editcampaigns/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }
            
            
            $tipoCampanha = "";
            if($value['data_criacao'] == "1"){
                $tipoCampanha = "Negociação";
            }
            
            if($value['data_criacao'] == "2"){
                $tipoCampanha = "Promoção";
            }
            
            if($value['data_criacao'] == "3"){
                $tipoCampanha = "Categoria";
            }
            
            
            $result['data'][$key] = array(
                $value['id'],
                $value['campanha'],
                $value['marketplace'],
                $tipoCampanha,
                $value['data_inicio_campanha'],
                $value['data_fim_campanha'],
                $value['status'],
                $buttons
            );
        } // /foreach
        
        echo json_encode($result);
        
        
    }
    
    public function editcampaigns($id){
        
        $campanha = $this->model_campaigns->getCampanhasData($id);
        
        $marketplaces = $this->model_campaigns->getMarketplacesData();
        
        $this->model_campaigns->cadastraSKUsTemp($campanha[0]['lote']);
        
        $categoriaN1 = $this->model_promotions->getCategories(1);
        $categoriaN2 = $this->model_promotions->getCategories(2);
        $categoriaN3 = $this->model_promotions->getCategories(3);
        
        $this->data['categoriaN1'] = $categoriaN1;
        $this->data['categoriaN2'] = $categoriaN2;
        $this->data['categoriaN3'] = $categoriaN3;
        
        $dataInicio = explode(" ", $campanha[0]['data_inicio_campanha']);
        $dataFim    = explode(" ", $campanha[0]['data_fim_campanha']);
        
        $campanha[0]['data_inicio_campanha'] = $dataInicio[0];
        $campanha[0]['data_fim_campanha'] = $dataFim[0];
        
        $this->data['hdnLote'] = $campanha[0]['lote'];
        $this->data['campanha'] =$campanha[0];
        $this->data['marketplaces'] = $marketplaces;

        $valorSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->data['sellercenter'] = $valorSellerCenter['value'];

        $this->render_template('campaigns/createcampaigns', $this->data);
        
    }
    
    
    /***********************************/
    
    public function filter()
    {
        if(!in_array('viewCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_manage_promotions_filtered');
        $campaignsFilter = "";
        $filters = array (
            'filter_name' => '',
            'filter_marketplace' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1,4,5),
            'filter_type' => array(3),
        );
        if (!is_null($this->postClean('do_filter'))) {
            
            if ((!is_null($this->postClean('filter_marketplace')))  && ($this->postClean('filter_marketplace_op')!="0")) {
                $marketplace = $this->postClean('filter_marketplace');
                if ($this->postClean('filter_marketplace_op') == 'LIKE') { $marketplace = '%'.$this->postClean('filter_marketplace').'%'; }
                $campaignsFilter .= " AND c.int_to ".$this->postClean('filter_marketplace_op')." '".$marketplace."'";
                $filters['filter_marketplace'] = $marketplace;
                $filters['filter_marketplace_op'] = $this->postClean('filter_marketplace_op');
            }
            if ((!is_null($this->postClean('filter_name')))  && ($this->postClean('filter_name_op')!="0")) {
                $name = $this->postClean('filter_name');
                if ($this->postClean('filter_name_op') == 'LIKE') { $name = '%'.$this->postClean('filter_name').'%'; }
                $campaignsFilter .= " AND c.name ".$this->postClean('filter_name_op')." '".$name."'";
                $filters['filter_name'] = $name;
                $filters['filter_name_op'] = $this->postClean('filter_name_op');
            }
            if ((!is_null($this->postClean('filter_start_date')))  && ($this->postClean('filter_start_date_op')!="0")) {
                $campaignsFilter .= " AND c.start_date ".$this->postClean('filter_start_date_op')." '".$this->convertDate($this->postClean('filter_start_date'),"00:00")."'";
                $filters['filter_start_date'] = $this->postClean('filter_start_date');
                $filters['filter_start_date_op'] = $this->postClean('filter_start_date_op');
            }
            if ((!is_null($this->postClean('filter_end_date')))  && ($this->postClean('filter_end_date_op')!="0")) {
                $campaignsFilter .= " AND c.end_date ".$this->postClean('filter_end_date_op')." '".$this->convertDate($this->postClean('filter_end_date'),"00:00")."'";
                $filters['filter_end_date'] = $this->postClean('filter_end_date');
                $filters['filter_end_date_op'] = $this->postClean('filter_end_date_op');
            }
            $fil_type ='';
            $filter_type = $this->postClean('filter_type');
            foreach  ($filter_type as $option_type) {
                if (($option_type == 3) || ($option_type == 1)) {
                    $fil_type .= " OR c.commission_type = 2";
                }
                if (($option_type == 3) || ($option_type == 2)) {
                    $fil_type .= " OR c.commission_type = 1";
                }
            }
            if ($fil_type != "") {
                $campaignsFilter.= ' AND ('.substr($fil_type,3).')';
            }
            
            $fil_sta = '';
            $filter_status = $this->postClean('filter_status');
            foreach  ($filter_status as $option_status) {
                $fil_sta .= " OR c.active = ".$option_status;
            }
            if ($fil_sta != "") {
                $campaignsFilter.= ' AND ('.substr($fil_sta,3).')';
            }
            
            // $this->session->set_flashdata('success',$campaignsFilter);
        }
        
        $this->session->set_userdata(Array('campaignsfilter' => $campaignsFilter));
        $this->data['filters'] = $filters;
        $this->render_template('campaigns/index', $this->data);
    }
    
    public function fetchCampaignsData()
    {
        if(!in_array('viewCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>1) {  // Garantir no minimo 2 letras
                $procura = " AND ( c.id like '%".$busca['value']."%' OR ";
                $procura .= " s.descloja '%".$busca['value']."%' OR ";
                $procura .= " c.name like '%".$busca['value']."%' OR ";
                $procura .= " c.start_date like '%".$busca['value']."%' OR ";
                $procura .= " c.end_date like '%".$busca['value']."%' ) ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('id','c.name','s.descloja','c.commission_type','c.start_date','c.end_date','c.active');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_campaigns->getCampaignsViewData($ini,$procura, $sOrder );
        
        $i = 0;
        $filtered = $this->model_campaigns->getCampaignsViewCount($procura);
        
        foreach ($data as $key => $value) {
            $i++;
            
            $type = ($value['commission_type']!=1) ? $this->lang->line('application_commission_type_unique') : $this->lang->line('application_commission_type_by_category') ;
            $buttons = '';
            
            if ((in_array('updateCampaigns', $this->permission)) && (($value['active'] == 3) || ($value['active'] == 4))) { // posso editar se está agendada ou em aprovação
                $buttons.= '<a href="'.base_url('campaigns/createproducts/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_edit').'"><i class="fa fa-pencil-square-o"></i></a>';
            }
            if ((in_array('deleteCampaigns', $this->permission))  && ($value['active'] == 1)) { // Posso inativar se estiver ativo
                $buttons.= '<button class="btn btn-danger" onclick="inactiveCampaign(event,'.$value['id'].')" data-toggle="tooltip" title="'.$this->lang->line('application_inactivate').'"><i class="fa fa-minus-square"></i></button>';
            }
            if ((in_array('viewCampaigns', $this->permission))  && (($value['active'] == 1) || ($value['active'] == 2))) { // Posso ver uma campanha ativa ou inativa
                $buttons.= '<a href="'.base_url('campaigns/createproducts/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';
            }
            
            if ((in_array('deleteCampaigns', $this->permission)) && (($value['active'] == 3) || ($value['active'] == 4) || ($value['active'] == 5)))  { // posso deletar se está em aprovação ou agendado
                $buttons.= '<button class="btn btn-warning" onclick="deleteCampaign(event,'.$value['id'].')" data-toggle="tooltip" title="'.$this->lang->line('application_delete').'"><i class="fa fa-trash"></i></button>';
            }
            if ((in_array('createCampaigns', $this->permission)) && (($value['active'] == 5)))  { // posso editar se está em aprovação ou agendado
                $buttons.= '<a href="'.base_url('campaigns/create/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_continue_edit').'"><i class="fa fa-list-alt"></i></a>';
            }
            
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">'. $this->lang->line('application_active').'</span>';
            }elseif ($value['active'] == 3) {
                $status = '<span class="label label-warning">'. $this->lang->line('application_approval').'</span>';
            }elseif ($value['active'] == 4) {
                $status = '<span class="label label-info">'. $this->lang->line('application_scheduled').'</span>';
            } elseif ($value['active'] == 5) {
                $status = '<span class="label label-default">'. $this->lang->line('application_on_edit').'</span>';
            } else {
                $status = '<span class="label label-danger">'. $this->lang->line('application_inactive').'</span>';
            }
            
            $result[$key] = array(
                $value['id'],
                $value['name'],
                $value['int_to'],
                $type,
                date('d/m/Y', strtotime($value['start_date'])),
                date('d/m/Y', strtotime($value['end_date'])),
                $status,
                $buttons
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getCampaignsViewCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
         ob_clean();
        echo json_encode($output);
    }
    
    public function deleteCampaigns() {
        if(!in_array('deleteCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id_remove',TRUE);
        if ($this->model_campaigns->deleteCampaign($id)) {
            $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_removed'));
        } else {
            $this->session->set_flashdata('success',  $this->lang->line('messages_error_occurred'));
        };
        redirect('campaigns/', 'refresh');
    }
    
    public function approveCampaign() {
        if(!in_array('deleteCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id',TRUE);
        if ($this->model_campaigns->chanceActive($id,'3')) {
            $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_created'));
        } else {
            $this->session->set_flashdata('success',  $this->lang->line('messages_error_occurred'));
        };
        redirect('campaigns/', 'refresh');
    }
    
	public function inactiveCampaign() {
        if(!in_array('deleteCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id_inactive',TRUE);
        if ($this->model_campaigns->chanceActive($id,'2')) {
            $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_removed'));
        } else {
            $this->session->set_flashdata('success',  $this->lang->line('messages_error_occurred'));
        };
        redirect('campaigns/', 'refresh');
    }
	
    public function create($id)
    {
        
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $marketplaces = $this->model_campaigns->getMarketplacesData();
        
        if ($id == '0') {
            $campaign=array(
                'name' => '',
                'description' => '',
                'int_to' => '',
                'active' => '5',
                'commission_type' => 1,
                'start_date' => '',
                'end_date' => '',
            );
            
        }
        else {
            $campaign=$this->model_campaigns->getCampaignsData($id);
            $campaign['commission_type'] = $campaign['commission_type'] - 1;
            $campaign['start_date'] = date('d/m/Y', strtotime($campaign['start_date']));
            $campaign['end_date'] = date('d/m/Y', strtotime($campaign['end_date']));
        }
        
        if ($campaign['active'] != 1) { // campanhas ativas não se alteram - Nao deveria acontecer poi vai direto para createproducts - Apenas proteção....
            $this->form_validation->set_rules('name', $this->lang->line('application_name_campaign'), 'trim|required');
            $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
            $this->form_validation->set_rules('int_to', $this->lang->line('application_marketplace'), 'trim|required');
            $this->form_validation->set_rules('start_date', $this->lang->line('application_start_date'), 'trim|required');
            $this->form_validation->set_rules('end_date', $this->lang->line('application_end_date'), 'trim|required');
        }
        if ($this->form_validation->run() == TRUE) {
            if ($campaign['active'] == 1) { // campanhas ativas não se alteram
                redirect('campaigns/createproducts/'.$id, 'refresh');
                return ;
            }
            if ($campaign['commission_type'] != $this->postClean('commission_type',TRUE)) {
                // mudou o tipo da campanha então apago os produtos e comissoes se existirem
                $delete=$this->model_campaigns->deleteCampaignCommissionProducts($id);
            }
            if ($campaign['int_to'] != $this->postClean('int_to',TRUE)) {
                // mudou o marketplace então apago os produtos e comissoes se existirem
                $delete=$this->model_campaigns->deleteCampaignCommissionProducts($id);
            }
            $rec = Array (
                'name' => $this->postClean('name',TRUE),
                'description' => $this->postClean('description',TRUE),
                'int_to' => $this->postClean('int_to',TRUE),
                'active' => 5,
                'commission_type' => $this->postClean('commission_type',TRUE)+1 ,
                'start_date' => $this->convertDate($this->postClean('start_date',TRUE),"00:00"),
                'end_date' => $this->convertDate($this->postClean('end_date',TRUE),"00:00"),
            );
            if ($id == '0') {
                $update = $this->model_campaigns->create($rec);
            } else {
                $rec['id'] = $id;
                $update = $this->model_campaigns->update($rec,$id);
                //if ($rec['commission_type'] == 2) {
                //	$delete = $this->model_campaigns->deleteCommissionCategory($id);
                //}
                
            }
            
            if($update) {
                if ($id == '0') {
                    $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_created'));
                } else {
                    $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_updated'));
                }
                redirect('campaigns/createcommission/'.$update, 'refresh'); // vou para a marcação de produtos
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('campaigns/create', 'refresh');
            }
        }
        else {
            $this->data['campaign'] =$campaign;
            $this->data['marketplaces'] = $marketplaces;
            $this->render_template('campaigns/create', $this->data);
        }
    }
    
    function commission_mkt_check($mkt,$actual) {
        
        if ($mkt >= $actual ) {
            $this->form_validation->set_message('commission_mkt_check', 'A nova comissão deve ser menor que a comissão atual ');
            return false;
        }
        return true;
        
    }
    
    function commission_store_check($store,$mktactual) {
        list($mkt,$actual) = explode('||',$mktactual);
        if ($store >= $actual ) {
            $this->form_validation->set_message('commission_store_check', 'A nova comissão deve ser menor que a comissão atual ');
            return false;
        }
        if ($mkt!='') {
            if ($store < $mkt+2)  {
                $this->form_validation->set_message('commission_store_check', 'A comissão para a loja deve ser, pelo menos, 2 pontos percentuais maior que a comissão do Marketplace');
                return false;
            }
        }
        return true;
    }
    
    function commission_mkt_cat_check($mkt,$storeactual) {
        list($store,$actual) = explode('||',$storeactual);
        if ($mkt!= '') {
            if ($mkt >= $actual ) {
                $this->form_validation->set_message('commission_mkt_cat_check', 'A nova comissão deve ser menor que a comissão atual ');
                return false;
            }
            if ($mkt == '') {
                $this->form_validation->set_message('commission_mkt_cat_check', 'Preencha as 2 comissões ou limpe se esta categoria não irá participar da campanha');
                return false;
            }
            return true;
        } elseif ($store != '') {
            $this->form_validation->set_message('commission_mkt_cat_check', 'Preencha as 2 comissões ou limpe se esta categoria não irá participar da campanha');
            return false;
        }
    }
    
    function commission_store_cat_check($store,$mktactual) {
        list($mkt,$actual) = explode('||',$mktactual);
        if ($store!='') {
            if ($store >= $actual ) {
                //$this->form_validation->set_message('commission_store_cat_check', 'A nova comissão deve ser menor que a comissão atual ');
                return true;
            }
            if ($mkt == '') {
                $this->form_validation->set_message('commission_store_cat_check', 'Preencha as 2 comissões ou limpe se esta categoria não irá participar da campanha');
                return false;
            }
            if ($store < $mkt+2)  {
                $this->form_validation->set_message('commission_store_cat_check', 'A comissão para a loja deve ser, pelo menos, 2 pontos percentuais maior que a comissão do Marketplace');
                return false;
            }
            return true;
        } elseif ($mkt != '') {
            $this->form_validation->set_message('commission_store_cat_check', 'Preencha as 2 comissões ou limpe se esta categoria não irá participar da campanha');
            return false;
        }
        return true;
    }
    
    public function createcommission($id)
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $campaign=$this->model_campaigns->getCampaignsData($id);
        $commissionsAll=$this->model_parametrosmktplace->getReceivablesData();
        
        $commissions_category=$this->model_campaigns->getCommissionsCategory($id);
        //var_dump($commissions_category);
        
        $commissions = array();
        foreach($commissionsAll as $commission) {
        	
            if ($commission['int_to']==$campaign['int_to']) {
                $commission['commission_mkt_campaign'] = '';
                $commission['commission_store_campaign'] = '';
                foreach ($commissions_category as $com_cat) {
                    if ($com_cat['category_id'] == $commission['id']) {
                        $commission['commission_mkt_campaign'] = $com_cat['commission_mkt'];
                        $commission['commission_store_campaign'] = $com_cat['commission_store'];
                        break;
                    }
                }
                
                $commissions[] = $commission;
            }
            
        }
        $commissionmkt = $commissions[0]['valor_aplicado'];
        //var_dump($commissions);
        if ($campaign['active'] != 1) { // campanhas ativas não se alteram. Devia ir direto para o createproducts, apenas proteção
            if ($campaign['commission_type'] == 2) {
                $this->form_validation->set_rules('commission_mkt_campaign',
                    $this->lang->line('application_commission_mkt_campaign'),
                    'trim|required|callback_commission_mkt_check['.$commissionmkt.']');
                    $this->form_validation->set_rules('commission_store_campaign',
                        $this->lang->line('application_commission_store_campaign'),
                        'trim|required|callback_commission_store_check['.$this->postClean('commission_mkt_campaign',TRUE).'||'.$commissionmkt.']');
                        
            } else {
                foreach($commissions as $commission) {
                    $this->form_validation->set_rules('commission_mkt_campaign_'.$commission['id'],
                        $this->lang->line('application_commission_mkt_campaign'),
                        'trim|callback_commission_mkt_cat_check['.$this->postClean('commission_store_campaign_'.$commission['id'],TRUE).'||'.$commission['valor_aplicado'].']');
                        $this->form_validation->set_rules('commission_store_campaign_'.$commission['id'],
                            $this->lang->line('application_commission_store_campaign'),
                            'trim|callback_commission_store_cat_check['.$this->postClean('commission_mkt_campaign_'.$commission['id'],TRUE).'||'.$commission['valor_aplicado'].']');
                }
            }
        }
        
        if ($this->form_validation->run() == TRUE) {
            if ($campaign['active'] == 1) { // campanhas ativas não se alteram
                redirect('campaigns/createproducts/'.$id, 'refresh');
                return ;
            }
            if ($campaign['commission_type'] == 2) {
                $rec = Array();
                $rec['commission_mkt'] = $this->postClean('commission_mkt_campaign',TRUE);
                $rec['commission_store']= $this->postClean('commission_store_campaign',TRUE);
                $update = $this->model_campaigns->update($rec,$id);
                if($update) {
                    $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_created'));
                    redirect('campaigns/createproducts/'.$id, 'refresh'); // vou para a marcação de produtos
                }
                else {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                    redirect('campaigns/createcommission/'.$id, 'refresh');
                }
            } else {
                $achei = false;
                foreach($commissions as $commission) {
                    $com_mkt = $this->postClean('commission_mkt_campaign_'.$commission['id'],TRUE);
                    $com_str = $this->postClean('commission_store_campaign_'.$commission['id'],TRUE);
                    if (($com_mkt!='') && ($com_str!='')) {
                        $achei = true;
                        $rec = array(
                            'campaigns_id' => $id,
                            'category_id' => $commission['id'],
                            'commission_mkt' => $com_mkt,
                            'commission_store' => $com_str,
                        );
                        $cre = $this->model_campaigns->createCommissionCategory($rec);
                        if (!$cre) {
                            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                            redirect('campaigns/createcommission/'.$id, 'refresh');
                            break;
                        }
                    }
                    else {
                        // apaga a categoria e os produtos destas categorias
                        $delete = $this->model_campaigns->deleteCommissionCategory($id,$commission['id']);
                    }
                }
                if (!$achei) {
                    // $delete = $this->model_campaigns->deleteCommissionCategory($id);
                    $this->session->set_flashdata('error', $this->lang->line('application_error_no_commission'));
                    redirect('campaigns/createcommission/'.$id, 'refresh');
                }
                else {
                    $this->session->set_flashdata('success',  $this->lang->line('messages_successfully_created'));
                    redirect('campaigns/createproducts/'.$id, 'refresh'); // vou para a marcação de produtos
                }
            }
        }
        else {
            
            $this->data['campaign'] = $campaign;
            $this->data['commissions'] = $commissions;
            $this->data['commissionmkt'] = $commissionmkt;
            $this->render_template('campaigns/createcommission', $this->data);
        }
    }
    
    public function createproducts($id)
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $campaign=$this->model_campaigns->getCampaignsData($id);
        if ($campaign['commission_type'] != 2) {
            $commissions= $this->model_campaigns->getCommissionCategoryData($id);
            $this->data['commissions'] = $commissions;
            $categoriesfilter = " AND (";
            foreach ($commissions as $commission) {
                $categoriesfilter .= " c.name LIKE '".$commission['categoria']." > %' OR ";
            }
            $categoriesfilter = substr($categoriesfilter,0,strlen($categoriesfilter)-3).")";
            $this->session->set_userdata(Array('cf' => $categoriesfilter));
        }
        else {
            $this->session->set_userdata(Array('cf' => ''));
        }
        
        if ($this->form_validation->run() == TRUE) {
            redirect('campaigns/createproducts/'.$id, 'refresh');
        }
        else {
            $this->data['exist_products'] = $this->model_campaigns->getProductsCampaignsCount($id);
            $this->data['campaign'] = $campaign;
            $this->render_template('campaigns/createproducts', $this->data);
        }
    }
    
    function convertDate($orgDate,$time) {
        $date =  str_replace('/','-', $orgDate);
        $newDate = date("Y-m-d", strtotime($date));
        if ($time == '') {
            return $newDate.' 00:00:00';
        }
        else {
            return $newDate.' '.$time.':00';
        }
    }
    
    public function fetchproductsdata($id)
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$campaign=$this->model_campaigns->getCampaignsData($id);
		 
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = "  AND (  p.sku like '%".$busca['value']."%' OR c.name like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%'  OR s.name like '%".$busca['value']."%' OR cpy.name like '%".$busca['value']."%') ";
            }
        }
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('p.id','','cpy.name','s.name','service_charge_value','p.sku','p.name','c.name','CAST(p.price AS DECIMAL(12,2))');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        var_dump($procura);
        $data = $this->model_campaigns->getProductsForCampaignsData($ini, $sOrder, $procura);
        
        $i = 0;
        $filtered = $this->model_campaigns->getProductsForCampaignsCount($procura);
        
        foreach ($data as $key => $value) {
            $i++;
            $campanha= $this->model_campaigns->getProductsOnCampaign($id, $value['id']);
            $selecionado = '';
			$sale = ' - ';
            if ($campanha) {
                $selecionado = '<span><i class="fa fa-check" aria-hidden="true"></i></span>';
				$sale = '<span style="float:right;">'.$this->formatprice($campanha['sale']).'</span>';
            }
			$skumkt = $this->model_blingultenvio->getSkuMkt($campaign['int_to'],$value['id']);
		
            $linkprd = '<a href="'.base_url().'products/update/'.$value['id'].'" target="_blank">'.$value['sku'].'</a>';
            $linkstore = '<a href="'.base_url().'stores/update/'.$value['store_id'].'" target="_blank">'.$value['store'].'</a>';
            $linkcompany = '<a href="'.base_url().'company/update/'.$value['company_id'].'" target="_blank">'.$value['company'].'</a>';
            
            $result[$key] = array(
                $id.'||'.$value['id'],
                $selecionado,
                $linkcompany,
                $linkstore,
                $value['service_charge_value'],
                $linkprd,
                $skumkt,
                $value['name'],
                $value['category'],
                '<span style="float:right;">'.$this->formatprice($value['price']).'</span>',
                $sale,
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getProductsForCampaignsCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
         ob_clean();
        echo json_encode($output);
    }
    
    public function fetchproductscampaignsdata($id)
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND ( p.sku like '%".$busca['value']."%' OR c.name like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%'  OR s.name like '%".$busca['value']."%' OR cpy.name like '%".$busca['value']."%') ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('cpy.name','s.name','service_charge_value','p.sku','p.name','c.name','CAST(p.price AS DECIMAL(12,2))','CAST(cp.price AS DECIMAL(12,2))');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        $data = $this->model_campaigns->getProductsCampaignsData($id, $ini, $sOrder, $procura);
        
        $filtered = $this->model_campaigns->getProductsCampaignsCount($id, $procura);
        
        
        foreach ($data as $key => $value) {
            $linkprd = '<a href="'.base_url().'products/update/'.$value['id'].'" target="_blank">'.$value['sku'].'</a>';
            $linkstore = '<a href="'.base_url().'stores/update/'.$value['store_id'].'" target="_blank">'.$value['store'].'</a>';
            $linkcompany = '<a href="'.base_url().'company/update/'.$value['company_id'].'" target="_blank">'.$value['company'].'</a>';
            $skumkt = $this->model_blingultenvio->getSkuMkt($campaign['int_to'],$value['id']);
            $result[$key] = array(
                $linkcompany,
                $linkstore,
                $value['service_charge_value'],
                $linkprd,
                $skumkt, 
                $value['name'],
                $value['category'],
                '<span style="float:right;">'.$this->formatprice($value['price']).'</span>',
                ($value['sale']=='')?'':'<span style="float:right;">'.$this->formatprice($value['sale']).'</span>',
            );
            
        } // /foreach
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getProductsCampaignsCount($id),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
         ob_clean();
        echo json_encode($output);
    }
    
    public function prdselect()
    {
        if(!in_array('createCampaigns', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id_post = $this->postClean('campaign_id');
        $int_to = $this->postClean('int_to');
        // get_instance()->log_data('Campaigns','Na Campanha',json_encode($_POST),"I");
        if (!is_null($this->postClean('id'))) {
            get_instance()->log_data('Campaigns','Na Campanha',json_encode($_POST),"I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    list($campaigns_id,$product_id) = explode('||',$id);
                    $this->model_campaigns->createCampaignsProducts($campaigns_id,$product_id,$int_to);
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created') );
                }
            }
            if (!is_null($this->postClean('deselect'))) {
                foreach ($ids as $k => $id) {
                    list($campaigns_id,$product_id) = explode('||',$id);
                    $this->model_campaigns->deleteCampaignsProducts($campaigns_id,$product_id);
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed') );
                }
            }
        }
        
        //$this->data['plats'] = $this->model_integrations->getIntegrationsData();
        //$this->render_template('campaigns/createproducts/'.$id_post, $this->data);
        redirect('campaigns/createproducts/'.$id_post, 'refresh');
    }
    
    public function storeIndex()
    {
        if(!in_array('viewCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['filters'] = array (
            'filter_name' => '',
            'filter_marketplace' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1,5),
            'filter_type' => array(3),
        );
        $this->session->set_userdata(Array('campaignsfilter' => ''));
        $this->data['page_title'] = $this->lang->line('application_manage_campaigns');
        $this->render_template('campaigns/storeindex', $this->data);
    }
    
    public function fetchCampaignsStoreData()
    {
        if(!in_array('viewCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>1) {  // Garantir no minimo 2 letras
                $procura = " AND ( c.id like '%".$busca['value']."%' OR ";
                $procura .= " s.descloja '%".$busca['value']."%' OR ";
                $procura .= " c.name like '%".$busca['value']."%' OR ";
                $procura .= " c.start_date like '%".$busca['value']."%' OR ";
                $procura .= " c.end_date like '%".$busca['value']."%' ) ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('id','c.name','s.descloja','c.commission_type','c.start_date','c.end_date','c.active');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        $procura = '';
        $sOrder = "";
        $result = array();
        
        $data = $this->model_campaigns->getCampaignsStoreViewData($ini,$procura, $sOrder );
        
        $i = 0;
        $filtered = $this->model_campaigns->getCampaignsStoreViewCount($procura);
        
        foreach ($data as $key => $value) {
            $i++;
            
            $type = ($value['commission_type']!=1) ? $this->lang->line('application_commission_type_unique') : $this->lang->line('application_commission_type_by_category') ;
            $buttons = '';
            
            if ((in_array('updateCampaignsStore', $this->permission)) && (($value['active'] == 3) || ($value['active'] == 4))) { // posso editar se está agendada ou em aprovação
                $buttons.= '<a href="'.base_url('campaigns/storeProducts/'.$value['id']).'" class="btn btn-primary" data-toggle="tooltip" title="'.$this->lang->line('application_edit').'"><i class="fa fa-pencil-square-o"></i></a>';
            }
            if ((in_array('viewCampaignsStore', $this->permission))  && (($value['active'] == 1) || ($value['active'] == 2))) { // Posso ver uma campanha ativa ou inativa
                $buttons.= '<a href="'.base_url('campaigns/storeProducts/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';
            }
            
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">'. $this->lang->line('application_active').'</span>';
            }elseif ($value['active'] == 3) {
                $status = '<span class="label label-warning">'. $this->lang->line('application_approval').'</span>';
            }elseif ($value['active'] == 4) {
                $status = '<span class="label label-info">'. $this->lang->line('application_scheduled').'</span>';
            } elseif ($value['active'] == 5) {
                $status = '<span class="label label-default">'. $this->lang->line('application_on_edit').'</span>';
            } else {
                $status = '<span class="label label-danger">'. $this->lang->line('application_inactive').'</span>';
            }
            $count =  $this->model_campaigns->getMyProductsOnCampaignWidthOutSaleCount($value['id']).'/'.$this->model_campaigns->getMyProductsOnCampaignCount($value['id']);
            $result[$key] = array(
                $value['id'],
                $value['name'],
                $value['int_to'],
                $type,
                date('d/m/Y', strtotime($value['start_date'])),
                date('d/m/Y', strtotime($value['end_date'])),
                $status,
                $count,
                $buttons
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getCampaignsStoreViewCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
         ob_clean();
        echo json_encode($output);
    }
    
    public function filterStore()
    {
        if(!in_array('viewCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $this->data['page_title'] = $this->lang->line('application_manage_promotions_filtered');
        $campaignsFilter = "";
        $filters = array (
            'filter_name' => '',
            'filter_marketplace' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1,4),
            'filter_type' => array(3),
        );
        if (!is_null($this->postClean('do_filter'))) {
            
            if ((!is_null($this->postClean('filter_marketplace')))  && ($this->postClean('filter_marketplace_op')!="0")) {
                $marketplace = $this->postClean('filter_marketplace');
                if ($this->postClean('filter_marketplace_op') == 'LIKE') { $marketplace = '%'.$this->postClean('filter_marketplace').'%'; }
                $campaignsFilter .= " AND c.int_to ".$this->postClean('filter_marketplace_op')." '".$marketplace."'";
                $filters['filter_marketplace'] = $marketplace;
                $filters['filter_marketplace_op'] = $this->postClean('filter_marketplace_op');
            }
            if ((!is_null($this->postClean('filter_name')))  && ($this->postClean('filter_name_op')!="0")) {
                $name = $this->postClean('filter_name');
                if ($this->postClean('filter_name_op') == 'LIKE') { $name = '%'.$this->postClean('filter_name').'%'; }
                $campaignsFilter .= " AND c.name ".$this->postClean('filter_name_op')." '".$name."'";
                $filters['filter_name'] = $name;
                $filters['filter_name_op'] = $this->postClean('filter_name_op');
            }
            if ((!is_null($this->postClean('filter_start_date')))  && ($this->postClean('filter_start_date_op')!="0")) {
                $campaignsFilter .= " AND c.start_date ".$this->postClean('filter_start_date_op')." '".$this->convertDate($this->postClean('filter_start_date'),"00:00")."'";
                $filters['filter_start_date'] = $this->postClean('filter_start_date');
                $filters['filter_start_date_op'] = $this->postClean('filter_start_date_op');
            }
            if ((!is_null($this->postClean('filter_end_date')))  && ($this->postClean('filter_end_date_op')!="0")) {
                $campaignsFilter .= " AND c.end_date ".$this->postClean('filter_end_date_op')." '".$this->convertDate($this->postClean('filter_end_date'),"00:00")."'";
                $filters['filter_end_date'] = $this->postClean('filter_end_date');
                $filters['filter_end_date_op'] = $this->postClean('filter_end_date_op');
            }
            $fil_type ='';
            $filter_type = $this->postClean('filter_type');
            foreach  ($filter_type as $option_type) {
                if (($option_type == 3) || ($option_type == 1)) {
                    $fil_type .= " OR c.commission_type = 2";
                }
                if (($option_type == 3) || ($option_type == 2)) {
                    $fil_type .= " OR c.commission_type = 1";
                }
            }
            if ($fil_type != "") {
                $campaignsFilter.= ' AND ('.substr($fil_type,3).')';
            }
            
            $fil_sta = '';
            $filter_status = $this->postClean('filter_status');
            foreach  ($filter_status as $option_status) {
                $fil_sta .= " OR c.active = ".$option_status;
            }
            if ($fil_sta != "") {
                $campaignsFilter.= ' AND ('.substr($fil_sta,3).')';
            }
            
            // $this->session->set_flashdata('success',$campaignsFilter);
        }
        
        $this->session->set_userdata(Array('campaignsfilter' => $campaignsFilter));
        $this->data['filters'] = $filters;
        $this->render_template('campaigns/storeindex', $this->data);
    }
    
    public function storeProducts($id)
    {
        if ((!in_array('viewCampaignsStore', $this->permission)) &&  (!in_array('updateCampaignsStore', $this->permission))) {
            redirect('dashboard', 'refresh');
        }
        
        $campaign=$this->model_campaigns->getCampaignsData($id);
        if ($campaign['commission_type'] != 2) {
            $commissions= $this->model_campaigns->getCommissionCategoryData($id);
            $this->data['commissions'] = $commissions;
            $categoriesfilter = " AND (";
            foreach ($commissions as $commission) {
                $categoriesfilter .= " c.name LIKE '".$commission['categoria']." > %' OR ";
            }
            $categoriesfilter = substr($categoriesfilter,0,strlen($categoriesfilter)-3).")";
            $this->session->set_userdata(Array('cf' => $categoriesfilter));
        }
        else {
            $this->session->set_userdata(Array('cf' => ''));
        }
        
        if ($this->form_validation->run() == TRUE) {
            redirect('campaigns/storeproducts/'.$id, 'refresh');
        }
        else {
            $this->data['campaign'] = $campaign;
            $this->render_template('campaigns/storeproducts', $this->data);
        }
    }
    
    public function fetchstoreproductsdata($id)
    {
        if(!in_array('updateCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND (  p.sku like '%".$busca['value']."%' OR cat.name like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%'  OR s.name like '%".$busca['value']."%' ) ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            
            $campos = array('s.name','p.sku','p.name','cat.name','CAST(p.qty AS UNSIGNED)', 'CAST(s.service_charge_value AS UNSIGNED)','','CAST(p.price AS DECIMAL(12,2))','','CAST(cp.price AS DECIMAL(12,2))','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        $data = $this->model_campaigns->getMyProductsOnCampaign($id, $ini, $sOrder, $procura);
        
        $i = 0;
        $filtered = $this->model_campaigns->getMyProductsOnCampaignCount($id, $procura);
        $taxa = '';
        $campanha= $this->model_campaigns->getCampaignsData($id);
        if ($campanha['commission_type'] != 1) {
            $taxa = $campanha['commission_store'];
        }
        foreach ($data as $key => $value) {
            $i++;
            
            if ($campanha['commission_type'] == 1) {
                $cat = $this->model_campaigns->getCommissionsCategoryByProduct($id,$value['product_id']);
                $taxa = $cat['commission_store'];
            }
            $linkprd = '<a href="'.base_url().'products/update/'.$value['product_id'].'" target="_blank">'.$value['sku'].'</a>';
            $sugerido = (float)$value['price'] * (1-(float)$value['service_charge_value']/100) / (1 - (float)$taxa/100);
            if ($value['sale']=='') {
                $sale = '<span class="label label-warning">'.$this->lang->line('application_not_participating').'</span>';
                $button = '<button class="btn btn-success" onclick="incluirProduto(event,\''.$id.'\',\''.$value['product_id'].'\',\''.$value['sku'].'\',\''.$value['produto'].'\',\''.$this->formatprice($value['price']).'\',\''.$this->formatprice($sugerido).'\',\''.$value['sale'].'\',\''.$value['qty'].'\',\''.$value['service_charge_value'].'\',\''.$taxa.'\')" data-toggle="tooltip" title="'.$this->lang->line('application_campaign_product').'"><i class="fa fa-money"></i></button>';
            }else {
                $sale ='<span style="float:right;">'.$this->formatprice($value['sale']).'</span>';
                $button = '<button class="btn btn-primary" onclick="incluirProduto(event,\''.$id.'\',\''.$value['product_id'].'\',\''.$value['sku'].'\',\''.$value['produto'].'\',\''.$this->formatprice($value['price']).'\',\''.$this->formatprice($sugerido).'\',\''.$value['sale'].'\',\''.$value['qty'].'\',\''.$value['service_charge_value'].'\',\''.$taxa.'\')" data-toggle="tooltip" title="'.$this->lang->line('application_change_campaign_product').'"><i class="fa fa-pencil"></i></button>';
                $button .= '<button class="btn btn-danger" onclick="removerProduto(event,\''.$id.'\',\''.$value['product_id'].'\',\''.$value['sku'].'\',\''.$value['produto'].'\',\''.$this->formatprice($value['price']).'\',\''.$this->formatprice($sugerido).'\',\''.$this->formatprice($value['sale']).'\',\''.$value['qty'].'\',\''.$value['service_charge_value'].'\',\''.$taxa.'\')" data-toggle="tooltip" title="'.$this->lang->line('application_remove_campaign_product').'"><i class="fa fa-trash"></i></button>';
                
            }
            $result[$key] = array(
                $value['store'],
                $linkprd,
                $value['produto'],
                $value['category'],
                $value['qty'],
                $value['service_charge_value'],
                $taxa,
                '<span style="float:right;">'.$this->formatprice($value['price']).'</span>',
                '<span style="float:right;">'.$this->formatprice($sugerido).'</span>',
                $sale,
                $button
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getMyProductsOnCampaignCount($id),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
         ob_clean();
        echo json_encode($output);
    }
    
    public function updateProductStore()
    {
        if(!in_array('updateCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $response = array();
        
        $this->form_validation->set_rules('prd_sale', $this->lang->line('application_price_sale'), 'trim|required|greater_than[0]');
        
        if ($this->form_validation->run() == TRUE) {
            $campaign_id = $this->postClean('id_campaign',TRUE);
            $product_id = $this->postClean('id_product',TRUE);
            $price = $this->postClean('prd_price',TRUE);
            $price = str_replace("R$","",$price);
            $price = str_replace(".","",$price);
            $price = str_replace(",",".",$price);
            $sale = $this->postClean('prd_sale',TRUE);
            $semerro = true;
            if ((float)$sale > (float)$price) {
                $response['success'] = false;
                $response['messages']['prd_sale'] = '<p class="text-danger">'.$this->lang->line('application_sale_price_must_be_lower').'</p>';
                $semerro = false;
            }
            if ($semerro) {
                $update = $this->model_campaigns->updateProductPrice($campaign_id,$product_id,$sale);
                if ($update == true) {
                    $response['success'] = true;
                    $response['messages'] = $this->lang->line('messages_successfully_updated');
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated') );
                }
                else {
                    $response['success'] = false;
                    $response['messages'] = $this->lang->line('messages_error_occurred');
                }
            }
        }
        else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);
    }
    public function removeProductStore()
    {
        if(!in_array('updateCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $response = array();
        
        $campaign_id = $this->postClean('id_del_campaign',TRUE);
        $product_id = $this->postClean('id_del_product',TRUE);
        
        $update = $this->model_campaigns->updateProductPrice($campaign_id, $product_id, '');
        if ($update == true) {
            $response['success'] = true;
            $response['messages'] = $this->lang->line('messages_successfully_removed');
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed') );
        }
        else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_error_occurred');
        }
         ob_clean();
        echo json_encode($response);
    }
    
    public function fetchstoreproductscampaignsdata($id)
    {
        if(!in_array('updateCampaignsStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND (  p.sku like '%".$busca['value']."%' OR cat.name like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%'  OR s.name like '%".$busca['value']."%' ) ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            
            $campos = array('s.name','p.sku','p.name','cat.name','CAST(p.qty AS UNSIGNED)', 'CAST(s.service_charge_value AS UNSIGNED)','','CAST(p.price AS DECIMAL(12,2))','CAST(cp.price AS DECIMAL(12,2))','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        $data = $this->model_campaigns->getMyProductsOnCampaign($id, $ini, $sOrder, $procura);
        
        $i = 0;
        $filtered = $this->model_campaigns->getMyProductsOnCampaignCount($id, $procura);
        $taxa = '';
        $campanha= $this->model_campaigns->getCampaignsData($id);
        if ($campanha['commission_type'] != 1) {
            $taxa = $campanha['commission_store'];
        }
        foreach ($data as $key => $value) {
            $i++;
            
            if ($campanha['commission_type'] == 1) {
                $cat = $this->model_campaigns->getCommissionsCategoryByProduct($id,$value['product_id']);
                $taxa = $cat['commission_store'];
            }
            $linkprd = '<a href="'.base_url().'products/update/'.$value['product_id'].'" target="_blank">'.$value['sku'].'</a>';
            $sugerido = (float)$value['price'] * (1-(float)$value['service_charge_value']/100) / (1 - (float)$taxa/100);
            if ($value['sale']=='') {
                $sale = '<span class="label label-warning">'.$this->lang->line('application_not_participating').'</span>';
            }else {
                $sale ='<span style="float:right;">'.$this->formatprice($value['sale']).'</span>';
            }
            $result[$key] = array(
                $value['store'],
                $linkprd,
                $value['produto'],
                $value['category'],
                $value['qty'],
                $value['service_charge_value'],
                $taxa,
                '<span style="float:right;">'.$this->formatprice($value['price']).'</span>',
                $sale,
            );
            
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_campaigns->getMyProductsOnCampaignCount($id),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean();
        echo json_encode($output);
    }


}