<?php
/*
SW Serviços de Informática 2019

Controller de Fornecedores

 */
defined('BASEPATH') or exit('No direct script access allowed');

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";

use Firebase\JWT\JWT;
use League\Csv\Reader;
use League\Csv\Statement;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Microservices\v1\Logistic\FreightTables;
use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Security $security
 * @property CI_Parser $parser
 * @property CI_Lang $lang
 * @property CI_Loader $load
 * @property CI_Output $output
 *
 * @property Model_shipping_company $model_shipping_company
 * @property Model_stores $model_stores
 * @property Model_billet $model_billet
 * @property Model_company $model_company
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_settings $model_settings
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integration_erps $model_integration_erps
 *
 * @property JWT $jwt
 * @property FreightTables $ms_freight_tables
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 */

class Shippingcompany extends Admin_Controller
{

  public function __construct()
  {
    parent::__construct();
    date_default_timezone_set('America/Sao_Paulo');
    $this->not_logged_in();

    $this->data['page_title'] = $this->lang->line('application_shipping_company');

    $this->load->model('model_shipping_company');
    $this->load->model('model_integration_logistic');
    $this->load->model('model_stores');
    $this->load->model('model_billet');
    $this->load->model('model_company');
    $this->load->model('model_csv_to_verifications');
    $this->load->model('model_settings');
    $this->load->model('model_api_integrations');
      $this->load->model('model_integration_erps');

    $this->load->library('JWT');
    $this->load->library('upload');
    $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');
    $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", array(), 'ms_shipping_integrator');
    $this->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", array(), 'ms_shipping_carrier');

    $this->load->helper('download');
  }

  /**
   * It only redirects to the manage providers page
   */
  public function index($store_id = null)
  {
    // Verifica se tem permissão
    if (!in_array('viewCarrierRegistration', $this->permission)) {
      redirect('dashboard', 'refresh');
    }

    if($this->session->userdata['usercomp'] == 1 ) {
      $this->data['company_list'] = $this->model_company->getAllCompanyData();     
    }
    $this->data['stores'] = $this->model_stores->getStoresData();
    $this->data['stores'] = array_filter($this->data['stores'] ?? [], function ($store) {
        return $store['active'] == 1;
    });
    $this->data['userData'] = $this->session->userdata;
    $this->data['store_id_selected'] = $store_id;
    $this->render_template('shipping_company/list', $this->data);
  }

