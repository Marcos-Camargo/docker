<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . "libraries/Microservices/v1/Logistic/PickupPoints.php";

use Microservices\v1\Logistic\PickupPoints;

/**
 * @property Model_stores $model_stores
 * @property PickupPoints $ms_pickup_points
 * @property Model_states $model_states
 * @property Model_pickup_point $model_pickup_point
 * @property Model_withdrawal_time $model_withdrawal_time
 */
class PickupPoint extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        $this->load->library("Microservices\\v1\\Logistic\\PickupPoints", array(), 'ms_pickup_points');
        $this->load->model('model_states');
        $this->load->model('model_stores');
        $this->load->model('model_pickup_point');
        $this->load->model('model_withdrawal_time');

        if (!in_array('viewPickUpPoint', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
    }

    public function index()
    {
        $this->data['page_title'] = 'Pontos de retirada';
        $stores_id = $this->model_stores->getMyCompanyStoresArrayIds();
        $this->data['stores'] = $this->model_stores->getStores($stores_id);

        $this->render_template('pickup_point/index', $this->data);
    }

    public function getPickupPoints()
    {
        ob_start();
        if ($this->ms_pickup_points->use_ms_shipping) {
            $postdata = $this->postClean(NULL, TRUE);

            $tableFields = ['name', 'street', 'district', 'city', 'state', 'status'];

            $ini = $postdata['start'];
            $draw = $postdata['draw'];
            $length = $postdata['length'];
            $busca = $postdata['search'];
            $order = $postdata['order'];

            $store_id = null;
            if (trim($postdata['store_id'])) {
                $store_id = $postdata['store_id'];
            }

            $order_by = null;
            if(!empty($order)){
                $column = $tableFields[$order[0]['column']];
                $dir = $order[0]['dir'];
                $order_by = $column . ':' . $dir;
            }

            $page = (int)($ini / $length);
            $pickup_points = $this->ms_pickup_points->getPickupPoints($store_id, $page, $length, $busca, $order_by, $tableFields);
            $pickup_points_data = $pickup_points->data;
        } else {
            $draw       = $this->postClean('draw');
            $store_id   = $this->postClean('store_id');
            $result     = array();

            try {
                $filters        = array();
                $filter_default = array();

                if (!empty($store_id)) {
                    /*$my_stores = $this->model_stores->getMyStores();

                    // Usuário não encontrado.
                    if (!in_array($store_id, $my_stores)) {
                        throw new Exception("Loja não encontrada", 404);
                    }*/

                    $filter_default[]['where']['pp.store_id'] = $store_id;
                }

                $fields_order = array('pp.name', 'pp.street', 'pp.district', 'pp.city', 'pp.state', 'pp.status', '');

                $query = array();
                $query['select'][] = 'pp.id,pp.name,pp.street,pp.district,pp.city,pp.state,pp.status,s.name as store_name';
                $query['join'][] = ["stores s", "s.id = pp.store_id"];
                $query['from'][] = 'pickup_points pp';

                $pickup_points = fetchDataTable(
                    $query,
                    array('id', 'DESC'),
                    array(
                        'company'   => 'pp.company_id',
                        'store'     => 'pp.store_id'
                    ),
                    null,
                    ['viewPickUpPoint'],
                    $filters,
                    $fields_order,
                    $filter_default
                );
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(
                        json_encode(array(
                            "draw"              => $draw,
                            "recordsTotal"      => 0,
                            "recordsFiltered"   => 0,
                            "data"              => $result,
                            "message"           => $exception->getMessage()
                        ))
                    );
            }
            $pickup_points_data = $pickup_points['data'];
        }

        $result = array();
        foreach ($pickup_points_data as $key => $r) {
            $id = is_array($r) ? $r['id'] : $r->id;
            $result[$key] = array(
                is_array($r) ? $r['name']      : $r->name,
                is_array($r) ? $r['street']    : $r->street,
                is_array($r) ? $r['district']  : $r->district,
                is_array($r) ? $r['city']      : $r->city,
                is_array($r) ? $r['state']     : $r->state,
                is_array($r) ? $r['status']    : $r->status == 1 ? 'Ativo' : 'Inativo',
            );

            if (!$this->ms_pickup_points->use_ms_shipping) {
                $result[$key][] = $r['store_name'];
            }

            $result[$key][] = in_array('updatePickUpPoint', $this->permission) ?
                '<a href="'. base_url("PickupPoint/edit/$id") . '" class="btn btn-sm btn-primary mr-2"><i class="fa fa-pen"></i></a>':
                '<a href="'. base_url("PickupPoint/edit/$id") . '" class="btn btn-sm btn-primary mr-2"><i class="fa fa-eye"></i></a>';
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => is_array($pickup_points) ? $pickup_points['recordsTotal'] : $pickup_points->meta->total,
            "recordsFiltered"   => is_array($pickup_points) ? $pickup_points['recordsFiltered'] : $pickup_points->meta->total,
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function create()
    {
        if (!in_array('createPickUpPoint', $this->permission)) {
            redirect('PickupPoint', 'refresh');
        }

        $stores_id = $this->model_stores->getMyCompanyStoresArrayIds();

        $this->data['stores'] = $this->model_stores->getStores($stores_id);
        $this->data['page_title'] = 'Criar ponto de retirada';
        $this->data['states'] = $this->model_states->get();

        $this->render_template('pickup_point/create', $this->data);
    }

    public function edit($id = null)
    {
        if (empty($id)) {
            $this->session->set_flashdata('error', 'Ponto de retirada não encontrado.');
            redirect('PickupPoint/', 'refresh');
        }

        try {
            if ($this->ms_pickup_points->use_ms_shipping) {
                $pickup_point = $this->ms_pickup_points->getPickupPoint($id);
                $pickup_point_data = $pickup_point->data ?? null;
            } else {
                $pickup_point_data = $this->model_pickup_point->getById($id);
                $pickup_point_data->withdrawal_times = $this->model_withdrawal_time->getByPickupPointId($id, false);
            }

            if (empty($pickup_point_data)) {
                $this->session->set_flashdata('error', 'Ponto de retirada não encontrado.');
                redirect('PickupPoint/', 'refresh');
            }

            $this->data['pickup_point'] = $pickup_point_data;
        } catch (Exception $exception) {
            $this->session->set_flashdata('error', 'Ponto de retirada não encontrado.');
            redirect('PickupPoint/', 'refresh');
        }

        $stores_id = $this->model_stores->getMyCompanyStoresArrayIds();

        $this->data['can_update'] = in_array('updatePickUpPoint', $this->permission);
        $this->data['page_title'] = $this->data['can_update'] ? 'Editar ponto de retirada' : 'Visualizar ponto de retirada';
        $this->data['stores'] = $this->model_stores->getStores($stores_id);
        $this->data['states'] = $this->model_states->get();

        $this->render_template('pickup_point/edit', $this->data);
    }

    public function savePickupPoint($id = null)
    {
        if (!in_array('createPickUpPoint', $this->permission) && !in_array('updatePickUpPoint', $this->permission)) {
            $this->generateResponse(false, 'Usuário sem permissão para fazer essa ação!');
            return;
        }

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        $address = [
            'name' => $request['nome'],
            'status' => $request['status'],
            'cep' => onlyNumbers($request['cep']),
            'number' => $request['numero'],
            'street' => $request['rua'],
            'district' => $request['bairro'],
            'city' => $request['cidade'],
            'state' => $request['estado'],
            'country' => 'BRA',//$request['country'],
            'complement' => $request['complemento'],
        ];

        $address['store_id'] = $request['store'];

        $data_store = $this->model_stores->getStoresData($address['store_id']);
        $address['company_id'] = $data_store['company_id'];

        foreach ($address as $elemento) {
            $key = array_search($elemento, $address);
            if ($key != 'complement' && $key != 'country' && $key != 'status' && empty($elemento)) {
                $this->generateResponse(false, 'Campos obrigatórios nao preenchidos. Revise o formulário!');
            }
            if($key == 'status' && $elemento == ""){
                $this->generateResponse(false, 'Selecione um valor válido para o campo "STATUS" do ponto de retirada!');
            }
        }

        $days_of_week = [];
        $index = 0;
        foreach ($request['semana'] as $key => $dia) {

            $inicio = !empty($dia['inicio']) ? date("H:i:s", strtotime($dia['inicio'])) : "";
            $fim = !empty($dia['fim']) ? date("H:i:s", strtotime($dia['fim'])) : "";
            $fechada = (bool)$dia['fechada'];

            if (!$fechada) {
                if (empty($inicio) || empty($fim)) {
                    $this->generateResponse(false, 'Digite um horário de abertura e fechamento para <strong>' . $key . '</strong> ou marque a loja como fechada nesse dia!');
                }
            }

            $days_of_week[] = array(
                'day_of_week' => $index,
                'start_hour' => $inicio,
                'end_hour' => $fim,
                'closed_store' => $fechada
            );

            $index++;
        }

        $address['withdrawal_time'] = $days_of_week;

        try {
            if ($id) {
                if ($this->ms_pickup_points->use_ms_shipping) {
                    $this->ms_pickup_points->updatePickupPoint($id, $address);
                } else {
                    $pickup_point_data = $address;
                    unset($pickup_point_data['withdrawal_time']);
                    unset($pickup_point_data['receive_orders_unavailable_products']);
                    $withdrawal_time = array_map(function($withdrawal_time) use ($id) {
                        $withdrawal_time['pickup_point_id'] = $id;
                        if ($withdrawal_time['closed_store']) {
                            $withdrawal_time['start_hour'] = null;
                            $withdrawal_time['end_hour'] = null;
                        }
                        return $withdrawal_time;
                    }, $address['withdrawal_time']);

                    $this->db->trans_start();
                    $this->model_pickup_point->update($pickup_point_data, $id);
                    $this->model_withdrawal_time->deleteByPickupPointId($id);
                    $this->model_withdrawal_time->create_batch($withdrawal_time);

                    if ($this->db->trans_status() === FALSE){
                        $this->generateResponse(false, 'Não foi possível atualizar os dados do ponto de retirada!');
                        $this->db->trans_rollback();
                    }

                    $this->db->trans_commit();
                }
            } else {
                if ($this->ms_pickup_points->use_ms_shipping) {
                    $this->ms_pickup_points->createPickupPoint($address);
                } else {
                    $this->db->trans_start();
                    $pickup_point_data = $address;
                    unset($pickup_point_data['withdrawal_time']);
                    unset($pickup_point_data['receive_orders_unavailable_products']);

                    $id = $this->model_pickup_point->create($pickup_point_data);

                    $withdrawal_time = array_map(function($withdrawal_time) use ($id) {
                        $withdrawal_time['pickup_point_id'] = $id;
                        if ($withdrawal_time['closed_store']) {
                            $withdrawal_time['start_hour'] = null;
                            $withdrawal_time['end_hour'] = null;
                        }
                        return $withdrawal_time;
                    }, $address['withdrawal_time']);

                    $this->model_withdrawal_time->create_batch($withdrawal_time);

                    if ($this->db->trans_status() === FALSE){
                        $this->generateResponse(false, 'Não foi possível cadastrar os dados do ponto de retirada!');
                        $this->db->trans_rollback();
                    }

                    $this->db->trans_commit();
                }
            }

            $this->generateResponse(true, 'Ponto de retirada e horário de funcionamento cadastrados com sucesso!');
        } catch (Exception $exception) {

            $message = '';
            $data = json_decode($exception->getMessage(), true);

            if ($data['message'] == 'Validation errors') {

                $message .= '<ul>';
                foreach ($data['data'] as $key => $t) {
                    foreach ($data['data'][$key] as $m) {
                        if (isset($m)) {
                            $message .= '<li>' . $m . '</li>';
                        }
                    }
                }
                $message .= '</ul>';

            } else {
                $message = 'Ocorreu um erro ao cadastrar o ponto de retirada. Por favor, tente novamente!';
            }
            $this->generateResponse(false, $message);
        }

    }

    public function saveAddress()
    {
        if (!in_array('createPickUpPoint', $this->permission) && !in_array('updatePickUpPoint', $this->permission)) {
            header('Content-type: application/json');
            $response['status'] = false;
            $response['message'] = 'Usuário sem permissão para fazer essa ação!';
            exit(json_encode($response));
        }

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $temVazio = false;
        foreach ($request as $elemento) {
            $key = array_search($elemento, $request);
            if ($key != 'complemento' && empty($elemento)) {
                if ($temVazio) {
                    header('Content-type: application/json');
                    $response['status'] = false;
                    $response['reason'] = 'form_empty';
                    $response['message'] = 'Por favor, preencha o campo ' . $key . '!';
                    exit(json_encode($response));
                }
                break;
            }
        }

        $data = array(
            'name' => $request['nome'],
            'status' => $request['status'],
            'cep' => $request['cep'],
            'street' => $request['rua'],
            'district' => $request['bairro'],
            'city' => $request['cidade'],
            'uf' => $request['estado'],
            'country' => $request['pais'],
            'number' => $request['numero'],
            'complement' => $request['complemento']
        );

        if (isset($request['store'])) {
            $data['store_id'] = $request['store'];
        } else {
            $data['store_id'] = $this->session->userdata['userstore'];
        }

        $create = true;

        if ($create) {
            header('Content-type: application/json');
            $response['id'] = $create;
            $response['status'] = true;
            $response['message'] = 'Endereço de ponto de retirada cadastrado com sucesso!';
            exit(json_encode($response));
        } else {
            header('Content-type: application/json');
            $response['status'] = false;
            $response['message'] = 'Não foi possível salvar esse endereço. Tente novamente!';
            exit(json_encode($response));
        }

    }

    private function generateResponse($status = false, $message = '')
    {
        header('Content-type: application/json');
        $response['status'] = $status;
        $response['message'] = $message;
        exit(json_encode($response));
    }

}