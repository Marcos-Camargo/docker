<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Companhias/Empresas
 
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Company extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_company');

        $this->load->model('model_company');
        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_products');
        $this->load->model('model_plans');
    }

    /*
     * It only redirects to the manage order page
     */
    public function index()
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_companies');
        $this->render_template('company/index', $this->data);
    }

    /*
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchCompaniesData()
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $busca = $postdata['search'];
        $length = $postdata['length'];

        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value']) >= 2) {  // Garantir no minimo 3 letras
                $procura = " AND ( c.name like '%" . $busca['value'] . "%' OR j.name like '%" . $busca['value'] . "%'  OR c.id like '%" . $busca['value'] . "%') ";
            }
        } else {
            if (trim($postdata['nome'])) {
                $procura .= " AND c.name like '%" . $postdata['nome'] . "%'";
            }
            if (trim($postdata['agencia'])) {
                $procura .= " AND j.name like '%" . $postdata['agencia'] . "%'";
            }
            if (trim($postdata['associado'])) {
                $procura .= " AND c.associate_type = " . $postdata['associado'];
            }
            if (trim($postdata['razaosocial'])) {
                $procura .= " AND c.raz_social like '%" . $postdata['razaosocial'] . "%'";
            }
        }

		$sOrder = '';
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "ASC";
            } else {
                $direcao = "DESC";
            }
            $campos = array('', 'c.id', 'c.name', 'c.address', 'c.phone_1', 'j.name', 'c.date_create', '');

            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $data = $this->model_company->getCompaniesDataView($ini, $procura, $sOrder, $length);
        $filtered = $this->model_company->getCompaniesDataCount($procura);
        if ($procura == '') {
            $total_rec = $filtered;
        } else {
            $total_rec = $this->model_company->getCompaniesDataCount();
        }
        $result = array();

        foreach ($data as $key => $value) {
            // button
            $buttons = '';
            // if (in_array('viewCompany', $this->permission)) {
            //     $buttons .= '<a target="__blank" href="' . base_url('company/printDiv/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-print"></i></a>';
            // }
            if (in_array('updateCompany', $this->permission) || in_array('viewCompany', $this->permission)) {
                $buttons .= ' <a href="' . base_url('company/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }
            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a target="__blank" href="' . base_url('company/viewstores/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-home"></i></a>';
            }
            $value['logo'] = $value['logo'] != '' ? $value['logo'] : 'assets/images/system/sem_foto.png';
            $dont_use_url=(!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission));
            $result[$key] = array(
                '<img src="' . base_url() . $value['logo'] . '" width="60" height="20">',
                $dont_use_url?$value['id']:' <a href="' . base_url('company/update/' . $value['id']) . '">'.$value['id'].'</a>',
                $value['name'],
                $value['address'] . ", " . $value['addr_num'] . ", " . $value['addr_compl'] . ", " . $value['addr_neigh'],
                $value['phone_1'] . "/" . $value['phone_2'],
                $value['pai'],
                date('d/m/Y', strtotime($value['date_create'])),
                $buttons
            );
        } // /foreach

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $total_rec,
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_start();
        ob_clean();
        echo json_encode($output);
    }

    public function inactive($id)
    {
        if(!in_array('updateCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['count_stores'] = count($this->model_stores->getCompanyStores($id));
        $this->data['count_users'] = count($this->model_users->getUsersByCompany($id));
        $this->data['count_products'] = count($this->model_products->getProductsByCompany($id));
        
        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('company/confirmInactive', $this->data);
    }
    public function active($id)
    {
        if (!in_array('updateCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['count_stores'] = count($this->model_stores->getCompanyStores($id));
        $this->data['count_users'] = count($this->model_users->getUsersByCompany($id));

        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('company/confirmActive', $this->data);
    }
    public function activeConfirmed($id){
        if (!in_array('updateCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->log_data(__CLASS__, __FUNCTION__,'activate_company_with_id', json_encode($id), 'I');
        $this->model_company->active($id);
        redirect('company', 'refresh');
    }
    public function inactiveConfirmed($id)
    {
        if (!in_array('updateCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->log_data(__CLASS__, __FUNCTION__,'inactivate_company_with_id', json_encode($id), 'I');
        $this->model_company->inactive($id);
        redirect('company', 'refresh');
        // $this->render_template('company/confirmInactive', $this->data);
    }

	public function fetchCompaniesDataOLD()
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $result = array('data' => array());

        $data = $this->model_company->getCompanyData();

        foreach ($data as $key => $value) {

            // button
            $buttons = '';

            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a target="__blank" href="' . base_url('company/printDiv/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-print"></i></a>';
            }

            if (in_array('updateCompany', $this->permission)) {
                $buttons .= ' <a href="' . base_url('company/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }

            if (in_array('viewCompany', $this->permission)) {
                $buttons .= '<a target="__blank" href="' . base_url('company/viewstores/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-home"></i></a>';
            }

            $result['data'][$key] = array(
                $value['id'],
                $value['name'],
                $value['address'] . ", " . $value['addr_num'] . ", " . $value['addr_compl'] . ", " . $value['addr_neigh'],
                $value['phone_1'] . "/" . $value['phone_2'],
                $value['pai'],
                date('d/m/Y H:i:s', strtotime($value['date_create'])),
                $buttons
            );
        } // /foreach

        echo json_encode($result);
    }

    /*
     * It only redirects to the manage order page
     */
    public function viewstores($id)
    {
        if (!in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_companies_stores');
        $this->data['store_id'] = $id;
        $this->render_template('company/viewstores', $this->data);
    }

    /*
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchCompaniesStores($id)
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $result = array('data' => array());

        $data = $this->model_stores->getCompanyStores($id);

        foreach ($data as $key => $value) {
            switch ($value['active']) {
                case 1:
                    $store_status = '<span class="label label-success">Ativo</span>';
                    break;
                case 2:
                    $store_status = '<span class="label label-danger">Inativo</span>';
                    break;
                case 3:
                    $store_status = '<span class="label label-warning">Em Negociação</span>';
                    break;
                case 4:
                    $store_status = '<span class="label label-warning">Boleto</span>';
                    break;
                case 5:
                    $store_status = '<span class="label label-danger">Churn</span>';
                    break;
                default:
                    $store_status = null;
                    break;
            }
            $result['data'][$key] = array(
                '<a href="' . base_url('stores/update/' . $value['id']) . '" >' . $value['id'] . '</a>',
                $value['name'],
                $value['raz_social'],
                $value['responsible_name'],
                $value['responsible_email'],
                $value['phone_1'] . ' / ' . $value['phone_2'],
                $store_status
            );
        } // /foreach

        echo json_encode($result);
    }

    /*
     * It redirects to the company page and displays all the company information
     * It also updates the company information into the database if the
     * validation for each input field is successfully valid
     */
    public function update($id)
    {
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $usar_mascara_banco = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;

        $plans = $this->model_plans->getPlans();
        $this->data['plans'] = $plans;

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
		
		    $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
		    if ($this->postClean('pj_pf',TRUE) == "PJ") {
			    $this->form_validation->set_rules('associate_type_pj', $this->lang->line('application_associate_type'), 'trim|required');
			    $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|callback_checkUniqueCNPJ['.$id.']');
			    $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
       		  	$this->form_validation->set_rules('gestor', $this->lang->line('application_gestor'), 'trim|required');
			    if ($this->postClean('associate_type_pj',TRUE) != "0") {  // Matriz não tem charge e dados bancarios
				    $this->form_validation->set_rules('service_charge_value', $this->lang->line('application_charge_amount'), 'trim|required|integer');
				    $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
		            $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
		            $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
		            $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
			    }
                if ($this->postClean('exempted',TRUE) != "1") {
                    $this->form_validation->set_rules('IEST', $this->lang->line('application_iest'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("addr_uf",TRUE) . ']');
                }
                if ($this->postClean('exemptmd',TRUE) != "1") {
                    $this->form_validation->set_rules('IMUN', $this->lang->line('application_imun'), 'trim|required');
                }
		    } else {
			    $this->form_validation->set_rules('associate_type_pf', $this->lang->line('application_associate_type'), 'trim|required');
			    $this->form_validation->set_rules('CPF', $this->lang->line('application_cpf'), 'trim|required|callback_checkCPF|edit_unique[company.CPF.'.$id.']');
      			$this->form_validation->set_rules('RG', $this->lang->line('application_rg'), 'trim|required');
      			$this->form_validation->set_rules('rg_expedition_agency', $this->lang->line('application_enter_rg_expedition_agency'), 'trim|required');
      			$this->form_validation->set_rules('rg_expedition_date', $this->lang->line('application_rg_expedition_date'), 'trim|required');
      			$this->form_validation->set_rules('affiliation', $this->lang->line('application_affiliation'), 'trim|required');
      			$this->form_validation->set_rules('birth_date', $this->lang->line('application_birth_date'), 'trim|required');
	      		$this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
        		$this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
       		  	$this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
        	  	$this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
		    }
		    $this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|required');
		
      //  $this->form_validation->set_rules('vat_charge_value', $this->lang->line('application_vat_charge'), 'trim|integer');
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
        // retidado em 02/03/2020	$this->form_validation->set_rules('message', 'Message', 'trim|required');


        if ($this->form_validation->run() == TRUE) {
            // true case
            $plan_id = $this->postClean('monthly_plan',TRUE);
            if ($plan_id == '0') $plan_id = null;
            if ($this->postClean('pj_pf',TRUE) == "PJ") {
                $associate_type = $this->postClean('associate_type_pj',TRUE);
                $raz_social = $this->strReplaceName($this->postClean('raz_soc',TRUE));
                $CNPJ = $this->postClean('CNPJ',TRUE);
                $IEST = $this->postClean('exempted',TRUE) == "1" ? "0" : $this->postClean('IEST',TRUE);
                $IMUN = $this->postClean('exemptmd',TRUE) == "1" ? "0" :$this->postClean('IMUN',TRUE);
                $gestor = $this->postClean('gestor',TRUE);
                if ($associate_type != "0") {
                    $service_charge_value = $this->postClean('service_charge_value',TRUE);
                    $bank = $this->postClean('bank',TRUE);
                    $agency = $this->postClean('agency',TRUE);
                    $account_type = $this->postClean('account_type',TRUE);
                    $account = $this->postClean('account',TRUE);
                    foreach ($this->data['banks'] as $local_bank) {
                        if ( $usar_mascara_banco == true) {
                            if ($local_bank['name'] == $bank) {
             
                                if(strlen($account) != strlen($local_bank['mask_account'])) {
                                    $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_account') . $local_bank['mask_account']);
                                    redirect('company/update/'.$id, 'refresh');
                                }
                                if( strlen($agency) != strlen($local_bank['mask_agency'])) {
                                    $this->session->set_flashdata('error', $this->lang->line('application_bank_validation_agency') . $local_bank['mask_agency']);
                                    redirect('company/update/'.$id, 'refresh');
                                }
                                continue;
                            }
                         }else{
                            continue;
                         }
                    }
                } else { // Matriz nao tem charge e não tem banco
                    $service_charge_value = null;
                    $bank = NULL;
                    $agency = NULL;
                    $account_type = NULL;
                    $account = NULL;
                }
                $CPF = NULL;
                $RG = NULL;
                $rg_expedition_agency = NULL;
                $rg_expedition_date = NULL;
                $affiliation = NULL;
                $birth_date = NULL;
            } else {
                $associate_type = $this->postClean('associate_type_pf',TRUE);
                $CPF = $this->postClean('CPF',TRUE);
                $RG = $this->postClean('RG',TRUE);
                $rg_expedition_agency = $this->postClean('rg_expedition_agency',TRUE);
                $rg_expedition_date = $this->convertDate($this->postClean('rg_expedition_date',TRUE), "");
                $affiliation = $this->postClean('affiliation',TRUE);
                $birth_date = $this->convertDate($this->postClean('birth_date',TRUE), "");
                $service_charge_value = $this->postClean('service_charge_value',TRUE);
                $raz_social = NULL;
                $CNPJ = NULL;
                $IEST = NULL;
                $IMUN = null;
                $gestor = NULL;
                $bank = $this->postClean('bank',TRUE);
                $agency = $this->postClean('agency',TRUE);
                $account_type = $this->postClean('account_type',TRUE);
                $account = $this->postClean('account',TRUE);
            }
            $data = array(
                'name' => $this->strReplaceName($this->postClean('name',TRUE)),
                'pj_pf' => $this->postClean('pj_pf',TRUE),
                'service_charge_value' => $service_charge_value,
                'vat_charge_value' => $this->postClean('vat_charge_value',TRUE),
                'address' => $this->postClean('address',TRUE),
                'addr_num' => $this->postClean('addr_num',TRUE),
                'addr_compl' => $this->postClean('addr_compl',TRUE),
                'addr_neigh' => $this->postClean('addr_neigh',TRUE),
                'addr_city' => $this->postClean('addr_city',TRUE),
                'addr_uf' => $this->postClean('addr_uf',TRUE),
                'zipcode' => $this->postClean('zipcode',TRUE),
                'phone_1' => $this->postClean('phone_1',TRUE),
                'phone_2' => $this->postClean('phone_2',TRUE),
                'country' => $this->postClean('country',TRUE),
                // retidado em 02/03/2020		'message' => $this->postClean('message',TRUE),
                // retidado em 02/03/2020       'currency' => $this->postClean('currency',TRUE),
                'currency' => 'BRL',
                'raz_social' => $raz_social,
                'CNPJ' => $CNPJ,
                'IEST' => $IEST,
                'IMUN' => $IMUN,
                'gestor' => $gestor,
                'email' => $this->postClean('email',TRUE),
                'associate_type' => $associate_type,
                'CPF' => $CPF,
                'RG' => $RG,
                'rg_expedition_agency' => $rg_expedition_agency,
                'rg_expedition_date' => $rg_expedition_date,
                'affiliation' => $affiliation,
                'birth_date' => $birth_date,
                'bank' => $bank,
                'agency' => $agency,
                'account_type' => $account_type,
                'account' => $account,
                'plan_id' => $plan_id,
                'responsible_finan_name' => $this->postClean('responsible_finan_name',TRUE),
                'responsible_finan_email' => $this->postClean('responsible_finan_email',TRUE),
                'responsible_finan_tell' => $this->postClean('responsible_finan_tell',TRUE),
                'responsible_ti_name' => $this->postClean('responsible_ti_name',TRUE),
                'responsible_ti_email' => $this->postClean('responsible_ti_email',TRUE),
                'responsible_ti_tell' => $this->postClean('responsible_ti_tell',TRUE),
                'responsible_sac_name' => $this->postClean('responsible_sac_name',TRUE),
                'responsible_sac_email' => $this->postClean('responsible_sac_email',TRUE),
                'responsible_sac_tell' => $this->postClean('responsible_sac_tell',TRUE),
            );

            if ($_FILES['company_image']['size'] > 0) {
                $upload_image = $this->upload_image();
                $upload_image = array('logo' => $upload_image);
                $this->model_company->update($upload_image, $id);
            }

            $update = $this->model_company->update($data, $id);
            if ($update == true) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('company/', 'refresh');
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('company/update', 'refresh');
            }
        } else {
            $company = $this->model_company->getCompanyData($id);
            if (!is_null($company['rg_expedition_date'])) {
                $company['rg_expedition_date'] = date('d/m/Y', strtotime($company['rg_expedition_date']));
            }
            if (!is_null($company['birth_date'])) {
                $company['birth_date'] = date('d/m/Y', strtotime($company['birth_date']));
            }
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
            $this->data['stores_multi_cd'] = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;

            $this->data['company_data'] = $company;
            $this->data['currency_symbols'] = $this->currency();

            $this->data['stores_by_company'] = 0;
            if ($company['multi_channel_fulfillment']) {
                $this->data['stores_by_company'] = $this->model_stores->getStoresByCompany($company['id']);
            }
            $this->render_template('company/edit', $this->data);
        }


    }
    public function printDiv($id){
        if (!in_array('updateCompany', $this->permission) && !in_array('viewCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $plans = $this->model_plans->getPlans();
        $this->data['plans'] = $plans;

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
        $company = $this->model_company->getCompanyData($id);
        if (!is_null($company['rg_expedition_date'])) {
            $company['rg_expedition_date'] = date('d/m/Y', strtotime($company['rg_expedition_date']));
        }
        if (!is_null($company['birth_date'])) {
            $company['birth_date'] = date('d/m/Y', strtotime($company['birth_date']));
        }

        $this->data['company_data'] = $company;
        $this->data['currency_symbols'] = $this->currency();
        $this->render_template('company/print', $this->data);
    }

    public function checkUniqueCNPJ($cnpj, $companyId = 0)
    {
        if (!$this->model_company->checkUniqueCNPJ($cnpj, $companyId)) {
            $company = current($this->model_company->getResults());
            $companyName = "{$company['name']} - (ID: {$company['id']})";
            $this->form_validation->set_message('checkUniqueCNPJ', "{field} já cadastrado para a empresa: {$companyName}.");
            return false;
        }
        return true;
    }

    /*
     * It redirects to the company page and displays all the company information
     * It also updates the company information into the database if the
     * validation for each input field is successfully valid
     */
    public function create()
    {
        if (!in_array('createCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $plans = $this->model_plans->getPlans();

        $this->data['plans'] = $plans;

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
		
        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        if ($this->postClean('pj_pf',TRUE) == "PJ") {
            $this->form_validation->set_rules('associate_type_pj', $this->lang->line('application_associate_type'), 'trim|required');
            $this->form_validation->set_rules('CNPJ', $this->lang->line('application_cnpj'), 'trim|required|callback_checkCNPJ|callback_checkUniqueCNPJ');
            $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'trim|required');
            $this->form_validation->set_rules('gestor', $this->lang->line('application_gestor'), 'trim|required');
            if ($this->postClean('associate_type_pj',TRUE) != "0") {
                $this->form_validation->set_rules('service_charge_value', $this->lang->line('application_charge_amount'), 'trim|required|integer');
                $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
                $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
                $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
                $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
            }
            
            if ($this->postClean('exempted',TRUE) != "1") {
                $this->form_validation->set_rules('IEST', $this->lang->line('application_iest'), 'trim|required|callback_checkInscricaoEstadual[' . $this->postClean("addr_uf",TRUE) . ']');
            }
            if ($this->postClean('exemptmd',TRUE) != "1") {
                $this->form_validation->set_rules('IMUN', $this->lang->line('application_imun'), 'trim|required');
            }
        } else {
            $this->form_validation->set_rules('associate_type_pf', $this->lang->line('application_associate_type'), 'trim|required');
            $this->form_validation->set_rules('CPF', $this->lang->line('application_cpf'), 'trim|required|callback_checkCPF|is_unique[company.CPF]');
            $this->form_validation->set_rules('RG', $this->lang->line('application_rg'), 'trim|required');
            $this->form_validation->set_rules('rg_expedition_agency', $this->lang->line('application_enter_rg_expedition_agency'), 'trim|required');
            $this->form_validation->set_rules('rg_expedition_date', $this->lang->line('application_rg_expedition_date'), 'trim|required');
            $this->form_validation->set_rules('affiliation', $this->lang->line('application_affiliation'), 'trim|required');
            $this->form_validation->set_rules('birth_date', $this->lang->line('application_birth_date'), 'trim|required');
            $this->form_validation->set_rules('bank', $this->lang->line('application_bank'), 'trim|required');
            $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), 'trim|required');
            $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), 'trim|required');
            $this->form_validation->set_rules('account', $this->lang->line('application_account'), 'trim|required');
        }
        $this->form_validation->set_rules('email', $this->lang->line('application_email'), 'trim|required');

        $this->form_validation->set_rules('vat_charge_value', $this->lang->line('application_vat_charge'), 'trim|integer');
        $this->form_validation->set_rules('address', $this->lang->line('application_address'), 'trim|required');
        $this->form_validation->set_rules('addr_num', $this->lang->line('application_number'), 'trim|required');
        $this->form_validation->set_rules('addr_compl', $this->lang->line('application_complement'), 'trim');
        $this->form_validation->set_rules('addr_neigh', $this->lang->line('application_neighb'), 'trim|required');
        $this->form_validation->set_rules('addr_city', $this->lang->line('application_city'), 'trim|required');
        $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), 'trim|required');
        $this->form_validation->set_rules('country', $this->lang->line('application_country'), 'trim|required');
        $this->form_validation->set_rules('zipcode', $this->lang->line('application_zip_code'), 'trim|required');
        // retidado em 02/03/2020	$this->form_validation->set_rules('message', 'Message', 'trim|required');

        if ($this->form_validation->run() == TRUE) {
            $plan_id = $this->postClean('monthly_plan',TRUE);
            if ($plan_id == '0') $plan_id = null;
            if ($this->postClean('pj_pf',TRUE) == "PJ") {
                $associate_type = $this->postClean('associate_type_pj',TRUE);
                $raz_social = $this->strReplaceName($this->postClean('raz_soc',TRUE));
                $CNPJ = $this->postClean('CNPJ',TRUE);
                $IEST = $this->postClean('exempted',TRUE) == "1" ? "0" : $this->postClean('IEST',TRUE);
                $IMUN = $this->postClean('exemptmd',TRUE) == "1" ? "0" : $this->postClean('IMUN',TRUE);
                $gestor = $this->postClean('gestor',TRUE);
                if ($associate_type != "0") {
                    $service_charge_value = $this->postClean('service_charge_value',TRUE);
                    $bank = $this->postClean('bank',TRUE);
                    $agency = $this->postClean('agency',TRUE);
                    $account_type = $this->postClean('account_type',TRUE);
                    $account = $this->postClean('account',TRUE);
                } else { // Matriz
                    $service_charge_value = NULL;
                    $bank = NULL;
                    $agency = NULL;
                    $account_type = NULL;
                    $account = NULL;
                }
                $CPF = NULL;
                $RG = NULL;
                $rg_expedition_agency = NULL;
                $rg_expedition_date = NULL;
                $affiliation = NULL;
                $birth_date = NULL;
            } else {
                $associate_type = $this->postClean('associate_type_pf',TRUE);
                $CPF = $this->postClean('CPF',TRUE);
                $RG = $this->postClean('RG',TRUE);
                $rg_expedition_agency = $this->postClean('rg_expedition_agency',TRUE);
                $rg_expedition_date = $this->convertDate($this->postClean('rg_expedition_date',TRUE), "");
                $affiliation = $this->postClean('affiliation',TRUE);
                $birth_date = $this->convertDate($this->postClean('birth_date',TRUE), "");
                $service_charge_value = $this->postClean('service_charge_value',TRUE);
                $raz_social = NULL;
                $CNPJ = NULL;
                $IEST = NULL;
                $IMUN = null;
                $gestor = NULL;
                $bank = $this->postClean('bank',TRUE);
                $agency = $this->postClean('agency',TRUE);
                $account_type = $this->postClean('account_type',TRUE);
                $account = $this->postClean('account',TRUE);
            }

            // true case
            $data = array(
                'name' => $this->strReplaceName($this->postClean('name',TRUE)),
                'pj_pf' => $this->postClean('pj_pf',TRUE),
                'service_charge_value' => $service_charge_value,
                'vat_charge_value' => $this->postClean('vat_charge_value',TRUE),
                'address' => $this->postClean('address',TRUE),
                'addr_num' => $this->postClean('addr_num',TRUE),
                'addr_compl' => $this->postClean('addr_compl',TRUE),
                'addr_neigh' => $this->postClean('addr_neigh',TRUE),
                'addr_city' => $this->postClean('addr_city',TRUE),
                'addr_uf' => $this->postClean('addr_uf',TRUE),
                'zipcode' => $this->postClean('zipcode',TRUE),
                'phone_1' => $this->postClean('phone_1',TRUE),
                'phone_2' => $this->postClean('phone_2',TRUE),
                'country' => $this->postClean('country',TRUE),
                // retidado em 02/03/2020 'message' => $this->postClean('message',TRUE),
                // retidado em 02/03/2020 'currency' => $this->postClean('currency',TRUE),
                'currency' => 'BRL',
                'raz_social' => $raz_social,
                'CNPJ' => $CNPJ,
                'IEST' => $IEST,
                'IMUN' => $IMUN,
                'gestor' => $gestor,
                'email' => $this->postClean('email',TRUE),
                'associate_type' => $associate_type,
                'prefix' => strtoupper(substr(md5(uniqid(mt_rand(99999, 99999999), true)), 0, 5)),
                'parent_id' => $this->data['usercomp'],
                'CPF' => $CPF,
                'RG' => $RG,
                'rg_expedition_agency' => $rg_expedition_agency,
                'rg_expedition_date' => $rg_expedition_date,
                'affiliation' => $affiliation,
                'birth_date' => $birth_date,
                'bank' => $bank,
                'agency' => $agency,
                'account_type' => $account_type,
                'account' => $account,
                'plan_id' => $plan_id,
                'responsible_finan_name' => $this->postClean('responsible_finan_name',TRUE),
                'responsible_finan_email' => $this->postClean('responsible_finan_email',TRUE),
                'responsible_finan_tell' => $this->postClean('responsible_finan_tell',TRUE),
                'responsible_ti_name' => $this->postClean('responsible_ti_name',TRUE),
                'responsible_ti_email' => $this->postClean('responsible_ti_email',TRUE),
                'responsible_ti_tell' => $this->postClean('responsible_ti_tell',TRUE),
                'responsible_sac_name' => $this->postClean('responsible_sac_name',TRUE),
                'responsible_sac_email' => $this->postClean('responsible_sac_email',TRUE),
                'responsible_sac_tell' => $this->postClean('responsible_sac_tell',TRUE),
                'multi_channel_fulfillment' => $this->postClean('multi_channel_fulfillment',TRUE) ?? 0,
            );


            $insert = $this->model_company->create($data);
            if ($insert) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created') . " Agora crie a loja da empresa. Se só tiver uma, copie os dados.");
                $this->session->set_flashdata('company_id', $insert);
                redirect('stores/create', 'refresh');  // Vai para Loja para criar
            } else {
                $this->session->set_flashdata('errors', $this->lang->line('messages_error_occurred'));
                redirect('company/create', 'refresh');
            }
        } else {

            // false case

            // retidado em 02/03/2020 $this->data['currency_symbols'] = $this->currency();
            $company = $this->model_company->getCompanyData(1);
            $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
            $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;
            $this->data['usar_mascara_banco'] = $this->model_settings->getStatusbyName('usar_mascara_banco') == 1 ? true : false;
            $this->data['stores_multi_cd'] = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;

            $this->render_template('company/create', $this->data);
        }

    }

    /*
     * This function is invoked from another function to upload the image into the assets folder
     * and returns the image path
     */
    public function upload_image()
    {
        if (!in_array('updateCompany', $this->permission) 
            && !in_array('viewCompany', $this->permission) 
            && !in_array('createCompany', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // assets/images/company_image
        $config['upload_path'] = 'assets/images/company_image';
        $config['file_name'] = uniqid();
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '1000';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('company_image')) {
            $error = $this->upload->display_errors();
            return $error;
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['company_image']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data == true) ? $path : false;
        }
    }
	
	function convertDate($orgDate,$time) {
		$date =  str_replace('/','-', $orgDate);
		$newDate = date("Y-m-d", strtotime($date)); 
		if ($time == '') {
			return $newDate.' 00:00:00';
		}
		else {
			return $newDate.' '.$time.':00';
		}
	}
	
	function checkCNPJ($cnpj) {
		$ok = $this->isCnpjValid($cnpj);
		if (!$ok) {
			 $this->form_validation->set_message('checkCNPJ', '{field} inválido.');
		}
		return $ok;
		
	}
	
	function checkCPF($cpf) {
		$ok = $this->isCPFValid($cpf);
		if (!$ok) {
			 $this->form_validation->set_message('checkCPF', '{field} inválido.');
		}
		return $ok;
		
	}

	function isCnpjValid($cnpj){
		//Etapa 1: Cria um array com apenas os digitos numéricos, isso permite receber o cnpj em diferentes formatos como "00.000.000/0000-00", "00000000000000", "00 000 000 0000 00" etc...
		$j=0;
		$num = array();
		for($i=0; $i<(strlen($cnpj)); $i++)
			{
				if(is_numeric($cnpj[$i]))
					{
						$num[$j]=$cnpj[$i];
						$j++;
					}
			}
		//Etapa 2: Conta os dígitos, um Cnpj válido possui 14 dígitos numéricos.
		if(count($num)!=14)
			{
				$isCnpjValid=false;
			}
		//Etapa 3: O número 00000000000 embora não seja um cnpj real resultaria um cnpj válido após o calculo dos dígitos verificares e por isso precisa ser filtradas nesta etapa.
		elseif ($num[0]==0 && $num[1]==0 && $num[2]==0 && $num[3]==0 && $num[4]==0 && $num[5]==0 && $num[6]==0 && $num[7]==0 && $num[8]==0 && $num[9]==0 && $num[10]==0 && $num[11]==0)
			{
				$isCnpjValid=false;
			}
		//Etapa 4: Calcula e compara o primeiro dígito verificador.
		else
			{
				$j=5;
				for($i=0; $i<4; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);
				$j=9;
				for($i=4; $i<12; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);	
				$resto = $soma%11;			
				if($resto<2)
					{
						$dg=0;
					}
				else
					{
						$dg=11-$resto;
					}
				if($dg!=$num[12])
					{
						$isCnpjValid=false;
					} 
			}
		//Etapa 5: Calcula e compara o segundo dígito verificador.
		if(!isset($isCnpjValid))
			{
				$j=6;
				for($i=0; $i<5; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);
				$j=9;
				for($i=5; $i<13; $i++)
					{
						$multiplica[$i]=$num[$i]*$j;
						$j--;
					}
				$soma = array_sum($multiplica);	
				$resto = $soma%11;			
				if($resto<2)
					{
						$dg=0;
					}
				else
					{
						$dg=11-$resto;
					}
				if($dg!=$num[13])
					{
						$isCnpjValid=false;
					}
				else
					{
						$isCnpjValid=true;
					}
			}
		//Trecho usado para depurar erros.
		/*
		if($isCnpjValid==true)
			{
				echo "<p><font color="GREEN">Cnpj é Válido</font></p>";
			}
		if($isCnpjValid==false)
			{
				echo "<p><font color="RED">Cnpj Inválido</font></p>";
			}
		*/
		//Etapa 6: Retorna o Resultado em um valor booleano.
		return $isCnpjValid;			
	}

	function isCPFValid($cpf) {
	    // Extrai somente os números
	    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
	     
	    // Verifica se foi informado todos os digitos corretamente
	    if (strlen($cpf) != 11) {
	        return false;
	    }
	
	    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
	    if (preg_match('/(\d)\1{10}/', $cpf)) {
	        return false;
	    }
	
	    // Faz o calculo para validar o CPF
	    for ($t = 9; $t < 11; $t++) {
	        for ($d = 0, $c = 0; $c < $t; $c++) {
	            $d += $cpf[$c] * (($t + 1) - $c);
	        }
	        $d = ((10 * $d) % 11) % 10;
	        if ($cpf[$c] != $d) {
	            return false;
	        }
	    }
	    return true;
	}

    function checkInscricaoEstadual($ie, $uf)
    {
        $ok = ValidatesIE::check($ie, $uf);
        if (!$ok) {
            $this->form_validation->set_message('checkInscricaoEstadual', '{field} inválida.');
        }
        return $ok;
    }

    public function strReplaceName($name) {
        return str_replace('&amp;', '&', str_replace('&#039', "'", $name));
    }
}