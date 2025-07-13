<?php

defined('BASEPATH') || exit('No direct script access allowed');

class LogQuotes extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        $this->data['page_title'] = $this->lang->line('application_quotations');
        
        $this->load->model('model_log_quotes');
		
    }
    
    public function index($prd_id = null)
    {
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }
        
        if (is_null($prd_id)) {
			redirect('dashboard', 'refresh');
		}
        
        $this->data['prd_id'] = $prd_id;
        $this->render_template('logquotes/log_quotes', $this->data);
    }
	
	public function fetchLogQuotessData()
    {
        ob_start();
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }

        $prd_id = $this->postClean('prd_id',TRUE);
        if (is_null($prd_id) || ($prd_id=='')) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $busca = $postdata['search'];

		$procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $procura .= " AND ( zipcode like '%" . $busca['value'] . "%' OR integration like '%" . $busca['value'] . "%' OR created_at like '%" . $busca['value'] . "%' )";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
            	$direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('id', 'skumkt', 'zipcode', 'seller_id', 'integration','success','contingency','response_total_time','response_total_time_quote');
            $campo =  $campos[$postdata['order'][0]['column']];           
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        $data = $this->model_log_quotes->getLogQuotesData($prd_id, $procura, $sOrder, $ini, $length);
		$rectotal = $this->model_log_quotes->getLogQuotesDataCount($prd_id);
		$filtered = $rectotal;
		if ($procura !='') {
			$filtered = $this->model_log_quotes->getLogQuotesDataCount($prd_id, $procura);
		}
		
        foreach ($data as $key => $value) {
            $button = '<button onclick="viewDetails('.$value['id'].',\'tempo\')" data-toggle="modal" data-target="#showResponseDetails">Tempo</button>';
            $button .= '<button onclick="viewDetails('.$value['id'].',\'sla\')" data-toggle="modal" data-target="#showResponseDetails">Resposta</button>';
            $button = '<button onclick="viewDetails('.$value['id'].',\'tempo\')" >Tempo</button>';
            $button .= '<button onclick="viewDetails('.$value['id'].',\'sla\')" >Resposta</button>';
            $result[$key] = array(
                $value['created_at'] ,
            	$value['skumkt'] ,
            	$value['zipcode'] ,
                $value['seller_id'] ,
                $value['integration'] ,
                ($value['success']) ? $this->lang->line('application_yes') :  $this->lang->line('application_no'),
                ($value['contingency']) ? $this->lang->line('application_yes') :  $this->lang->line('application_no'),
                $value['response_total_time'],
                $value['response_total_time_quote'] ,
                $button
            );
        } 
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $rectotal,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
		ob_clean();
        echo json_encode($output);

    }

    public function getDetails($id =null, $type = 'sla') {
        ob_start();
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }
        
        if (is_null($id)) {
			redirect('dashboard', 'refresh');
		}

        $log = $this->model_log_quotes->getLog($id);

        ob_clean();
        if ($log) {
            echo json_encode (array (
                'success' => true, 
                'message' => htmlspecialchars(json_encode(json_decode(
                    ($type == 'sla') ? $log['response_slas'] : $log['response_details_time']),JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES)),
                'error_message' => $log['error_message']
            ));
        }
        else {
            echo json_encode (array (
                'success' => false,
                'message' => 'Não encontrado',
                'error_message' => ''
            ));
        }

    }
    
}