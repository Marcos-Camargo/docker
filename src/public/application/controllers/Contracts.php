<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_contracts $model_contracts
 * @property Model_attributes $model_attributes
 * @property Model_stores $model_stores
 * @property Model_users $model_users
 * @property Model_contract_signatures $model_contract_signatures
 */
class Contracts extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_contracts');

		$this->load->model('model_contracts');
		$this->load->model('model_attributes');
		$this->load->model('model_stores');
		$this->load->model('model_users');
		$this->load->model('model_contract_signatures');
	}


	public function index()
	{
		if (!in_array('viewContracts', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$attribs = $this->model_attributes->getAttributeValuesAndIdByName('contract_type');
		$stores = $this->model_stores->getActiveStore();

		$this->data['stores'] = $stores;
		$this->data['attribs'] = $attribs;
		$this->render_template('contracts/index', $this->data);
	}
	
	public function fetchContracts()
	{

		$result = array();
		$postdata = $this->postClean(NULL,TRUE);
		$draw = $postdata['draw'];
		$busca = $postdata['search'];

		$procura = '1 = 1';

			if ($busca['value']) {
				if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
					$procura .= " AND ( contract_title like '%" . $busca['value'] . "%' OR user_name like '%" . $busca['value'] . "%') ";
				}
			}
            if (trim($postdata['contractTitle'])) {
                $procura .= " AND contract_title like '%" . $postdata['contractTitle'] . "%'";
            }
            if (trim($postdata['documentType'])) {
                $procura .= " AND document_type = " . $postdata['documentType'] . "";
            }
			if (!empty($postdata['block']) || $postdata['block'] === "0") {
                $procura .= " AND block = " . $postdata['block'];
            }
            if (!empty($postdata['status']) || $postdata['status'] === "0") {
                $procura .= " AND active = " . $postdata['status'];
            }

        

		$data = $this->model_contracts->getAll($procura);
		$total = $this->model_contracts->getCountContracts();
		$filtered = count($data);
		$total_rec = $total['total'];

		foreach ($data as $key => $value) {

			$buttons = '';

			$value['document_type'] = $this->model_attributes->getAttributeValueById($value['document_type']);
			//refatorar if else
			if ($value['active']) {
				$value['active'] = $this->lang->line('application_yes');
			} else {
				$value['active'] = $this->lang->line('application_no');
			}
			if ($value['block']) {
				$value['block'] = $this->lang->line('application_yes');
			} else {
				$value['block'] = $this->lang->line('application_no');
			}
			if ($value['validity']) {
				$valid = date('d/m/Y', strtotime($value['validity']));
				$value['block'] = $this->lang->line('application_yes');
			} else {
				$valid = $this->lang->line('application_no_expiration_date');
			}

			if (in_array('updateContracts', $this->permission)) {
				$buttons .= ' <a href="' . base_url('contracts/edit/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>';
			}

			$result[$key] = array(
				$value['id'],
				$value['contract_title'],
				$value['company_id'],
				$value['user_name'],
				date('d/m/Y', strtotime($value['creation_date'])),
				$value['document_type'],
				$value['active'],
				$value['block'],
				$valid,
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

	public function create()
	{

		if (!in_array('createContracts', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$this->form_validation->set_rules('contract_title', $this->lang->line('application_purchase_id'), 'trim|required');


		if ($this->form_validation->run() == TRUE) {

			$participatingStores = $this->postClean('participating_stores', TRUE);
			$data = array(
				'store_id' => $_SESSION['userstore'],
				'company_id' => $_SESSION['usercomp'],
				'user_id' => $_SESSION['id'],
				'user_name' => $_SESSION['username'],
				'creation_date' => date_create()->format('Y-m-d H:i:s'),
				'document_type' => $this->postClean('document_type', TRUE),
				'active' => empty($this->postClean('active', TRUE))  ? 0 : 1,
				'validity' => empty($this->postClean('validity', TRUE)) ? null : $this->postClean('validity', TRUE),
				'block' => empty($this->postClean('block', TRUE)) ? 0 : 1,
				'contract_title' => $this->postClean('contract_title', TRUE),
				'participating_stores' => json_encode($participatingStores),
				'attachment' => $this->postClean('attachment', TRUE),
			);

			$contractId = $this->model_contracts->create($data);

			$log = array(
				'module' => 'contracts',
				'type' => 'insert',
				'body' => json_encode($data),
				'response' => $contractId,
				'user' => $_SESSION['username'],
				'creation_date' => date_create()->format('Y-m-d H:i:s'),

			);
			$this->model_contracts->addLog($log);
			
			foreach ($participatingStores as $store) {
				
				// VERIFICA SE O CONTRATO ASSINADO É O ANTECIPAÇÃO DE REPASSE E ATUALIZA A LOJA DE ACORDO COM A ASSINATURA::INICIO	
						
				$anticipationTransferContract = $this->model_contracts->checkNewContractIsAnticipationTransfer($contractId);										
				if($anticipationTransferContract){																	
					$assinado = $this->model_contracts->checkContractSignatureStore($store, $anticipationTransferContract->document_type);						
					if(!$assinado){
						$contractsSign = array(
							'store_id' => $store,
							'contract_id' => $contractId,
							'active' => empty($this->postClean('active', TRUE))  ? 0 : 1,
						);
						$contractSignature = $this->model_contract_signatures->create($contractsSign);
					}
				}else{				
					$contractsSign = array(
						'store_id' => $store,
						'contract_id' => $contractId,
						'active' => empty($this->postClean('active', TRUE))  ? 0 : 1,
					);
					$contractSignature = $this->model_contract_signatures->create($contractsSign);
				}				

				if(isset($contractsSign)){
					$log = array(
						'module' => 'contract_signature',
						'type' => 'insert',
						'body' => json_encode($contractsSign),
						'response' => $contractSignature,
						'user' => $_SESSION['username'],
						'creation_date' => date_create()->format('Y-m-d H:i:s'),
					);
					$this->model_contracts->addLog($log);
				}
			}

			redirect('contracts/', 'refresh');
		} else {

			$valids = array();
			$attribs = $this->model_attributes->getAttributeValuesAndIdByName('contract_type');
			$session = $_SESSION;
			$stores = $this->model_stores->getActiveStore();
			$contract = array();
			$contract['id'] = "";
			$contract['store_id'] = "";
			$contract['company_id'] = "";
			$contract['user_id'] = "";
			$contract['user_name'] = "";
			$contract['creation_date'] = "";
			$contract['document_type'] = "";
			$contract['active'] = false;
			$contract['validity'] = "";
			$contract['block'] = false;
			$contract['contract_title'] = "";
			$contract['participating_stores'] = array();
			$contract['attachment'] = date('YmdHis') . rand(1, 1000000);


			$this->data['stores'] = $stores;
			$this->data['attribs'] = $attribs;
			$this->data['contract'] = $contract;
			$this->data['read_only'] = '';

			$this->render_template('contracts/create', $this->data);
		}
	}

	public function edit($id = null)
	{

		if (!in_array('updateContracts', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$this->form_validation->set_rules('contract_title', $this->lang->line('application_purchase_id'), 'trim|required');

		if ($this->form_validation->run() == TRUE) {

			$contract = $this->model_contracts->getDataById($id);
			$participatingStoresSaved = json_decode($contract['participating_stores']);

			if ($this->postClean('participating_stores', TRUE)) {
				$participatingStoresSaved = array_merge($participatingStoresSaved, $this->postClean('participating_stores', TRUE));
			}


			$data = array(
				'participating_stores' => json_encode($participatingStoresSaved),
				'active' => empty($this->postClean('active', TRUE))  ? 0 : 1,
			);
			$response = $this->model_contracts->update($data, $id);

			$log = array(
				'module' => 'contracts',
				'type' => 'update',
				'body' => json_encode($data),
				'response' => $response,
				'user' => $_SESSION['username'],
				'creation_date' => date_create()->format('Y-m-d H:i:s'),

			);
			$this->model_contracts->addLog($log);

			$participatingStores = $this->postClean('participating_stores', TRUE);
			if ($participatingStores) {
				foreach ($participatingStores as $store) {

					$contractsSign = array(
						'store_id' => $store,
						'contract_id' => $id,
						'active' => empty($this->postClean('active', TRUE))  ? 0 : 1,
					);

					$contractSignature = $this->model_contract_signatures->create($contractsSign);

					$log = array(
						'module' => 'contract_signature',
						'type' => 'insert',
						'body' => json_encode($contractsSign),
						'response' => $contractSignature,
						'user' => $_SESSION['username'],
						'creation_date' => date_create()->format('Y-m-d H:i:s'),

					);
					$this->model_contracts->addLog($log);
				}
			}


			if ($this->postClean('active', TRUE)) {
				$contractSignature = $this->model_contract_signatures->activeContracts($id);
			}

			redirect('contracts', 'refresh');
		} else {

			$attribs = $this->model_attributes->getAttributeValuesAndIdByName('contract_type');
			$session = $_SESSION;
			$stores = $this->model_stores->getActiveStore();
			$contract = $this->model_contracts->getDataById($id);
			$contract['participating_stores'] = json_decode($contract['participating_stores']);
			$this->data['contract'] = $contract;
			$this->data['stores'] = $stores;
			$this->data['attribs'] = $attribs;
			$this->data['read_only'] = 'readonly="true"';

			$this->render_template('contracts/create', $this->data);
		}
	}


	public function fileUpload(): CI_Output
    {
        if (empty($_FILES)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ret'   => "fail",
                    'error' => 'Nenhum arquivo enviado'
                )));
        }

        $token      = $this->postClean('uploadToken');
        $dir_to_csv = "assets/files/contracts/$token/";

        // Se não existir o diretório, será criado.
        checkIfDirExist($dir_to_csv);

        $config = array(
            'upload_path'   => $dir_to_csv,
            'allowed_types' => 'pdf'
        );
        $this->load->library('upload', $config);

        $files = $_FILES['document_upload'];
        $upload_error = array();

        foreach ($files['name'] as $key => $image) {
            $_FILES['document_upload[]']['name']     = $files['name'][$key];
            $_FILES['document_upload[]']['type']     = $files['type'][$key];
            $_FILES['document_upload[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['document_upload[]']['error']    = $files['error'][$key];
            $_FILES['document_upload[]']['size']     = $files['size'][$key];

            $config['file_name'] = pathinfo($files['name'][$key])['filename'] . '-' . uniqid();

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('document_upload[]')) {
                $upload_error[] = strip_tags($this->upload->display_errors());
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ret'       => "sucesso",
                'extensao'  => 'pdf',
                'error'     => $upload_error ?: null
            )));
	}

	public function getFiles()
	{
        ob_start();
		$token = $this->postClean('token');

		$numft = 0;
		$ln1 = array();
		$ln2 = array();
		$fotos = array();
		$targetPath = str_replace('\\', '/', FCPATH . 'assets/files/contracts/' . $token);

		if (is_dir($targetPath)) {

			$fotos = scandir($targetPath);
			foreach ($fotos as $foto) {
				if (($foto != ".") && ($foto != "..") && ($foto != "")) {
					$exp_extens = explode(".", $foto);
					$extensao = $exp_extens[count($exp_extens) - 1];

					array_push($ln1, [base_url("assets/files/contracts/" . $token . "/" . $foto)]);
					array_push(
						$ln2,
						(object) array(
							'key' => $token . "/" . $foto,
							'downloadUrl' => base_url("assets/files/contracts/" . $token . "/" . $foto),
							'type' => $extensao,
							'caption' => $foto
						)
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
		if (strpos(".." . $file, "http") > 0) {
		} else {
			$serverpath = $_SERVER['SCRIPT_FILENAME'];
			$pos = strpos($serverpath, 'index.php');
			$serverpath = substr($serverpath, 0, $pos);
			$caminhoMapeado = $serverpath . 'assets/files/contracts/';

			unlink($caminhoMapeado . $file);
		}

		echo json_encode([]);
	}
}
