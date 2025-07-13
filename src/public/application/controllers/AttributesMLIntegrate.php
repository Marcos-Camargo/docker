<?php
/*
SW Serviços de Informática 2019

Controller de Produtos
 
*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class AttributesMLIntegrate extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_attributes_integration');
		
		$this->load->model('model_atributos_categorias_marketplaces'); 
		
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$this->data['mycontroller']=$this; 
		
	}

	public function index()
	{
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		//get_instance()->log_data('Products','index','-');

		// $this->data['plats'] = $this->model_integrations->getIntegrationsData();
		$this->render_template('attributesmlintegrate/index', $this->data);	
	}
	
	public function mktselect()
	{
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		
		if (!is_null($this->postClean('id'))) {
			$ids = $this->postClean('id');
			if (!is_null($this->postClean('select'))) {
				foreach ($ids as $k => $v) {
				   list($id_integration, $id_atributo,$id_categoria) = explode("|", $v);
				   $this->model_atributos_categorias_marketplaces->setMarcaInt($id_integration, $id_atributo,$id_categoria,"1");
				}
			}
			if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer.... 
				foreach ($ids as $k => $v) {
					list($id_integration, $id_atributo,$id_categoria) = explode("|", $v);
					$this->model_atributos_categorias_marketplaces->setMarcaInt($id_integration, $id_atributo,$id_categoria,null);
				//this->model_integrations->unsetProductToMkt($mkt,$id,$cpy,$prd);
				}
			}			
		}	
		 
		// $this->data['plats'] = $this->model_integrations->getIntegrationsData();
		$this->render_template('attributesmlintegrate/index', $this->data);	
	}
	
	public function atributosSemIntegracaoData()
	{
		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		//$ini = 0; 
	    //get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
		$busca = $postdata['search']; 
		$procura = '';
		if ($busca['value']) {
			if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
				$procura = " AND ( a.id_atributo like '%".$busca['value']."%' OR ".
				$procura .= " am.nome like '%".$busca['value']."%' OR c.nome like '%".$busca['value']."%' ) ";
			} 
		} 		
		
		$sOrder = "";
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "asc";
			} else { 
				$direcao = "desc";
		    }
			$campos = array('','atributo','nome_atributo','categoria','marketplace', 'usado');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}
		
		$result = array();


		$data = $this->model_atributos_categorias_marketplaces->getDataSemItegracao($ini, $sOrder, $procura);

		$filtered = $this->model_atributos_categorias_marketplaces->getCountSemItegracao($procura);;

		$i = 0;
		foreach ($data as $key => $value) {
			$i++;
			// echo $value['id_integration']."|".$value['atributo'].'|'.$value['categoria_id']."\n";
			$status ='<span class="label-danger">-</span>';
			
			$catName = '<em style="color:red">'.$value['categoria'].'</em>' ; 
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
			
			
			$result[$key] = array(
				$value['id_integration']."|".$value['atributo'].'|'.$value['categoria_id'],			
				$value['atributo'],
				$value['nome_atributo'],
                $catName,
                $value['marketplace'],
                $status
			);
	
		} // /foreach

		$output = array(
		   "draw" => $draw,
		     "recordsTotal" => $this->model_atributos_categorias_marketplaces->getCountSemItegracao(),
		     "recordsFiltered" => $filtered,
		     "data" => $result
		);

		echo json_encode($output);
	}	
}