<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";

use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Microservices\v1\Logistic\FreightTables;

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 * @property Model_users $model_users
 *
 * @property CI_Output $output
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 * @property FreightTables $ms_freight_tables
 */

class FileProcess extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_file_process');

		$this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores');
        $this->load->model('model_shipping_company');

        $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');

        $this->load->model('model_users');
	}

	public function index()
	{
		if (!in_array('createCarrierRegistration', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

        $this->data['page_title'] = $this->lang->line('application_file_process');
        $this->data['stores_filter'] = $this->model_stores->getActiveStore();

		$this->render_template('fileprocess/index', $this->data);
	}

    public function product_load()
    {
        $this->data['page_title'] = $this->lang->line('application_file_process');
        $this->data['users_filter'] = $this->model_users->getMyUsersData();
        $this->data['user_id'] = $this->session->userdata('id');
        $this->data['add_on_permission'] = in_array('addOn', $this->permission);
        $this->data['catalog_product_marketplace_permission'] = !in_array('createProduct', $this->permission) && in_array('disablePrice', $this->permission);

        $this->render_template('fileprocess/product_load', $this->data);
    }

    /**
     * Busca todos os arquivos para serem mostrados em uma listagem na view.
     *
     * @return CI_Output
     */
    public function fetchFileProcessShippingCompanyData(): CI_Output
    {
        if (!in_array('createCarrierRegistration', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        $type = $postdata['type'];
        $stores = $postdata['stores'];
        $res_order = $postdata['order'][0]['dir'];
        $result = array();

        if ($this->ms_freight_tables->use_ms_shipping) {
            $store_id = 0;
            if (is_array($stores) && (count($stores) == 1)) {
                $store_id = $stores[0];
            }

            if (($store_id <= 0) || (empty($store_id))) {
                $output = array(
                    "draw" => $draw,
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => $result
                );

                echo json_encode($output);
                die;
            }

            $company_id = $this->session->userdata['usercomp'];
            $data = $this->model_shipping_company->getShippingCompanyByStore((int)$store_id);
            //$shipping_company_id = $data[0]['id'];

            $client = new Client();
            $ms_url = "{$this->ms_freight_tables->process_url}{$this->ms_freight_tables->path_url}/{$store_id}/v1/shipping_table/processed_files_list";
            // $request = new Request('GET', 'https://ms.conectala.com.br/freight_tables/conectala/api/10/v1/shipping_table/processed_files_list/3');
            $request = $this->requestMs('GET', $ms_url);
            $res = $client->sendAsync($request)->wait();
            $json = json_decode((string)$res->getBody(), true);
            $filtered = count($json);

            if ($res_order == 'desc') {
                rsort($json);
            }

            $store_data = $this->model_stores->getStoresData($store_id);
            $store_name = $store_data['name'];

            $c = -1;
            $counter = 0;
            foreach ($json as $key => $value) {
                $counter += 1;
                if (($counter <= $ini) || ($counter > ($ini + $length))) {
                    continue;
                } else if (($value['checked'] == 0) || ($value['checked'] == 2)) {
                    continue;
                }

                $user_data = $this->model_users->getUserData(($value['user_id'] ?? 0) > 0 ? $value['user_id'] : $this->session->userdata['id']);
                $user_name = $user_data['username'];

                $ms_url = $value['file_url'] ?? "{$this->ms_freight_tables->process_url}{$this->ms_freight_tables->path_url}/{$store_id}/v1/shipping_table/download_one/{$value['id']}";
                // $download_file = "<a href='javascript:void(0)' onclick='location.href=\"https://ms.conectala.com.br/freight_tables/conectala/api/{$store_id}/v1///shipping_table/download_one/{$value['id']}\"' download>Baixar Arquivo <i class='fa fa-download'></i></a>";
                $download_file = "<a href='javascript:void(0)' onclick='location.href=\"{$ms_url}\"' download>Baixar Arquivo <i class='fa fa-download'></i></a>";

                $total_steps = 0;
                $body_content = '';
                $messages = $value['messages'];
                foreach ($messages as $m) {
                    if (gettype($m) !== 'string') {
                        continue;
                    }

                    if (strpos($m, "File {$value['file_name']} uploaded.") !== false) {
                        $body_content .= "&#9745; Arquivo armazenado no servidor.<br/>";
                        $total_steps += 1;
                    } else if (strpos($m, "Inserting ") !== false) {
                        $body_content .= "&#9745; Arquivo validado.<br/>";
                        $total_steps += 1;
                    } else if (strpos($m, "Total processing time: ") !== false) {
                        $temp = substr($messages[13], 23);
                        $pos = strpos($temp, '.');
                        $temp = substr($temp, 0, $pos);
                        $body_content .= "&#9745; Arquivo processado em {$temp} segundos.";
                        $total_steps += 1;
                    }
                }

                if ($total_steps == 2) {
                    $body_content .= "&#9744; Processamento do arquivo: pendente.";
                } else if ($total_steps == 1) {
                    $body_content .= "&#9744; Validação do arquivo: pendente.<br/>
                        &#9744; Processamento do arquivo: pendente.";
                } else if ($total_steps == 0) {
                    $body_content .= "&#9744; Armazenamento do arquivo no servidor: pendente.<br/>
                        &#9744; Validação do arquivo: pendente.<br/>
                        &#9744; Processamento do arquivo: pendente.";
                }

                $colorStatus = '';
                $nameStatus = '';
                $disabled = !$value['checked'] || $value['error_found'] ? '' : 'disabled';

                if (($value['checked'] == 1) && ($value['error_found'] == 0)) {
                    $colorStatus = 'success';
                    $nameStatus = $this->lang->line('application_success');
                } else if (($value['checked'] == 1) && ($value['error_found'] == 1)) {
                    $colorStatus = 'danger';
                    $nameStatus = $this->lang->line('application_error');
                    /*$body_content = '&#9746; Erros foram encontrados durante o processamento do arquivo. Favor verificar e se certificar de que ele segue o padrão estabelecido antes de tentar novamente.<br/>&nbsp;<br/>';

                    $errors_found = $value['errors'];
                    foreach ($errors_found as $e) {
                        $body_content .= "&mdash; " . str_replace("'", "\"", $e) . "<br/>";
                    }*/
                }
                $status = "<span class='label label-$colorStatus'>$nameStatus</span>";
                //$buttons = "<button type='button' class='btn btn-primary view-status-file' file-id='{$value['id']}' body-content='{$body_content}'><i class='fa fa-eye'></i></button>";
                $buttons = "<button type='button' class='btn btn-primary view-status-file' file-id='{$value['id']}' $disabled><i class='fa fa-eye'></i></button>";

                $c += 1;
                $result[$c] = array(
                    $value['id'],
                    $download_file,
                    $status,
                    $user_name,
                    $value['shipping_company'],
                    $store_name,
                    date('d/m/Y H:i', strtotime($value['created_at'])),
                    $buttons
                );
            }

            $output = array(
                "draw" => $draw,
                "recordsTotal" => $filtered, // $this->model_csv_to_verifications->getCountFileProcessData('', $type),
                "recordsFiltered" => $filtered,
                "data" => $result
            );

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($output));
        }
        try {
            $type = $this->postClean('type');
            $stores = $this->postClean('stores');
            $draw = $this->postClean('draw');
            $result = array();

            $filters = array();
            if (!empty($stores)) {
                $filters['where_in']['csv_to_verification.store_id'] = implode($stores, ',');
            }

            if (!empty($type)) {
                $filters['where']['csv_to_verification.module'] = $type;
            }

            $fields_order = array('csv_to_verification.id', 'csv_to_verification.upload_file', 'csv_to_verification.final_situation', 'csv_to_verification.user_email', 'shipping_company.name', 'stores.name', 'csv_to_verification.created_at', '');

            $data = $this->fetchDataTable('model_csv_to_verifications', 'getFetchFileProcessData', ['createCarrierRegistration'], $filters, $fields_order);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        foreach ($data['data'] as $key => $value) {

            $disabled = $value['final_situation'] === 'success' ? 'disabled' : '';

            switch ($value['final_situation']) {
                case 'wait':
                    $colorStatus = 'warning';
                    $nameStatus = '<i class="fa fa-spinner fa-spin"></i>';
                    break;
                case 'success':
                    $colorStatus = 'success';
                    $nameStatus = $this->lang->line('application_success');
                    break;
                case 'err':
                    $colorStatus = 'danger';
                    $nameStatus = $this->lang->line('application_error');
                    break;
                default:
                    $colorStatus = '';
                    $nameStatus = '';
            }
            $status = "<span class='label label-$colorStatus'>$nameStatus</span>";

            if (empty($value['name_store'])) {
                $value['name_store'] = 'Seller Center';
            }

            $result[$key] = array(
                $value['id'],
                "<a href='" . base_url($value['upload_file']) . "' download>Baixar Arquivo <i class='fa fa-download'></i></a>",
                $status,
                $value['username'],
                $value['name_provider'],
                $value['name_store'],
                date('d/m/Y H:i', strtotime($value['created_at'])),
                "<button type='button' class='btn btn-primary view-status-file' file-id='{$value['id']}' $disabled><i class='fa fa-eye'></i></button>"
            );
        }

        $output = array(
            "draw" => $draw,
            "recordsTotal" => $data['recordsTotal'],
            "recordsFiltered" => $data['recordsFiltered'],
            "data" => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Busca todos os arquivos para serem mostrados em uma listagem na view.
     *
     * @return CI_Output
     */
    public function fetchFileProcessProductsLoadData(): CI_Output
    {
        try {
            $type   = $this->postClean('type');
            $users  = $this->postClean('users');
            $draw   = $this->postClean('draw');
            $result = array();

            $my_users = $this->model_users->getMyUsersData();
            // Se não informar usuário, recupero todos os meus.
            if (empty($users)) {
                $users = array_map(function ($item) {
                    return $item['id'];
                }, $my_users);
            }

            // Verifica se o usuário existe.
            $exist_user = true;
            foreach ($users as $user) {
                if (!in_array(true, array_map(function ($item) use ($user) {
                    return $user == $item['id'];
                }, $my_users))) {
                    $exist_user = false;
                    break;
                }
            }

            // Usuário não encontrado.
            if (!$exist_user) {
                throw new Exception("Usuário(s) não encontrado(s)", 404);
            }

            $filters        = array();
            $filter_default = array();

            if (!empty($users)) {
                $filters['where_in']['user_id'] = $users;
            }

            if (!empty($type)) {
                $filter_default['where']['module'] = $type;
            }

            $users_inactives = array_map(function ($item) { return $item['id']; }, $this->model_users->getMyUsersData(false));
            if (!empty($users_inactives)) {
                $filter_default['where_not_in']['user_id'] = $users_inactives;
            }

            $fields_order = array('id','upload_file','final_situation','user_email','created_at','');

            $data = $this->fetchDataTable('model_csv_to_verifications', 'getFetchFileProcessProductsLoadData', [], $filters, $fields_order, $filter_default);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output($exception->getMessage())
                ->set_status_header($exception->getCode());
        }

        foreach ($data['data'] as $key => $value) {

            $disabled = $value['final_situation'] === 'success' ? 'disabled' : '';

            switch ($value['final_situation']) {
                case 'wait':
                    $colorStatus = 'warning';
                    $nameStatus = '<i class="fa fa-spinner fa-spin"></i>';
                    break;
                case 'success':
                    $colorStatus = 'success';
                    $nameStatus = $this->lang->line('application_success');
                    break;
                case 'err':
                case 'error':
                    $colorStatus = 'danger';
                    $nameStatus = $this->lang->line('application_error');
                    break;
                default:
                    $colorStatus = '';
                    $nameStatus = '';
            }
            $status = "<span class='label label-$colorStatus'>$nameStatus</span>";

            $result[$key] = array(
                $value['id'],
                "<a href='".base_url($value['upload_file'])."' download>Baixar Arquivo <i class='fa fa-download'></i></a>",
                $status,
                $value['username'],
                date('d/m/Y H:i', strtotime($value['created_at'])),
                "<button type='button' class='btn btn-primary view-status-file' file-id='{$value['id']}' $disabled><i class='fa fa-eye'></i></button>"
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Recupera a resposta do processamento do arquivo
     *
     * @return CI_Output
     */
    public function getResponseFile(): CI_Output
    {
        $module     = $this->postClean('type');
        $idFile     = $this->postClean('idFile');
        $resp       = null;
        $errors     = array();
        $messages   = '';

        $this->output->set_content_type('application/json');
        
        if ($this->ms_freight_tables->use_ms_shipping && $module == "Shippingcompany") {
            try {
                $response   = $this->ms_freight_tables->getProcessingStatus($idFile);
                $errors     = property_exists($response, 'errors') && is_array($response->errors) ? $response->errors : [];
                $messages   = property_exists($response, 'messages') && is_array($response->messages) ? end($response->messages) : '';
                $checked    = $response->checked;
            } catch (Throwable $e) {
                return $this->output->set_output(json_encode(array(
                    'waiting'   => true,
                    'errors'    => $e->getMessage(),
                    'messages'  => $e->getMessage()
                )));
            }
        } else {
            $response   = $this->model_csv_to_verifications->getResponseFile($module, $idFile);
            $resp       = json_decode($response["processing_response"],true);
            $checked    = $response['checked'];

            if ($response['final_situation'] === 'err') {
                if (!is_null($resp)) {
                    $errors = $resp;
                } else {
                    $errors = explode('</li><li>', str_replace('</li></ul>', '', str_replace('<ul><li>', '', $response["processing_response"])));
                }
            }

            if (!empty($errors)) {
                $messages = $response["processing_response"];
            }
        }

        // Não encontrou o arquivo
        if (empty($response)) {
            return $this->output->set_output(json_encode(array(
                'errors'    => 'Não foi possível identificar o arquivo',
                'messages'  => 'Não foi possível identificar o arquivo'
            )));
        }

        // Ainda não foi processado.
        if (!$checked) {
            return $this->output->set_output(json_encode([
                'waiting'   => true,
                'errors'    => [],
                'messages'  => [$this->lang->line('messages_process_file_csv')]
            ]));
        }

        if (in_array($module, array("Products", 'SyncPublishedSku', 'GroupSimpleSku', 'AddOnSkus', 'CatalogProductMarketplace'))) {
            if (!is_null($resp)) {
                $result = array_map(function($item) {
                    return [
                        'line'      => $item['line'] ?? null,
                        'messages'  => $item['message'] ?? [$this->lang->line('messages_process_file_csv')]
                    ];
                }, $resp);
            } else {
                $result = [
                    'errors'    => $errors,
                    'messages'  => $messages
                ];
            }
        } else if ($module == "Shippingcompany") {
            $errors = array_filter(
                array_map(function($item) {
                    if (!likeText('', str_replace("\n", ' .',$item), "#.\\[Line #") && !likeText('%Um erro foi encontrado ao processar o arquivo de id%', $item)) {
                        return $item;
                    }
                    return null;
                }, $errors), function ($error) {
                    return !is_null($error);
                }
            );

            $result = [
                'errors'    => empty($errors) ? '' : '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>',
                'messages'  => $messages
            ];
        } else {
            $result = (object)[
                'errors'    => $errors,
                'messages'  => $messages
            ];
        }
        return $this->output->set_output(json_encode($result));
	}
}