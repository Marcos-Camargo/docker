<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_Loader $load
 *
 * @property Model_cycles $model_cycles
 *
 */
class Cycles extends API
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_cycles');

        $this->max_per_page = 100;
        $this->cycle_type = 'all';

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

        $cycle_type = empty($search['cycle_by']) ? $this->cycle_type : $search['cycle_by'];

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

        $response = $this->model_cycles->getListCycles(false, $cycle_type, $filters, ['page' => $page, 'per_page' => $per_page, 'page_per_page' => $page_per_page, 'search' => $search]);

        $datas = [];

        if ($response['qty'] > 0) {
            foreach ($response['data'] as $cycle) {
                $data = [
                    'id' => $cycle['pmc_id'],
                    'cycle_by' => $cycle['store_id'] ? 'loja' : 'marketplace',
                    'marketplace' => $cycle['descloja'],
                ];

                if ($cycle_type === "store" || $cycle_type === "all") {
                    $data['store'] = $cycle['name'] ?? "";
                }

                $data['start_date'] = $cycle['data_inicio'];
                $data['end_date'] = $cycle['data_fim'];
                $data['payment_date'] = $cycle['data_pagamento'];
                $data['payment_date_conecta'] = $cycle['data_pagamento_conecta'] ?? "";
                $data['cut_date'] = $cycle['data_usada'];
                $data['status'] = $cycle['ativo'] == 1 ? 'Ativo' : 'Inativo';

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
     * Post All Orders to Anticipate.
     *
     * @return void
     */
    public function index_post()
    {

        $this->header = array_change_key_case(getallheaders());
        $check_auth   = $this->checkAuth($this->header);

        if(!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

        if(!isset($data['ciclos']) || !is_array($data['ciclos'])){
            return $this->response($this->returnError('Erro ao identificar os pedidos.'), REST_Controller::HTTP_NOT_FOUND);
        }

        if(count($data['ciclos']) == 0){
            return $this->response($this->returnError('Não encontramos ciclos para salvar.'), REST_Controller::HTTP_NOT_FOUND);
        }

        $cycles = $this->generatePostRequest($data['ciclos']);

        $errors = [];
        foreach($cycles as $cycle){

            $error = [];

            if(isset($cycle['vHiddenId']) && $cycle['vHiddenId'] == 0){
                unset($cycle["vHiddenId"]);
            }

            if(!$this->model_cycles->exists($cycle['vMarketplace'], "stores_mkts_linked", "id_mkt")){
                $error[] = "Marketplace informado com o ID {$cycle['vMarketplace']} não foi encontrado.";
            }

            if(!is_null($cycle['vStores']) && !$this->model_cycles->exists($cycle['vStores'], "stores")){
                $error[] = "Loja informada com o ID {$cycle['vStores']} não foi encontrada.";
            }

//            if($this->model_cycles->getCutDates(true, $cycle['vDateCut'])){
//                $error[] = "Data de corte não encontrada.";
//            }

            $check_order = $this->model_cycles->checkValidCycles($cycle['vMarketplace'], $cycle['vDataInicio'], $cycle['vDataFim'], $cycle['vStores']);

            if(!$check_order){
                $error[] = "Não é possível cadastrar/editar este ciclo, por favor revise as datas de início de fim para que não haja conflito com outros ciclos.";
            }

            if(count($error) > 0){
                $cycle["errors"] = $error;
                $errors[] = $cycle;
            }
        }

        if(count($errors) > 0){
            $message = [
                "success" => false,
                "message" => "Não foi possível salvar alguns ciclos enviados. Corrija os erros apresentados em cada ciclo e tente novamente.",
                "cycles" => $errors
            ];

            $this->log_data('api', __CLASS__ . "/" . __FUNCTION__, json_encode($message), "E");

            return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_OK);
        }else{

            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__," Host: ".$this->header["host"]." E-mail:".$this->header["x-email"]."- Ciclos salvos: " . json_encode($cycles),"I");

            foreach($cycles as $cycle){

                $vDataPagamentoMkt = ltrim($cycle['vDataPagamentoMkt'], '0');
                $vDataPagamentoConectala = ltrim($cycle['vDataPagamentoConectala'], '0');
                $vDateCut = $this->model_cycles->getCutDates(true, $cycle['vDateCut']);
                $vStore = empty($cycle['vStores']) ? null : $cycle['vStores'];

                $save = $this->model_cycles->saveCycle($cycle['vDataInicio'], $cycle['vDataFim'], $vDataPagamentoMkt, $vDataPagamentoConectala, $vDateCut, $vStore, 0, $cycle['vMarketplace'], $cycle['vStores']);
                if(!$save){
                    $message = [
                        "success" => false,
                        "message" => "Ocorreu um erro inesperado ao salvar o(s) ciclo(s). Tente novamente!",
                        "cycles" => []
                    ];
                    return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_OK);
                }
            }

            return $this->response(array('success' => true, 'result' => 'Ciclos salvos com sucesso!'), REST_Controller::HTTP_OK);
        }


    }

    public function generatePostRequest($cycles = []): array
    {
        $request = [];

        foreach ($cycles as $cycle){
            $request[] = [
                'vHiddenId' => 0,
                'vDataInicio' => $cycle['start_date'],
                'vDataFim' => $cycle['end_date'],
                'vMarketplace' => $cycle['marketplace_id'],
                'vStores' => $cycle['store_id'] ?? null,
                'vDataPagamentoMkt' => $cycle['payment_date'],
                'vDataPagamentoConectala' => $cycle['payment_date_conecta'],
                'vDateCut' => $cycle['cut_date'],
            ];
        }

        return $request;
    }

    private function generateRequestFilters($search = []): array
    {

        $request = [];

        if ($search['id']) {
            $request['vCycleId'] = $search['id'];
        }
        if ($search['start_day']) {
            $request['vInicio'] = $search['start_day'];
        }
        if ($search['end_day']) {
            $request['vFim'] = $search['end_day'];
        }
        if ($search['payment_day']) {
            $request['vDataPagamento'] = $search['payment_day'];
        }
        if ($search['payment_day_conecta']) {
            $request['vDataPagamentoConectala'] = $search['payment_day_conecta'];
        }
        if ($search['active']) {
            $request['active'] = $search['active'];
        }
        if ($search['active']) {
            $request['active'] = $search['active'];
        }
        if ($search['gatilho']) {
            $request['gatilho'] = $search['gatilho'];
        }
        if ($search['store']) {
            $request['vStore'] = $search['store'];
        }
        if ($search['store_name']) {
            $request['store_name'] = $search['store_name'];
        }
        if ($search['marketplace_name']) {
            $request['marketplace_name'] = $search['marketplace_name'];
        }
        if ($search['start_date']) {
            $request['vInicio'] = $search['start_date'];
        }
        if ($search['end_date']) {
            $request['vFim'] = $search['end_date'];
        }
        if ($search['payment_date']) {
            $request['vDataPagamento'] = $search['payment_date'];
        }
        if ($search['payment_date_conecta']) {
            $request['vDataPagamentoConectala'] = $search['payment_date_conecta'];
        }

        return $request;

    }
}
