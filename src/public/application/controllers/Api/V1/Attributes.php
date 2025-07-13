<?php

require APPPATH . "controllers/Api/V1/API.php";

class Attributes extends API
{
    public function index_get($id = null, $int_to = null)
    {
        
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        if(!$id){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_category_not_informed'),"W");
            return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_category_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
        }

        if(!$int_to){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->lang->line('api_mkt_not_informed'),"W");
            return $this->response($this->returnError(array('error' => true, 'data' => $this->lang->line('api_mkt_not_informed'))), REST_Controller::HTTP_NOT_FOUND);
        }

        $result = $this->createArrayAttributes($id, $int_to);

        // Verifica se foram encontrado resultados
        if(isset($result['error']) && $result['error']){
//            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__, $this->returnError()['message'] . " - code: {$this->cod}","W");
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
    private function createArrayAttributes($category_id, $int_to)
    {
        $arrAttributes = array();

        $query = $this->getDataAttributes($category_id, $int_to);

        // Verifica se foi encontrado resultados
        if($query->num_rows() === 0) return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));

        $attributes = $query->result_array();

        foreach ($attributes as $attribute){
            
            $values = array();

            if ($attribute['valor'] != '') {
                $attr_values = json_decode($attribute['valor'], true);
                foreach($attr_values as $attr_value) {
                    if ($attribute['int_to'] == 'VIA') {
                        array_push($values, array(
                            'id' => $attr_value['udaValueId'],
                            'value' => $attr_value['udaValue']
                        ));    
                    }
                    else if ($attribute['int_to'] == 'ML') {
                        array_push($values, array(
                            'id' => $attr_value['id'],
                            'value' => $attr_value['name']
                        ));    
                    }
                    else if ($attribute['int_to'] == 'NovoMundo') {
                        array_push($values, array(
                            'id' => $attr_value['FieldValueId'],
                            'value' => $attr_value['Value']
                        ));    
                    }

                }
            }

            array_push($arrAttributes, array(
                "attribute_id" => $attribute['id_atributo'],
                "name" => $attribute['nome'],
                "required" => ($attribute['obrigatorio'] == 1) ? true : false,
                "variation" => ($attribute['variacao'] == 1) ? true : false,
                "type" => $attribute['tipo'],
                "values" => $values
            ));
        }

        return $arrAttributes;
    }

    private function getDataAttributes($category_id, $int_to)
    {
        $sql = "select id_categoria , id_atributo, acm.int_to, nome, obrigatorio, variacao, tipo, valor 
        from atributos_categorias_marketplaces acm
        join categorias_marketplaces cm on cm.category_marketplace_id = acm.id_categoria 
        where cm.category_id = ? and cm.int_to = ?";
        return $this->db->query($sql,array($category_id, $int_to ));
    }
}