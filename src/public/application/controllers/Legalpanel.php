<?php

use App\Libraries\Enum\LegalPanelNotificationType;

defined('BASEPATH') OR exit('No direct script access allowed');

class Legalpanel extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_legal_panel');
        
        $this->load->model('model_legal_panel');
        $this->load->model('model_stores');

    }
    

    public function index()
    {
        if(!in_array('viewLegalPanel', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->render_template('legalpanel/index', $this->data);
    }

    public function fetchLegalPanelData()
    {

        $status = $_GET['status'];

		$result = array('data' => array());

		$data = $this->model_legal_panel->getAll($status);
		
		setlocale(LC_MONETARY,"pt_BR", "ptb");
		
		foreach ($data as $key => $value) {

			$store_id = '';
			$store_data = [];
			$buttons = '';

			if(in_array('updateLegalPanel', $this->permission)) {
				$buttons .= ' <a href="'.base_url('legalpanel/edit/'.$value['id']).'" class="btn btn-sm btn-default"><i class="fa fa-edit"></i></a>';
			}
			if(in_array('viewLegalPanel', $this->permission)) {
			$buttons .= ' <a href="'.base_url('legalpanel/read/'.$value['id']).'" class="btn btn-sm btn-default"><i class="fa fa-eye"></i></a>';
			}
			if(in_array('deleteLegalPanel', $this->permission)) { 
    			$buttons .= ' <button type="button" class="btn btn-sm btn-default" onclick="removeFunc('.$value['id'].')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }

			if (isset($value['store_id']) && $value['store_id'] > 0)
			{
				$store_data = $this->model_stores->getStoresById($value['store_id']);

				if (!empty($store_data))
				{
					$store_id = $store_data['name'];
				}
			}

			$result['data'][$key] = array(
				$value['id'],
				$store_id,
				$value['notification_type'] ? LegalPanelNotificationType::getName($value['notification_type']) : '',
                $value['notification_type'] == LegalPanelNotificationType::ORDER ? $value['orders_id'] : '',
				$value['notification_id'],
			    "R$ ".str_replace ( ".", ",", $value['balance_debit'] ),
                "R$ ".str_replace ( ".", ",", $value['balance_paid'] ),
				$value['status'],
				date('d/m/Y', strtotime($value['creation_date'])),
				date('d/m/Y', strtotime($value['update_date'])),
				$buttons
			);
		}

		echo json_encode($result);
    }

    public function create()
	{

		if(!in_array('createLegalPanel', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($this->postClean('notification_type',TRUE) == LegalPanelNotificationType::ORDER){
		    $this->form_validation->set_rules('orders_id', $this->lang->line('application_purchase_id'), 'trim|required');
        }

        if ($this->postClean('notification_type',TRUE) == LegalPanelNotificationType::OTHERS){
            $this->form_validation->set_rules('notification_title', $this->lang->line('application_legal_panel_notification_title'), 'trim|required');
            $this->form_validation->set_rules('store_id', $this->lang->line('application_store_id'), 'trim|required');
        }

		$this->form_validation->set_rules('balance_debit', $this->lang->line('application_balance_debit'), 'trim|required');
		$this->form_validation->set_rules('status', $this->lang->line('application_status'), 'trim|required');

		if ($this->form_validation->run() == TRUE) {

			$data = array(
				'orders_id' => $this->postClean('orders_id',TRUE),
				'store_id' => $this->postClean('store_id',TRUE),
				'notification_type' => $this->postClean('notification_type',TRUE),
				'notification_title' => $this->postClean('notification_title',TRUE),
				'notification_id' => $this->postClean('notification_id',TRUE),
				'status' => $this->postClean('status',TRUE),
				'description' => $this->postClean('description',TRUE),
				'balance_paid' => $this->postClean('balance_paid',TRUE),
				'balance_debit' => $this->postClean('balance_debit',TRUE),
				'attachment' => $this->postClean('attachment',TRUE),
				'creation_date' => date_create()->format('Y-m-d H:i:s'),
				'update_date' => date_create()->format('Y-m-d H:i:s'),
				'accountable_opening' => $_SESSION['username'],
				'accountable_update' => NULL,			
			);
			$response = $this->model_legal_panel->create($data);
			redirect('legalpanel/', 'refresh');

		}else{

			$legal_panel = array();
			$legal_panel['id'] = "";
			$legal_panel['orders_id'] = "";
			$legal_panel['notification_type'] = $this->postClean('notification_type',TRUE);
			$legal_panel['notification_title'] = "";
			$legal_panel['notification_id'] = "";
			$legal_panel['status'] = "";
			$legal_panel['description'] = "";
			$legal_panel['balance_paid'] = "";
			$legal_panel['balance_debit'] = "";
			$legal_panel['attachment'] = "";
			$legal_panel['creation_date'] = "";
			$legal_panel['update_date'] = "";
			$legal_panel['accountable_opening'] = "";
			$legal_panel['accountable_update'] = "";
			$legal_panel['attachment'] = date('YmdHis').rand(1,1000000);
			
			$this->data['legal_panel'] = $legal_panel;
			$this->data['read_only'] = '';

			$this->render_template('legalpanel/create', $this->data);

		}

	}

	public function edit($id = null)
	{
		if(!in_array('updateLegalPanel', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->form_validation->set_rules('orders_id', $this->lang->line('application_purchase_id'), 'trim|required');
		$this->form_validation->set_rules('balance_debit', $this->lang->line('application_balance_debit'), 'trim|required');

		if ($this->form_validation->run() == TRUE) {
			
			$data = array(
				'orders_id' => $this->postClean('orders_id',TRUE),
				'notification_id' => $this->postClean('notification_id',TRUE),
				'status' => $this->postClean('status',TRUE),
				'description' => $this->postClean('description',TRUE),
				'balance_paid' => $this->postClean('balance_paid',TRUE),
				'balance_debit' => $this->postClean('balance_debit',TRUE),
				'attachment' => $this->postClean('attachment',TRUE),
				'update_date' => date_create()->format('Y-m-d H:i:s'),
				'accountable_update' => $_SESSION['username'],
			);

			$response = $this->model_legal_panel->update($data, $id);
			redirect('legalpanel/', 'refresh');

		}else{
			
			$this->data['read_only'] = '';
			$legal_panel = $this->model_legal_panel->getDataById($id);
			$this->data['legal_panel'] = $legal_panel;
			if($legal_panel['status'] == "Chamado Fechado"){
				$this->data['read_only'] = 'readonly="true"';
			}
			
					
			$this->render_template('legalpanel/create', $this->data);	
		}

	}

	public function read($id = null)
	{
	
		if(!in_array('viewLegalPanel', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$legal_panel = $this->model_legal_panel->getDataById($id);
		$this->data['legal_panel'] = $legal_panel;
		$this->data['read_only'] = 'readonly="true"';
					
		$this->render_template('legalpanel/create', $this->data);	
		
	}

	public function remove(){
		$id = $this->postClean('id');
		$retorno['success'] = false;
		if($id){
			$data['status'] = 'Chamado Fechado';
			$response = $this->model_legal_panel->update($data, $id);
			$retorno['success'] = $response;
			if($response ){
				$retorno['messages'] = "Excluído com sucesso.";
			}else{
				$retorno['messages'] = "Erro ao exlcuir a notificação.";
			}
		}

		echo json_encode($retorno);
		
	}
    
	public function fileUpload()
	{
	    
	    if (!empty($_FILES)) {
	        
	        $exp_extens = explode( ".", $_FILES['document_upload']['name'][0]) ;
	        $extensao = $exp_extens[count($exp_extens)-1];
			$lote = microtime(true)*10000;
			$name = $exp_extens['0'];  
			$token = $this->postClean('uploadToken');
	        

	        $tempFile = $_FILES['document_upload']['tmp_name'][0];	

			$serverpath = $_SERVER['SCRIPT_FILENAME'];
			$pos = strpos($serverpath,'index.php');
			$serverpath = substr($serverpath,0,$pos);
			$caminhoMapeado = $serverpath . 'assets/docs/legalpanel/';
			$caminhoMapeado = str_replace('\\','/',$caminhoMapeado);	
	        $targetPath = $caminhoMapeado.$token.'/';
	        $targetFile =  $targetPath . $name . '-' . $lote . '.' . $extensao;

			if (!file_exists($caminhoMapeado)) {
				@mkdir($caminhoMapeado,0775);
			}

			if (!file_exists($targetPath)) {
				@mkdir($targetPath,0775);
			}
	        
	        move_uploaded_file($tempFile,$targetFile);
	        
	        $retorno['ret'] = "sucesso";
	        $retorno['extensao'] = $extensao;
	        
	        echo json_encode($retorno);
	    }
	    
	    
	}

	public function getFiles()
    {
        if (!in_array('createLegalPanel', $this->permission) && !in_array('updateLegalPanel', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $token = $this->postClean('token');

        $numft = 0;
        $ln1 = array();
        $ln2 = array();
        $fotos = array();
		$targetPath = str_replace('\\','/',FCPATH . 'assets/docs/legalpanel/' . $token);

		if(is_dir($targetPath)){
					
			$fotos = scandir($targetPath);
			foreach ($fotos as $foto) {
				if (($foto != ".") && ($foto != "..") && ($foto != "")) {
					$exp_extens = explode( ".", $foto) ;
					$extensao = $exp_extens[count($exp_extens)-1];
	
					if($extensao == 'xls'){
						$extensao = 'other';
					}else{
						$extensao = 'pdf';
					}
	
					array_push($ln1, [base_url("assets/docs/legalpanel/".$token."/" . $foto )]);
					array_push($ln2, (object) array( 'key' => $token."/" . $foto, 
						'downloadUrl' => base_url("assets/docs/legalpanel/".$token."/" . $foto),
						'type' => $extensao, 
						'caption' => $foto )
					);
				}
			}

		}

        ob_clean();
        echo json_encode(array('success' => true, 'ln1' => $ln1, 'ln2' => $ln2));
    }

	public function deleteFile()
	{
		$file = $this->postClean('key');
		if (strpos("..".$file,"http")>0) {
		} else {
			$serverpath = $_SERVER['SCRIPT_FILENAME'];
			$pos = strpos($serverpath,'index.php');
			$serverpath = substr($serverpath,0,$pos);
			$caminhoMapeado = $serverpath . 'assets/docs/legalpanel/';	

			unlink($caminhoMapeado.$file);
		}	  			  
	  
		echo json_encode( [  ] );
	}


    
    
}
