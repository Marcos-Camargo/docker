<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_Loader $load
 *
 * @property Model_cycles $model_cycles
 */
class Models extends API
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_cycles');

        $this->max_per_page = 100;

    }

    /**
     * Return an object of Store
     *
     * @return mixed
     */
    public function index_get()
    {

        $this->header = array_change_key_case(getallheaders());
        $check_auth = $this->checkAuth($this->header);

        if (!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $search = $this->cleanGet();

        $page = $search['page'] ?? 0;
        $per_page = $search['per_page'] ?? $this->max_per_page;
        $page = filter_var($page, FILTER_VALIDATE_INT);
        $per_page = filter_var($per_page, FILTER_VALIDATE_INT);

        if ($page <= 0) {
            $page = 1;
        }
        if ($per_page <= 0) {
            $per_page = 1;
        }
        if ($per_page > $this->max_per_page) {
            $per_page = $this->max_per_page;
        }

        $page_per_page = ($page <= 1 ? 0 : $page) * $per_page;

        $filters = $this->generateRequestFilters($search);

        $response = $this->model_cycles->getModelCycles(['page' => $page, 'per_page' => $per_page, 'page_per_page' => $page_per_page], $filters);

        $datas = [];

        if ($response['qty'] > 0) {
            foreach ($response['data'] as $model) {
                $data = [
                    'id' => $model['id'],
                    'start_date' => $model['data_inicio'],
                    'end_date' => $model['data_fim'],
                    'payment_date' => $model['data_pagamento']
                ];
                $datas[] = $data;
            }
        }

        $result = [
            'page' => $page,
            'items_per_page' => $response['qty'],
            'data' => $datas
        ];

        return $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);

    }

    /**
     * Post Model Cycles
     *
     * @return void
     */
    public function index_post()
    {

        $this->header = array_change_key_case(getallheaders());
        $check_auth = $this->checkAuth($this->header);

        if (!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

        if(!isset($data['models']) || !is_array($data['models'])){
            return $this->response($this->returnError('Erro ao identificar os modelos de ciclos.'), REST_Controller::HTTP_NOT_FOUND);
        }

        if(count($data['models']) == 0){
            return $this->response($this->returnError('Não encontramos modelos de ciclos para salvar.'), REST_Controller::HTTP_NOT_FOUND);
        }

        $models = $data['models'];

        $errors = [];
        foreach ($models as $model) {

            $error = [];

            $data = $this->generatePostRequest($model);
            if ($this->model_cycles->checkModelExists($data)) {
                $error[] = "Já existe um modelo de ciclo cadastrado com os mesmos dados informados.";
            }

            if (count($error) > 0) {
                $model["errors"] = $error;
                $errors[] = $model;
            }
        }

        if (count($errors) > 0) {
            $message = [
                "success" => false,
                "message" => "Não foi possível salvar alguns modelos enviados. Corrija os erros apresentados em cada modelo e tente novamente.",
                "cycles" => $errors
            ];

            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, json_encode($message), "E");

            return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_OK);
        } else {

            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " Host: " . $this->header["host"] . " E-mail:" . $this->header["x-email"] . "- Ciclos salvos: " . json_encode($cycles), "I");

            foreach ($models as $model) {

                $data = $this->generatePostRequest($model);
                if (!$this->model_cycles->checkModelExists($data)) {
                    $save = $this->model_cycles->insertModel($data);
                }
                if (!$save) {
                    $message = [
                        "success" => false,
                        "message" => "Ocorreu um erro inesperado ao salvar o(s) modelo(s). Tente novamente!",
                        "cycles" => []
                    ];
                    return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_OK);
                }
            }

            return $this->response(array('success' => true, 'result' => 'Modelos de ciclos salvos com sucesso!'), REST_Controller::HTTP_OK);
        }


    }

    private function generatePostRequest($data = []): array
    {

        $request = [];

        if ($data['start_day']) {
            $request['data_inicio'] = $data['start_day'];
        }
        if ($data['end_day']) {
            $request['data_fim'] = $data['end_day'];
        }
        if ($data['payment_day']) {
            $request['data_pagamento'] = $data['payment_day'];
        }

        return $request;

    }

    private function generateRequestFilters($search = []): array
    {

        $request = [];

        if ($search['id']) {
            $request['id'] = $search['id'];
        }
        if ($search['start_day']) {
            $request['start_day'] = $search['start_day'];
        }
        if ($search['end_day']) {
            $request['end_day'] = $search['end_day'];
        }
        if ($search['payment_day']) {
            $request['payment_day'] = $search['payment_day'];
        }

        return $request;

    }
}
