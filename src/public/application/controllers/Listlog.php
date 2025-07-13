<?php
/*
SW Serviços de Informática 2019

Controller de Lojas/Depósitos

*/   
defined('BASEPATH') OR exit('No direct script access allowed');

class Listlog extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_logs');

		$this->load->model('model_log_history');
		$this->load->model('model_users');
	}

	/* 
    * It only redirects to the manage stores page
    */
	public function index()
	{
		if(!in_array('viewStore', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$this->render_template('listlog/index', $this->data);	
	}

	/*
	* It retrieve the specific store information via a store id
	* and returns the data in json format.
	*/
	public function fetchStoresDataById($id) 
	{
		if($id) {
			$data = $this->model_stores->getStoresData($id);
			echo json_encode($data);
		}
	}
	public function fetchCompanyDataById($id) 
	{
		log_message('info', 'fetch START');
		if($id) {
			log_message('info', 'fetch ID:'.$id);

			$data = $this->model_company->getCompanyData($id);
			echo json_encode($data);
		}
	}

	/*
	* It retrieves all the store data from the database 
	* This function is called from the datatable ajax function
	* The data is return based on the json format.
	*/
	public function fetchLogData()
	{
        ob_start();
		$result = array('data' => array());
		
		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
        $busca = $postdata['search'];

        $procura ='';
		$busca = $postdata['search'];
		$length = $postdata['length'];

		$log = $postdata['log']; 
		
		$procura ='';

//		if ($busca['value']) {
			if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
				$procura .= " AND (module like '%".$busca['value']."%' OR users.email like '%".$busca['value']."%' OR action like '%".$busca['value']."%' OR ip like '%".$busca['value']."%') ";
			}
//		} else {
            if (trim($postdata['type'])) {
                $procura .= " AND tipo = '".$postdata['type']."' ";
            }  
			if (trim($postdata['module'])) {
                $procura .= " AND module like '%".$postdata['module']."%' ";
            }
			if (trim($postdata['action'])) {
                $procura .= " AND action like '%".$postdata['action']."%' ";
            }
			if (trim($postdata['value'])) {
                $procura .= " AND value like '%".$postdata['value']."%' ";
            }
			if (trim($postdata['startdate'])) {
                $procura .= " AND date_log >= '".$postdata['startdate'].":00"."'";
            }
			if (trim($postdata['enddate'])) {
                $procura .= " AND date_log <= '".$postdata['enddate'].":59"."'";
            }
			$procura = substr($procura,4);
//        }

		$sOrder = ' ORDER BY date_log DESC ';
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "ASC";
			} else { 
				$direcao = "DESC";
		    }
			$campos = array('id','date_log','users.email','tipo','module','action','ip','');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				if ($campo == 'id') {
					if ($direcao =="ASC") {$direcao ="DESC";}
					else {$direcao ="ASC";}
				}
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}

		$data = $this->model_log_history->getLogDataView($log, $ini, $procura, $sOrder, $length );
		$filtered = $this->model_log_history->getLogDataCount($log, $procura);
		if ($procura =='') {
			$total_rec = $filtered;
		} else {
			$total_rec = $this->model_log_history->getLogDataCount($log);
		}
		
		$users_table= $this->model_users->getUserData();
		foreach($users_table as $u) {
			$users[$u['id']] = $u['email'];
		}
		
		$result = array();
		$i = 0;
		foreach ($data as $key => $value) {
			$i++;
			// button
			$buttons = '';

			if(in_array('updateStore', $this->permission)) {
				$buttons .= ' <a href="'.base_url('listlog/view/'.$log.'/'.$value['id']).'" class="btn btn-default"><i class="fa fa-eye"></i></a>';
			}

			if ($value['tipo'] == 'I') {
				$tipo = '<span class="label label-success">Info</span>';			
			} elseif ($value['tipo'] == 'W') {
				$tipo = '<span class="label label-warning">Warning</span>';
			} else {
				$tipo = '<span class="label label-danger">Error</span>'; 
			}
			
			$result[$key] = array(
				$value['id'],
				$value['date_log'],
				$value['email'],
				$tipo,
				$value['module'],
				$value['action'],
				$value['ip'],
				$buttons
			);

			
		} // /foreach
		if ($filtered==0) {$filtered = $i;}
		$output = array(
		   "draw" => $draw,
		     "recordsTotal" => $total_rec,
		     "recordsFiltered" => $filtered,
		     "data" => $result
		);
		ob_clean();
		echo json_encode($output);
		
	}

	public function view($log,$id)
	{
		if(!in_array('updateStore', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		$users_table= $this->model_users->getUserData();
		$users =array();
		$users[0] = 'Rotina batch';
		foreach($users_table as $u) {
			$users[$u['id']] = $u['email'];
		}
		$this->data['log_data'] = $this->model_log_history->getLogData($log, $id);
		$this->data['log_data']['log'] = $log;
		$this->data['log_data']['retorna'] = $this->model_log_history->existLog($log,(string) $id-1);
		$this->data['log_data']['avanca'] = $this->model_log_history->existLog($log,$id+1);
		$this->data['log_data']['user'] = $users[$this->data['log_data']['user_id']]; 
		$this->render_template('listlog/view', $this->data);			
	}

}