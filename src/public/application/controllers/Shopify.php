<?php
/*

Controller de lojas shopfy

*/   
defined('BASEPATH') OR exit('No direct script access allowed');

class Shopify extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->data['page_title'] = $this->lang->line('application_stores');

		$this->not_logged_in();
		$this->load->model('Model_shopify_new_stores');
	
	}

	/* 
    * It only redirects to the manage stores page
    */
	public function shopify_requests()
	{
		if(!in_array('viewStore', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$this->render_template('shopify/shopify_requests', $this->data);	
	}

    // public function shopify_received_data($id)
    // {
    //     if (!in_array('viewStore', $this->permission)) {
    //         redirect('dashboard', 'refresh');
    //     }
    //     // $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
    //     // $this->data['store_id'] = $id;
    //     $this->render_template('shopify/shopify_received_data', $this->data);
    // }

	public function fetchStoresData()
    {

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( s.company_id like '%" . $busca['value'] . "%' OR s.company_CNPJ like '%" . $busca['value'] . "%') ";  //OR s.creation_status like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['company_id'])) {
                $procura .= " AND s.company_id like '%" . $postdata['company_id'] . "%'";
            }
            if (trim($postdata['CNPJ'])) {
                $procura .= " AND s.company_CNPJ like '%" . $postdata['company_CNPJ'] . "%'";
            }
            if (trim($postdata['creation_status'])!="") {
                $procura .= " AND s.creation_status = " . $postdata['creation_status'];
				
            }
            
        }

		if ($procura != "") {
            $procura = " WHERE ". substr($procura, 4);
        }

        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('s.company_id', 's.company_CNPJ', 's.responsible_email', 's.email_date', 's.store_creation_date', 's.creation_status');

            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

     
        $data = $this->Model_shopify_new_stores->getShopifyStoresDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->Model_shopify_new_stores->getShopifyStoresDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->Model_shopify_new_stores->getShopifyStoresDataCount();
        }

        $result = array();
        
        foreach ($data as $key => $value) {
        
        
			$result[$key] = array(
				$value['company_id'],
                //'<a href="' . base_url('shopify/shopify_received_data/' . $value['company_id']) . '">' . $value['company_id'] . '</a>',
                $value['company_name'],
				$value['company_CNPJ'],
				$value['responsible_email'],
				date("d/m/Y H:i", strtotime($value['email_date'])),
				$value['store_creation_date'] ?date("d/m/Y H:i", strtotime($value['store_creation_date'])):'',
				$value['creation_status'] == 1?"<span class='label label-success'>Criada</span>":"<span class='label label-danger'>Não criada</span>",
                $value['creation_status'] == 0?"<a href = '".base_url($value['creation_link'])."' class = 'btn btn-default'><i class=\"fas fa-link\"></i> &nbsp;Link</a>":""
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

		
}