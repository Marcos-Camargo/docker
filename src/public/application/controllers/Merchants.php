<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Merchants extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        $this->data['page_title'] = $this->lang->line('application_merchant');
        
        $this->load->model('model_merchant');
		$this->config->set_item('csrf_protection', false);
    }
    
    /*
     * It only redirects to the manage product page and
     */
    public function index()
    {
        if(!in_array('viewMerchant', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
		$this->config->set_item('csrf_protection', false);
        $this->render_template('merchant/index', $this->data);
    }

    public function fetchMerchantData()
    {
        if(!in_array('viewMerchant', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL, TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		$busca = $postdata['search']; 
		$length = $postdata['length'];
		
        $procura = '';
        
        if (array_key_exists('ramo', $postdata) && trim($postdata['ramo'])) {
            $procura .= " AND ap.`text` like '%" . $this->db->escape_like_str($postdata['ramo']). "%'";
        }
        if (array_key_exists('nomeFantasia', $postdata) && trim($postdata['nomeFantasia'])) {
            $procura .= " AND s.fantasia like '%" . $this->db->escape_like_str($postdata['nomeFantasia']). "%'";
        }
        if (array_key_exists('razaoSocial', $postdata) && trim($postdata['razaoSocial'])) {
            $procura .= " AND s.nome like '%" . $this->db->escape_like_str($postdata['razaoSocial']). "%'";
        }
        if (array_key_exists('cnpj', $postdata) && trim($postdata['cnpj'])) {
            $procura .= " AND s.B2W_cnpj like '%" . $this->db->escape_like_str($postdata['cnpj']) . "%'";
        }
        if (array_key_exists('estado', $postdata) && trim($postdata['estado'])) {
            $procura .= " AND s.uf like '%" . $this->db->escape_like_str($postdata['estado']) . "%'";
        }
        if (array_key_exists('cidade', $postdata) && trim($postdata['cidade'])) {
            $procura .= " AND s.municipio like '%" . $this->db->escape_like_str($postdata['cidade']) . "%'";
        }
        if (trim($procura) != '') {
            $procura .= " ESCAPE '!'"; 
        }
        
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $busca['value'] = $this->db->escape_like_str($busca['value']);
                $busca['value'] = str_replace($busca['value'], "'", "''");
                $procura .= " AND ( ap.`text` like '%" . $busca['value'] . 
                    "%' OR s.fantasia like '%" . $busca['value'] . 
                    "%'  OR s.nome like '%" . $busca['value'] . 
                    "%'  OR s.B2W_cnpj like '%" . $busca['value'] . 
                    "%'  OR s.uf like '%" . $busca['value'] .
                    "%'  OR s.porte like '%" . $busca['value'] . 
                    "%'  OR s.municipio like '%" . $busca['value'] . "%') ";
            }
        } 

		$sOrder = '';
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('s.nome', 'ap.`text`', 's.porte', 's.uf', 's.municipio', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $records = $this->model_merchant->list($ini, $length, $procura, $sOrder);
        $recordsFiltered = $this->model_merchant->listCount($procura);
        
        $record_count = $this->model_merchant->count();
        $output = array(
			"draw" => $draw,
		    "recordsTotal" => $record_count['total_records'],
		    "recordsFiltered" => $recordsFiltered['total'],
		    "data" => $records
		);
		ob_clean();
		echo json_encode($output);
    }

    public function get($cnpj) {
        if(!in_array('viewMerchant', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $record = $this->model_merchant->find($cnpj);
        $record['ultima_atualizacao'] = date("d-m-Y", strtotime($record['ultima_atualizacao']));
        ob_clean();
        echo json_encode($record);
    }
}
