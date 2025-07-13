<?php

require APPPATH . "controllers/Api/V1/API.php";

class Product_Return extends API
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Model_product_return');
    }

    /**
     * Ocorrências devem ser recebidas via POST
     */
    public function index_put()
    {
        $status = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($status), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Ocorrências devem ser recebidas via POST
     */
    public function index_get()
    {
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou GET, não deveria - GET='.json_encode($_GET), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Recebimento de registros
     */
    public function index_post()
    {   
        // $verifyInit = $this->verifyInit();
        // if(!$verifyInit[0])
        //     return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        
        //date_default_timezone_set('America/Sao_Paulo');
        
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $product_return = $this->cleanGet(json_decode(file_get_contents('php://input'),true));

        $this->log_data('api', $log_name, "Chegou requisição de Devolução de produto.\n\n body=".json_encode($product_return)."\n\n header=".json_encode(getallheaders()), "I");
        //$authentication_token = $this->model_settings->getSettingDatabyName('GS_ProducDevolution_token')['value'];
        
        // valida o body do json
        if (!$product_return) {
            $this->log_data('api', $log_name, "Não existem dados válidos no body do JSON.\n\n body=".file_get_contents('php://input')."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $product_return['requisition_ID'],
                'status' => 'Dados inválidos no body, JSON vazio ou com formatação incorreta',
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
               
        //valida se o order id já foi enviado com esse status, para não receber registros repetidos
        foreach ($product_return["items"] as $item){
            
            $verify_order_ID = $this->Model_product_return->getOrderIdAndStatus($product_return['orderId'],$product_return['status'], $item['skuMarketplace']);
            if($verify_order_ID) {
                $this->log_data('api', $log_name, "Já existe um registro para o item {$item['skuMarketplace']} com o mesmo status.\n\n body=".json_encode($product_return)."\n\n header=".json_encode(getallheaders()), "E");
                return $this->response(array( 
                    'status' => "Já existe um registro para o item {$item['skuMarketplace']} do pedido {$product_return['orderId']} com o mesmo status", 
                    'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
            }
        }    
        //$productReturn['ocorrencia']['linkComprovante'] = urldecode($productReturn['ocorrencia']['linkComprovante']);
        $response_array = array (
            'response' => []
        );
        foreach ($product_return["items"] as $item){
            $product_devolution_data = array(
                'logistic_operator_type'    => $product_return['logisticOperatorType'],
                'logistic_operator_name'    => $product_return['logisticOperatorName'],
                'status'                    => $product_return['status'],
                'order_id'                  => $product_return['orderId'],
                'tracking_Number'           => $product_return['trackingNumber'],
                'reverse_logistic_code'     => $product_return['reverseLogisticCode'],
                'devolution_invoice_number' => $product_return['devolutionInvoiceNumber'],
                'return_total_value'        => $product_return['returnTotalValue'],  
                'devolution_request_date'   => $product_return['devolutionRequestDate'], 
                'devolution_contract_date'  => $product_return['devolutionContractDate'], 
                'devolution_date'           => $product_return['devolutionDate'],
                'return_shipping_value'     => $product_return['returnShippingValue'],
                'sku_marketplace'           => $item['skuMarketplace'],
                'quantity_requested'        => $item['quantityRequested'], 
                'quantity_in_order'         => $item['quantityInOrder'],                  
                'motive'                    => $item['motive']  
            );

            $create_data = $this->Model_product_return->create($product_devolution_data);
            $response_add = array(
                'created_id'              => $create_data,
                'product_devolution_data' => $product_devolution_data
            );
            array_push($response_array['response'], $response_add);
        };
        if($create_data == False) {
            $this->log_data('batch', $log_name, "Não foi possível criar o registro na tabela.\n\n body=".json_encode($product_return)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'orderId' => $product_return['order_id'], 
                'status' => 'Não foi possível criar o registro na tabela do banco de dados', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
        return $this->response($response_array);
    }
}