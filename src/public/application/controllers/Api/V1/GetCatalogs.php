<?php

use phpDocumentor\Reflection\Types\Boolean;

require APPPATH . "controllers/Api/V1/API.php";

class GetCatalogs extends API
{
    // code 01: Processamento ok;
    // code 21: Não foi encontrado nenhum catálogo cadastrado na tabela "catalogs" ou a própria tabela "catalogs" não existe no ambiente;
    // code 24: Houve algum problema na formatação dos dados para exibição que precisa ser analisado;

    //private const NO_CATALOG = 'No registered catalog found.';
    //private const NO_DATA    = 'The requested data could not be displayed. Contact support.';

    private $header;
    private $catalogs;
    private $catalogsForDisplay = [];
	
	public function __construct()
	{
		parent::__construct();
        $this->load->model('model_catalogs');
        $this->header = getallheaders();
	}

    public function index_get()
    {
        $checkAuth = $this->checkAuth($this->header);

        if(!$checkAuth[0]) {
            return $this->response($checkAuth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $hasCatalogs = $this->getCatalogs();

        if (!$hasCatalogs) {
            return $this->response(array('success' => true, 'code' => '21', 'result' => $this->lang->line('api_catalog_no_found')), REST_Controller::HTTP_OK);
        }

        $hasOk = $this->formatDataForDisplay();

        if (!$hasOk) {
            return $this->response(array('success' => false, 'code' => '24', 'result' => $this->lang->line('api_not_diplayed')), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array('success' => true, 'code' => '01', 'result' => $this->catalogsForDisplay), REST_Controller::HTTP_OK);
    }

    public function stores_get() 
    {
        $checkAuth = $this->checkAuth($this->header);

        if(!$checkAuth[0]) {
            return $this->response($checkAuth[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
        
        $records = $this->model_catalogs->getStoresInCatalogs();
        return $this->response(array('success' => true, 'code' => '01', 'result' => $records), REST_Controller::HTTP_OK);
    }

    private function getCatalogs() : bool
    {
        $catalogs = $this->model_catalogs->getAllCatalogs();

        if (!$catalogs) {
            return false;
        }

        $this->catalogs = $catalogs;

        return true;
    }

    private function formatDataForDisplay() : bool
    {
        if (!$this->catalogs) {
            return false;
        }

        foreach ($this->catalogs as $catalog) {
            $data = [
                'id'          => $catalog['id'],
                'name'        => $catalog['name'],
                'description' => $catalog['description'],
                'status'      => $catalog['status'] === "1" ? 'active' : 'inactive'
            ];
            array_push($this->catalogsForDisplay, $data);
        }

        if (!$this->catalogsForDisplay) {
            return false;
        }

        return true;
    }
}