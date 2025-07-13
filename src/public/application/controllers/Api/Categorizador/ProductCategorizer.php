<?php

require APPPATH . "controllers/Api/V1/API.php";

class ProductCategorizer extends API
{

    public function __construct() {
        parent::__construct();
        
        $this->load->model('Model_products');

    }

    public function verifyInit($validateStoreProvider = true)
    {
        // Verifica se foram enviados todos os headers
        $headers = $this->verifyHeader(getallheaders());

        // Não foram enviado todos os headers
        if(!$headers[0]){
            $this->log_data('api',__CLASS__ . "/" . __FUNCTION__,"Not all headers were sent. Missing header: {$headers[1]}","W");
            return array(false, array('success' => false, 'message' => "Not all headers were sent. Missing header: {$headers[1]}"));
        }

        $headers = $headers[1];

        // Decodifica token
        $decodeKeyAPI = $this->decodeKeyAPI($headers['x-api-key']);

        // Não possível decodificar a key_api
        if(is_array($decodeKeyAPI)) return array(false, $decodeKeyAPI);

        return array(true, $headers);
    }

    public function verifyHeader($header_request, $validateStoreProvider = true)
    {
        $headers = array();

        // Headers obrigatórios para requisição
        $headers_valid = array(
            "x-api-key",
            "accept",
            "content-type",
        );

        // headers recuperado na solicitação
        foreach ($header_request as $header => $value)
            $headers[strtolower($header)] = $value;

        // Verifica se não foram enviados todos os headers
        foreach ($headers_valid as $header_valid)
            if(!array_key_exists($header_valid , $headers)) return array(false, $header_valid);

        return array(true, $headers);
    }

   public function index_post()
    {   
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $body = file_get_contents('php://input');


        // Verificação inicial
        $verifyInit = $this->verifyInit();
         // Verificação inicial
         $verifyInit = $this->verifyInit();
         if(!$verifyInit[0])
             return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
         // Recupera dados enviado pelo body
         $product_category = json_decode($body, true);


        $this->log_data('api', $log_name, "Chegou requisição do Categorização de produto.\n\n body=".json_encode($product_category)."\n\n header=".json_encode(getallheaders()), "I");
        if (!$product_category) {
            $this->log_data('api', $log_name, "Não existem dados válidos no body do JSON.\n\n body=".file_get_contents('php://input')."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'products_to_categorizer' => $product_category['products_to_categorizer'],
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
        $updated_products = [];
        $unupdated_products = [];
        foreach ($product_category["products_to_categorizer"] as $item){
            $verify_product_ID = $this->Model_products->getProductData(0, (int)$item['product_id']);
            if($verify_product_ID['category_id'] != '[""]' && $verify_product_ID['category_id'] != null ) {
                $this->log_data('api', $log_name, "O produto" . $item['product_id'] . " já possui categoria.\n\n body=".json_encode($product_category)."\n\n header=".json_encode(getallheaders()), "E");
                array_push($unupdated_products,['product_id' => $item['product_id']]);
                continue;
            }

            $this->log_data('api', $log_name, "O produto {$item['product_id']} teve a categoria atualizada.\n\n body=".json_encode($product_category)."\n\n header=".json_encode(getallheaders()), "E");
            $product_category_data = array(
                'category_id' => '["'.$item['category_id'].'"]'
            );
            array_push($updated_products,['product_id' => $item['product_id']]);
            $this->Model_products->update($product_category_data, $item['product_id']);
            
        }
        return $this->response(array(
            'status' => "Os produtos foram processados",
            'updated' => $updated_products,
            'unupdated' => $unupdated_products,
            'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_ACCEPTED);    
    }
}