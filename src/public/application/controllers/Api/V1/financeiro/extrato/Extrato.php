<?php

require APPPATH . "controllers/Api/V1/API.php";

class Extrato extends API
{

    private $params = array('source' => 'api');

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_orders');
        $this->load->model('model_billet');
        $this->load->model('model_iugu');
        $this->load->model('model_payment');
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
        $check_auth   = $this->checkAuth($this->header);

        if (!$check_auth[0]) {
            return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $search = $this->cleanGet();

        $page       = $search['page'] ?? 1;
        $per_page   = $search['per_page'] ?? $this->max_per_page;
        $page       = filter_var($page, FILTER_VALIDATE_INT);
        $per_page   = filter_var($per_page, FILTER_VALIDATE_INT);

        if ($page <= 0){
            $page = 1;
        }
        if ($per_page <= 0){
            $per_page = 1;
        }
        if ($per_page > $this->max_per_page) {
            $per_page = $this->max_per_page;
        }

        if(isset($search['page']) && $per_page > 100){
            $per_page = 100;
        }

        $page--;
        $page_per_page = $page * $per_page;

        $params = array('source' => 'api', 'length' => $per_page, 'start' => $page_per_page, 'page' => ($page + 1));
        $this->load->library('Extrato/ExtratoLibrary', $params);

        $response = $this->extratolibrary->extratopedidos();

        return $this->response(array('success' => true, 'result' => $response), REST_Controller::HTTP_OK);

    }
}