    public function updateCredentialIntegration(): CI_Output
    {
        $fields           = $this->postClean();
        $store            = $fields['store_id'] ?? null;
        $type_integration = $fields['type_integration_ms'] ?? null;
        $integration      = $fields['integration'] ?? null;

        unset($fields['store_id']);
        unset($fields['type_integration_ms']);
        unset($fields['integration']);

        $formIntegrationData = $this->postClean('data') ?? [];
        $formIntegrationData = is_array($formIntegrationData) ? $formIntegrationData : [];
        $formCredentials = array_reduce(array_map(function ($item) {
            return [
                    $item['name'] ?? '' => $item['value'] ?? ''
            ];
        }, $formIntegrationData), 'array_merge', []);

        if (!$store) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => '', 'message' => "Credenciais atualizadas")));
        }

        if ($this->ms_shipping_carrier->use_ms_shipping || $this->ms_shipping_integrator->use_ms_shipping) {
            try {
                if ($type_integration === 'carrier' && $this->ms_shipping_carrier->use_ms_shipping) {
                    $this->ms_shipping_carrier->setStore($store);
                    $this->ms_shipping_carrier->updateConfigure($integration, array('credentials' => $formCredentials));
                    if($this->ms_shipping_carrier->use_ms_shipping_replica) {
                        $this->model_shipping_company->updateKeyIntegration(array('credentials' => json_encode($formCredentials)), $store);
                    }
                }
                else if ($type_integration === 'integrator' && $this->ms_shipping_integrator->use_ms_shipping) {
                    $this->ms_shipping_integrator->setStore($store);
                    $this->ms_shipping_integrator->updateConfigure($integration, array('credentials' => $formCredentials));
                    if($this->ms_shipping_integrator->use_ms_shipping_replica) {
                        $this->model_shipping_company->updateKeyIntegration(array('credentials' => json_encode($formCredentials)), $store);
                    }
                }
            } catch (Exception $exception) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('error' => '', 'message' => "Erro ao atualizar as credenciais")));
            }

            return $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => '', 'message' => "Credenciais atualizadas")));
        } else {
            $backup = $this->model_shipping_company->updateKeyIntegration(array('credentials' => json_encode($formCredentials)), $store);

            $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração para a loja {$store} atualizada!\nbackup_integration=".json_encode($backup)."\nnew_integration=".json_encode($formCredentials));

            if ($backup !== false) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => '', 'message' => "Credenciais atualizadas")));
            } else {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('error' => '', 'message' => "Erro ao atualizar as credenciais")));
            }
        }
    }
  
    public function getStoreForCompany()
    {
        if(empty($this->postClean('companyId'))) {
            $result = array(
                "error" => "Informe uma empresa."
            );
            echo json_encode($result);
            exit;
        }

        $result = $this->model_stores->getCompanyStores($this->postClean('companyId'));
        echo json_encode($result);
    }

    public function getCredentialIntegration()
    {
        $integration        = $this->postClean('integration');
        $type_integration   = $this->postClean('type_integration');
        $store_id           = $this->postClean('store_id');

        if ($this->ms_shipping_carrier->use_ms_shipping || $this->ms_shipping_integrator->use_ms_shipping) {
            try {
                if ($type_integration === 'carrier' && $this->ms_shipping_carrier->use_ms_shipping) {
                    $this->ms_shipping_carrier->setStore($store_id);
                    $result = $this->ms_shipping_carrier->getConfigure($integration);
                }
                else if ($type_integration === 'integrator' && $this->ms_shipping_integrator->use_ms_shipping) {
                    $this->ms_shipping_integrator->setStore($store_id);
                    $result = $this->ms_shipping_integrator->getConfigure($integration);
                }
            } catch (Exception $exception) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array()));
            }
            if ($result->type_contract === 'sellercenter') {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array()));
            }
        } else {
            $result = $this->model_shipping_company->getIntegrationByStore($store_id);
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /**
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchShippingCompanyData()
    {
        $result = array('data' => array());
        $store = (int)$this->input->get('store_id', true);

        if (empty($store)) {
            echo json_encode(array('data' => array()));
            die;
        }

        // Transportadoras.
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $store_name = $this->model_stores->getStoresData($store)['name'];
                $this->ms_freight_tables->setStore($store);
                $data = $this->ms_freight_tables->getShippingCompanies();
            } catch (Exception $exception) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => array())));
            }
        } else {
            $data = $this->model_shipping_company->getShippingCompanyByStore((int)$store);
        }

        foreach ($data as $value) {
            $freight_seller             = $this->ms_freight_tables->use_ms_shipping ? $value->freight_seller : $value['freight_seller'];
            $shippingCompanyId          = $this->ms_freight_tables->use_ms_shipping ? $value->id : $value['id'];
            $canUpdateShippingCompany   = $this->ms_freight_tables->use_ms_shipping ? (($value->freight_seller === 0 && $this->session->userdata['usercomp'] == 1) || $value->freight_seller === 1) : (($value['store_shipping_company'] === null && $this->session->userdata['usercomp'] == 1) || $value['store_shipping_company'] !== null);
            $store_name                 = $this->ms_freight_tables->use_ms_shipping ? $store_name : $value['store_name'];
            $name                       = $this->ms_freight_tables->use_ms_shipping ? $value->name : $value['provider_name'];
            $active                     = $this->ms_freight_tables->use_ms_shipping ? $value->active : ($value['active'] == 1);

            $btnUpdateShipping = $this->session->userdata['usercomp'] != 1 ?
                ' <a href="' . base_url('shippingcompany/updatesimplified/' . $shippingCompanyId . '/' . $store) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>' :
                ' <a href="' . base_url('shippingcompany/update/' . $shippingCompanyId . '/' . $store) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>';

            $btnTableFreight = '<button type="button" class="btn btn-primary modal_frete_simplificado_id" onclick="openModal(' . $shippingCompanyId . ', '.$store.')" data-toggle="modal" data-id="' . $shippingCompanyId . '" data-target="#modal_frete_simplificado"><i class="fa fa-edit"> </i> Simplificado </button>';
            $btnTableFreight .= '<a href="' . base_url('shippingcompany/tableshippingcompany/' . $shippingCompanyId . '/' . $store) . '" class="btn btn-info"><i class="fa fa-table"></i> Tabela</a>';

            $result['data'][] = array(
                $shippingCompanyId,
                $store_name,
                $name,
                $canUpdateShippingCompany ? $active ? '<input type="checkbox" name="my-checkbox" checked data-bootstrap-switch onchange="updateStatus(' . $shippingCompanyId . ',' . $freight_seller . ',$(this))">' : '<input type="checkbox"  name="my-checkbox" data-bootstrap-switch onchange="updateStatus(' . $shippingCompanyId . ',' . $freight_seller . ',$(this))">' : ($active ? $this->lang->line('application_active') : $this->lang->line('application_inactive')),
                $canUpdateShippingCompany ? $btnTableFreight : $this->lang->line('application_logistics') . ' ' . $this->model_settings->getValueIfAtiveByName('sellercenter_name'),
                $canUpdateShippingCompany ? $btnUpdateShipping : '',
            );
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Fetches the orders data from the orders table
     * this function is called from the datatable ajax function
     */
    public function fetchIntegrationsData()
    {
        $result = array('data' => array());
        $store  = (int)$this->input->get('store_id', true);

        if (empty($store)) {
            echo json_encode(array('data' => array()));
            die;
        }

        $dataIntegrationLogistic = array();
        $ms_used = false;
        if ($this->ms_shipping_integrator->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_integrator->setStore($store);
                $dataIntegrationLogistic = array(array_merge(array('type' => 'integrator'), (array)$this->ms_shipping_integrator->getConfigures()));
            } catch (Exception $exception) {}
        }

        if ($this->ms_shipping_carrier->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_carrier->setStore($store);
                $dataIntegrationLogistic = array(array_merge(array('type' => 'carrier'), (array)$this->ms_shipping_carrier->getConfigures()));
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            $dataIntegrationLogistic = $this->model_shipping_company->getIntegrationLogistic($store);
        } else {
            $store_name = $this->model_stores->getStoresData($store)['name'];
        }

        $api_integrations = array_map(function ($item) {
            return $item['integration'];
        }, $this->model_api_integrations->getIntegrationsWithCredentials());

        // Remove a integração precode, também é consideração como uma logística.
        $keyErp = array_search('precode', $api_integrations);
        if($keyErp){
            unset($api_integrations[$keyErp]);
        }

        if ($dataIntegrationLogistic && count($dataIntegrationLogistic) > 0) {
            foreach($dataIntegrationLogistic as $_value) {
                $integration             = $this->ms_freight_tables->use_ms_shipping ? $_value['integration_name'] : $_value['integration'];
                $active                  = $this->ms_freight_tables->use_ms_shipping ? $_value['active'] : $_value['active'];
                $store_name              = $this->ms_freight_tables->use_ms_shipping ? $store_name : $_value['store_name'];
                $id                      = $this->ms_freight_tables->use_ms_shipping ? $integration : $_value['id'];
                $type_integration        = $this->ms_freight_tables->use_ms_shipping ? $_value['type'] : null;
                $credentials             = $this->ms_freight_tables->use_ms_shipping ? $_value['credentials'] : $_value['credentials'];
                $description             = $this->ms_freight_tables->use_ms_shipping ? $_value['integration_description'] : $_value['description'];
                $external_integration_id = $this->ms_freight_tables->use_ms_shipping ? null : $_value['external_integration_id'];

                $btn_credentials = "";
                if (!is_null($credentials) && (in_array($integration, $api_integrations))) {
                    $btn_credentials = '<button type="button" class="btn btn-sm btn-outline-primary" data-id="' . $id . '" data-toggle="modal" data-target="#integration_modal"><i class="fa fa-edit"></i> Ver credenciais</button>';
                } else if (!is_null($credentials)) {
                    $btn_credentials = '<button type="button" class="btn btn-sm btn-outline-primary" data-id="' . $id . '" onclick="modalIntegration(\'modal_logitic_integration_credential\',\''.$integration.'\', this, \''.$type_integration.'\', '.$store.');"><i class="fa fa-edit"></i> Alterar credenciais</button>';
                }

                if ($this->ms_freight_tables->use_ms_shipping && $_value['type_contract'] === 'sellercenter') {
                    $btn_credentials = "";
                }

                $btn_update_status = $active == 1 ? "<input type='checkbox' name='my-checkbox' checked data-bootstrap-switch onchange=\"updateStatusIntegration('$id',$(this),'$type_integration', '$integration')\">" : "<input type='checkbox' name='my-checkbox' data-bootstrap-switch onchange=\"updateStatusIntegration('$id',$(this),'$type_integration', '$integration')\">";
                $btn_update_status = empty($credentials) && !$this->data['only_admin'] ? '' : $btn_update_status;

                $logo_integration = '<img src="'.base_url("assets/files/integrations/$integration/$integration.png") . '" width="70px" alt="'.$description.'">';
                if ($external_integration_id) {
                    $external_integration = $this->model_integration_erps->getById($external_integration_id);
                    $logo_integration = '<img src="'.base_url("assets/images/integration_erps/$external_integration->image") . '" width="70px" alt="'.$description.'">';
                }

                $resultIntegration = array(
                    $id,
                    $store_name,
                    $logo_integration,
                    $btn_credentials,
                    $btn_update_status
                    
                );
                $result['data'][] = $resultIntegration;
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    public function fetchModalIntegrations(): CI_Output
    {
        $store_id = $this->postClean('store_id');
        $ms_used = false;
        $dataIntegration = null;
        if ($this->ms_shipping_integrator->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_integrator->setStore($store_id);
                $dataIntegration = array(array_merge(array('type' => 'integrator'), (array)$this->ms_shipping_integrator->getConfigures()));
            } catch (Exception $exception) {}
        }

        if ($this->ms_shipping_carrier->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_carrier->setStore($store_id);
                $dataIntegration = array(array_merge(array('type' => 'carrier'), (array)$this->ms_shipping_carrier->getConfigures()));
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
            $credentials = json_decode($dataIntegration['credentials'], true);
            $dataIntegration = $this->model_stores->getDataApiIntegrationForId($dataIntegration['id']);
        } else{
            if ($dataIntegration && count($dataIntegration) > 0) {
                foreach($dataIntegration as $_value) {
                    $credentials = $_value['credentials'];
                }
            }
        }              
        
        $credentials_html = '';
        if (!empty($dataIntegration)) {
            foreach ($credentials as $credential_name => $credential_value) {

                if (!is_string($credential_value)) {
                    $credential_value = json_encode($credential_value, JSON_UNESCAPED_UNICODE);
                }

                $label = $this->lang->line("application_credentials_erp_$credential_name");
                if (!$label) {
                    //$label = $credential_name;
                    continue;
                }

                $credentials_html .= "<div class='row'>
                    <div class='col-md-12'>
                        <div class='form-group'>
                            <label>$label</label>
                            <input type='text' class='form-control' name='token_vtex' value='$credential_value' disabled>
                        </div>
                    </div>
                </div>";
            }
        }

        if (empty($credentials_html)) {
            $credentials_html .= '
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4 class="text-danger text-center">Integração ainda não realizada. Clique no botão <b>Administrar Integração</b>, logo abaixo, para dar andamento ao processo.</h4>
                    </div>
                </div>
            </div>';
        }

      $integration_modal = '<div class="modal fade" tabindex="-1" role="dialog" id="integration_modal">
          <div class="modal-dialog" role="document">
              <form action="" method="POST" enctype="multipart/form-data">
                  <div class="modal-content">
                      <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <h4 class="modal-title">' . $this->lang->line('application_integration') . '</span></h4>
                      </div>
                      <div class="modal-body">'.$credentials_html.'</div>
                      <div class="modal-footer d-flex justify-content-between">
                          <button type="button" class="btn btn-danger col-md-4" data-dismiss="modal">' . $this->lang->line("application_close") . '</button>
                          <a href="' . base_url('stores/integration?store=' . $store_id) . '" class="btn btn-success col-md-4">Administrar Integração</a>
                      </div>
                  </div>
              </form>
          </div>
      </div>';

      return $this->output
          ->set_content_type('application/json')
          ->set_output(json_encode($integration_modal));
  }

    public function tableshippingcompany(int $shipping_company_id, $store_id = null)
    {
        // Verifica se tem permissão
        if (!in_array('createCarrierRegistration', $this->permission)) {
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $this->ms_freight_tables->setStore($store_id);
                $provider = $this->ms_freight_tables->getShippingCompany($shipping_company_id);
            } catch (Exception $exception) {
                $this->session->set_flashdata('error', $exception->getMessage());
                redirect("shippingcompany/tableshippingcompany/$shipping_company_id/$store_id", 'refresh');
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => array())));
            }
        } else {
            $provider = $this->model_shipping_company->getStoreByProvider($shipping_company_id);
        }

        // não encontrou a transportadora
        if (!$provider) {
            $this->session->set_flashdata('error', 'Transportadora não encontrada!');
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        if (
            ($store_id === null && $this->session->userdata['usercomp'] != 1) ||
            ($store_id !== null && $this->model_stores->checkIfTheStoreIsMine($store_id) === false)
        ) {
            $this->session->set_flashdata('error', 'Transportadora não encontrada!');
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        $this->data['shipping_company_id'] = $shipping_company_id;
        $this->data['store_id'] = $store_id;
        $this->render_template('shipping_company/tablelist', $this->data);
    }

    public function tablelist($shipping_company_id, $store_id = null)
    {
        $result = array('data' => array());

        if ($this->ms_freight_tables->use_ms_shipping) {
            $client = new Client();
            $ms_url = "{$this->ms_freight_tables->process_url}{$this->ms_freight_tables->path_url}/{$store_id}/v1/shipping_table/processed_files_list/{$shipping_company_id}";
            // $request = new Request('GET', "https://ms.conectala.com.br/freight_tables/conectala/api/{$store_id}/v1/shipping_table/processed_files_list/{$company_user_id}");
            $request = $this->request('GET', $ms_url); 
            $res = $client->sendAsync($request)->wait();
            $json = json_decode((string) $res->getBody(), true);
            rsort($json);

            $company_user_id = $this->session->userdata('usercomp');
            $store_data = $this->model_stores->getStoresData($store_id);
            $store_name = $store_data['name'];

            $user_data = $this->model_users->getUserData($this->session->userdata['id']);
            $user_name = $user_data['username'];

            foreach ($json as $key => $value) {
                if ($value['error_found'] == 1) {
                    continue;
                }

                $ms_url = $value['file_url'] ?? "{$this->ms_freight_tables->process_url}{$this->ms_freight_tables->path_url}/{$store_id}/v1/shipping_table/download_one/{$value['id']}";
                // $download_file = "<a href='javascript:void(0)' onclick='location.href=\"https://ms.conectala.com.br/freight_tables/conectala/api/{$store_id}/v1/shipping_table/download_one/{$value['id']}\"'>Tabela_de_frete_{$value['id']}</a>";
                $download_file = "<a href='javascript:void(0)' onclick='location.href=\"{$ms_url}\"'>Tabela_de_frete_{$value['id']}</a>";

                $colorStatus = '';
                $nameStatus = '';

                $status = " &nbsp;&nbsp;<span class='label label-danger'>Inativa</span>";
                if (($value['status'] == 1) && ($value['error_found'] == 0) && (($value['checked'] == 0) || ($value['checked'] == 2))) {
                    $status = ' &nbsp;&nbsp;<span class="label label-warning"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></span>';
                } else if (($value['status'] == 1) && ($value['error_found'] == 0) && ($value['checked'] == 1)) {
                    $status = " &nbsp;&nbsp;<span class='label label-success'>Ativa</span>";
                }

                $result['data'][] = array(
                    $status,
                    (string) $value['id'],
                    $download_file,
                    date('d/m/Y', strtotime($value['expiration_date'])),
                    date('d/m/Y H:i:s', strtotime($value['created_at'])),
                );
            }
        } else {
            $data = $this->model_shipping_company->getTableConfigShipping($shipping_company_id, (int) $this->session->userdata['id']);
            if (count($data) > 0) {
                $ultimo_id = end($data)["id_file"];
            }
            rsort($data);

            foreach ($data as $key => $value) {
                $action = '<button type="button" class="btn btn-danger" disabled><i class="fa fa-ban"></i></button>';
                $id_value_primary = $value['id_file'];
                $typeTableShipping = $this->model_shipping_company->getTypeTableShipping($shipping_company_id);
                if (count($typeTableShipping) > 0 && isset($typeTableShipping[0]["id_type"]) && !empty($typeTableShipping[0]["id_type"])) {
                    $typeTableShipping = $typeTableShipping[0]["id_type"];
                }

                if ($value['id_file'] == $ultimo_id && $typeTableShipping == 1) {
                    $value['status'] = ' &nbsp;&nbsp;<span class="label label-success">' . 'Ativo</span>';
                } else {
                    $value['status'] = ' &nbsp;&nbsp;<span class="label label-danger">' . 'Inativo</span>';
                }

                $tableName = '';
                if ($value['deleted'] != null && isset($value['deleted']) && !empty($value['deleted'])) {
                    $value['status'] = ' &nbsp;&nbsp;<span class="label label-danger">' . 'Excluído</span>';
                    $tableName = '<a> tabela_de_frete_' . $id_value_primary . '</a>';
                } else {
                    $tableName = '<a target="_blank" href="' . base_url('shippingcompany/download/' . $id_value_primary) . '">' . 'tabela_de_frete_' . $id_value_primary . '</a>';
                }

                $result['data'][] = array(
                    $value['status'],
                    $value['id_file'],
                    $tableName,
                    //$value['dt_start_v'],
                    $value['dt_end_v'],
                    $value['dt_create_file']
                );
            }
        }

        echo json_encode($result);
    }

    public function tablestatus()
    {

        $fileId = $this->postClean('idFile');
        $status = $this->postClean('status');

        $data = $this->model_shipping_company->updateStatusFileTableShippigCompany($fileId, $status);

        if ($data) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        }
        return 'Ok';
    }

    public function download($fileId)
    {

        if (!$fileId) {
            $file = realpath("importacao/frete") . "/tabela_frete.csv";
            force_download($file, null);
        }

        $sql = "SELECT directory, file_table_shippingcol, shipping_company_id FROM file_table_shipping WHERE idfile_table_shipping = $fileId;";
        $query = $this->db->query($sql);            
        $file_table_shipping = $query->result_array();

        if (!$file_table_shipping) {
            $file = realpath("importacao/frete") . "/tabela_frete.csv";
            force_download($file, null);
        }

        $directory = $file_table_shipping[0]['directory'];
        $file_name = $file_table_shipping[0]['file_table_shippingcol'];
        $shipping_company_id = $file_table_shipping[0]['shipping_company_id'];
        $csv_file = "";

        $file_found = false;
        // C:\xampp\htdocs\Fase1 - /importacao/frete - /17 - /63890b081ea5e.csv
        if (file_exists(getcwd() . "$directory/$shipping_company_id/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = getcwd() . "$directory/$shipping_company_id/$file_name";

        // C:\xampp\htdocs\Fase1 - /importacao/frete - /63890b081ea5e.csv
        } else if (file_exists(getcwd() . "$directory/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = getcwd() . "$directory/$file_name";

        // /importacao/frete - /17 - /63890b081ea5e.csv
        } else if (file_exists("$directory/$shipping_company_id/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = "$directory/$shipping_company_id/$file_name";

        // /importacao/frete - /63890b081ea5e.csv
        } else if (file_exists("$directory/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = "$directory/$file_name";

        // C:\xampp\htdocs\Fase1 - /assets/images/csv_of_products_uploaded - /63890b081ea5e.csv
        } else if (file_exists(getcwd() . "/assets/images/csv_of_products_uploaded/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = getcwd() . "/assets/images/csv_of_products_uploaded/$file_name";

        // assets/images/csv_of_products_uploaded - /63890b081ea5e.csv
        } else if (file_exists("/assets/images/csv_of_products_uploaded/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = "/assets/images/csv_of_products_uploaded/$file_name";

        // C:\xampp\htdocs\Fase1 - /assets/images/freight_table - /17 - /63890b081ea5e.csv
        } else if (file_exists(getcwd() . "/assets/images/freight_table/$shipping_company_id/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = getcwd() . "/assets/images/csv_of_products_uploaded/$shipping_company_id/$file_name";

        // C:\xampp\htdocs\Fase1 - /assets/images/freight_table - /63890b081ea5e.csv
        } else if (file_exists(getcwd() . "/assets/images/freight_table/$file_name") && !$file_found) {
            $file_found = true;
            $csv_file = getcwd() . "/assets/images/csv_of_products_uploaded/$file_name";
        }

        if ($file_found) {
            $file = file_get_contents($csv_file);
            force_download("Tabela_de_frete_$fileId.csv", $file);
        } else {
            $file = realpath("importacao/frete") . "/tabela_frete.csv";
            force_download($file, null);
        }
    }

    public function tableconfig($shipping_company_id, $store_id = null)
    {
        // Verifica se tem permissão
        if (!in_array('createCarrierRegistration', $this->permission)) {
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        if (empty($shipping_company_id)) {
            redirect("shippingcompany/index/$store_id");
        }

        // Transportadoras.
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $this->ms_freight_tables->setStore($store_id);
                $data = $this->ms_freight_tables->getShippingCompany($shipping_company_id);
            } catch (Exception $exception) {
                $this->session->set_flashdata('error', $exception->getMessage());
                redirect("shippingcompany/tableshippingcompany/$shipping_company_id/$store_id", 'refresh');
                return $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => array())));
            }
        } else {
            $data = $this->model_shipping_company->getProviderDataCompanyId($shipping_company_id);
        }

        $result = array(
            'id'            => $this->ms_freight_tables->use_ms_shipping ? $data->id : $data['id'],
            'razao_social'  => $this->ms_freight_tables->use_ms_shipping ? $data->corporate_name : $data['razao_social'],
            'name'          => $this->ms_freight_tables->use_ms_shipping ? $data->name : $data['name']
        );

        $limitLine = $this->model_settings->getValueIfAtiveByName('limit_line_freight_table');
        $this->data['fileExem'] = base_url('shippingcompany/download/0');
        $this->data['results'] = $result;
        $this->data['idProvider'] = $shipping_company_id;
        $this->data['store_id'] = $store_id; // $data[0]['store_id'];
        $this->data['limit_line'] = $limitLine;
        $this->render_template('shipping_company/tableconfig', $this->data);
    }

    public function uploadconfig()
    {
        ob_start();
        $this->form_validation->set_rules('shippingCompanyId', $this->lang->line('application_name'), 'trim|required');

        $uri = current_url(true);
        $uri_array = explode('/', $uri);
        $uri_length = count($uri_array);
        if (is_numeric($uri_array[$uri_length - 1]) && is_numeric($uri_array[$uri_length - 2])) {
            $store_id = $uri_array[$uri_length - 1];
        }

        $this->data['user_data'] = $this->model_users->getUserData();
        $user_id = $this->session->userdata('id');
        $company_id = $this->session->userdata('usercomp');
        $shipping_company_id = $this->postClean('shippingCompanyId');
        $end_date = $this->postClean('dt_fim');
        $end_date_aux = explode('/', $end_date);
        if (count($end_date_aux) != 3) {
            ob_clean();
            $this->session->set_flashdata('error', 'Favor informar a data de vigência.');
            redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
        }
        $end_date = "{$end_date_aux[2]}-{$end_date_aux[1]}-{$end_date_aux[0]}";

        $fileType = explode(".", $_FILES["tableconfig"]["name"]);
        $fileType = end($fileType) ?? '';

        // O arquivo precisa ter a extensão CSV.
        if ($fileType != 'csv') {
            ob_clean();
            if (!!is_null($shipping_company_id) || empty($shipping_company_id)) {
                redirect('shippingcompany');
            }

            $this->session->set_flashdata('error', $this->lang->line('messages_error_csv_error_type_file'));
            redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
        }

        if ($this->ms_freight_tables->use_ms_shipping) {
            $client = new Client();
            $options = [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => Psr7\Utils::tryFopen($_FILES["tableconfig"]["tmp_name"], 'r'),
                        'filename' => $_FILES["tableconfig"]["name"],
                        'headers'  => [
                            'Content-Type' => '<Content-type header>'
                        ]
                    ], [
                        'name' => 'user',
                        'contents' => $user_id
                    ], [
                        'name' => 'expiration',
                        'contents' => $end_date
                    ], [
                        'name' => 'shipping-company',
                        'contents' => $shipping_company_id
                    ], [
                        'name' => 'company',
                        'contents' => $company_id
                    ]
                ]
            ];

            $ms_url = "{$this->ms_freight_tables->process_url}{$this->ms_freight_tables->path_url}/{$store_id}/v1/shipping_table/upload";
            // $request = new Request('POST', "https://ms.conectala.com.br/freight_tables/conectala/api/{$store_id}/v1/shipping_table/upload");
            $request = $this->request('POST', $ms_url); //new Request('POST', $ms_url);
            try {
                $res = $client->sendAsync($request, $options)->wait();
            } catch (Exception $exception) {   
                ob_clean();             
                $this->session->set_flashdata('error', 'Não foi possível salvar o arquivo. Verifique o arquivo de exemplo e tente novamente!');
                if (!!is_null($shipping_company_id) || empty($shipping_company_id)) {
                    redirect('shippingcompany');
                }
                redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');                
            }
        }

        if(!$this->ms_freight_tables->use_ms_shipping || $this->ms_freight_tables->use_ms_shipping_replica) {
            // arquivo precisa ter completado o upload para temp.
            // Caso chegue aqui com size=0 pode ser algo com memoria do servidor.
            // form_validation->run = false, falhou algum envio do formulário
            if ($_FILES["tableconfig"]["size"] == 0 || $this->form_validation->run() !== true) {
                if (!!is_null($this->postClean('shippingCompanyId')) || empty($this->postClean('shippingCompanyId'))) {
                    redirect('shippingcompany');
                }

                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
            }

            $csv = Reader::createFromPath($_FILES['tableconfig']['tmp_name']); // lê o arquivo csv
            $csv->setDelimiter(';'); // separados de colunas
            $csv->setHeaderOffset(0); // linha do header
            $stmt   = new Statement();
            $datas  = $stmt->process($csv);

            $limitLine = $this->model_settings->getValueIfAtiveByName('limit_line_freight_table');
            if ($limitLine && count($datas) > $limitLine) {
                $this->session->set_flashdata('error', "Não é permitido mais que $limitLine linhas no arquivo! Seu arquivo tem: " . count($datas) . " linhas.");
                redirect("shippingcompany/tableconfig/$shipping_company_id/{$store_id}", 'refresh');
            }

            $provider = $this->model_shipping_company->getStoreByProvider($shipping_company_id);

            // não encontrou a transportadora
            if (!$provider) {
                $this->session->set_flashdata('error', 'Transportadora não encontrada!');
                redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
            }

            if (
                ($provider['store_id'] === null && $this->session->userdata['usercomp'] != 1) ||
                ($provider['store_id'] !== null && $this->model_stores->checkIfTheStoreIsMine($provider['store_id']) === false)
            ) {
                $this->session->set_flashdata('error', 'Transportadora não encontrada!');
                redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
            }

            $server_path = $_SERVER['SCRIPT_FILENAME'];
            $pos = strrpos($server_path, '/');
            $project_root = substr($server_path, 0, $pos);
            $folder_path = "$project_root/importacao/frete";

            if (!file_exists("$folder_path/$shipping_company_id")) {
                @mkdir("$folder_path/$shipping_company_id", 0775, true);
            }

            if (is_dir("$project_root/importacao")) {
                chmod("$project_root/importacao", 0755);
            }

            if (is_dir("$project_root/importacao/frete")) {
                chmod("$project_root/importacao/frete", 0755);
            }

            if (is_dir("$project_root/importacao/frete/$shipping_company_id")) {
                chmod("$project_root/importacao/frete/$shipping_company_id", 0755);
            }

            $pathFileCsv = "importacao/frete/{$shipping_company_id}";
            $nameFileCsv = uniqid().'.csv';

            $this->upload->initialize(array(
                'upload_path'   => $pathFileCsv,
                'allowed_types' => 'csv',
                'file_name'     => $nameFileCsv
            ));
            // falha para realizar o upload
            if (!$this->upload->do_upload('tableconfig')) {
                $this->session->set_flashdata('error', 'Não foi possível salvar o arquivo. Tente novamente!');
                redirect("shippingcompany/tableconfig/{$shipping_company_id}/{$store_id}", 'refresh');
            }

            $csvToVerification = array(
                'upload_file'   => "{$pathFileCsv}/{$nameFileCsv}",
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $provider['company_id'] ?? 1,
                'allow_delete'  => true,
                'module'        => 'Shippingcompany',
                'form_data'     => json_encode($this->postClean()),
                'store_id'      => $provider['store_id']
            );

            $this->model_csv_to_verifications->create($csvToVerification);
        }

        ob_clean();
        $this->session->set_flashdata('success', sprintf($this->lang->line('messages_file_added_to_import_queue_see_the_link'), base_url('FileProcess/index'), 'Logística > Arquivos Processados.'));
        redirect("shippingcompany/tableshippingcompany/{$shipping_company_id}/{$store_id}", 'refresh');
    }

    public function updateStatusShippingCompany()
    {
        $status         = (bool)$this->postClean('status', true);
        $storeId        = (int)$this->postClean('storeId', true);
        $idProvider     = (int)$this->postClean('id_transportadora', true);
        $freight_seller = (int)$this->postClean('freight_seller');

        if ($this->model_stores->checkIfTheStoreIsMine($storeId) === false) {
            echo  json_encode(array(
                'success' => '',
                'message' => "Transportadora não localizada."
            ));
            die;
        }

        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                if ($freight_seller == 1) {
                    $this->ms_freight_tables->setStore($storeId);
                }
                $this->ms_freight_tables->updateShippingCompany($idProvider, array('active' => $status));
                $result = 1;
                if ($this->ms_freight_tables->use_ms_shipping_replica) {
                    $this->model_shipping_company->updateStatusShippingCompany($storeId, $idProvider, $status);
                }
            } catch (Exception $exception) {
                echo json_encode(array('error' => '', 'message' => $this->ms_freight_tables->getErrorFormatted($exception->getMessage())), JSON_UNESCAPED_UNICODE);die;
            }
        } else {
            $result = $this->model_shipping_company->updateStatusShippingCompany($storeId, $idProvider, $status);
        }

        $return = array();
        switch ($result) {
            case 1:
                $return = array(
                    'success' => '',
                    'message' => "Transportadora alterada com sucesso."
                );
                break;
            case 2:
                $return = array(
                    'error' => '',
                    'message' => "Necessário pelo menos um transportadora/integração habilitada."
                );
                break;
            case 3:
                $return = array(
                    'error' => '',
                    'message' => "Transportadora não pôde ser alterada (Excedido o limite de cinco transportadoras/integrações habilitadas)"
                );
                break;
        }
        echo  json_encode($return);
    }

    public function updateStatusIntegration()
    {
        $status = (bool)$this->postClean('status');
        $id_integration = (int)$this->postClean('id_integration');
        $integration = $this->postClean('integration') ?? $this->postClean('id_integration');
        $store_id = (int)$this->postClean('storeId');
        $type_integration = $this->postClean('type_integration');


        if ($this->ms_shipping_integrator->use_ms_shipping && $type_integration === 'integrator') {
            try {
                $this->ms_shipping_integrator->setStore($store_id);
                $this->ms_shipping_integrator->updateConfigure($integration, array('active' => $status));
                $result = 1;
                if ($this->ms_shipping_integrator->use_ms_shipping_replica) {
                    $this->model_shipping_company->updateStatusShippingCompanyIntegration($store_id, null, $status);
                }
            } catch (Exception $exception) {
                echo json_encode(array('error' => '', 'message' => $this->ms_shipping_integrator->getErrorFormatted($exception->getMessage())), JSON_UNESCAPED_UNICODE);die;
            }
        } else if ($this->ms_shipping_carrier->use_ms_shipping && $type_integration === 'carrier') {
            try {
                $this->ms_shipping_carrier->setStore($store_id);
                $this->ms_shipping_carrier->updateConfigure($integration, array('active' => $status));
                $result = 1;
                if ($this->ms_shipping_carrier->use_ms_shipping_replica) {
                    $this->model_shipping_company->updateStatusShippingCompanyIntegration($store_id, null, $status);
                }
            } catch (Exception $exception) {
                echo json_encode(array('error' => '', 'message' => $this->ms_shipping_carrier->getErrorFormatted($exception->getMessage())), JSON_UNESCAPED_UNICODE);die;
            }
        } else {
            $result = $this->model_shipping_company->updateStatusShippingCompanyIntegration($store_id, $id_integration, $status);
        }

        $return = array();
        switch ($result) {
            case 1:
                $return = array(
                    'success' => '',
                    'message' => "Integradora alterada com sucesso."
                );
                break;
            case 2:
                $return = array(
                    'error' => '',
                    'message' => "Necessário pelo menos uma transportadora/integração habilitada."
                );
                break;
            case 3:
                $return = array(
                    'error' => '',
                    'message' => "Integradora não pôde ser alterada (Excedido o limite de cinco transportadoras/integrações habilitadas)"
                );
                break;
        }
        echo  json_encode($return);
    }

    public function tableshippingsimplified($id, $store_id = null)
    {
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $this->ms_freight_tables->setStore($store_id);
                $provider_data = $this->ms_freight_tables->getShippingCompany($id);
                foreach ($provider_data->store_id as $store_id_validate) {
                    // Usuário não gerencia a empresa principal e a transportadora é do seller center.
                    if ($this->data['usercomp'] != 1 && $provider_data->freight_seller == 0) {
                        redirect('shippingcompany', 'refresh');
                    }
                    if ($this->model_stores->checkIfTheStoreIsMine($store_id_validate) === false) {
                        redirect('shippingcompany', 'refresh');
                    }
                }
            } catch (Exception $exception) {
                redirect('shippingcompany', 'refresh');
            }
        }

        // Transportadoras.
        $getPriceRegionShipping = array();
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $this->ms_freight_tables->setStore($provider_data->freight_seller == 1 ? $store_id : null);
                $getPriceRegionShipping = $this->ms_freight_tables->getSimplifiedTable($id);
            } catch (Exception $exception) {}
        } else {
            $getPriceRegionShipping = $this->model_shipping_company->getPriceRegionShipping($id);
        }

        $dataRegion = [];
        foreach ($getPriceRegionShipping as $region_id => $value) {
            if ($this->ms_freight_tables->use_ms_shipping) {
                foreach ($value as $state_id => $value_ms) {
                    $dataRegion = array_merge($dataRegion, $this->formatDataToViewSimplified(
                        $region_id,
                        $state_id === 'region' ? null : $state_id,
                        property_exists($value_ms, 'price') ? $value_ms->price : null,
                        property_exists($value_ms, 'deadline') ? $value_ms->deadline : null,
                        property_exists($value_ms, 'capital') && property_exists($value_ms->capital, 'deadline') ? $value_ms->capital->deadline : null,
                        property_exists($value_ms, 'capital') && property_exists($value_ms->capital, 'price') ? $value_ms->capital->price : null,
                        property_exists($value_ms, 'interior') && property_exists($value_ms->interior, 'deadline') ? $value_ms->interior->deadline : null,
                        property_exists($value_ms, 'interior') && property_exists($value_ms->interior, 'price') ? $value_ms->interior->price : null
                    ));
                }
            } else {
                $dataRegion = array_merge($dataRegion, $this->formatDataToViewSimplified(
                    $value["id_regiao"],
                    $value["id_estado"],
                    $value["valor"],
                    $value["qtd_dias"],
                    $value["capital_qtd_dias"],
                    $value["capital_valor"],
                    $value["interior_qtd_dias"],
                    $value["interior_valor"]
                ));
            }
        }

        $this->data['shipping_company_id'] = $id;
        $this->data['dataRegion'] = $dataRegion;
        $this->data['store_id'] = $store_id;
        $this->data['freight_seller'] = $this->ms_freight_tables->use_ms_shipping ? $provider_data->freight_seller : null;
        $this->data['data_form_regions'] = $this->getDataRegionToState();

        $this->render_template('shipping_company/simplified/shipping_company_simplified', $this->data);
    }

    private function getDataRegionToState(): array
    {
        $response = array(
            'norte'         => array(),
            'nordeste'      => array(),
            'centro-oeste'  => array(),
            'sudeste'       => array(),
            'sul'           => array(),
        );
        foreach ($this->model_shipping_company->getRegions() as $region) {
            $response[strtolower($region['Nome'])] = array();

            foreach ($this->model_shipping_company->getStatesByRegion($region['idRegiao']) as $state) {
                $response[strtolower($region['Nome'])][strtolower($state['Uf'])] = $state['Nome'];
            }
        }

        return $response;
    }

    private function formatDataToViewSimplified($region_id, $state_id, $price, $deadline, $deadline_capital, $price_capital, $deadline_interior, $price_interior): array
    {
        $region      = $this->model_shipping_company->getRegionById($region_id);
        $region_name = strtolower($region['Nome']);

        if (is_null($state_id)) {
            $dataRegion[$region_name]["qtd_dias"] = $deadline;
            $dataRegion[$region_name]["valor"] = trim(money($price, ''));

            if ($dataRegion[$region_name]["qtd_dias"] > 0 && $dataRegion[$region_name]["valor"] > 0) {
                $dataRegion[$region_name]["icon"] = " <i class='fa fa-circle' style='color:green; font-size:0.6em;'></i>";
            }
        } else {
            $state      = $this->model_shipping_company->getStateById($state_id);
            $state_name = strtolower($state['Uf']);

            $dataRegion[$state_name]["capital_qtd_dias"]    = $deadline_capital;
            $dataRegion[$state_name]["capital_valor"]       = trim(money($price_capital, ''));
            $dataRegion[$state_name]["interior_qtd_dias"]   = $deadline_interior;
            $dataRegion[$state_name]["interior_valor"]      = trim(money($price_interior, ''));

            if (
                $dataRegion[$state_name]["capital_qtd_dias"]    > 0 ||
                $dataRegion[$state_name]["interior_valor"]      > 0 ||
                $dataRegion[$state_name]["capital_valor"]       > 0 ||
                $dataRegion[$state_name]["interior_qtd_dias"]   > 0
            ) {
                $dataRegion[$state_name]["icon"] = 'green';
            }
        }

        return $dataRegion;
    }

    public function updatetableshippingsimplified($shipping_company_id)
    {
        $idProvider     = $shipping_company_id;
        $dados          = $this->postClean();
        $store_id       = $dados['store_id'] ?? null;
        $freight_seller = $dados['freight_seller'] ?? null;

        try {
            $parsedData = $this->parseDataValidate($dados, $shipping_company_id);
            $result = $this->getDataFormattedSimplifiedToCreate($parsedData, $this->ms_freight_tables->use_ms_shipping);
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . '<br>' . $exception->getMessage());
            redirect("shippingcompany/tableshippingsimplified/$shipping_company_id/$store_id", 'refresh');
        }

        $update = true;
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                if ($freight_seller == 1) {
                    $this->ms_freight_tables->setStore($store_id);
                }
                $this->ms_freight_tables->createSimplifiedTable($result, $shipping_company_id);
                if ($this->ms_freight_tables->use_ms_shipping_replica) {
                    $this->model_shipping_company->setPriceRegionShipping(
                        $this->getDataFormattedSimplifiedToCreate($parsedData, false), $idProvider
                    );
                    $this->model_shipping_company->setTypeTableShipping($idProvider, 2);
                }
            } catch (Exception $exception) {
                $update = false;
                $this->session->set_flashdata('error', implode('<br>', $this->ms_freight_tables->getErrorFormatted($exception->getMessage())));
                redirect('shippingcompany/index', 'refresh');
            }
        } else {
            $update = $this->model_shipping_company->setPriceRegionShipping($result, $idProvider);
            $this->model_shipping_company->setTypeTableShipping($idProvider, 2);
        }

        if ($update) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            if (isset($refresh)) {
                redirect("shippingcompany/tableshippingsimplified/$shipping_company_id/$store_id", 'refresh');
            }
            redirect("shippingcompany/tableshippingsimplified/$shipping_company_id/$store_id", 'refresh');
        }

        $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        redirect('shippingcompany/index', 'refresh');
    }

  private function parseDataValidate($dados, $shipping_company_id)
  {
    $data = array();
    // dd($dados);
    foreach ($dados as $key => $value) {
      if ($key === "norte_qtd_dias") {
        $norte_qtd_dias = $this->postClean('norte_qtd_dias');
        $norte_valor = $this->postClean('norte_valor');
        $norte_valor = str_replace('.', '', $norte_valor);
        $norte_valor = str_replace(',', '.', $norte_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'valor' => $norte_valor,
          'qtd_dias' => (int) $norte_qtd_dias,
        ));
      } if ($key == "nordeste_qtd_dias") {
        $nordeste_qtd_dias = $this->postClean('nordeste_qtd_dias');
        $nordeste_valor = $this->postClean('nordeste_valor');
        $nordeste_valor = str_replace('.', '', $nordeste_valor);
        $nordeste_valor = str_replace(',', '.', $nordeste_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'valor' => $nordeste_valor,
          'qtd_dias' => (int) $nordeste_qtd_dias,
        ));
        // dd($data);
      } if ($key == "sudeste_qtd_dias") {
        $sudeste_qtd_dias = $this->postClean('sudeste_qtd_dias');
        $sudeste_valor = $this->postClean('sudeste_valor');
        $sudeste_valor = str_replace('.', '', $sudeste_valor);
        $sudeste_valor = str_replace(',', '.', $sudeste_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 3,
          'valor' => $sudeste_valor,
          'qtd_dias' => (int) $sudeste_qtd_dias,
        ));
      } if ($key == "sul_qtd_dias") {
        $sul_qtd_dias = $this->postClean('sul_qtd_dias');
        $sul_valor = $this->postClean('sul_valor');
        // $sul_valor = str_replace(',', '.', $sul_valor);
        $sul_valor = str_replace('.', '', $sul_valor);
        $sul_valor = str_replace(',', '.', $sul_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 4,
          'valor' => $sul_valor,
          'qtd_dias' => (int) $sul_qtd_dias,
        ));
      } if ($key == "centro-oeste_qtd_dias") {
        $centro_oeste_qtd_dias = $this->postClean('centro-oeste_qtd_dias');
        $centro_oeste_valor = $this->postClean('centro-oeste_valor');
        // $centro_oeste_valor = str_replace(',', '.', $centro_oeste_valor);
        $centro_oeste_valor = str_replace('.', '', $centro_oeste_valor);
        $centro_oeste_valor = str_replace(',', '.', $centro_oeste_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 5,
          'valor' => $centro_oeste_valor,
          'qtd_dias' => (int) $centro_oeste_qtd_dias,
        ));
      } if ($key == "ac_capital_qtd_dias") {
        $ac_capital_qtd_dias = $this->postClean('ac_capital_qtd_dias');
        $ac_capital_valor = $this->postClean('ac_capital_valor');
        $ac_interior_qtd_dias = $this->postClean('ac_interior_qtd_dias');
        $ac_interior_valor = $this->postClean('ac_interior_valor');
        $ac_capital_valor = str_replace('.', '', $ac_capital_valor);
        $ac_capital_valor = str_replace(',', '.', $ac_capital_valor);
        $ac_interior_valor = str_replace('.', '', $ac_interior_valor);
        $ac_interior_valor = str_replace(',', '.', $ac_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 1,
          'capital_valor' => $ac_capital_valor,
          'capital_qtd_dias' => $ac_capital_qtd_dias,
          'interior_valor' =>   $ac_interior_valor,
          'interior_qtd_dias' => (int) $ac_interior_qtd_dias,
        ));
      } if ($key == "ap_capital_qtd_dias") {
        $ap_capital_qtd_dias = $this->postClean('ap_capital_qtd_dias');
        $ap_capital_valor = $this->postClean('ap_capital_valor');
        $ap_interior_qtd_dias = $this->postClean('ap_interior_qtd_dias');
        $ap_interior_valor = $this->postClean('ap_interior_valor');
        $ap_capital_valor = str_replace('.', '', $ap_capital_valor);
        $ap_capital_valor = str_replace(',', '.', $ap_capital_valor);
        $ap_interior_valor = str_replace('.', '', $ap_interior_valor);
        $ap_interior_valor = str_replace(',', '.', $ap_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 3,
          'capital_valor' => $ap_capital_valor,
          'capital_qtd_dias' => $ap_capital_qtd_dias,
          'interior_valor' =>   $ap_interior_valor,
          'interior_qtd_dias' => (int) $ap_interior_qtd_dias,
        ));
      } if ($key == "am_capital_qtd_dias") {
        $am_capital_qtd_dias = $this->postClean('am_capital_qtd_dias');
        $am_capital_valor = $this->postClean('am_capital_valor');
        $am_interior_qtd_dias = $this->postClean('am_interior_qtd_dias');
        $am_interior_valor = $this->postClean('am_interior_valor');
        $am_capital_valor = str_replace('.', '', $am_capital_valor);
        $am_capital_valor = str_replace(',', '.', $am_capital_valor);
        $am_interior_valor = str_replace('.', '', $am_interior_valor);
        $am_interior_valor = str_replace(',', '.', $am_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 4,
          'capital_valor' => $am_capital_valor,
          'capital_qtd_dias' => $am_capital_qtd_dias,
          'interior_valor' =>   $am_interior_valor,
          'interior_qtd_dias' => (int) $am_interior_qtd_dias,
        ));
      } if ($key == "pa_capital_qtd_dias") {
        $pa_capital_qtd_dias = $this->postClean('pa_capital_qtd_dias');
        $pa_capital_valor = $this->postClean('pa_capital_valor');
        $pa_interior_qtd_dias = $this->postClean('pa_interior_qtd_dias');
        $pa_interior_valor = $this->postClean('pa_interior_valor');
        $pa_capital_valor = str_replace('.', '', $pa_capital_valor);
        $pa_capital_valor = str_replace(',', '.', $pa_capital_valor);
        $pa_interior_valor = str_replace('.', '', $pa_interior_valor);
        $pa_interior_valor = str_replace(',', '.', $pa_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 14,
          'capital_valor' => $pa_capital_valor,
          'capital_qtd_dias' => $pa_capital_qtd_dias,
          'interior_valor' =>   $pa_interior_valor,
          'interior_qtd_dias' => (int) $pa_interior_qtd_dias,
        ));
      } if ($key == "ro_capital_qtd_dias") {
        $ro_capital_qtd_dias = $this->postClean('ro_capital_qtd_dias');
        $ro_capital_valor = $this->postClean('ro_capital_valor');
        $ro_interior_qtd_dias = $this->postClean('ro_interior_qtd_dias');
        $ro_interior_valor = $this->postClean('ro_interior_valor');
        $ro_capital_valor = str_replace('.', '', $ro_capital_valor);
        $ro_capital_valor = str_replace(',', '.', $ro_capital_valor);
        $ro_interior_valor = str_replace('.', '', $ro_interior_valor);
        $ro_interior_valor = str_replace(',', '.', $ro_interior_valor);
        
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 22,
          'capital_valor' => $ro_capital_valor,
          'capital_qtd_dias' => $ro_capital_qtd_dias,
          'interior_valor' =>   $ro_interior_valor,
          'interior_qtd_dias' => (int) $ro_interior_qtd_dias,
        ));
      } if ($key == "rr_capital_qtd_dias") {
        $rr_capital_qtd_dias = $this->postClean('rr_capital_qtd_dias');
        $rr_capital_valor = $this->postClean('rr_capital_valor');
        $rr_interior_qtd_dias = $this->postClean('rr_interior_qtd_dias');
        $rr_interior_valor = $this->postClean('rr_interior_valor');
        $rr_capital_valor = str_replace('.', '', $rr_capital_valor);
        $rr_capital_valor = str_replace(',', '.', $rr_capital_valor);
        $rr_interior_valor = str_replace('.', '', $rr_interior_valor);
        $rr_interior_valor = str_replace(',', '.', $rr_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 23,
          'capital_valor' => $rr_capital_valor,
          'capital_qtd_dias' => $rr_capital_qtd_dias,
          'interior_valor' =>   $rr_interior_valor,
          'interior_qtd_dias' => (int) $rr_interior_qtd_dias,
        ));
      } if ($key == "to_capital_qtd_dias") {
        $to_capital_qtd_dias = $this->postClean('to_capital_qtd_dias');
        $to_capital_valor = $this->postClean('to_capital_valor');
        $to_interior_qtd_dias = $this->postClean('to_interior_qtd_dias');
        $to_interior_valor = $this->postClean('to_interior_valor');
        $to_capital_valor = str_replace('.', '', $to_capital_valor);
        $to_capital_valor = str_replace(',', '.', $to_capital_valor);
        $to_interior_valor = str_replace('.', '', $to_interior_valor);
        $to_interior_valor = str_replace(',', '.', $to_interior_valor);
        // $to_interior_valor = str_replace(',', '.', $to_interior_valor);
          
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 1,
          'id_estado' => 27,
          'capital_valor' => $to_capital_valor,
          'capital_qtd_dias' => $to_capital_qtd_dias,
          'interior_valor' =>   $to_interior_valor,
          'interior_qtd_dias' => $to_interior_qtd_dias,
        ));
        // dd($data);
      } if ($key == "al_capital_qtd_dias") {
        $al_capital_qtd_dias = $this->postClean('al_capital_qtd_dias');
        $al_capital_valor = $this->postClean('al_capital_valor');
        $al_interior_qtd_dias = $this->postClean('al_interior_qtd_dias');
        $al_interior_valor = $this->postClean('al_interior_valor');
        $al_capital_valor = str_replace('.', '', $al_capital_valor);
        $al_capital_valor = str_replace(',', '.', $al_capital_valor);
        $al_interior_valor = str_replace('.', '', $al_interior_valor);
        $al_interior_valor = str_replace(',', '.', $al_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 2,
          'capital_valor' => $al_capital_valor,
          'capital_qtd_dias' => $al_capital_qtd_dias,
          'interior_valor' =>   $al_interior_valor,
          'interior_qtd_dias' => (int) $al_interior_qtd_dias,
        ));
      } if ($key == "ba_capital_qtd_dias") {
        $ba_capital_qtd_dias = $this->postClean('ba_capital_qtd_dias');
        $ba_capital_valor = $this->postClean('ba_capital_valor');
        $ba_interior_qtd_dias = $this->postClean('ba_interior_qtd_dias');
        $ba_interior_valor = $this->postClean('ba_interior_valor');
        $ba_capital_valor = str_replace('.', '', $ba_capital_valor);
        $ba_capital_valor = str_replace(',', '.', $ba_capital_valor);
        $ba_interior_valor = str_replace('.', '', $ba_interior_valor);
        $ba_interior_valor = str_replace(',', '.', $ba_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 5,
          'capital_valor' => $ba_capital_valor,
          'capital_qtd_dias' => $ba_capital_qtd_dias,
          'interior_valor' =>   $ba_interior_valor,
          'interior_qtd_dias' => (int) $ba_interior_qtd_dias,
        ));
      } if ($key == "ce_capital_qtd_dias") {
        $ce_capital_qtd_dias = $this->postClean('ce_capital_qtd_dias');
        $ce_capital_valor = $this->postClean('ce_capital_valor');
        $ce_interior_qtd_dias = $this->postClean('ce_interior_qtd_dias');
        $ce_interior_valor = $this->postClean('ce_interior_valor');
        $ce_capital_valor = str_replace('.', '', $ce_capital_valor);
        $ce_capital_valor = str_replace(',', '.', $ce_capital_valor);
        $ce_interior_valor = str_replace('.', '', $ce_interior_valor);
        $ce_interior_valor = str_replace(',', '.', $ce_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 6,
          'capital_valor' => $ce_capital_valor,
          'capital_qtd_dias' => $ce_capital_qtd_dias,
          'interior_valor' =>   $ce_interior_valor,
          'interior_qtd_dias' => (int) $ce_interior_qtd_dias,
        ));
      } if ($key == "ma_capital_qtd_dias") {
        $ma_capital_qtd_dias = $this->postClean('ma_capital_qtd_dias');
        $ma_capital_valor = $this->postClean('ma_capital_valor');
        $ma_interior_qtd_dias = $this->postClean('ma_interior_qtd_dias');
        $ma_interior_valor = $this->postClean('ma_interior_valor');
        $ma_capital_valor = str_replace('.', '', $ma_capital_valor);
        $ma_capital_valor = str_replace(',', '.', $ma_capital_valor);
        $ma_interior_valor = str_replace('.', '', $ma_interior_valor);
        $ma_interior_valor = str_replace(',', '.', $ma_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 10,
          'capital_valor' => $ma_capital_valor,
          'capital_qtd_dias' => $ma_capital_qtd_dias,
          'interior_valor' =>   $ma_interior_valor,
          'interior_qtd_dias' => (int) $ma_interior_qtd_dias,
        ));
      } if ($key == "pb_capital_qtd_dias") {
        $pb_capital_qtd_dias = $this->postClean('pb_capital_qtd_dias');
        $pb_capital_valor = $this->postClean('pb_capital_valor');
        $pb_interior_qtd_dias = $this->postClean('pb_interior_qtd_dias');
        $pb_interior_valor = $this->postClean('pb_interior_valor');
        $pb_capital_valor = str_replace('.', '', $pb_capital_valor);
        $pb_capital_valor = str_replace(',', '.', $pb_capital_valor);
        $pb_interior_valor = str_replace('.', '', $pb_interior_valor);
        $pb_interior_valor = str_replace(',', '.', $pb_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 15,
          'capital_valor' => $pb_capital_valor,
          'capital_qtd_dias' => $pb_capital_qtd_dias,
          'interior_valor' =>   $pb_interior_valor,
          'interior_qtd_dias' => (int) $pb_interior_qtd_dias,
        ));
      } if ($key == "pe_capital_qtd_dias") {
        $pe_capital_qtd_dias = $this->postClean('pe_capital_qtd_dias');
        $pe_capital_valor = $this->postClean('pe_capital_valor');
        $pe_interior_qtd_dias = $this->postClean('pe_interior_qtd_dias');
        $pe_interior_valor = $this->postClean('pe_interior_valor');
        $pe_capital_valor = str_replace('.', '', $pe_capital_valor);
        $pe_capital_valor = str_replace(',', '.', $pe_capital_valor);
        $pe_interior_valor = str_replace('.', '', $pe_interior_valor);
        $pe_interior_valor = str_replace(',', '.', $pe_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 17,
          'capital_valor' => $pe_capital_valor,
          'capital_qtd_dias' => $pe_capital_qtd_dias,
          'interior_valor' =>   $pe_interior_valor,
          'interior_qtd_dias' => (int) $pe_interior_qtd_dias,
        ));
      } if ($key == "pi_capital_qtd_dias") {
        $pi_capital_qtd_dias = $this->postClean('pi_capital_qtd_dias');
        $pi_capital_valor = $this->postClean('pi_capital_valor');
        $pi_interior_qtd_dias = $this->postClean('pi_interior_qtd_dias');
        $pi_interior_valor = $this->postClean('pi_interior_valor');
        $pi_capital_valor = str_replace('.', '', $pi_capital_valor);
        $pi_capital_valor = str_replace(',', '.', $pi_capital_valor);
        $pi_interior_valor = str_replace('.', '', $pi_interior_valor);
        $pi_interior_valor = str_replace(',', '.', $pi_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 18,
          'capital_valor' => $pi_capital_valor,
          'capital_qtd_dias' => $pi_capital_qtd_dias,
          'interior_valor' =>   $pi_interior_valor,
          'interior_qtd_dias' => (int) $pi_interior_qtd_dias,
        ));
      } if ($key == "rn_capital_qtd_dias") {
        $rn_capital_qtd_dias = $this->postClean('rn_capital_qtd_dias');
        $rn_capital_valor = $this->postClean('rn_capital_valor');
        $rn_interior_qtd_dias = $this->postClean('rn_interior_qtd_dias');
        $rn_interior_valor = $this->postClean('rn_interior_valor');
        $rn_capital_valor = str_replace('.', '', $rn_capital_valor);
        $rn_capital_valor = str_replace(',', '.', $rn_capital_valor);
        $rn_interior_valor = str_replace('.', '', $rn_interior_valor);
        $rn_interior_valor = str_replace(',', '.', $rn_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 20,
          'capital_valor' => $rn_capital_valor,
          'capital_qtd_dias' => $rn_capital_qtd_dias,
          'interior_valor' =>   $rn_interior_valor,
          'interior_qtd_dias' => (int) $rn_interior_qtd_dias,
        ));
      } if ($key == "se_capital_qtd_dias") {
        $se_capital_qtd_dias = $this->postClean('se_capital_qtd_dias');
        $se_capital_valor = $this->postClean('se_capital_valor');
        $se_interior_qtd_dias = $this->postClean('se_interior_qtd_dias');
        $se_interior_valor = $this->postClean('se_interior_valor');
        // $se_capital_valor = str_replace(',', '.', $se_capital_valor);
        $se_capital_valor = str_replace('.', '', $se_capital_valor);
        $se_capital_valor = str_replace(',', '.', $se_capital_valor);
        $se_interior_valor = str_replace('.', '', $se_interior_valor);
        $se_interior_valor = str_replace(',', '.', $se_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 2,
          'id_estado' => 26,
          'capital_valor' => $se_capital_valor,
          'capital_qtd_dias' => $se_capital_qtd_dias,
          'interior_valor' =>   $se_interior_valor,
          'interior_qtd_dias' => (int) $se_interior_qtd_dias,
        ));
      } if ($key == "go_capital_qtd_dias") {
        $go_capital_qtd_dias = $this->postClean('go_capital_qtd_dias');
        $go_capital_valor = $this->postClean('go_capital_valor');
        $go_interior_qtd_dias = $this->postClean('go_interior_qtd_dias');
        $go_interior_valor = $this->postClean('go_interior_valor');
        $go_capital_valor = str_replace('.', '', $go_capital_valor);
        $go_capital_valor = str_replace(',', '.', $go_capital_valor);
        $go_interior_valor = str_replace('.', '', $go_interior_valor);
        $go_interior_valor = str_replace(',', '.', $go_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 5,
          'id_estado' => 9,
          'capital_valor' => $go_capital_valor,
          'capital_qtd_dias' => $go_capital_qtd_dias,
          'interior_valor' =>   $go_interior_valor,
          'interior_qtd_dias' => (int) $go_interior_qtd_dias,
        ));
      } if ($key == "mt_capital_qtd_dias") {
        $mt_capital_qtd_dias = $this->postClean('mt_capital_qtd_dias');
        $mt_capital_valor = $this->postClean('mt_capital_valor');
        $mt_interior_qtd_dias = $this->postClean('mt_interior_qtd_dias');
        $mt_interior_valor = $this->postClean('mt_interior_valor');
        $mt_capital_valor = str_replace('.', '', $mt_capital_valor);
        $mt_capital_valor = str_replace(',', '.', $mt_capital_valor);
        $mt_interior_valor = str_replace('.', '', $mt_interior_valor);
        $mt_interior_valor = str_replace(',', '.', $mt_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 5,
          'id_estado' => 11,
          'capital_valor' => $mt_capital_valor,
          'capital_qtd_dias' => $mt_capital_qtd_dias,
          'interior_valor' =>   $mt_interior_valor,
          'interior_qtd_dias' => (int) $mt_interior_qtd_dias,
        ));
      } if ($key == "ms_capital_qtd_dias") {
        $ms_capital_qtd_dias = $this->postClean('ms_capital_qtd_dias');
        $ms_capital_valor = $this->postClean('ms_capital_valor');
        $ms_interior_qtd_dias = $this->postClean('ms_interior_qtd_dias');
        $ms_interior_valor = $this->postClean('ms_interior_valor');
        $ms_capital_valor = str_replace('.', '', $ms_capital_valor);
        $ms_capital_valor = str_replace(',', '.', $ms_capital_valor);
        $ms_interior_valor = str_replace('.', '', $ms_interior_valor);
        $ms_interior_valor = str_replace(',', '.', $ms_interior_valor);

        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 5,
          'id_estado' => 12,
          'capital_valor' => $ms_capital_valor,
          'capital_qtd_dias' => $ms_capital_qtd_dias,
          'interior_valor' =>   $ms_interior_valor,
          'interior_qtd_dias' => (int) $ms_interior_qtd_dias,
        ));
      } if ($key == "df_capital_qtd_dias") {
        $df_capital_qtd_dias = $this->postClean('df_capital_qtd_dias');
        $df_capital_valor = $this->postClean('df_capital_valor');
        $df_interior_qtd_dias = $this->postClean('df_interior_qtd_dias');
        $df_interior_valor = $this->postClean('df_interior_valor');
        $df_capital_valor = str_replace('.', '', $df_capital_valor);
        $df_capital_valor = str_replace(',', '.', $df_capital_valor);
        $df_interior_valor = str_replace('.', '', $df_interior_valor);
        $df_interior_valor = str_replace(',', '.', $df_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 5,
          'id_estado' => 7,
          'capital_valor' => $df_capital_valor,
          'capital_qtd_dias' => $df_capital_qtd_dias,
          'interior_valor' =>   $df_interior_valor,
          'interior_qtd_dias' => (int) $df_interior_qtd_dias,
        ));
      } if ($key == "es_capital_qtd_dias") {
        $es_capital_qtd_dias = $this->postClean('es_capital_qtd_dias');
        $es_capital_valor = $this->postClean('es_capital_valor');
        $es_interior_qtd_dias = $this->postClean('es_interior_qtd_dias');
        $es_interior_valor = $this->postClean('es_interior_valor');
        $es_capital_valor = str_replace('.', '', $es_capital_valor);
        $es_capital_valor = str_replace(',', '.', $es_capital_valor);
        $es_interior_valor = str_replace('.', '', $es_interior_valor);
        $es_interior_valor = str_replace(',', '.', $es_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 3,
          'id_estado' => 8,
          'capital_valor' => $es_capital_valor,
          'capital_qtd_dias' => $es_capital_qtd_dias,
          'interior_valor' =>   $es_interior_valor,
          'interior_qtd_dias' => (int) $es_interior_qtd_dias,
        ));
      } if ($key == "mg_capital_qtd_dias") {
        $mg_capital_qtd_dias = $this->postClean('mg_capital_qtd_dias');
        $mg_capital_valor = $this->postClean('mg_capital_valor');
        $mg_interior_qtd_dias = $this->postClean('mg_interior_qtd_dias');
        $mg_interior_valor = $this->postClean('mg_interior_valor');
        $mg_capital_valor = str_replace('.', '', $mg_capital_valor);
        $mg_capital_valor = str_replace(',', '.', $mg_capital_valor);
        $mg_interior_valor = str_replace('.', '', $mg_interior_valor);
        $mg_interior_valor = str_replace(',', '.', $mg_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 3,
          'id_estado' => 13,
          'capital_valor' => $mg_capital_valor,
          'capital_qtd_dias' => $mg_capital_qtd_dias,
          'interior_valor' =>   $mg_interior_valor,
          'interior_qtd_dias' => (int) $mg_interior_qtd_dias,
        ));
      } if ($key == "rj_capital_qtd_dias") {
        $rj_capital_qtd_dias = $this->postClean('rj_capital_qtd_dias');
        $rj_capital_valor = $this->postClean('rj_capital_valor');
        $rj_interior_qtd_dias = $this->postClean('rj_interior_qtd_dias');
        $rj_interior_valor = $this->postClean('rj_interior_valor');
        $rj_capital_valor = str_replace('.', '', $rj_capital_valor);
        $rj_capital_valor = str_replace(',', '.', $rj_capital_valor);
        $rj_interior_valor = str_replace('.', '', $rj_interior_valor);
        $rj_interior_valor = str_replace(',', '.', $rj_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 3,
          'id_estado' => 19,
          'capital_valor' => $rj_capital_valor,
          'capital_qtd_dias' => $rj_capital_qtd_dias,
          'interior_valor' =>   $rj_interior_valor,
          'interior_qtd_dias' => (int) $rj_interior_qtd_dias,
        ));
      } if ($key == "sp_capital_qtd_dias") {
        $sp_capital_qtd_dias = $this->postClean('sp_capital_qtd_dias');
        $sp_capital_valor = $this->postClean('sp_capital_valor');
        $sp_interior_qtd_dias = $this->postClean('sp_interior_qtd_dias');
        $sp_interior_valor = $this->postClean('sp_interior_valor');
        $sp_capital_valor = str_replace('.', '', $sp_capital_valor);
        $sp_capital_valor = str_replace(',', '.', $sp_capital_valor);
        $sp_interior_valor = str_replace('.', '', $sp_interior_valor);
        $sp_interior_valor = str_replace(',', '.', $sp_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 3,
          'id_estado' => 25,
          'capital_valor' => $sp_capital_valor,
          'capital_qtd_dias' => $sp_capital_qtd_dias,
          'interior_valor' =>   $sp_interior_valor,
          'interior_qtd_dias' => (int) $sp_interior_qtd_dias,
        ));
      } if ($key == "pr_capital_qtd_dias") {
        $pr_capital_qtd_dias = $this->postClean('pr_capital_qtd_dias');
        $pr_capital_valor = $this->postClean('pr_capital_valor');
        $pr_interior_qtd_dias = $this->postClean('pr_interior_qtd_dias');
        $pr_interior_valor = $this->postClean('pr_interior_valor');
        $pr_capital_valor = str_replace('.', '', $pr_capital_valor);
        $pr_capital_valor = str_replace(',', '.', $pr_capital_valor);
        $pr_interior_valor = str_replace('.', '', $pr_interior_valor);
        $pr_interior_valor = str_replace(',', '.', $pr_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 4,
          'id_estado' => 16,
          'capital_valor' => $pr_capital_valor,
          'capital_qtd_dias' => $pr_capital_qtd_dias,
          'interior_valor' =>   $pr_interior_valor,
          'interior_qtd_dias' => (int) $pr_interior_qtd_dias,
        ));
      } if ($key == "rs_capital_qtd_dias") {
        $rs_capital_qtd_dias = $this->postClean('rs_capital_qtd_dias');
        $rs_capital_valor = $this->postClean('rs_capital_valor');
        $rs_interior_qtd_dias = $this->postClean('rs_interior_qtd_dias');
        $rs_interior_valor = $this->postClean('rs_interior_valor');
        $rs_capital_valor = str_replace('.', '', $rs_capital_valor);
        $rs_capital_valor = str_replace(',', '.', $rs_capital_valor);
        $rs_interior_valor = str_replace('.', '', $rs_interior_valor);
        $rs_interior_valor = str_replace(',', '.', $rs_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 4,
          'id_estado' => 21,
          'capital_valor' => $rs_capital_valor,
          'capital_qtd_dias' => $rs_capital_qtd_dias,
          'interior_valor' =>   $rs_interior_valor,
          'interior_qtd_dias' => (int) $rs_interior_qtd_dias,
        ));
      } if ($key == "sc_capital_qtd_dias") {
        $sc_capital_qtd_dias = $this->postClean('sc_capital_qtd_dias');
        $sc_capital_valor = $this->postClean('sc_capital_valor');
        $sc_interior_qtd_dias = $this->postClean('sc_interior_qtd_dias');
        $sc_interior_valor = $this->postClean('sc_interior_valor'); 
        $sc_capital_valor = str_replace('.', '', $sc_capital_valor);
        $sc_capital_valor = str_replace(',', '.', $sc_capital_valor);
        $sc_interior_valor = str_replace('.', '', $sc_interior_valor);
        $sc_interior_valor = str_replace(',', '.', $sc_interior_valor);
        array_push($data, array(
          'id_provider' => $shipping_company_id,
          'id_regiao' => 4,
          'id_estado' => 24,
          'capital_valor' => $sc_capital_valor,
          'capital_qtd_dias' => $sc_capital_qtd_dias,
          'interior_valor' =>   $sc_interior_valor,
          'interior_qtd_dias' => (int) $sc_interior_qtd_dias,
        ));
      }
    }
    return $data;
  }

  public function typeTableShipping()
  {
    $result = $this->model_shipping_company->getTypeTableShipping($this->postClean('id_transportadora'));

    if (count($result) > 0 && isset($result[0]["id_type"]) && !empty($result[0]["id_type"])) {
      $result = $result[0]["id_type"];
      echo json_encode(array('id_type' => "$result"));
    }
  }

    public function createsimplified()
    {
        // Verifica se tem permissão
        if(!in_array('createCarrierRegistration', $this->permission)) {
           redirect('shippingcompany', 'refresh');
        }

        $this->data['stores'] = $this->model_stores->getStoresData();

        if ($this->postClean()) {
            if ($this->validateFieldsShippingCompany($this->postClean(), 'create', null, true)) {
                $data = $this->getDataFormattedToCreate(true, true, $this->ms_freight_tables->use_ms_shipping);

                if ($this->model_stores->checkIfTheStoreIsMine($data['store_id']) === false) {
                    $this->session->set_flashdata('error', 'Loja não encontrada');
                    redirect('shippingcompany/createsimplified', 'refresh');
                }

                $this->load->model('model_shipping_company', 'model_shipping_company');

                if ($this->ms_freight_tables->use_ms_shipping) {
                    try {
                        $this->ms_freight_tables->setStore($data['store_id']);
                        $dataStoreId = $data['store_id'];
                        unset($data['store_id']);

                        $insert = $this->ms_freight_tables->createShippingCompany($data);
                        $insert = $insert->id;
                        if ($this->ms_freight_tables->use_ms_shipping_replica) {
                            $this->model_shipping_company->createsimplified($this->getDataFormattedToCreate(true, true, false));
                        }
                    } catch (Exception $exception) {
                        $this->session->set_flashdata('error', implode('<br>', $this->ms_freight_tables->getErrorFormatted($exception->getMessage())));
                        $insert = false;
                    }
                } else {
                    $insert = $this->model_shipping_company->createsimplified($data);
                }

                if ($insert) {
                    if ($this->postClean('active_token_api')) {
                        $data['token_api'] = $this->createTokenAPI($insert, $this->postClean('responsible_email'));
                        $this->model_shipping_company->update_token_simplified($data, $insert);
                    }
                    $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                    redirect('shippingcompany', 'refresh');
                }
            } else {
                if (count($this->postClean())) {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                }
            }
        }

        $this->data['page_title'] = $this->lang->line('application_register_provider');
        $this->render_template('shipping_company/simplified/createsimplified', $this->data);
    }
  
    public function updatesimplified($id, $store_id = null)
    {
        // Verifica se tem permissão
        if(!in_array('updateCarrierRegistration', $this->permission)) {
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        // Verifica se a transportadora existe
        $shipping_company_not_found = false;
        $provider_data = null;
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $this->ms_freight_tables->setStore($store_id);
                $provider_data = $this->ms_freight_tables->getShippingCompany($id);
                foreach ($provider_data->store_id as $store_id_validate) {
                    // Usuário não gerencia a empresa principal e a transportadora é do seller center.
                    if ($this->data['usercomp'] != 1 && $provider_data->freight_seller == 0) {
                        $shipping_company_not_found = true;
                        break;
                    }
                    if ($this->model_stores->checkIfTheStoreIsMine($store_id_validate) === false) {
                        $shipping_company_not_found = true;
                        break;
                    }
                }
            } catch (Exception $exception) {
                $shipping_company_not_found = true;
            }
        } else {
            $provider_data = $this->model_shipping_company->getShippingCompany($id);
            $shipping_company_not_found = $provider_data === null || $provider_data['store_id'] === null || $this->model_stores->checkIfTheStoreIsMine($provider_data['store_id']) === false;
        }

        if ($shipping_company_not_found || !$provider_data) {
            $this->session->set_flashdata('error', 'Transportadora não encontrada');
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['fields'] = $this->getDataFormattedToView($provider_data);

        $this->validateFieldsShippingCompany($this->postClean(), 'update', $id, true);

        if ($this->validateFieldsShippingCompany($this->postClean(), 'update', $id, true)) {
            $data   = $this->getDataFormattedToCreate(true, false, $this->ms_freight_tables->use_ms_shipping);
            $update = true;

            if ($this->ms_freight_tables->use_ms_shipping) {
                try {
                    $this->ms_freight_tables->setStore($data['store_id']);
                    unset($data['store_id']);
                    $this->ms_freight_tables->updateShippingCompany($id, $data);
                    if ($this->ms_freight_tables->use_ms_shipping_replica) {
                        $this->model_shipping_company->update($this->getDataFormattedToCreate(true, false, false), $id);
                    }
                } catch (Exception $exception) {
                    $this->session->set_flashdata('error', implode('<br>', $this->ms_freight_tables->getErrorFormatted($exception->getMessage())));
                    $update = false;
                }
            } else {
                $update = $this->model_shipping_company->update($data, $id);
            }
          
            if($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('shippingcompany/', 'refresh');
            }
        } else {
            if (count($this->postClean())) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
        }

        $this->data['page_title'] = $this->lang->line('application_register_provider');
        $this->render_template('shipping_company/simplified/updatesimplified', $this->data);
    }

    /**
     * Valida os campos enviados no formulário.
     *
     * @param   array   $fields Campos enviados via POST.
     * @param   string   $type  Tipo de validação. (create | update).
     * @return  bool            Os campos estão válidos.
     */
    private function validateFieldsShippingCompany(array $fields, string $type, int $shippingCompany = null, bool $simplified = false): bool
    {
        $this->form_validation->set_data($fields);

        $this->form_validation->set_rules('name', $this->lang->line('application_name'), 'required');
        $this->form_validation->set_rules('raz_soc', $this->lang->line('application_raz_soc'), 'required');
        $this->form_validation->set_rules('phone', $this->lang->line('application_phone'), 'required');

        if ($type === 'create') {
            $validate = $this->ms_freight_tables->use_ms_shipping ? 'required' : 'required|callback_checkCnpjAvailable[' . $this->postClean($simplified ? 'store' : "slc_store") . ']';
            $this->form_validation->set_rules('cnpj', $this->lang->line('application_cnpj'), $validate);
        } elseif ($type === 'update' && $shippingCompany) {
            $validate = $this->ms_freight_tables->use_ms_shipping ? 'required' : 'required|callback_checkCnpjAvailable[' . $this->postClean($simplified ? 'store' : "slc_store") . ','.$shippingCompany.']';
            $this->form_validation->set_rules('cnpj', $this->lang->line('application_cnpj'), $validate);
        }

        $this->form_validation->set_rules('responsible_name', $this->lang->line('application_responsible_name'), 'required');
        $this->form_validation->set_rules('responsible_email', $this->lang->line('application_responsible_email'), 'required|valid_email');

        $this->form_validation->set_rules('slc_tipo_cubage', $this->lang->line('application_cubage_factor'), 'required');
        $typeCubage = $this->postClean('slc_tipo_cubage') === "Sim";
        $this->form_validation->set_rules('cubage_factor', $this->lang->line('application_cubage_factor'), $typeCubage ? 'required' : 'trim');

        $this->form_validation->set_rules('ad_valorem', $this->lang->line('application_ad_valorem'), 'trim');
        $this->form_validation->set_rules('gris', $this->lang->line('application_gris'), 'trim');
        $this->form_validation->set_rules('toll', $this->lang->line('application_toll'), 'trim');
        $this->form_validation->set_rules('shipping_revenue', $this->lang->line('application_shipping_revenue'), 'trim');

        // É cadastro simplificado.
        if (!$simplified) {
            // Inscrição Estadual
            $exempted = $this->postClean('exempted') != "1";
            $this->form_validation->set_rules('txt_insc_estadual', $this->lang->line('application_iest'), $exempted ? 'required|callback_checkInscricaoEstadual['.$this->postClean("addr_uf").']' : 'trim');
            $this->form_validation->set_rules('addr_uf', $this->lang->line('application_uf'), $exempted ? 'required' : 'trim');

            // Se precisar gerar a chave, faz a validação.
            $activeTokenApi = $this->postClean('active_token_api');
            //$this->form_validation->set_rules('active_token_api', $this->lang->line('application_token'), $activeTokenApi ? 'required' : 'trim);
            $this->form_validation->set_rules('token_api', $this->lang->line('application_token'), $activeTokenApi ? 'required' : 'trim');

            $this->form_validation->set_rules('responsible_cpf', $this->lang->line('application_responsible_cpf'), 'required');
            $this->form_validation->set_rules('tracking_web_site', $this->lang->line('application_tracking_web_site'), 'trim|valid_url');
            $this->form_validation->set_rules('responsible_oper_name', $this->lang->line('application_responsible_oper_name'), 'trim');
            $this->form_validation->set_rules('responsible_oper_email', $this->lang->line('application_responsible_oper_email'), 'trim');
            $this->form_validation->set_rules('responsible_oper_cpf', $this->lang->line('application_responsible_oper_cpf'), 'trim');
            $this->form_validation->set_rules('responsible_finan_name', $this->lang->line('application_responsible_finan_name'), 'trim');
            $this->form_validation->set_rules('responsible_finan_email', $this->lang->line('application_responsible_finan_email'), 'trim');
            $this->form_validation->set_rules('responsible_finan_cpf', $this->lang->line('application_responsible_finan_cpf'), 'trim');
            $this->form_validation->set_rules('slc_tipo_provider', $this->lang->line('application_providers_type'), 'required');

        // Modo de cálculo do frete
        $this->form_validation->set_rules('freight_calculation_standard', 'Modo de cálculo do frete', 'required');
        $freight_calculation_standard = $this->postClean('freight_calculation_standard', true) === "PorPeso";
        $this->form_validation->set_rules('freight_calculation_standard', 'Modo de cálculo do frete', $freight_calculation_standard ? 'required' : 'trim');

        $this->form_validation->set_rules('slc_tipo_pagamento', $this->lang->line('application_billet_type_payment'), 'required');
        $typePayment = $this->postClean('slc_tipo_pagamento', true) === "Transferencia";
        $this->form_validation->set_rules('bank', $this->lang->line('application_name_bank'), $typePayment ? 'required' : 'trim');
        $this->form_validation->set_rules('agency', $this->lang->line('application_agency'), $typePayment ? 'required' : 'trim');
        $this->form_validation->set_rules('account_type', $this->lang->line('application_type_account'), $typePayment ? 'required' : 'trim');
        $this->form_validation->set_rules('account', $this->lang->line('application_bank_account'), $typePayment ? 'required' : 'trim');

            $this->form_validation->set_rules('txt_regiao_entrega', $this->lang->line('application_providers_region_delivery'), 'trim');
            $this->form_validation->set_rules('txt_regiao_coleta', $this->lang->line('application_providers_region_collect'), 'required');
            $this->form_validation->set_rules('txt_tempo_coleta', $this->lang->line('application_providers_time_collect'), 'trim');
            $this->form_validation->set_rules('txt_fluxo_fin', $this->lang->line('application_providers_finan_flow'), 'required');

            // Frete mínimo
            $this->form_validation->set_rules('slc_val_credito', $this->lang->line('application_providers_credit_value'), 'required');
            $valCredito = $this->postClean('slc_val_credito', true) === "Sim";
            $this->form_validation->set_rules('txt_val_credito', $this->lang->line('application_providers_credit_value'), $valCredito ? 'required' : 'trim');

            // Frete mínimo
            $this->form_validation->set_rules('slc_val_ship_min', $this->lang->line('application_providers_ship_min'), 'required');
            $valShipMin = $this->postClean('slc_val_ship_min', true) === "Sim";
            $this->form_validation->set_rules('txt_val_ship_min', $this->lang->line('application_providers_ship_min'), $valShipMin ? 'required' : 'trim');

            // Quantidade mínima
            $this->form_validation->set_rules('slc_qtd_min', $this->lang->line('application_providers_qtd_min'), 'required');
            $qtdMin = $this->postClean('slc_qtd_min', true) === "Sim";
            $this->form_validation->set_rules('txt_qtd_min', $this->lang->line('application_providers_qtd_min'), $qtdMin ? 'required' : 'trim');

            $this->form_validation->set_rules('slc_tipo_pagamento', $this->lang->line('application_billet_type_payment'), 'required');
            $this->form_validation->set_rules('txt_tipo_produto', $this->lang->line('application_providers_product_type'), 'trim');
            $this->form_validation->set_rules('txt_observacao', $this->lang->line('application_extract_obs'), 'trim');

            $this->form_validation->set_rules('freight_seller', $this->lang->line('application_contract_type'), 'required');
            $freightSeller = $this->postClean('freight_seller', true) == 1;
            $this->form_validation->set_rules('slc_store', $this->lang->line('application_store'), $freightSeller ? 'required|integer' : 'trim');
            $this->form_validation->set_rules('slc_company', $this->lang->line('application_company'), $freightSeller ? 'required|integer' : 'trim');
            $this->form_validation->set_rules('stores_sellercenter[]', $this->lang->line('application_stores'), $freightSeller ? 'trim' : 'required');
        } else {
            $this->form_validation->set_rules('store', $this->lang->line('application_store'), 'required|integer');
        }

        return $this->form_validation->run() === true;
    }

    public function create()
    {
        // Verifica se tem permissão
        if (!in_array('createCarrierRegistration', $this->permission) || $this->data['usercomp'] != 1) {
            redirect('shippingcompany', 'refresh');
        }

        $errorValidation = false;

        // Valida os campos do formulário.
        if (count($this->postClean())) {
            $errorValidation = $this->validateFieldsShippingCompany($this->postClean(), 'create') === false;

            if ($errorValidation) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
        }

        if (!$errorValidation && count($this->postClean())) {
            $data = $this->getDataFormattedToCreate(false, true, $this->ms_freight_tables->use_ms_shipping);

            if ($data['freight_seller'] == 1 && !empty($data['store_id']) && $this->model_stores->checkIfTheStoreIsMine($data['store_id']) === false) {
                $this->session->set_flashdata('error', 'Loja não encontrada');
                redirect('shippingcompany/createsimplified', 'refresh');
            }

            if ($this->ms_freight_tables->use_ms_shipping) {
                try {
                    $dataStoreId = $data['store_id'];
                    if ($data['freight_seller'] == 1) {
                        $this->ms_freight_tables->setStore($data['store_id']);
                        unset($data['store_id']);
                    }
                    $response = $this->ms_freight_tables->createShippingCompany($data);
                    $insert = $response->id;
                    if ($this->ms_freight_tables->use_ms_shipping_replica) {
                        $this->model_shipping_company->create($this->getDataFormattedToCreate(false, true, false), $this->postClean('freight_seller') == 0 ? $this->postClean('stores_sellercenter') : null);
                    }
                } catch (Exception $exception) {
                    $this->session->set_flashdata('error', implode('<br>', $this->ms_freight_tables->getErrorFormatted($exception->getMessage())));
                    $insert = false;
                }
            } else {
                $insert = $this->model_shipping_company->create($data, $this->postClean('freight_seller') == 0 ? $this->postClean('stores_sellercenter') : null);
            }

            if ($insert) {
                /*if ($this->postClean('active_token_api')) {
                    $data['token_api'] = $this->createTokenAPI($insert, $this->postClean('responsible_email'));
                    $this->model_shipping_company->update($data, $insert);
                }*/
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('shippingcompany', 'refresh');
            }
        }

        $this->data['type_accounts'] = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks'] = $this->getBanks();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['company_list'] = $this->model_company->getAllCompanyData();
        $this->data['page_title'] = $this->lang->line('application_register_provider');
        $this->render_template('shipping_company/createAdm', $this->data);
    }

    public function createTokenAPI(int $provider_id, string$email): string
    {
        $key = get_instance()->config->config['encryption_key'];

        $payload = array(
            "provider_id" => $provider_id,
            "email"       => $email
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return $this->jwt->encode($payload, $key);
    }

    /**
     * It redirects to the provider page and displays all the provider information
     * It also updates the provider information into the database if the
     * validation for each input field is successfully valid
     */
    public function update($id, $store_id = null)
    {
        // Verifica se tem permissão
        if (!in_array('updateCarrierRegistration', $this->permission) || $this->data['usercomp'] != 1) {
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        $errorValidation            = false;
        $dataShippingCompany        = null;
        $shipping_company_not_found = false;
        // Validação se transportadora existe.
        if ($this->ms_freight_tables->use_ms_shipping) {
            try {
                $dataShippingCompany = $this->ms_freight_tables->getShippingCompany($id);
            } catch (Exception $exception) {
                $shipping_company_not_found = true;
            }
        } else {
            $dataShippingCompany = $this->model_shipping_company->getShippingCompany($id);
            $shipping_company_not_found = (
                $dataShippingCompany === null ||
                ($dataShippingCompany['store_id'] === null && $this->session->userdata['usercomp'] != 1) ||
                ($dataShippingCompany['store_id'] !== null && $this->model_stores->checkIfTheStoreIsMine($dataShippingCompany['store_id']) === false)
            );
        }

        if ($shipping_company_not_found || !$dataShippingCompany) {
            $this->session->set_flashdata('error', 'Transportadora não encontrada');
            redirect("shippingcompany/index/$store_id", 'refresh');
        }

        $providerToSeller = $this->ms_freight_tables->use_ms_shipping ? array() : $this->model_shipping_company->getShippingCompanyToSellerData($id);

        // Valida os campos do formulário.
        if (count($this->postClean())) {
            $errorValidation = $this->validateFieldsShippingCompany($this->postClean(), 'update', $id) === false;

            if ($errorValidation) {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            }
        }

        if (!$errorValidation && count($this->postClean())) {
            $data = $this->getDataFormattedToCreate(false, false, $this->ms_freight_tables->use_ms_shipping);

            if ($this->postClean('active_token_api')) {
                $data['token_api'] = $this->createTokenAPI($id, $this->postClean('responsible_email'));
            }

            $update = true;
            if ($this->ms_freight_tables->use_ms_shipping) {
                try {
                    if ($data['freight_seller'] == 1) {
                        $this->ms_freight_tables->setStore($data['store_id']);
                        unset($data['store_id']);
                    }
                    $this->ms_freight_tables->updateShippingCompany($id, $data);
                    if ($this->ms_freight_tables->use_ms_shipping_replica) {
                        $this->model_shipping_company->update($this->getDataFormattedToCreate(false, false, false), $id, $this->postClean('freight_seller', true) == 0 ? $this->postClean('stores_sellercenter', true) : null);
                    }
                } catch (Exception $exception) {
                    $this->session->set_flashdata('error', implode('<br>', $this->ms_freight_tables->getErrorFormatted($exception->getMessage())));
                    $update = false;
                }
            } else {
                $update = $this->model_shipping_company->update($data, $id, $this->postClean('freight_seller', true) == 0 ? $this->postClean('stores_sellercenter', true) : null);
            }

            if ($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('shippingcompany/', 'refresh');
            }
        }

        $this->data['provider_data']    = $dataShippingCompany;
        $this->data['type_accounts']    = array($this->lang->line('application_account'), $this->lang->line('application_savings'));
        $this->data['banks']            = $this->getBanks();
        $this->data['stores']           = $this->model_stores->getActiveStore();
        $this->data['company_list']     = $this->model_company->getAllCompanyData();
        $this->data['providerToSeller'] = $this->ms_freight_tables->use_ms_shipping ? $dataShippingCompany->store_id : array_map(function($array){ return $array['store_id']; }, $providerToSeller);

        if (!$this->ms_freight_tables->use_ms_shipping) {
            $dataShippingCompany['company_id'] = $providerToSeller[0]['company_id'] ?? null;
        }
        // Não foi encontrado a empresa, então é consulta qual é a empresa, por uma loja.
        else if ($dataShippingCompany->freight_seller == 1 && $dataShippingCompany->company_id === null) {
            $company = $this->model_stores->getStoresData($dataShippingCompany->store_id[0]);
            $dataShippingCompany->company_id = (int)$company['company_id'];
        }

        $this->data['fields'] = $this->getDataFormattedToView($dataShippingCompany);

        $this->render_template('shipping_company/editAdm', $this->data);
    }

    /**
     * Verifica se a IE está correta de acordo com o estado.
     *
     * @param   string  $ie Código da IE.
     * @param   string  $uf Código UF.
     * @return  bool        IE está válido.
     */
    public function checkInscricaoEstadual(string $ie, string $uf): bool
    {
        $ok = ValidatesIE::check($ie, $uf);
        if (!$ok) {
            $this->form_validation->set_message('checkInscricaoEstadual', '{field} inválida.');
        }
        return $ok;
    }

    public function checkCnpjAvailable(string $cnpj, string $params): bool
    {
        $param           = preg_split('/,/', $params);
        $store           = (int)$param[0];
        $shippingCompany = $param[1] ?? null;

        $getProviderCNPJ = $this->model_shipping_company->getShippingCompanyByCnpjAndStore($cnpj, $store);

        if ($getProviderCNPJ) {

            // Se o 'shippingCompany' for nulo é uma criação de uma nova transportadora.
            if ($shippingCompany === null) {
                $this->form_validation->set_message('checkCnpjAvailable', '{field} já está em uso.');
                return false;
            }

            // Se o 'shippingCompany' não for nulo é uma atualização de uma transportadora e o ID recuperado é comparado para saber se o CNPJ existe em outra transportadora que não seja a que eu estou atualizando.
            if ($getProviderCNPJ['id'] != $shippingCompany) {
                $this->form_validation->set_message('checkCnpjAvailable', '{field} já está em uso, para: ' . $getProviderCNPJ['name']);
                return false;
            }
        }

        return true;
    }

    private function getDataFormattedToView($dataShippingCompany): array
    {
        $dataShippingCompanyToView = (array)$dataShippingCompany;

        if ($this->ms_freight_tables->use_ms_shipping) {
            $dataShippingCompanyToView["razao_social"] = $dataShippingCompany->corporate_name;
            $dataShippingCompanyToView["insc_estadual"] = $dataShippingCompany->state_registration ?: 0;
            $dataShippingCompanyToView["address"] = $dataShippingCompany->address_place;
            $dataShippingCompanyToView["addr_num"] = $dataShippingCompany->address_number;
            $dataShippingCompanyToView["addr_compl"] = $dataShippingCompany->address_complement;
            $dataShippingCompanyToView["zipcode"] = $dataShippingCompany->address_zipcode;
            $dataShippingCompanyToView["addr_neigh"] = $dataShippingCompany->address_neighborhood;
            $dataShippingCompanyToView["addr_city"] = $dataShippingCompany->address_city;
            $dataShippingCompanyToView["addr_uf"] = $dataShippingCompany->address_state;
            $dataShippingCompanyToView["active_token_api"] = (bool)$dataShippingCompany->token;
            $dataShippingCompanyToView["token_api"] = $dataShippingCompany->token;
            $dataShippingCompanyToView["responsible_oper_name"] = $dataShippingCompany->responsible_operational_name;
            $dataShippingCompanyToView["responsible_oper_cpf"] = $dataShippingCompany->responsible_operational_cpf;
            $dataShippingCompanyToView["responsible_oper_email"] = $dataShippingCompany->responsible_operational_email;
            $dataShippingCompanyToView["responsible_finan_name"] = $dataShippingCompany->responsible_financial_name;
            $dataShippingCompanyToView["responsible_finan_cpf"] = $dataShippingCompany->responsible_financial_cpf;
            $dataShippingCompanyToView["responsible_finan_email"] = $dataShippingCompany->responsible_financial_email;
            $dataShippingCompanyToView["tipo_fornecedor"] = 'Transportadora';
            $dataShippingCompanyToView["observacao"] = $dataShippingCompany->observation;
            $dataShippingCompanyToView["regiao_entrega"] = $dataShippingCompany->delivery_region;
            $dataShippingCompanyToView["regiao_coleta"] = $dataShippingCompany->region_collection;
            $dataShippingCompanyToView["tempo_coleta"] = $dataShippingCompany->collection_time;
            $dataShippingCompanyToView["fluxo_fin"] = $dataShippingCompany->cash_flow;
            $dataShippingCompanyToView["credito"] = $dataShippingCompany->credit_value ? 'Sim' : "Nao";
            $dataShippingCompanyToView["val_credito"] = $dataShippingCompany->credit_value;
            $dataShippingCompanyToView["ship_min"] = $dataShippingCompany->minimum_shipping ? 'Sim' : "Nao";
            $dataShippingCompanyToView["val_ship_min"] = $dataShippingCompany->minimum_shipping;
            $dataShippingCompanyToView["qtd_min"] = $dataShippingCompany->minimum_quantity ? 'Sim' : "Nao";
            $dataShippingCompanyToView["val_qtd_min"] = $dataShippingCompany->minimum_quantity;
            $dataShippingCompanyToView["tipo_pagamento"] = $dataShippingCompany->payment_type;
            $dataShippingCompanyToView["tipo_produto"] = $dataShippingCompany->product_type;
            $dataShippingCompanyToView["tracking_web_site"] = $dataShippingCompany->tracking_website;
            $dataShippingCompanyToView["slc_tipo_cubage"] = $dataShippingCompany->cubage_factor;
            $dataShippingCompanyToView["store_id"] = $dataShippingCompany->freight_seller == 1 ? $dataShippingCompany->store_id[0] : null;
            $dataShippingCompanyToView['freight_calculation_standard'] = $dataShippingCompany->freight_calculation_standard ?? 0;
        }

        $dataShippingCompanyToView["slc_tipo_cubage"] = $dataShippingCompanyToView["slc_tipo_cubage"] ? 'FreteCubadoSim' : 'FreteCubadoNao';

        return $dataShippingCompanyToView;
    }

    private function getDataFormattedToCreate($simplified, $create, $use_ms_shipping = false): array
    {
        // É microsserviço.
        if ($use_ms_shipping) {
            $data = array(
                "name"                          => $this->postClean('name'),
                "corporate_name"                => $this->postClean('raz_soc'),
                "cnpj"                          => onlyNumbers($this->postClean('cnpj')),
                "phone"                         => onlyNumbers($this->postClean('phone')),
                "responsible_name"              => $this->postClean('responsible_name') ?: null,
                "responsible_email"             => $this->postClean('responsible_email') ?: null,
                "ad_valorem"                    => $this->postClean('ad_valorem') == "" ? null : $this->postClean('ad_valorem') ,
                "gris"                          => $this->postClean('gris') == "" ? null : $this->postClean('gris'),
                "toll"                          => $this->postClean('toll') == "" ? null : $this->postClean('toll'),
                "shipping_revenue"              => $this->postClean('shipping_revenue') == "" ? null : $this->postClean('shipping_revenue'),
                "cubage_factor"                 => $this->postClean('cubage_factor') == "" ? null : $this->postClean('cubage_factor'),
                "freight_seller"                => 1,
                "type"                          => 1,
                'company_id'                    => null,
                'store_id'                      => null,
                'freight_calculation_standard'  => $this->postClean('freight_calculation_standard') == "PorPeso" ? 1 : 0
            );

            // Se não é simplificada, enviar mais informações.
            if (!$simplified) {
                $data = array_merge($data, array(
                    "state_registration"            => $this->postClean('exempted') == "1" ? null : $this->postClean('txt_insc_estadual'),
                    "observation"                   => $this->postClean('txt_observacao') ?: null,
                    "delivery_region"               => $this->postClean('txt_regiao_entrega') ?: null,
                    "region_collection"             => $this->postClean('txt_regiao_coleta') ?: null,
                    "collection_time"               => $this->postClean('txt_tempo_coleta') ?: null,
                    "cash_flow"                     => $this->postClean('txt_fluxo_fin') ?: null,
                    "credit_value"                  => $this->postClean('txt_val_credito') ?: null,
                    "minimum_shipping"              => $this->postClean('txt_val_ship_min') ?: null,
                    "minimum_quantity"              => $this->postClean('txt_qtd_min') ?: null,
                    "payment_type"                  => $this->postClean('slc_tipo_pagamento') ?: null,
                    "product_type"                  => $this->postClean('txt_tipo_produto') ?: null,
                    "tracking_website"              => $this->postClean('tracking_web_site') ?: null,
                    "address_place"                 => null,
                    "address_number"                => null,
                    "address_complement"            => null,
                    "address_zipcode"               => null,
                    "address_neighborhood"          => null,
                    "address_city"                  => null,
                    "address_state"                 => $this->postClean('addr_uf') ?: null,
                    "responsible_cpf"               => onlyNumbers($this->postClean('responsible_cpf')),
                    "bank"                          => $this->postClean('bank') ?: null,
                    "agency"                        => $this->postClean('agency') ?: null,
                    "account_type"                  => $this->postClean('account_type') ?: null,
                    "account"                       => $this->postClean('account') ?: null,
                    "responsible_operational_name"  => $this->postClean('responsible_oper_name') ?: null,
                    "responsible_operational_cpf"   => onlyNumbers($this->postClean('responsible_oper_cpf')),
                    "responsible_operational_email" => $this->postClean('responsible_oper_email') ?: null,
                    "responsible_financial_name"    => $this->postClean('responsible_finan_email') ?: null,
                    "responsible_financial_cpf"     => onlyNumbers($this->postClean('responsible_finan_cpf')),
                    "responsible_financial_email"   => $this->postClean('responsible_finan_email') ?: null,
                    'token'                         => $this->postClean('token_api'),
                    'freight_seller'                => $this->postClean('freight_seller')
                ));
            }

            // É simplificado, então eu sei qual a loja e a empresa.
            if ($simplified) {
                $company = $this->model_stores->getStoresData($this->postClean('store'));
                $data['store_id']     = (int)$this->postClean('store');
                $data['company_id']   = (int)$company['company_id'];
            }
            // Não é simplificado e é admin criando transportadora para uma loja.
            else if ($this->postClean('freight_seller') == 1) {
                $data['store_id']   = $this->postClean('slc_store');
                $data['company_id'] = $this->postClean('slc_company');
            }
            // Não é simplificado e é admin criando transportadora para o seller center.
            else if ($this->postClean('freight_seller') == 0) {
                $data['store_id'] = $this->postClean('stores_sellercenter');
            }

            // É uma criação de transportadora, então criar a transportadora como inativo.
            if ($create) {
                $data['active'] = 0;
            }

            return $data;
        }

        // Não é microsserviço.
        $data = array(
            'name'                          => $this->postClean('name'),
            'razao_social'                  => $this->postClean('raz_soc'),
            'cnpj'                          => onlyNumbers($this->postClean('cnpj')),
            'phone'                         => onlyNumbers($this->postClean('phone')),
            'responsible_name'              => $this->postClean('responsible_name'),
            'responsible_email'             => $this->postClean('responsible_email'),
            'ad_valorem'                    => $this->postClean('ad_valorem') == "" ? null : $this->postClean('ad_valorem') ,
            'gris'                          => $this->postClean('gris') == "" ? null : $this->postClean('gris'),
            'toll'                          => $this->postClean('toll') == "" ? null : $this->postClean('toll'),
            'shipping_revenue'              => $this->postClean('shipping_revenue') == "" ? null : $this->postClean('shipping_revenue'),
            'slc_tipo_cubage'               => $this->postClean('slc_tipo_cubage') == "FreteCubadoSim" ? 1 : 0,
            'freight_calculation_standard'  => $this->postClean('freight_calculation_standard') == "PorPeso" ? 1 : 0,
            'cubage_factor'                 => $this->postClean('cubage_factor') == "" ? null : $this->postClean('cubage_factor'),
        );

        if ($simplified) {
            $company = $this->model_stores->getStoresData($this->postClean('store'));
            $data = array_merge($data, array(
                'store_id'          => (int)$this->postClean('store'),
                'freight_seller'    => 1,
                'company_id'        => (int)$company['company_id'],
            ));
        } else {
            $data = array_merge($data, array(
                'active'                    => $this->postClean('active') ? 1 : 0,
                'active_token_api'          => $this->postClean('active_token_api') ? 1 : 0,
                'token_api'                 => $this->postClean('token_api'),
                'responsible_cpf'           => onlyNumbers($this->postClean('responsible_cpf')),
                'tracking_web_site'         => $this->postClean('tracking_web_site'),
                'responsible_oper_name'     => $this->postClean('responsible_oper_name'),
                'responsible_oper_email'    => $this->postClean('responsible_oper_email'),
                'responsible_oper_cpf'      => onlyNumbers($this->postClean('responsible_oper_cpf')),
                'responsible_finan_name'    => $this->postClean('responsible_finan_name'),
                'responsible_finan_email'   => $this->postClean('responsible_finan_email'),
                'responsible_finan_cpf'     => onlyNumbers($this->postClean('responsible_finan_cpf')),
                'tipo_fornecedor'           => $this->postClean('slc_tipo_provider'),
                'observacao'                => $this->postClean('txt_observacao'),
                'address'                   => '',
                'addr_num'                  => '',
                'addr_compl'                => '',
                'addr_neigh'                => '',
                'addr_city'                 => '',
                'addr_uf'                   => $this->postClean('addr_uf'),
                'bank'                      => $this->postClean('bank'),
                'agency'                    => $this->postClean('agency'),
                'account_type'              => $this->postClean('account_type'),
                'account'                   => $this->postClean('account'),
                'regiao_entrega'            => $this->postClean('txt_regiao_entrega'),
                'regiao_coleta'             => $this->postClean('txt_regiao_coleta'),
                'tempo_coleta'              => $this->postClean('txt_tempo_coleta'),
                'fluxo_fin'                 => $this->postClean('txt_fluxo_fin'),
                'credito'                   => $this->postClean('slc_val_credito'),
                'val_credito'               => $this->postClean('txt_val_credito'),
                'ship_min'                  => $this->postClean('slc_val_ship_min'),
                'val_ship_min'              => $this->postClean('txt_val_ship_min'),
                'qtd_min'                   => $this->postClean('slc_qtd_min'),
                'val_qtd_min'               => $this->postClean('txt_qtd_min'),
                'tipo_pagamento'            => $this->postClean('slc_tipo_pagamento'),
                'store_id'                  => $this->postClean('freight_seller') == 1 ? $this->postClean('slc_store') : null,
                'company_id'                => $this->postClean('freight_seller') == 1 ? $this->postClean('slc_company') : null,
                'tipo_produto'              => $this->postClean('txt_tipo_produto'),
                'insc_estadual'             => $this->postClean('exempted') == "1" ? "0" : $this->postClean('txt_insc_estadual'),
                'freight_seller'            => $this->postClean('freight_seller')
            ));
        }

        return $data;
    }

    private function getDataFormattedSimplifiedToCreate($dados, $use_ms_shipping = false): array
    {
        if (!$use_ms_shipping) {
            return $dados;
        }

        $response = array();

        if ($use_ms_shipping) {
            foreach ($dados as $data) {
                // São os dados da região
                if (!array_key_exists('id_estado', $data)) {
                    // Registro os dados por região.
                    if (!empty($data['qtd_dias']) && $data['valor'] !== '') {
                        $response[$data['id_regiao']] = array();
                        $response[$data['id_regiao']]['region'] = array(
                            'price'     => $data['valor'],
                            'deadline'  => (int)$data['qtd_dias']
                        );
                    }
                }
                // São os dados por estado.
                else {
                    if (
                        !empty($data['interior_qtd_dias']) &&
                        $data['interior_valor'] !== '' &&
                        !empty($data['capital_qtd_dias']) &&
                        $data['capital_valor'] !== ''
                    ) {
                        // Cria o índice com o código da região.
                        if (!array_key_exists($data['id_regiao'], $response)) {
                            $response[$data['id_regiao']] = array();
                        }

                        // Cria o índice com o código do estado.
                        if (!array_key_exists($data['id_estado'], $response[$data['id_regiao']])) {
                            $response[$data['id_regiao']][$data['id_estado']] = array();
                        }

                        $response[$data['id_regiao']][$data['id_estado']]['capital'] = array(
                            'price'     => $data['capital_valor'],
                            'deadline'  => (int)$data['capital_qtd_dias']
                        );

                        $response[$data['id_regiao']][$data['id_estado']]['interior'] = array(
                            'price'     => $data['interior_valor'],
                            'deadline'  => (int)$data['interior_qtd_dias']
                        );
                    }
                }
            }
        }

        return $response;
    }

    public function removeShippingCompany(): CI_Output
    {
        $shipping_company = $this->postClean('shipping_company');
        $data_shipping_company = $this->model_shipping_company->getShippingCompany($shipping_company);

        // Não encontrou a transportadora.
        if (!$data_shipping_company) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(array(
                'success' => false,
                'message' => "Transportadora não localizada."
            )));
        }

        // Não é a empresa principal.
        if ($this->session->userdata['usercomp'] != 1 ) {
            // Transportadora não pertence à loja do usuário.
            if (!in_array($data_shipping_company['store_id'], $this->model_stores->getMyStores())) {
                return $this->output->set_content_type('application/json')->set_output(json_encode(array(
                    'success' => false,
                    'message' => "Transportadora não localizada para a loja."
                )));
            }
        }

        $this->model_shipping_company->update(array(
            'deleted' => true,
            'active' => false
        ), $shipping_company);

        return $this->output->set_content_type('application/json')->set_output(json_encode(array(
            'success' => true,
            'message' => "Transportadora excluída com sucesso."
        )));
    }

    private function request($method, $url) {
        $keycloak_token = $this->ms_shipping_carrier->authenticatorKeycloak();

        $headers = [ 'Authorization' => $keycloak_token->token_type . ' ' . $keycloak_token->access_token];
        return new Request($method, $url, $headers);
    }
}
