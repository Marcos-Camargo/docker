<?php

defined('BASEPATH') or exit('No direct script access allowed');

class ContractSignatures extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_contract_signatures');

		$this->load->model('model_contracts');
		$this->load->model('model_attributes');
		$this->load->model('model_stores');
		$this->load->model('model_users');
		$this->load->model('model_contract_signatures');
	}


	public function index()
	{
		if (!in_array('viewContractSignatures', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$attribs = $this->model_attributes->getAttributeValuesAndIdByName('contract_type');
		$stores = $this->model_stores->getActiveStore();

		$this->data['stores'] = $stores;
		$this->data['attribs'] = $attribs;
		$this->render_template('contractSignatures/index', $this->data);
	}

	public function fetchContractSignatures()
	{

		$result = array();
		$postdata = $this->postClean(NULL,TRUE);
		$draw = $postdata['draw'];
		$busca = $postdata['search'];
		$stores = $this->model_stores->getStoresId();
		$data = [];
		if($stores){
		$procura = '1 = 1';

			if ($busca['value']) {
				if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
					$procura .= " AND ( contracts.contract_title like '%" . $busca['value'] . "%' OR stores.name like '%" . $busca['value'] . "%' OR contract_signatures.email like '%" . $busca['value'] . "%') ";
				}
			}
            if (trim($postdata['contractTitle'])) {
                $procura .= " AND contracts.contract_title like '%" . $postdata['contractTitle'] . "%'";
            }
            if (trim($postdata['documentType'])) {
                $procura .= " AND contracts.document_type = " . $postdata['documentType'] . "";
            }
			if (isset($postdata['store'])) {
				$postdata['store'] = array_map('intval', $postdata['store']);
                $procura .= " AND contract_signatures.store_id in (" . implode(",",$postdata['store']) . ")";
            }
			if ($postdata['sign'] === "0") {
                $procura .= " AND contract_signatures.signature_date is null";
            }
			if ($postdata['sign'] == 1) {
                $procura .= " AND contract_signatures.signature_date is not null";
            }
            if (!empty($postdata['status']) || $postdata['status'] === "0") {
                $procura .= " AND contract_signatures.active = " . $postdata['status'];
            }
			if(!isset($postdata['store'])){
				$procura .= " AND contract_signatures.store_id in ( ". $stores." ) ";
			}
        

		$data = $this->model_contract_signatures->getAll($procura);
		}
		$filtered = count($data);
		$total = $this->model_contract_signatures->getCountContracts($stores);
		$total_rec = $total['total'];


		foreach ($data as $key => $value) {

			$buttons = '';
			$value['document_type'] = $this->model_attributes->getAttributeValueById($value['document_type']);
			if ($value['signature_date']) {
				$signatureDate = date('d/m/Y', strtotime($value['signature_date']));
			} else {
				$signatureDate = $this->lang->line('application_not_signed');
			}
			if ($value['active']) {
				$active = $this->lang->line('application_yes');
			} else {
				$active = $this->lang->line('application_no');
			}

			if (in_array('updateContractSignatures', $this->permission)) {
				if($value['active'] && !$value['signature_date'] && $_SESSION['legal_administrator']){
					$buttons .= ' <a href="' . base_url('contractSignatures/edit/' . $value['id']) . '" class="btn btn-default" data-toggle="popover" title="' . $this->lang->line('application_sign_contract') . '"><i class="fas fa-marker"></i></a>';
				}
			}
			if (in_array('deleteContractSignatures', $this->permission)) {
				if($value['active']){
					$buttons .= '<span  data-toggle="popover" title="' . $this->lang->line('application_inactivate_contract') . '"><button type="button" class="btn btn-default" onclick="inactiveFunc(' . $value['id'] . ')" data-toggle="modal" data-target="#removeModal"><i class="fas fa-times"></i></button></span>';
				}	
			}
			if (in_array('viewContractSignatures', $this->permission)) {
				if($value['signature_date'] || !$value['active']){
					$buttons .= ' <a href="' . base_url('contractSignatures/edit/' . $value['id']) . '" class="btn btn-default" data-toggle="popover" title="' . $this->lang->line('application_sign_contract') . '"><i class="fa fa-eye"></i></a>';
				}
			}

			$result[$key] = array(
				$value['id'],
				$value['contract_title'],
				$value['document_type'],
				$value['storeName'],
				$this->formatDoc($value['storeCnpj']),
				$value['email'],
				$this->formatDoc($value['cpf']),
				$signatureDate,
				$active,
				$buttons
			);
		}

		$output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
		echo json_encode($output);
	}

	public function edit($id = null)
	{

		if (!in_array('viewContractSignatures', $this->permission) || 
			(in_array('updateContractSignatures', $this->permission) && !in_array('viewContractSignatures', $this->permission)) ) { 
			redirect('dashboard', 'refresh');
		}

		$this->form_validation->set_rules('sign', 'sign', 'trim|required');

		if ($this->form_validation->run() == TRUE) {
			$user = $this->model_users->getUserById($_SESSION['id']);
			$data = array(
				'legal_administrator_id' => $_SESSION['id'],
				'signature_date' => date_create()->format('Y-m-d H:i:s'),
				'email' => $user['email'],
				'cpf' => $user['cpf']
			);
			$response = $this->model_contract_signatures->update($data, $id);

			// VERIFICA SE O CONTRATO ASSINADO É O ANTECIPAÇÃO DE REPASSE E ATUALIZA A LOJA DE ACORDO COM A ASSINATURA::INICIO						
			$anticipationTransfer = $this->model_contract_signatures->checkContractTypeIsAnticipationTransfer($id);						
			if($anticipationTransfer){											
				$store_id = $anticipationTransfer->loja_id;
				$this->db->where('id', $store_id);
				$this->db->update('stores', ['flag_antecipacao_repasse' => "S"]);				
			}
			// VERIFICA SE O CONTRATO ASSINADO É O ANTECIPAÇÃO DE REPASSE E ATUALIZA A LOJA DE ACORDO COM A ASSINATURA::FIM

			$userSession = $_SESSION;
			$userSession['block'] = false;
			$userSession['contract_sign'] = false;
			$this->session->set_userdata($userSession);
			$log = array(
				'module' => 'contract_signature',
				'type' => 'sign',
				'body' => json_encode($data),
				'response' => $response,
				'user' => $_SESSION['username'],
				'creation_date' => date_create()->format('Y-m-d H:i:s'),

			);
			$this->model_contracts->addLog($log);

			redirect('contractSignatures', 'refresh');
		} else {
			$user = $this->model_users->getUserById($_SESSION['id']);

			if(!$user['store_id']){
				$stores = $this->model_stores->getStoresId();							
				$stores = array_map('intval', explode(',', $stores));	
			}else{
				$stores = array($user['store_id']);
			}

			$contract = $this->model_contract_signatures->getDataById($id);
			$contract['sign'] = null;
			if($contract['signature_date']){
				$contract['sign'] = 1;
			}
			if(!in_array( $contract['store_id'] , $stores )){
				$this->session->set_flashdata(
					'error',
					sprintf($this->lang->line('application_contracts_restricted_access'))
				);
				redirect('contractSignatures/index', 'refresh');
			}
			$contract['document_type'] = $this->model_attributes->getAttributeValueById($contract['document_type']);
			$this->data['contract'] = $contract;
			$this->data['read_only'] = 'readonly="true"';
			$this->render_template('contractSignatures/edit', $this->data);
		}
	}


	public function inactiveContract()
	{
		$id = $this->postClean('id');
		$response = $this->model_contract_signatures->inactiveContracts($id);
		$retorno['success'] = $response;
		if ($response) {
			$retorno['messages'] = $this->lang->line('application_contract_successfully_inactivated');
		} else {
			$retorno['messages'] = $this->lang->line('application_contract_error_inactivated');
		}

		// VERIFICA SE O CONTRATO ASSINADO É O ANTECIPAÇÃO DE REPASSE E ATUALIZA A LOJA DE ACORDO COM A ASSINATURA::INICIO						
		$anticipationTransfer = $this->model_contract_signatures->checkContractTypeIsAnticipationTransfer($id);						
		if($anticipationTransfer){										
			$store_id = $anticipationTransfer->loja_id;
			$this->db->where('id', $store_id);
			$this->db->update('stores', ['flag_antecipacao_repasse' => "N"]);
		}
		// VERIFICA SE O CONTRATO ASSINADO É O ANTECIPAÇÃO DE REPASSE E ATUALIZA A LOJA DE ACORDO COM A ASSINATURA::FIM

		$log = array(
			'module' => 'contract_signature',
			'type' => 'disable',
			'body' => json_encode($id),
			'response' => $response,
			'user' => $_SESSION['username'],
			'creation_date' => date_create()->format('Y-m-d H:i:s'),

		);
		$this->model_contracts->addLog($log);

		echo json_encode($retorno);
	}

	public function read()
	{
		$this->render_template('contractSignatures/read', $this->data);
	}
}
