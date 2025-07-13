<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class TroubleTicket extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = 'Administrar Chamados';

		$this->load->model('model_iugu');
		$this->load->model('model_orders');
		$this->load->model('model_billet');
		$this->load->model('model_troubleticket');
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$more = " company_id = ".$usercomp;
		
	}

	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewTTMkt', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = 'Administrar os boletos';
		$this->render_template('troubleticket/list', $this->data);		
	}
	
	public function list()
	{
	    
	    if(!in_array('viewTTMkt', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $split_data = $this->model_iugu->getBilletStatusData("Chamado Marketplace");
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    
	    $this->data['status_billets'] = $split_data;
	    $this->data['mktplaces'] = $group_data1;
	    
	    $this->render_template('troubleticket/list', $this->data);
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchBalanceData()
	{
	    
		$result = array('data' => array());
		
		$inputs = $this->input->get();
		
		$data = $this->model_troubleticket->getChamadossData(null, $inputs);
		
		setlocale(LC_MONETARY,"pt_BR", "ptb");
		
		foreach ($data as $key => $value) {

			// button
			$buttons = '';
			$status = '';

			if(in_array('updateTTMkt', $this->permission)) {
				$buttons .= ' <a href="'.base_url('TroubleTicket/editchamado/'.$value['id']).'" class="btn btn-default"><i class="fa fa-eye"></i></a>';
			}
			
			$result['data'][$key] = array(
				$value['id'],
				$value['descloja'],
				$value['numero_chamado'],
			    $value['data_criacao'],
			    $value['previsao_solucao'],
			    $value['status'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}
	
	public function fetchBalanceDataExcel()
	{
    
        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-type: application/x-msexcel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Chmadoss.xls");
    	
        $result = array('data' => array());
        
        $inputs = $this->input->get();
        
        $data = $this->model_troubleticket->getChamadossData(null, $inputs);
        
        setlocale(LC_MONETARY,"pt_BR", "ptb");
        
        foreach ($data as $key => $value) {
            
            // button
            
            $result['data'][$key] = array(
                $value['id'],
                $value['descloja'],
                $value['numero_chamado'],
                $value['data_criacao'],
                $value['previsao_solucao'],
                $value['status']
            );
        }// /foreach

    	echo "<table>
                    <tr>
                    <th>".$this->lang->line('application_id')."</th>
                    <th>".$this->lang->line('application_runmarketplaces')."</th> 
                    <th>".$this->lang->line('application_number_troubleticket')."</th>
                    <th>".$this->lang->line('application_date_troubleticket')."</th>
                    <th>".$this->lang->line('application_date_forcast_troubleticket')."</th>  
                    <th>".$this->lang->line('application_status')."</th>
                  </tr>";
    	
    	foreach($result['data'] as $value){
    	    
    	    echo "<tr>";
    	    echo "<td>".$value[0]."</td>";
    	    echo "<td>".$value[1]."</td>";
    	    echo "<td>".$value[2]."</td>";
    	    echo "<td>".$value[3]."</td>";
    	    echo "<td>".$value[4]."</td>";
    	    echo "<td>".$value[5]."</td>";
    	    echo "</tr>";
    	    
    	}
    	
    	echo "</table>";
	
	}

	public function createtroubleticket(){
	    
	    if(!in_array('createTTMkt', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $split_data = $this->model_iugu->getBilletStatusData("Chamado Marketplace");
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    
	    $group_data2 = array();
	    $group_data3 = array();
	    
	    $group_data2['id'] = "";
	    $group_data2['integ_id'] = "";
	    $group_data2['numero_chamado'] = "";
	    $group_data2['billet_status_id'] = "";
	    $group_data2['previsao_solucao'] = "";
	    $group_data2['descricao'] = "";
	    $group_data2['hdnPedido'] = "0";
	    $group_data2['previsao_solucao_formatada'] = "";
	    
	    $group_data3['chamado_marketplace_id'] = "";
	    $group_data3['pedidos'] = "";
	    
	    $this->data['status_billets'] = $split_data;
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['chamado'] = $group_data2;
	    $this->data['pedidos'] = $group_data3;
	    
	    $this->render_template('troubleticket/create', $this->data);
	}
	
	public function createchamado(){
	    
	    if(!in_array('createTTMkt', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['hdnChamado'] == ""){
    	    $ret = $this->model_troubleticket->cadastraChamado($inputs);
    	    
    	    if($ret){
    	        $ret2 = $this->model_troubleticket->cadastraPedidos($inputs,$ret);
    	        if($ret2 == "1"){
    	            $saida = "0;Chamado cadastrado com sucesso";
    	        }else{
    	            $saida = "1;Erro na geração do Chamado";
    	        }
    	    }else{
    	        $saida = "1;Erro na geração do Chamado";
    	    }
	    }else{
	        $ret = $this->model_troubleticket->editaChamado($inputs);
	        
	        if($ret){
	            $ret2 = $this->model_troubleticket->editaPedidos($inputs);
	            if($ret2){
	                $saida = "0;Chamado editado com sucesso";
	            }else{
	                $saida = "1;Erro na edição do Chamado";
	            }
	        }else{
	            $saida = "1;Erro na edição do Chamado";
	        }
	    }
	    
	    echo $saida;
	    
	}
	
	public function editchamado($id){
	    
	    if(!in_array('updateTTMkt', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $split_data = $this->model_iugu->getBilletStatusData("Chamado Marketplace");
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_troubleticket->getChamadossData($id, null);
	    $group_data3 = $this->model_troubleticket->getPedidosChamadosa($id);
	    
	    $group_data2[0]['hdnPedido'] = "1";
	    
	    $this->data['status_billets'] = $split_data;
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['chamado'] = $group_data2[0];
	    $this->data['pedidos'] = $group_data3[0];
	    
	    $this->render_template('troubleticket/create', $this->data);
	    
	}

		
}