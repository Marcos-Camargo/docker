<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Produtos
 
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class WaitingIntegration extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_waitingintegration');
        
        $this->load->model('model_integrations');
        $this->load->model('model_blingultenvio');
        
        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = ".$usercomp;
        $this->data['mycontroller']=$this;
        
    }
    
    public function semIntegracao()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //get_instance()->log_data('Products','index','-');
        
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('waitingintegration/semintegracao', $this->data);
    }
    
    public function mktselect()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        if (!is_null($this->postClean('id'))) {
            get_instance()->log_data('Products','SendMKT',json_encode($_POST),"I");
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_blingultenvio->setMarcaInt($id,"1");
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    $this->model_blingultenvio->setMarcaInt($id,null);
                    //this->model_integrations->unsetProductToMkt($mkt,$id,$cpy,$prd);
                }
            }
        }
        
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('waitingintegration/semintegracao', $this->data);
    }
    
    public function semIntegracaoData($isMkt = false)
    {
        
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND ( skubling like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%' ) ";
            }
        }
        // Filtro por marketplace
        if ($postdata['marketplace'] != "") {
            $procura .= " AND int_to = '{$postdata['marketplace']}' ";
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('','int_to','skubling','p.name','b.price','b.qty','','','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        $data = $this->model_blingultenvio->getDataSemItegracao($ini, $sOrder, $procura);
        
        $i = 0;
        $filtered = $this->model_blingultenvio->getCountSemItegracao($procura);
        
        foreach ($data as $key => $value) {
            $i++;
            $instrucoes ="Escolha a categoria,";
            if ($value['int_to'] == 'MAGALU') {
                $instrucoes .=" zere id na loja,";
            }
            $instrucoes = substr($instrucoes, 0, -1);
            $result[$key] = array(
                $value['id'],
                $value['int_to'],
                $value['skubling'],
                $value['name'],
                $value['price'],
                $value['qty'],
                date('d/m/Y H:i:s', strtotime($value['date_create'])),
                $instrucoes
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_blingultenvio->getCountSemItegracao(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean(); 
        echo json_encode($output);
    }

	public function integrationPriceQty()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        //get_instance()->log_data('Products','index','-');
        
        $this->render_template('waitingintegration/integrationpriceqty', $this->data);
    }
	
	 public function integrationPriceQtyData($isMkt = false)
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        
        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
		
        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND ( skubling like '%".$busca['value']."%' OR s.name like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%' OR p.id like '%".$busca['value']."%') ";
            }
        }
        // Filtro por marketplace
        if ($postdata['marketplace'] != "") {
            $procura .= " AND int_to = '{$postdata['marketplace']}' ";
        }
		// Filtro por preco ou qty 
		$escopo = " AND (integrar_price = true OR integrar_qty = true) ";
        if ($postdata['integration'] != "") {
        	if ($postdata['integration'] == 'Preço') {
            	$escopo = " AND integrar_price = true ";
			}
			else {
				$escopo = "  AND integrar_qty = true ";
			}
        }
		
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('','p.id','int_to','s.name','skubling','p.name','CAST(b.price AS UNSIGNED)','CAST(b.qty AS UNSIGNED)','data_ult_envio','','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
        $data = $this->model_blingultenvio->getDataIntegrationPriceQty($ini, $sOrder, $escopo.$procura);
        
        $i = 0;
        $filtered = $this->model_blingultenvio->getCountIntegrationPriceQty($escopo.$procura);
        
        foreach ($data as $key => $value) {
            $i++;
			if ($value['integrar_price'] && $value['integrar_qty'])  {
				$instrucoes ="Integrar Preço e Estoque";
			}
			elseif ($value['integrar_price']) {
				$instrucoes ="Integrar Preço";
			}else {
				$instrucoes ="Integrar Estoque";
			}
            $linkid = '<a href="'.base_url().'products/update/'.$value['product_id'].'" target="_blank">'.$value['product_id'].'</a>';
            $result[$key] = array(
                $value['id'],
                $linkid,
                $value['int_to'],
                $value['store'],
                $value['skubling'],
                $value['name'],
                $value['price'],
                $value['qty'],
                (is_null($value['data_ult_envio'])) ?'' :date('d/m/Y H:i:s', strtotime($value['data_ult_envio'])),
                $instrucoes
            );
            
        } // /foreach
        if ($filtered==0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_blingultenvio->getCountIntegrationPriceQty($escopo),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

		 //$this->session->set_flashdata('success',json_encode( $output )); 
		ob_clean(); 
        echo json_encode($output);
    }
    
     public function markIntegrated()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        if (!is_null($this->postClean('id'))) {
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('selectPrice'))) {
                foreach ($ids as $k => $id) {
                    $this->model_blingultenvio->setIntegrarPrice($id,false);
                }
            }
            if (!is_null($this->postClean('selectQty'))) {
                foreach ($ids as $k => $id) {
                    $this->model_blingultenvio->setIntegrarQty($id,false);
                }
            }
        }
        
        $this->data['plats'] = $this->model_integrations->getIntegrationsData();
        $this->render_template('waitingintegration/integrationpriceqty', $this->data);
    }
}