<?php

defined('BASEPATH') or exit('No direct script access allowed');

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";

/**
 * @property PrivateBucket $privatebucket
 * @property Model_stores $model_stores
 * @property Model_users $model_users
 * @property Model_csv_generator_export $model_csv_generator_export
 *
 * @property CI_Output $output
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 */

class DownloadCenter extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_download_center');
        $this->load->library('PrivateBucket');
        $this->load->model('model_csv_generator_export');
        $this->load->model('model_stores');
        $this->load->model('model_users');
    }

    public function index()
    {
        $this->data['page_title'] = $this->lang->line('application_download_center');
        $this->data['users_filter'] = $this->model_users->getMyUsersData();
        $this->data['user_id'] = $this->session->userdata('id');
        $this->data['add_on_permission'] = in_array('addOn', $this->permission);
        $this->data['catalog_product_marketplace_permission'] = !in_array('createProduct', $this->permission) && in_array('disablePrice', $this->permission);

        $this->render_template('downloadcenter/index', $this->data);
    }

    /**
     * Busca os downloads realizados via exportação.
     */
    public function fetchDownloads()
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
                $types = [];

                // Caso seja tela de produto, também retorna variações.
                $types[]=$type;
                if($type=="Product"){
                    $types[]="Variation";
                }

                $filter_default['where_in']['type'] = $types;
            }

            $users_inactives = array_map(function ($item) {
                return $item['id'];
            }, $this->model_users->getMyUsersData(false));

            if (!empty($users_inactives)) {
                $filter_default['where_not_in']['user_id'] = $users_inactives;
            }

            // Busca os campos especificados no banco.
            $fields_order = array('id', 'file_name', 'file_create_date', 'user_id', 'type');
            $data = $this->fetchDataTable('model_csv_generator_export', 'getGeneratedCsv', [], $filters, $fields_order, $filter_default);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output($exception->getMessage())
                ->set_status_header($exception->getCode());
        }

        foreach ($data['data'] as $key => $value) {

            // Verifica se há data de criação.
            $created = !is_null($value['file_create_date']);

            // Verifico se há data de exclusão do arquivo e se esta data já passou.
            $deleted = !is_null($value['file_delete_date']) && $value['file_delete_date'] < date('Y-m-d H:i:s');

            // Switch para mostrar o status atual do arquivo.
            switch ($created) {
                case 0:
                    $colorStatus = 'warning';
                    $nameStatus = '<i class="fa fa-spinner fa-spin"></i>';
                    break;
                case 1:
                    // Trata os casos de exportações já deletadas.
                    switch ($deleted) {
                        case 0:
                            $colorStatus = 'success';
                            $nameStatus = $this->lang->line('application_success');
                            break;
                        case 1:
                            $colorStatus = 'danger';
                            $nameStatus = $this->lang->line('application_deleted');
                            break;
                    }
                    break;
                default:
                    $colorStatus = '';
                    $nameStatus = '';
            }
            $status = "<span class='label label-$colorStatus'>$nameStatus</span>";

            $url = '';

            // Diretório padrão das exportações.
            $export_dir = "assets/images/temp/csv_temp_export/";

            // Cria a chave do objeto e cria a request de autenticação para ele.
            $file_key = $this->privatebucket->getAssetKey($export_dir . $value['file_name'], true, false, true);
            $pre_signed_request = $this->privatebucket->generatePreSignedRequest($file_key, '+30 minutes');

            // Busca apenas a URL caso sucesso, se não, retorna vazio.
            $url = $pre_signed_request['success'] ? $pre_signed_request['url'] : "";

            $create_date = $value['file_create_date'] ?
                date('d/m/Y H:i', strtotime($value['file_create_date'])) : "Aguardando conclusão";
            $result[$key] = array(
                $value['id'],
                "<a href='" . $url . "' download>Baixar Arquivo <i class='fa fa-download'></i></a>",
                $status,
                $value['username'],
                $create_date
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
}
