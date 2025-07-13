<?php

require APPPATH . "controllers/Api/V1/API.php";

class PickupStore extends API
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_orders');
        $this->load->model('model_freights');

        if (!$this->model_settings->getValueIfAtiveByName('occ_pickupstore')) {
            $this->response($this->lang->line('api_unauthorized_request'), REST_Controller::HTTP_UNAUTHORIZED);
            die;
        }
    }

    public function index_post($orderId, $externalId = false)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
        $orderId = xssClean($orderId);
        $externalId = xssClean($externalId);

        try {
            // Recupera dados enviado pelo body
            $data = json_decode($this->input->raw_input_stream); //raw_input_stream

            if (!property_exists($data, 'status') || ($data->status !== 'ENTREGUE' && $data->status !== 'AGUARDANDO COLETA')) {
                throw new Exception("O envio do status é obrigatório.");
            }
            
            if($externalId == 'externalId'){
                throw new Exception("externalId não é suportado pelo endpoint."); 
            }else{
                $order = $this->model_orders->getOrdersData(0,$orderId);
            }

            if(!$order){
                throw new Exception("Pedido não encontrado."); 
            }

            if ($data->status === 'ENTREGUE'){

                if ($order['paid_status'] != 58){
                    throw new Exception("Ação permitida somente para pedidos no status 43.");
                }

                $delivered_date = date('Y-m-d H:i:s');

                $updateOrder = $this->model_orders->updateByOrigin($order['id'], array(
                    'paid_status' => 60,
                    'data_entrega' => $delivered_date
                ));
        
                $updateFreight = $this->model_freights->updateFreightsOrderId($order['id'], array(
                    'date_delivered' => $delivered_date
                ));

                if (!$updateOrder || !$updateFreight) {
                    throw new Exception("Erro ao atualizar o pedido: ".$order['id']);
                }

            }

            if ($data->status === 'AGUARDANDO COLETA'){

                if ($order['paid_status'] != 43){
                    throw new Exception("Ação permitida somente para pedidos no status 43.");
                }

                $shipping_date = date('Y-m-d H:i:s');
                $updateOrder = $this->model_orders->updateByOrigin($order['id'], array(
                    'paid_status' => 55,
                    'data_envio'  => $shipping_date
                ));

                if (!$updateOrder) {
                    throw new Exception("Erro ao atualizar o pedido: ".$order['id']);
                }
            }

        } catch (Exception | Error $exception) {
            return $this->response(array(
                "success" => false,
                "message" => $exception->getMessage()
            ), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array(
            "success" => true,
            "message" => 'Pedido atualizado com sucesso'
        ), REST_Controller::HTTP_OK);
    }


}
