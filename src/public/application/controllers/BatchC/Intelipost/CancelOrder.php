<?php

require APPPATH . "libraries/CalculoFrete.php";

class CancelOrder extends BatchBackground_Controller {

    private $frete;

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->library('ordersMarketplace');

        $this->frete = new CalculoFrete();
    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        $this->cancelOrders();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();

    }

    private function cancelOrders()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $orders = $this->model_orders->getOrdersForCancelIntelipost();

        foreach ($orders as $order) {

            // pego as informações da loja
            $store = $this->model_stores->getStoresData($order['store_id']);

            // verifico se realmente o seller é intelipost
            if ($store['freight_seller'] != 1 || ($store['freight_seller_type'] != 3 && $store['freight_seller_type'] != 4)) {
                echo "Pedido está configurado pela intelipost, mas a loja não está.\n\nSTORE=".json_encode($store)."\nORDER=".json_encode($order)."\n";
                $this->log_data('batch',$log_name,"Pedido está configurado pela intelipost, mas a loja não está.\n\nSTORE=".json_encode($store)."\nORDER=".json_encode($order),"W");
                //$this->model_orders->updatePaidStatus($order['id'], 97);
                continue;
            }

            // verifico se o seller usa sua logistica ou do sellercenter
            if ($store['freight_seller_type'] == 3) $tokenIntelipost = $store['freight_seller_end_point'];
            else {
                $rowSettings     = $this->model_settings->getSettingDatabyName('token_intelipost_sellercenter');
                $tokenIntelipost = $rowSettings['value'];
            }

            $jsonCancel = json_encode(array('order_number' => $order['id']));
            $url = "https://api.intelipost.com.br/api/v1/shipment_order/cancel_shipment_order/";
            $postCancelOrder = $this->frete->sendRest($url, array("api-key: {$tokenIntelipost}"), $jsonCancel, 'POST');
            $httpCode = (int)$postCancelOrder['httpcode'];

            if ($httpCode != 200) {
                echo "ERRO para cancelar o pedido ( {$order['id']} ) na intelipost. \n\nhttpcode={$httpCode}\ncontent={$postCancelOrder['content']}\n";
                $this->log_data('batch',$log_name, "ERRO para cancelar o pedido ( {$order['id']} ) na intelipost. \n\nhttpcode={$httpCode}\npayload_send={$jsonCancel}\ncontent={$postCancelOrder['content']}","E");
                continue;
            }

            $this->log_data('batch',$log_name, "Pedido ( {$order['id']} ) cancelado na intelipost. \n\nhttpcode={$httpCode}\npayload_send={$jsonCancel}\ncontent={$postCancelOrder['content']}","I");

            $this->ordersmarketplace->cancelOrder($order['id'], false);
        }
    }

}