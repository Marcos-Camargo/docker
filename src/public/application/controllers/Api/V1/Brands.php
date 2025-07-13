<?php

require APPPATH . "controllers/Api/V1/API.php";

class Brands extends API
{
    private $code;

    public function index_get($code = null)
    {
        //if (!$this->app_authorized)
        //    return $this->response(array('success' => false, "message" => 'Feature unavailable.'), REST_Controller::HTTP_UNAUTHORIZED);

        $this->code = $code;

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $result = $this->createArrayBrand();

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
//            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - code: {$this->code}","W");
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);
        }

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    public function index_post()
    {
        
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        if($this->store_id){
            $catalog = $this->getDataCatalogByStore($this->store_id);

            if ($catalog)
               return $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED);
               
        }
            

        // Recupera dados enviado pelo body
        $data   = $this->inputClean();

        // Verifica se foi informado um SKU
        $result = $this->insert($data);

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error'])
            return $this->response($this->returnError($result['data']), REST_Controller::HTTP_NOT_FOUND);

        $this->response(array('success' => true, "message" => $this->lang->line('api_brand_inserted')), REST_Controller::HTTP_CREATED);
    }

    private function insert($data)
    {
        $dataSql = array();

        if(!isset($data->brand)) return array('error' => true, 'data' => $this->lang->line('api_key_brand_not_found'));
        if(count((array)$data->brand) === 0) return array('error' => true, 'data' => $this->lang->line('api_found_no_data'));
        if(!isset($data->brand->name) || (string)$data->brand->name == "") return array('error' => true, 'data' => $this->lang->line('api_name_needs'));

        $name = trim((string)$data->brand->name);

        if($name == "") return array('error' => true, 'data' => $this->lang->line('api_name_needs'));

        if(!$this->verifyNameAvailable($name)) return array('error' => true, 'data' => $this->lang->line('api_name_in_use'));

        // Inserção produto
        $sqlBrand = $this->db->insert_string('brands', array('name' => $name, 'active' => 1));
        $queryBrand = $this->db->query($sqlBrand);

        if (!$queryBrand){
            return array('error' => true, 'data' => $this->lang->line('api_failure_communicate_database'));
        }

        return array('error' => false);
    }

    private function createArrayBrand()
    {
        if($this->code) {
            $query = $this->getBrand($this->code);

            // Verifica se foi encontrado resultados
            if ($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

            $result = $query->first_row();

            return array(
                'brand' => array(
                    array(
                        $result->id => $result->name
                    )
                )
            );
        }

        $brands = array('brands' => array());

        // Consulta
        $query = $this->getBrands();

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        foreach($query->result_array() as $brand){
            array_push($brands['brands'], array($brand['id'] => $brand['name']));
        }

        return $brands;
    }

    private function verifyNameAvailable($name)
    {
        $sql = "SELECT * FROM brands WHERE `name` = ?";
        $query = $this->db->query($sql,array($name));
        return $query->num_rows() === 0 ? true : false;
    }

    private function getBrands()
    {
        $sql = "SELECT * FROM brands";
        return $this->db->query($sql);
    }

    private function getBrand($id)
    {
        $sql = "SELECT * FROM brands WHERE id = ?";
        return $this->db->query($sql, array($id));
    }

}