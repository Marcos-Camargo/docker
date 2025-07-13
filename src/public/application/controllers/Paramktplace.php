<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/  
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";

use Microservices\v1\Logistic\FreightTables;

/**
 * @property CI_Session $session
 * @property CI_Lang $lang
 * @property CI_Loader $load
 *
 * @property Model_parametrosmktplace $model_parametrosmktplace
 * @property Model_shipping_company $model_shipping_company
 *
 * @property FreightTables $ms_freight_tables
 */

class Paramktplace extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_params_mktplace');

        $this->load->model('model_parametrosmktplace');
        $this->load->model('model_shipping_company');

        $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');

		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$more = " company_id = ".$usercomp;
		
	}

	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewParametersmktplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = $this->lang->line('application_params_manage');
		$this->render_template('parametrosmktplace/index', $this->data);		
	}
	
	public function list()
	{
	    if(!in_array('viewParammktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_manage_receivables');
	    
	    if(in_array('createParamktplace', $this->permission)) {
	        $this->data['categs'] = $this->model_parametrosmktplace->getAllCategs();
	        $this->data['mktPlaces'] = $this->model_parametrosmktplace->getAllMktPlace();
	        $this->data['mktPlace2s'] = $this->model_parametrosmktplace->getAllMktPlace();
	    }
	    
	    $this->render_template('paramktplace/list', $this->data);
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchParamktPlaceData()
	{
	    
		$result = array('data' => array());

		$data = $this->model_parametrosmktplace->getReceivablesData();
		
		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			/*if(in_array('viewParammktplace', $this->permission)) {
				$buttons .= '<a target="__blank" href="'.base_url('paramktplace/printDiv/'.$value['id']).'" class="btn btn-default"><i class="fa fa-print"></i></a>';
			}*/

			if(in_array('updateParamktplace', $this->permission)) {
				$buttons .= ' <a href="'.base_url('paramktplace/edit/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
			}

			if(in_array('deleteParamktplace', $this->permission)) {
				$buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
			}
			 
			if($value['integ_id'] == "11"){
			    if($value['valor_aplicado_ml_free'] <> ""){
			        $valorAplicado = $value['valor_aplicado']."%"." / ".$value['valor_aplicado_ml_free']."%";
			    }else{
			        $valorAplicado = $value['valor_aplicado']."%";
			    }
			    
			}else{
			    $valorAplicado = $value['valor_aplicado']."%";
			}

			$result['data'][$key] = array(
				$value['id'],
				$value['mkt_place'],
			    $value['categoria'],
				$value['data_inicio_vigencia'],
			    $value['data_fim_vigencia'],
			    $valorAplicado,
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}

	/*
	* If the validation is not valid, then it redirects to the create page.
	* If the validation for each input field is valid then it inserts the data into the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function verificacadastro(){
	    
	    if(!in_array('createReceivables', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
    	    $params = $this->postClean(NULL,TRUE);
    	    //Verifica se já existe essa combinação de categoria x mktplace
    	    $checkExiste = $this->model_parametrosmktplace->checkExiste($params['cmb_mktplace'],$params['cmb_categoria']);
    	    
    	    if($checkExiste){
    	        echo "1;Categoria já cadastrada para esse Marketplace";
    	    }else{
    	        //Verifica se é categoria outros e se já existe essa categoria cadastrada
    	        $checkExiste2 = $this->model_parametrosmktplace->checkExisteOutros($params['cmb_mktplace'],$params['txt_categoria']);
    	        if($checkExiste2){
    	            echo "1;Categoria já cadastrada para esse Marketplace";
    	        }else{
    	            //cadastra categoria
    	            $checkCadastro = $this->model_parametrosmktplace->insertCategoria($params);
    	            if($checkCadastro == false){
                        echo "1;Erro ao cadastrar essa categoria no Marketplace";           
    	            }else{
    	                echo "0;Cadastro de categoria efetuado com sucesso";
    	                redirect('paramktplace/list', 'refresh');
    	            }
    	        }
    	    }
	    
	    }
	    
	}
	
	public function editcadastromktplacefull(){
	    
	    if(!in_array('updateReceivables', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        
	        //print_r($params);die;
	        
            //edita todas as categorias do mktplace
            $checkCadastro = $this->model_parametrosmktplace->editFullCategoriasMktplace($params);
            if($checkCadastro == false){
                echo "1;Erro ao editar as categoria do Marketplace";
            }else{
                echo "0;Edição de categoria efetuado com sucesso";
                redirect('paramktplace/list', 'refresh');
            }
	        
	    }
	    
	}
	
	public function edit($id)
	{
	    if(!in_array('updateParamktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    if($id == "" || $id == null){
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_update_category');
	    
	    $param_mktplace_data = $this->model_parametrosmktplace->getReceivablesData($id);
// 	    echo '<pre>';print_r($param_mktplace_data);die;
	    $categ = $this->model_parametrosmktplace->getAllCategs();
	    $mktPlace = $this->model_parametrosmktplace->getAllMktPlace();
	    
	    foreach($mktPlace as $mktPlac){
	        if($mktPlac['id'] == $param_mktplace_data['integ_id']){
	            $saidaMktPlace = $mktPlac;
	        }
	    }
	    
	    
	    $this->data['categs'] = $categ[$param_mktplace_data['mkt_categ_id']];
	    $this->data['mktPlaces'] = $saidaMktPlace;
	    
	    $result = array();
	    $this->data['paramktplace_data'] = $param_mktplace_data;
	    
	    $this->render_template('paramktplace/edit', $this->data);
	    
	}
	
	public function verificaedicao(){
	    
	    if(!in_array('updateParamktplace', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        
	        //Verifica se os valores de input estão corretos
	        if($params['cmb_categoria'] <> "" && $params['txt_valor_aplicado'] <> "" && is_numeric($params['txt_valor_aplicado']) && $params['cmb_mktplace'] <> ""  ){
	            
	            //Salva as alterações de percentual desconto
	            $checkEdit = $this->model_parametrosmktplace->editPercentual($params);
	            
	            if($checkEdit){
	                echo "0;Edição efetuada com sucesso";
	            }else{
	                echo "1;Erro ao editar o percentual de desconto";
	            }
	            
	        }else{
	            echo "1;Erro no formulário";
	        }
	    }
	        
	}
	
		/*
	* It removes the data from the database
	* and it returns the response into the json format
	*/
	public function remove()
	{
		if(!in_array('deleteParamktplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$id = $this->postClean('id');

		$response = array();
		if($id) {
            $delete = $this->model_parametrosmktplace->remove($id);
            if($delete == true) {
                echo "0;Exclusão efetuada com sucesso";
            }
            else {
                echo "1;Erro ao excluir";
            }
        }
        else {
            echo "1;Erro ao excluir";
        }

	}
	
	
	/**************** CICLO *******************/
	
	public function listciclo($update = null)
	{
	    if(!in_array('viewParammktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_manage_receivables');
	    
	    if(in_array('createParamktplace', $this->permission)) {
	        $this->data['categs'] = $this->model_parametrosmktplace->getAllCategs();
	        $this->data['mktPlaces'] = $this->model_parametrosmktplace->getAllMktPlace();
	        $this->data['mktPlace2s'] = $this->model_parametrosmktplace->getAllMktPlace();
	    }
	    if(!is_null($update)){
            $this->session->set_flashdata('success', 'Atualizado com sucesso!');
        }
	    $this->render_template('paramktplace/listciclo', $this->data);
	}
	
	public function fetchParamktPlaceDataCiclo()
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    
	    foreach ($data as $key => $value) {
	        
	        // button
	        $buttons = '';
	        
	        if(in_array('updateParamktplace', $this->permission)) {
	            $buttons .= ' <a href="'.base_url('paramktplace/editciclo/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
	        }
	        
	        if(in_array('deleteParamktplace', $this->permission)) {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFuncCiclo('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['mkt_place'],
	            $value['data_inicio'],
	            $value['data_fim'],
	            $value['data_pagamento'],
	            $value['data_pagamento_conecta'],
				$value['data_usada'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}
	
	public function verificacadastrociclo(){
	    
	    if(!in_array('createParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        //Verifica se já existe essa combinação de categoria x mktplace
	        $checkExiste = $this->model_parametrosmktplace->checkExisteCiclo($params);
	        if($checkExiste){
	            echo "1;Categoria já cadastrada para esse Marketplace";
	        }else{
                //cadastra ciclo
	            $checkCadastro = $this->model_parametrosmktplace->insertciclo($params);
                if($checkCadastro == false){
                    echo "1;Erro ao cadastrar esse tipo de ciclo no Marketplace";
                }else{
                    echo "0;Cadastro de ciclo efetuado com sucesso";
                    redirect('paramktplace/listciclo', 'refresh');
                }
	        }
	        
	    }
	    
	}
	
	public function removeciclo()
	{
	    if(!in_array('deleteParamktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $id = $this->postClean('id');
	    
	    $response = array();
	    if($id) {
	        $delete = $this->model_parametrosmktplace->removeciclo($id);
	        if($delete == true) {
	            echo "0;Exclusão efetuada com sucesso";
	        }
	        else {
	            echo "1;Erro ao excluir";
	        }
	    }
	    else {
	        echo "1;Erro ao excluir";
	    }
	    
	}
	
	public function editciclo($id)
	{
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    if($id == "" || $id == null){
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_parameter_mktplace_ciclo_edit');
	    
	    $param_mktplace_data = $this->model_parametrosmktplace->getReceivablesDataCiclo($id);
	    $mktPlace = $this->model_parametrosmktplace->getAllMktPlace();

	    foreach($mktPlace as $mktPlac){
	        if($mktPlac['id'] == $param_mktplace_data['integ_id']){
	            $saidaMktPlace = $mktPlac;
	        }
	    }
	    
	    $this->data['mktPlaces'] = $saidaMktPlace;
	    
	    $result = array();
	    $this->data['paramktplace_data'] = $param_mktplace_data;

	    $this->render_template('paramktplace/editciclo', $this->data);
	    
	}
	
	public function verificaedicaociclo(){
	    
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        
	        //Verifica se os valores de input estão corretos
	        if($params['cmb_mktplace'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_pagamento'] <> ""  && $params['txt_data_pagamento_conecta'] <> ""  ){
	            
	            //Salva as alterações de percentual desconto
	            $checkEdit = $this->model_parametrosmktplace->editCiclo($params);
	            
	            if($checkEdit){
	                echo "0;Edição efetuada com sucesso";
	            }else{
	                echo "1;Erro ao editar o ciclo";
	            }
	            
	        }else{
	            echo "1;Erro no formulário";
	        }
	    }
	    
	}

 
	/*************** CICLO TRANSPORTADORA ********************/

	public function listciclotransp()
	{
	    if(!in_array('viewParammktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_manage_receivables');
	    
	    if(in_array('createParamktplace', $this->permission)) {
	        $this->data['mktPlace2s'] = $this->model_parametrosmktplace->getAllMktPlacetransp();

            if ($this->ms_freight_tables->use_ms_shipping) {
                try {
                    $mktPlaces = $this->ms_freight_tables->getShippingCompanies();
                } catch (Exception $exception) {
                    $mktPlaces = array();
                }
            } else {
                $mktPlaces = $this->model_parametrosmktplace->getAllMktPlacetransp();
            }

            $this->data['mktPlaces'] = array();
            foreach ($mktPlaces as $shipping_company) {
                $this->data['mktPlaces'][] = array(
                    'id'            => $this->ms_freight_tables->use_ms_shipping ? $shipping_company->id : $shipping_company['id'],
                    'razao_social'  => $this->ms_freight_tables->use_ms_shipping ? $shipping_company->name : $shipping_company['razao_social']
                );
            }
	    }
	    
	    $this->render_template('paramktplace/listciclotransp', $this->data);
	}
	
	public function fetchParamktPlaceDataCiclotransp()
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_parametrosmktplace->getReceivablesDataCiclotransp();
	    
	    foreach ($data as $key => $value) {
	        
	        // button
	        $buttons = '';
	        
	        if(in_array('updateParamktplace', $this->permission)) {
	            $buttons .= ' <a href="'.base_url('paramktplace/editciclotransp/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
	        }
	        
	        if(in_array('deleteParamktplace', $this->permission)) {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFuncCiclo('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['mkt_place'],
	            $value['tipo_ciclo'],
	            $value['dia_semana'],
	            $value['data_inicio'],
	            $value['data_fim'],
	            $value['data_pagamento'],
	            $value['data_pagamento_conecta'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}
	
	public function verificacadastrociclotransp(){
	    
	    if(!in_array('createParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        //Verifica se já existe essa combinação de categoria x mktplace
	        $checkExiste = $this->model_parametrosmktplace->checkExisteCiclotransp($params);
	        if($checkExiste){
	            echo "1;Categoria já cadastrada para esse Marketplace";
	        }else{
	            //cadastra ciclo
	            $checkCadastro = $this->model_parametrosmktplace->insertciclotransp($params);
	            if($checkCadastro == false){
	                echo "1;Erro ao cadastrar esse tipo de ciclo no Marketplace";
	            }else{
	                echo "0;Cadastro de ciclo efetuado com sucesso";
	                redirect('paramktplace/listciclotransp', 'refresh');
	            }
	        }
	        
	    }
	    
	}
	
	public function removeciclotransp()
	{
	    if(!in_array('deleteParamktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $id = $this->postClean('id');
	    
	    $response = array();
	    if($id) {
	        $delete = $this->model_parametrosmktplace->removeciclotransp($id);
	        if($delete == true) {
	            echo "0;Exclusão efetuada com sucesso";
	        }
	        else {
	            echo "1;Erro ao excluir";
	        }
	    }
	    else {
	        echo "1;Erro ao excluir";
	    }
	    
	}
	
	public function editciclotransp($id)
	{
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    if($id == "" || $id == null){
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_parameter_mktplace_ciclo_edit');
	    
	    $param_mktplace_data = $this->model_parametrosmktplace->getReceivablesDataCiclotransp($id);
	    $mktPlace = $this->model_parametrosmktplace->getAllMktPlacetransp();
	    
	    foreach($mktPlace as $mktPlac){
	        if($mktPlac['id'] == $param_mktplace_data['providers_id']){
	            $saidaMktPlace = $mktPlac;
	        }
	    }
	    
	    $this->data['mktPlaces'] = $saidaMktPlace;
	    
	    $result = array();
	    $this->data['paramktplace_data'] = $param_mktplace_data;
	    
	    $this->render_template('paramktplace/editciclotransp', $this->data);
	    
	}
	
	public function verificaedicaociclotransp(){
	    
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        
	        //Verifica se os valores de input estão corretos
	        if($params['cmb_mktplace'] <> ""){
	            
	            if( $params['cmb_mktplace'] == "Ciclo" && ($params['txt_data_inicio'] == "" || $params['txt_data_fim'] <> "" || $params['txt_data_pagamento'] <> ""  || $params['txt_data_pagamento_conecta'] <> "")){
	                echo "1;Erro no formulário3";die;
	            }else{
	                //Salva as alterações de percentual desconto
	                $checkEdit = $this->model_parametrosmktplace->editCiclotransp($params);
	                
	                if($checkEdit){
	                    echo "0;Edição efetuada com sucesso";
	                }else{
	                    echo "1;Erro ao editar o ciclo";
	                }
	            }
	            
	            if( $params['cmb_mktplace'] == "Semanal"  && ($params['cmb_week_day'] <> "" || $params['txt_data_pagamento'] <> ""  || $params['txt_data_pagamento_conecta'] <> "")){
	                echo "1;Erro no formulário2";die;
	            }else{
	                //Salva as alterações de percentual desconto
	                $checkEdit = $this->model_parametrosmktplace->editCiclotransp($params);
	                
	                if($checkEdit){
	                    echo "0;Edição efetuada com sucesso";
	                }else{
	                    echo "1;Erro ao editar o ciclo";
	                }
	            }
	         
	        }
	    
	   }
	
	}
	
	/**************** CICLO GRUPO SOMA *******************/
	
	public function listciclosellercenter()
	{
	    if(!in_array('viewParammktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_manage_receivables');
	    
	    if(in_array('createParamktplace', $this->permission)) {
	        $this->data['categs'] = $this->model_parametrosmktplace->getAllCategs();
	        $this->data['mktPlaces'] = $this->model_parametrosmktplace->getAllMktPlace();
	        $this->data['mktPlace2s'] = $this->model_parametrosmktplace->getAllMktPlace();
	    }
	    
	    $this->render_template('paramktplace/listciclosoma', $this->data);
	}
	
	public function fetchParamktPlaceDataCiclosoma()
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    
	    foreach ($data as $key => $value) {
	        
	        // button
	        $buttons = '';
	        
	        if(in_array('updateParamktplace', $this->permission)) {
	            $buttons .= ' <a href="'.base_url('paramktplace/editciclosoma/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
	        }
	        
	        if(in_array('deleteParamktplace', $this->permission)) {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFuncCiclo('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['mkt_place'],
	            $value['data_inicio'],
	            $value['data_fim'],
	            $value['data_pagamento'],
				$value['data_usada'],
	           // $value['data_pagamento_conecta'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}
	
	public function verificacadastrociclosoma(){
	    
	    if(!in_array('createParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        $params['txt_data_pagamento_conecta'] = "0";
	        //Verifica se já existe essa combinação de mktplace x data inicio/data fim
	        $checkExiste = $this->model_parametrosmktplace->checkExisteCiclo($params);
	        if($checkExiste){
	            echo "1;Cilco já cadastrada para esse Marketplace";
	        }else{
	            //cadastra ciclo
	            $checkCadastro = $this->model_parametrosmktplace->insertciclo($params);
	            if($checkCadastro == false){
	                echo "1;Erro ao cadastrar esse tipo de ciclo no Marketplace";
	            }else{
	                echo "0;Cadastro de ciclo efetuado com sucesso";
	                redirect('paramktplace/listciclo', 'refresh');
	            }
	        }
	        
	    }
	    
	}
	
	public function removeciclosoma()
	{
	    if(!in_array('deleteParamktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $id = $this->postClean('id');
	    
	    $response = array();
	    if($id) {
	        $delete = $this->model_parametrosmktplace->removeciclo($id);
	        if($delete == true) {
	            echo "0;Exclusão efetuada com sucesso";
	        }
	        else {
	            echo "1;Erro ao excluir";
	        }
	    }
	    else {
	        echo "1;Erro ao excluir";
	    }
	    
	}
	
	public function editciclosoma($id)
	{
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    if($id == "" || $id == null){
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_parameter_mktplace_ciclo_edit');
	    
	    $param_mktplace_data = $this->model_parametrosmktplace->getReceivablesDataCiclo($id);
	    $mktPlace = $this->model_parametrosmktplace->getAllMktPlace();
	    
	    foreach($mktPlace as $mktPlac){
	        if($mktPlac['id'] == $param_mktplace_data['integ_id']){
	            $saidaMktPlace = $mktPlac;
	        }
	    }
	    
	    $this->data['mktPlaces'] = $saidaMktPlace;
	    
	    $result = array();
	    $this->data['paramktplace_data'] = $param_mktplace_data;
	    
	    $this->render_template('paramktplace/editciclosoma', $this->data);
	    
	}
	
	public function verificaedicaociclosoma(){
	    
	    if(!in_array('updateParamktplaceCiclo', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        $params['txt_data_pagamento_conecta'] = "0";
	        //Verifica se os valores de input estão corretos
	        if($params['cmb_mktplace'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_pagamento'] <> "" ){
	            
	            //Salva as alterações de percentual desconto
	            $checkEdit = $this->model_parametrosmktplace->editCiclo($params);
	            
	            if($checkEdit){
	                echo "0;Edição efetuada com sucesso";
	            }else{
	                echo "1;Erro ao editar o ciclo";
	            }
	            
	        }else{
	            echo "1;Erro no formulário";
	        }
	    }
	    
	}


	/**************** CICLO FISCAL *******************/
	
	public function listciclofiscalsellercenter()
	{
	    if(!in_array('createParamktplaceFiscal', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_manage_receivables');
	    
	    if(in_array('createParamktplaceFiscal', $this->permission)) {
	        $this->data['categs'] = $this->model_parametrosmktplace->getAllCategs();
	        $this->data['mktPlaces'] = $this->model_parametrosmktplace->getAllMktPlace();
	        $this->data['mktPlace2s'] = $this->model_parametrosmktplace->getAllMktPlace();
	    }
	    
	    $this->render_template('paramktplace/listciclofiscal', $this->data);
	}

	public function fetchParamktPlaceDataFiscal()
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_parametrosmktplace->getReceivablesDataCicloFiscal();
	    
	    foreach ($data as $key => $value) {
	        
	        // button
	        $buttons = '';
	        
	        if(in_array('updateParamktplace', $this->permission)) {
	            $buttons .= ' <a href="'.base_url('paramktplace/editciclofiscal/'.$value['id']).'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
	        }
	        
	        if(in_array('deleteParamktplace', $this->permission)) {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFuncCiclo('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['mkt_place'],
	            $value['data_inicio'],
	            $value['data_fim'],
	            $value['data_ciclo_fiscal'],
				$value['data_usada'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}

	public function verificacadastrocicloFiscal(){
	    
	    if(!in_array('createParamktplaceFiscal', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        //Verifica se já existe essa combinação de mktplace x data inicio/data fim
	        $checkExiste = $this->model_parametrosmktplace->checkExisteCicloFiscal($params);
	        if($checkExiste){
	            echo "1;Ciclco já cadastrada para esse Marketplace";
	        }else{
	            //cadastra ciclo
	            $checkCadastro = $this->model_parametrosmktplace->insertciclofiscal($params);
	            if($checkCadastro == false){
	                echo "1;Erro ao cadastrar esse tipo de ciclo no Marketplace";
	            }else{
	                echo "0;Cadastro de ciclo efetuado com sucesso";
	                redirect('paramktplace/listciclo', 'refresh');
	            }
	        }
	        
	    }
	    
	}

	public function removeciclofiscal()
	{
	    if(!in_array('deleteParamktplace', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $id = $this->postClean('id');
	    
	    $response = array();
	    if($id) {
	        $delete = $this->model_parametrosmktplace->removeciclofiscal($id);
	        if($delete == true) {
	            echo "0;Exclusão efetuada com sucesso";
	        }
	        else {
	            echo "1;Erro ao excluir";
	        }
	    }
	    else {
	        echo "1;Erro ao excluir";
	    }
	    
	}

	public function editciclofiscal($id)
	{
	    if(!in_array('updateParamktplaceFiscal', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    if($id == "" || $id == null){
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_parameter_mktplace_ciclo_edit');
	    
	    $param_mktplace_data = $this->model_parametrosmktplace->getReceivablesDataCicloFiscal($id);
	    $mktPlace = $this->model_parametrosmktplace->getAllMktPlace();
	    
	    foreach($mktPlace as $mktPlac){
	        if($mktPlac['id'] == $param_mktplace_data['integ_id']){
	            $saidaMktPlace = $mktPlac;
	        }
	    }
	    
	    $this->data['mktPlaces'] = $saidaMktPlace;
	    
	    $result = array();
	    $this->data['paramktplace_data'] = $param_mktplace_data;
	    
	    $this->render_template('paramktplace/editciclofiscal', $this->data);
	    
	}

	public function verificaedicaociclofiscal(){
	    
	    if(!in_array('updateParamktplaceFiscal', $this->permission)) {
	        echo "1;Acesso negado";
	    }else{
	        $params = $this->postClean(NULL,TRUE);
	        $params['txt_data_pagamento_conecta'] = "0";
	        //Verifica se os valores de input estão corretos
	        if($params['cmb_mktplace'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_inicio'] <> "" && $params['txt_data_pagamento'] <> "" ){
	            
	            //Salva as alterações de percentual desconto
	            $checkEdit = $this->model_parametrosmktplace->editCicloFiscal($params);
	            
	            if($checkEdit){
	                echo "0;Edição efetuada com sucesso";
	            }else{
	                echo "1;Erro ao editar o ciclo";
	            }
	            
	        }else{
	            echo "1;Erro no formulário";
	        }
	    }
	    
	}
	
	
}