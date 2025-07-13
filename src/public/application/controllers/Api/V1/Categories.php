<?php

require APPPATH . "controllers/Api/V1/API.php";

class Categories extends API
{
    private $cod;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_atributos_categorias_marketplaces');
    }

    public function index_get($cod = null)
    {
        
        $this->cod = $cod;

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $result = $this->createArrayCategory();

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
//            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - code: {$this->cod}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    public function attributes_get($category_id) {
        //if (!$this->app_authorized)
        //    return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->cod = $category_id;

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $result = $this->createArrayAttributes();

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }


    /**
     * Cria array formatado para retorno
     *
     * @param null $cod
     * @return array
     */
    private function createArrayCategory()
    {
        $arrCategories = array();

        $query = $this->getDataCategory();

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $categories = $query->result_array();

        foreach ($categories as $cotegory){
            array_push($arrCategories, array(
                "code" => $this->changeType($cotegory['code'], "int"),
                "name" => $cotegory['name'],
                "status" => $cotegory['active'] == 1 ? "enabled" : "disabled",
                "update_date" => $cotegory['update_date'],
                "volume" => array(
                    "code" => $this->changeType($cotegory['codigo'], "int"),
                    "products" => $cotegory['produto']
                ),
            ));
        }

        return $arrCategories;

    }

    private function getDataCategory()
    {
        $where = "";
        if($this->cod) $where = "WHERE c.id = ".$this->db->escape($this->cod);

        $sql = "SELECT *, c.data_alteracao as update_date, c.id as code 
                FROM categories as c 
                LEFT JOIN tipos_volumes as tv ON c.tipo_volume_id = tv.id 
                {$where}";
        return $this->db->query($sql);
    }

    private function createArrayAttributes()
    {
        $arrAttributes = array();

        $attributes = $this->getDataAttributes();

        // Verifica se foi encontrado resultados
        if(count($attributes) === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        foreach ($attributes as $attribute){
            array_push($arrAttributes, array(
                "category_id" => $attribute['category_id'],
                "attribute_id" => $attribute['id_atributo'],
                "name" => $attribute['nome'],
                "required" => $attribute['obrigatorio'] == 1,
                "variation" => $attribute['variacao'] == 1,
                "type" => $attribute['tipo'],
                "values" => $attribute['valor'] == '[]' ? null : $attribute['valor']
            ));
        }

        return $arrAttributes;
    }

    private function getDataAttributes(){
        return $this->model_atributos_categorias_marketplaces->getAllAtributesByCategory($this->cod);
    }

}