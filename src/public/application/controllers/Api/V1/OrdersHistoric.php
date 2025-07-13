<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_settings $model_settings
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_orders $model_orders
 * @property Model_orders_item $model_orders_item
 * @property Model_stores $model_stores
 * @property Model_providers $model_providers
 * @property Model_integrations $model_integrations
 */

class OrdersHistoric extends API
{
    private $cod_order;
    private $filters;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders');
        $this->load->model('model_orders_item');
        $this->load->model('model_stores');
        $this->load->model('model_providers');
        $this->load->model('model_integrations');
        $this->load->library('ordersMarketplace');
    }

    public function index_get($cod_order = null)
    {
        $cod_order = xssClean($cod_order);
        $this->endPointFunction = __FUNCTION__;
        $this->cod_order = $cod_order;

        if ($cod_order === null) {
            return $this->response($this->returnError($this->lang->line('api_order_not_found')), REST_Controller::HTTP_NOT_FOUND);
        }

        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
        // filtros
        $this->filters = cleanGet($_GET) ?? null;

        // encontrou codigo do pedido
        if ($cod_order !== null) {$result = $this->createArrayOrder();}

        // Verifica se foram encontrado resultados
        if (isset($result['error']) && $result['error']) {
            return $this->response($this->returnError($result['data']), $result['data'] == $this->lang->line('api_no_results_where') ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_BAD_REQUEST);
        }
        $response = array('success' => true, 'result' => $result);

        $this->response($response, REST_Controller::HTTP_OK);
    }

    private function createArrayOrder()
    {

        // consulta o seller center
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $sellercenter = $settingSellerCenter['value'];

        // Consulta
        $queryOrder = $this->getDataOrder();
        $queryHistoric = $this->getDataHistoric();        

        // Verifica se foi encontrado resultados
        if ($queryOrder->num_rows() === 0) {return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));}
        if ($queryHistoric->num_rows() === 0) {return array('error' => true, 'data' => $this->lang->line('api_no_results_where'));}

        $resultOrder = $queryOrder->result_array();
        $resultHistoric = $queryHistoric->result_array();

        // Dados pedido
        $order = $resultOrder[0];

        // mesma lógica da tela do pedido
        $store_id = $this->store_id > 0 ? $this->store_id : $order['store_id'];

        $arrhistoric = array();
        if (count($resultHistoric)) {
            foreach ($resultHistoric as $key => $historic) {
                
                $date_notification = null;

                switch ($historic['status']) {
                    case 3: 
                    case 50:
                        $date_notification = $order['data_mkt_invoice'] ?? ''; 
                        break;
                    case 5:
                    case 45:
                    case 55: 
                        $date_notification = $order['data_mkt_sent'] ?? ''; 
                        break;
                    case 6: 
                    case 60:
                        $date_notification = $order['data_mkt_delivered'] ?? ''; 
                        break;
                }
              
                array_push($arrhistoric, array(
                    "status_id"         => $historic['status'],
                    "status"            => $this->ordersmarketplace->getStatusOrder($historic['status']),
                    "date"              => $historic['date_status_update'],
                    "date_notification" => $date_notification
                ));              
            }
        }

        return array(
            'orderHistoric' => array(
                "order_code" => $this->changeType($this->cod_order, "int"),
                "historic" => $arrhistoric
            )
        );
    }        

    private function getDataOrder()
    {
        $sql = "SELECT 
                orders.store_id as store_id,
                orders.company_id as company_id,
                DATE_FORMAT(orders.date_time,'%Y-%m-%d %H:%i:%s') as date_time_order,
                orders.data_pago as data_pago_order,
                orders.data_entrega,
                orders.data_mkt_invoice,
                orders.data_mkt_sent,
                orders.data_mkt_delivered,
                orders.data_envio
                FROM orders 
                JOIN orders_item ON orders.id = orders_item.order_id 
                WHERE orders.id = ?";

        if ($this->store_id > 0) {
            $sql .= " AND orders.store_id = {$this->store_id} AND orders_item.store_id = {$this->store_id}";
        }
        
        return $this->db->query($sql, array($this->cod_order));
    }

    private function getDataHistoric()
    {
        $sql = "SELECT * FROM order_status WHERE order_id = ? ORDER BY id";
        return $this->db->query($sql, array($this->cod_order));
    }

}
