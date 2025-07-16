<?php
use App\Libraries\Enum\AnticipationStatusEnum;
use App\Libraries\Enum\AnticipationStatusFilterEnum;

/**
 * @property CI_DB_query_builder $db
 */
class Model_orders extends CI_Model
{
    const TABLE = 'orders';
    public $PAID_STATUS = [
        'awaiting_payment'                  => 1,
        'processing_payment'                => 2,
        'awaiting_billing'                  => 3,
        'awaiting_pickup_shipping'          => 4,
        'on_carriage'                       => 5,
        'wait_to_set_conclude'              => 5,
        'delivered'                         => 6,
        'in_refund'                         => 7,
        'refunded'                          => 8,
        'checking_invoice'                  => 9,
        'wait_tracking'                     => 40,
        'waiting_issue_label'               => 41,
        'billed_on_marketplace'             => 43,
        'sent_on_marketplace'               => 45,
        'nfe_sent_to_marketplace'           => 50,
        'tracking'                          => 51,
        'sent_trace_to_marketplace'         => 51,
        'sented_nfe_to_marketplace'         => 52,
        'invoice_status'                    => 52,
        'awaiting_pickup_or_shipping'       => 53,
        'wait_to_send_to_marketplace_sent'  => 55,
        'invoice_with_error'                => 57,
        'awaiting_withdrawal'               => 58,
        'delivered_reported_buyer'          => 60,
        'refunded_sent_to_marketplace'      => 81,
        'canceled_by_seller'                => 95,
        'canceled_before_payment'           => 96,
        'canceled_after_payment'            => 97,
        'manual_tracking_hire'              => 101,

        'finished_order' => [6, 60, 95, 96, 97, 98, 99], // Pedido finalizado
        'can_delete_tracking' => [40, 43, 51, 53], // Pode remover rastreio

        'orders_cancel' => [
            7, // in_refund
            8, // refunded
            81, // refunded_sent_to_marketplace
            95, // canceled_by_seller
            96, // canceled_before_payment
            97,  // canceled_after_payment
            99 //cancelado
        ],

        'orders_cancel_after_paid' => [
            95, // canceled_by_seller
            97 // canceled_after_payment
        ],

        'uninvoiced' => [
            1, // awaiting_payment
            2, // processing_payment
            3, // awaiting_billing
            96 // canceled_before_payment
        ],

        'can_printing_tag' => [4,5,40,41,43,50,51,52,53] //pode imprimir etiqueta
    ];

    public $PENALTY_CANCEL = [
        'no_penalty'    => '0-Sem penalidade',
        'seller'        => '1-Seller',
        'sellercenter'  => '2-Seller Center',
        'marketplace'   => '3-Marketplace',
    ];


    public function __construct()
    {
        parent::__construct();
    }

    /* get the orders data */
    public function getOrdersData($offset = 0, $id = null, $limit = true)
    {

       
        if ($id) {
            $sql = "SELECT * FROM orders WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter = "";
        }

        //$more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE o.company_id = " . $this->data['usercomp'] : " WHERE o.store_id = " . $this->data['userstore']);

        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }
        $limit = $limit ? "LIMIT 200 OFFSET {$offset}" : "";

        $sql = "SELECT o.*, c.cpf_cnpj FROM orders o LEFT JOIN clients c ON o.customer_id =c.id " . $more . $filter . " ORDER BY o.id DESC " . $limit;
        get_instance()->log_data('Orders', 'count', json_encode($sql));
        $query = $this->db->query($sql);
        return $query ? $query->result_array() : false;
    }

    public function getOrdersCount($filter = "")
    {

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE o.company_id = " . $this->data['usercomp'] : " WHERE o.store_id = " . $this->data['userstore']);

        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }
        $sql = "SELECT count(*) as qtd FROM orders as o " . $more . $filter;
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getOrdersDatabyBill($origin = null, $id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM orders WHERE origin = ? AND bill_no = ?";
            $query = $this->db->query($sql, array($origin, $id));
            return $query->row_array();
        }
        //return $query->result_array();
    }

    public function getOrdersDatabyNumeroMarketplace($id = null, int $store = null)
    {
        if ($id) {
            $sql = "SELECT * FROM orders WHERE numero_marketplace = ?";
            if ($store) {
                $sql .= " AND store_id = $store";
            }
            $query = $this->db->query($sql, array((string)$id));
            return $query->row_array();
        }
        return;
    }

    public function getAmountOrders($id)
    {
        $sql = "SELECT count(1) as amount FROM orders WHERE store_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    /* get the Latest orders data */
    public function getLatestOrders()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM orders " . $more . " ORDER BY id DESC LIMIT 10";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    // // get the orders item data
    // public function getOrdersItemData($order_id = null)
    // {
    //     if(!$order_id) {
    //         return false;
    //     }

    //     $sql = "SELECT * FROM orders_item WHERE order_id = ?";
    //     $query = $this->db->query($sql, array($order_id));
    //     return $query->result_array();
    // }

    // get the orders item data
    public function getOrdersItemData($order_id = null, $getPrdToIntegrationInfo = false)
    {
        if (!$order_id) {
            return false;
        }

        $sql = "SELECT orders_item.*,
                products.image ,
                products.qty AS stock,
                products.prazo_operacional_extra,
                products.principal_image,
                products.product_catalog_id ,
                products.category_id ";
        if ($getPrdToIntegrationInfo){
            $sql .= ", prd_to_integration.mkt_sku_id";
        }
        $sql .= " FROM orders_item
                    LEFT JOIN products ON products.id = orders_item.product_id ";
        if ($getPrdToIntegrationInfo){

            $sql .= " JOIN orders ON (orders.id = orders_item.order_id)
                      JOIN prd_to_integration ON (prd_to_integration.prd_id = orders_item.product_id AND COALESCE(orders_item.variant, '') = COALESCE(prd_to_integration.variant, '')
                                                    AND prd_to_integration.int_to = orders.origin) ";

        }
        $sql.= " WHERE orders_item.order_id = ? ";

        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }

    public function getOrdersDate($order_id = null){

        if (!$order_id) {
            return false;
        }
 
        return $this->db
            ->select('o.id, f.codigo_rastreio, f.url_tracking, o.data_envio, f.ship_company')
            ->from('orders o')
            ->join('freights f', 'o.id = f.order_id')
            ->where(['o.id' => $order_id])
            ->get()
            ->row_array();
    }
    
    // get the orders item data
    public function getItemsFreights($order_id = null, $inResend = null)
    {
        if (!$order_id) {
            return false;
        }
        $whereResend = '';
        if ($inResend !== null) {
            if ($inResend) {
                $whereResend = 'AND in_resend_active = 1';
            } else {
                $whereResend = 'AND in_resend_active = 0';
            }

        }

        $sql = "SELECT * FROM freights WHERE order_id = ? {$whereResend} ORDER BY id";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }
    // get the orders item data
    public function getOrdersNfes($order_id = null)
    {
        if (!$order_id) {
            return false;
        }

        $sql = "SELECT * FROM nfes WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }
    // get the orders item data
    public function getOrdersParcels($order_id = null)
    {
        if (!$order_id) {
            return false;
        }

        $sql = "SELECT * FROM orders_payment WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }

    public function getOrdersOpenToSyncStatusMkt($int_to, $offset = 0, $limit = 100)
    {
        $sql = "select id, origin, bill_no, numero_marketplace, paid_status, status_mkt from orders " .
            "where finished_monitoring = false and origin = ? limit ?, ?";
        $query = $this->db->query($sql, array($int_to, $offset, $limit));
        return $query->result_array();
    }

    public function getOrdersStatusMkt($origin, $status)
    {
        $sql = "select * from orders o where origin = ? and  o.status_mkt = ? and finished_monitoring = 0";
        $query = $this->db->query($sql, array($origin, $status));
        return $query->result_array();
    }

    public function wasUpdatedByOrderToDelivered($order_id){
        $query = $this->db->select('forced_to_delivery')
                      ->from('orders')
                      ->where('id', $order_id)
                      ->get();

        $row = $query->row_array(); // <-- troca para array

        return isset($row['forced_to_delivery']) ? (int) $row['forced_to_delivery'] : 0;

    }

    public function getOrdersStatusMktAndPaidStatus($origin, $status, $paid_status)
    {
        $sql = "select * from orders o where origin = ? and  o.status_mkt = ? and paid_status = ? and finished_monitoring = 0";
        $query = $this->db->query($sql, array($origin, $status, $paid_status));
        return $query->result_array();
    }

    public function create()
    {
        $user_id = $this->session->userdata('id');
        $bill_no = 'BILPR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $data = array(
            'bill_no' => $bill_no,
            'customer_name' => $this->postClean('customer_name'),
            'customer_address' => $this->postClean('customer_address'),
            'customer_phone' => $this->postClean('customer_phone'),
            'date_time' => strtotime(date('Y-m-d h:i:s a')),
            'gross_amount' => $this->postClean('gross_amount_value'),
            'service_charge_rate' => $this->postClean('service_charge_rate'),
            'service_charge' => ($this->postClean('service_charge_value') > 0) ? $this->postClean('service_charge_value') : 0,
            'vat_charge_rate' => $this->postClean('vat_charge_rate'),
            'vat_charge' => ($this->postClean('vat_charge_value') > 0) ? $this->postClean('vat_charge_value') : 0,
            'net_amount' => $this->postClean('net_amount_value'),
            'discount' => $this->postClean('discount'),
            'paid_status' => 1,
            'company_id' => $this->session->userdata('usercomp'),
            'user_id' => $user_id,
        );

        $insert = $this->db->insert('orders', $data);
        $order_id = $this->db->insert_id();
        get_instance()->log_data('Orders', 'create', json_encode($data), "I");
        $this->load->model('model_products');

        $count_product = count($this->postClean('product'));
        for ($x = 0; $x < $count_product; $x++) {
            $items = array(
                'order_id' => $order_id,
                'product_id' => $this->postClean('product')[$x],
                'qty' => $this->postClean('qty')[$x],
                'rate' => $this->postClean('rate_value')[$x],
                'amount' => $this->postClean('amount_value')[$x],
            );

            $this->db->insert('orders_item', $items);

            // now decrease the stock from the product
            $product_data = $this->model_products->getProductData($this->postClean('product')[$x]);
            $qty = (int) $product_data['qty'] - (int) $this->postClean('qty')[$x];

            $update_product = array('qty' => $qty);

            $this->model_products->update($update_product, $this->postClean('product')[$x]);
        }

        return ($order_id) ? $order_id : false;
    }

    public function countOrderItem($order_id)
    {
        if ($order_id) {
            $sql = "SELECT * FROM orders_item WHERE order_id = ?";
            $query = $this->db->query($sql, array($order_id));
            return $query->num_rows();
        }
    }
    public function updateByOrigin($id, $data)
    {
        if ($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('orders', $data);
            get_instance()->log_data('Orders', 'edit after', json_encode($data), "I");

            $order = $this->getOrdersData(0,$id);

            
            $this->load->model('model_integrations_webhook');

            if($order['paid_status'] == 3){
               
                $paidOrderWebhook  = [
                    'id' =>  $order['id'],
                    "code" => $order['paid_status'],
                    'status' => 'Aguardando Faturamento'
                ];
                $store_id_wh = $order['store_id'];
    
                if ($this->model_integrations_webhook->storeExists($store_id_wh)) {
                    $this->load->library('ordersmarketplace');
                    
                    $typeIntegration = "pedido_pago";
                    $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$paidOrderWebhook);
                }    
            }else if(in_array($order['paid_status'], $this->PAID_STATUS['orders_cancel'])) {
                
                $cancelOrderWebhook  = [
                    'id' =>  $order['id'],
                    "code" => $order['paid_status'],
                    'status' => 'pedido_cancelado'
                ];
                $store_id_wh = $order['store_id'];
    
                if ($this->model_integrations_webhook->storeExists($store_id_wh)) {
                    $this->load->library('ordersmarketplace');
                
                    $typeIntegration = "pedido_cancelado";
                    $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$cancelOrderWebhook);
                }    
            }
            
            return $id;
        }
        return false;
    }

    public function update($id)
    {
        if ($id) {
            $user_id = $this->session->userdata('id');
            // get form order data

            $data = array(
                'customer_name' => $this->postClean('customer_name'),
                'customer_address' => $this->postClean('customer_address'),
                'customer_phone' => $this->postClean('customer_phone'),
                //'gross_amount' => $this->postClean('gross_amount_value'),
                //'service_charge_rate' => $this->postClean('service_charge_rate'),
                //'service_charge' => ($this->postClean('service_charge_value') > 0) ? $this->postClean('service_charge_value'):0,
                //'vat_charge_rate' => $this->postClean('vat_charge_rate'),
                //'vat_charge' => ($this->postClean('vat_charge_value') > 0) ? $this->postClean('vat_charge_value') : 0,
                //'net_amount' => $this->postClean('net_amount_value'),
                // 'discount' => $this->postClean('discount'),
                'paid_status' => $this->postClean('paid_status'),
                'user_id' => $user_id,
            );
            $this->db->where('id', $id);
            $update = $this->db->update('orders', $data);
            // SW - Log update
            get_instance()->log_data('Orders', 'edit after', json_encode($data), "I");
            // LOG STATUS CHANGE;

            /* Avoid changing items data
            // now the order item
            // first we will replace the product qty to original and subtract the qty again
            $this->load->model('model_products');
            $get_order_item = $this->getOrdersItemData($id);
            foreach ($get_order_item as $k => $v) {
            $product_id = $v['product_id'];
            $qty = $v['qty'];
            // get the product
            get_instance()->log_data('Orders','productid',json_encode($product_id),"I");
            $product_data = $this->model_products->getProductData(0,$product_id);
            get_instance()->log_data('Orders','product',json_encode($product_data),"I");
            $update_qty = $qty + $product_data['qty'];
            $update_product_data = array('qty' => $update_qty);

            // update the product qty
            $this->model_products->update($update_product_data, $product_id);
            }
            // now remove the order item data
            $this->db->where('order_id', $id);
            $this->db->delete('orders_item');
            get_instance()->log_data('Orders','delitems',json_encode($this->postClean('sku')),"I");

            // now decrease the product qty
            $count_product = count($this->postClean('product'));
            get_instance()->log_data('Orders','items',json_encode($count_product),"I");

            for($x = 0; $x < $count_product; $x++) {
            $items = array(
            'order_id' => $id,
            'product_id' => $this->postClean('product')[$x],
            'qty' => $this->postClean('qty')[$x],
            'rate' => $this->postClean('rate_value')[$x],
            'amount' => $this->postClean('amount_value')[$x],
            );
            get_instance()->log_data('Orders','insertitem',json_encode($items),"I");
            $itemid = $this->db->insert('orders_item', $items);
            get_instance()->log_data('Orders','insertitem',json_encode($itemid),"I");

            // now decrease the stock from the product
            $product_data = $this->model_products->getProductData($this->postClean('product')[$x]);
            $qty = (int) $product_data['qty'] - (int) $this->postClean('qty')[$x];

            $update_product = array('qty' => $qty);
            $this->model_products->update($update_product, $this->postClean('product')[$x]);
            }
             */
            return true;
        }
    }

    public function remove($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('orders');
            // SW - Log update
            get_instance()->log_data('Orders', 'remove', $id, "I");

            $this->db->where('order_id', $id);
            $delete_item = $this->db->delete('orders_item');
            return ($delete == true && $delete_item) ? true : false;
        }
    }

    public function insertOrder($data = null)
    {    
        $store=$this->db->select()->from('stores s')->where(['s.id'=>$data['store_id']])->get()->row_array();
        $data['phase_id']=$store['phase_id'];
        $insert = $this->db->insert('orders', $data);
        $order_id = $this->db->insert_id();
        // get_instance()->log_data('Orders','insert',json_encode($data),"I");
        
        if ($this->model_integrations_webhook->storeExists($data['store_id'])) {
            
            $this->load->library('ordersmarketplace');
            
            $store_id_wh = $data['store_id'];
            $typeIntegration = "pedido_criado";
            $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$data);
        }

        return ($order_id) ? $order_id : false;
    }

    public function deleteItem($id = null)
    {
        if ($id) {
            $this->db->where('order_id', $id);
            $delete = $this->db->delete('orders_item');
            if ($this->db->affected_rows() > 0) {
                try {
                    get_instance()->log_data('order_item', 'delete', var_export(debug_backtrace(), true));
                } catch (Throwable $e) {

                }
            }
            return ($delete == true) ? true : false;
        }
        return false;
    }

    public function insertItem(&$data = null)
    {
        $insert = $this->db->insert('orders_item', $data);
        $item_id = $this->db->insert_id();
        // get_instance()->log_data('Orders_item','insert',json_encode($data),"I");

        $this->load->library('ordersMarketplace');
        $this->ordersmarketplace->saveTotalDiscounts($data, $item_id);

        return ($item_id) ? $item_id : false;
    }

    public function insertFreight($data = null)
    {
        $insert = $this->db->replace('freights', $data);
        $freight_id = $this->db->insert_id();
        // get_instance()->log_data('Freights','insert',json_encode($data),"I");

        return ($freight_id) ? $freight_id : false;
    }
    public function insertParcels($data = null)
    {
        $insert = $this->db->insert('orders_payment', $data);
        $parcs_id = $this->db->insert_id();
        // get_instance()->log_data('Parcels','insert',json_encode($data),"I");

        return ($parcs_id) ? $parcs_id : false;
    }

    /* get the orders data */
    public function getLast3M()
    {
        // $more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

        $sql = "select date_format(date_time,'%Y-%b') as mes, origin, sum(gross_amount) as total,count(*) as qtd from orders" . $more . " group by mes,origin";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /* get the orders data */
    public function getMonthGauges()
    {
        // $more = ($this->data['usercomp'] == 1) ? "": " AND company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "select paid_status as ps, count(*) as qtd from orders where month(date_time) = month(now())" . $more . "  group by paid_status";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function countTotalPaidOrders()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status > ?" . $more;
        $query = $this->db->query($sql, array(1));
        $row = $query->row_array();
        return $row['qtd'];
    }

    /* get the orders data */
    public function ExcelList($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM orders WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        //$more = ($this->data['usercomp'] == 1) ? "": " AND company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND o.company_id = " . $this->data['usercomp'] : " AND o.store_id = " . $this->data['userstore']);

        if ($this->session->has_userdata('orderExportFilters')) {
            $more .= $this->session->orderExportFilters;
        }

        $sql = "SELECT ";
        $sql .= " o.id as application_id";
        $sql .= ", s.name as application_store";
        $sql .= ", i.product_id as application_product_id";
        $sql .= ", i.sku as application_product_sku";
        $sql .= ", i.name as application_product_name";
        $sql .= ", i.qty as application_item_qty";
        $sql .= ", i.amount as application_value_products";
        $sql .= ", DATE_FORMAT(o.date_time,'%d/%m/%Y') as application_included ";
        $sql .= ", DATE_FORMAT(o.data_pago,'%d/%m/%Y') as application_approved ";
        $sql .= ", o.origin as application_marketplace ";
        $sql .= ", o.numero_marketplace as application_order_marketplace_full ";
        $sql .= ", o.customer_name as application_client ";
        $sql .= ", c.cpf_cnpj as application_cpf_cnpj ";
        $sql .= ", o.customer_address as application_delivery_address";
        $sql .= ", o.customer_address_num as application_number";
        $sql .= ", o.customer_address_compl as application_complement";
        $sql .= ", o.customer_address_neigh as application_neighb";
        $sql .= ", o.customer_address_city as application_city";
        $sql .= ", o.customer_address_uf as application_uf";
        $sql .= ", o.customer_address_zip as application_zip_code";
        $sql .= ", o.total_order as application_total_order";
        $sql .= ", o.gross_amount as application_orders_value";
        $sql .= ", o.discount as application_discount";
        $sql .= ", o.total_ship as application_ship_value";
        $sql .= ", ph.name as application_phase";
        $sql .= ", DATE_FORMAT(o.data_limite_cross_docking,'%d/%m/%Y') as application_dispatch";
        $sql .= ", (SELECT DATE_FORMAT(MIN(prazoprevisto),'%d/%m/%Y') FROM freights WHERE order_id = o.id) AS application_promised";
        $sql .= ", o.paid_status as application_status";
        $sql .= ", n.nfe_num as application_nfe_num";
        $sql .= ", n.nfe_serie as application_serie";
        $sql .= ", n.chave as application_key";
        $sql .= ", (SELECT MIN(ship_company) FROM freights WHERE order_id = o.id) AS application_ship_company";
        $sql .= ", (SELECT MIN(method) FROM freights WHERE order_id = o.id) AS application_service";
        $sql .= ", (SELECT GROUP_CONCAT(codigo_rastreio SEPARATOR ';') FROM freights WHERE order_id = o.id) AS application_tracking_code";
        $sql .= ", case
                    when ticket.numero_marketplace is null then 0 else 1
                  end application_ticket";
        $sql .= ", DATE_FORMAT(o.data_envio,'%d/%m/%Y') as application_ship_date";
        $sql .= ", DATE_FORMAT(o.data_entrega,'%d/%m/%Y') as application_delivered_date";
        $sql .= ", o.last_occurrence as application_transportation_status";
        $sql .= " FROM orders o ";
        $sql .= " LEFT JOIN clients c ON c.id = o.customer_id";
        $sql .= " LEFT JOIN orders_item i ON i.order_id = o.id";
        $sql .= " LEFT JOIN stores s ON s.id = o.store_id";
        $sql .= " LEFT JOIN nfes n ON n.order_id = o.id";
        $sql .= " LEFT JOIN freights f ON f.order_id = o.id";
        $sql .= " LEFT JOIN phases ph ON o.phase_id = ph.id";
        $sql .= " LEFT JOIN (
                    select distinct code numero_marketplace from ticket_b2w
                        union all
                    select distinct order_id from ticket_ml
                        union all
                    select distinct
                        replace(order_href, '/orders/', '') numero_marketplace
                    from ticket_via
                ) ticket
                on o.numero_marketplace = ticket.numero_marketplace";
        $sql .= " WHERE o.paid_status > 0" . $more . " GROUP BY i.id ORDER BY o.id DESC";
        get_instance()->log_data('Orders', 'export', $sql, "I");

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function replaceNfe($data)
    {
        if ($data) {
            $insert = $this->db->replace('nfes', $data);
            return ($insert == true) ? true : false;
        }
    }

    public function saveNfXml($data)
    {
        $hasNf = $this->getOrdersNfes($data['order_id']);
        if ($hasNf) {
            $this->db->delete('nfes', ['order_id' => $data['order_id']]);
            $insert = $this->db->insert('nfes', $data);
            return ($insert == true) ? 'updated' : false;
        } else {
            $insert = $this->db->insert('nfes', $data);
            return ($insert == true) ? 'inserted' : false;
        }
        // if (!$hasNf) {
        // $this->replaceNfe($data);
        // return true;
        // }
        // return false;
    }

    public function getOrdensByPaidStatus($paid_status): array
    {
        if (is_array($paid_status)) {
            $this->db->where_in('paid_status', $paid_status);
        } else {
            $this->db->where('paid_status', $paid_status);
        }

        return $this->db->get('orders')->result_array();
    }

    public function updatePaidStatus($id, $paid_status)
    {
        if ($paid_status == 3) {
            $sql = "UPDATE orders SET paid_status = ?, data_pago=now(), is_incomplete = 0 WHERE id = ?";
        } elseif ($paid_status == 2) {
            $sql = "UPDATE orders SET paid_status = ?, is_incomplete = 0 WHERE id = ?";
        } else {
            $sql = "UPDATE orders SET paid_status = ? WHERE id = ?";
        }
        $result = $this->db->query($sql, array($paid_status, $id));

        if($paid_status == 3){
            $order = $this->getOrdersData(0,$id);

            $paidOrderWebhook  = [
                'id' =>  $order['id'],
                "code" => $order['paid_status'],
                'status' => 'Aguardando Faturamento'
            ];
            $store_id_wh = $order['store_id'];

            $this->load->model('model_integrations_webhook');
        
            if ($this->model_integrations_webhook->storeExists($store_id_wh)) {
                                
                $this->load->library('ordersmarketplace');
            
                $typeIntegration = "pedido_pago";
                $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$paidOrderWebhook);
            }
           
        }

        return ($result == true) ? true : false;
    }

    public function updateStatusMkt($id, $status_mkt, $finished = false)
    {
        $sql = "UPDATE orders SET status_mkt = ?, finished_monitoring = ? WHERE id = ?";
        $result = $this->db->query($sql, array($status_mkt, $finished, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataPago($id, $data_pago)
    {
        $sql = "UPDATE orders SET data_pago = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_pago, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataEnvio($id, $data_envio)
    {
        $sql = "UPDATE orders SET data_envio = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_envio, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataEntrega($id, $data_entrega)
    {
        $sql = "UPDATE orders SET data_entrega = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_entrega, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataPagoWithCrossDocking($id, $data_pago, $data_limite_cross_docking)
    {
        $sql = "UPDATE orders SET data_pago = ?, data_limite_cross_docking = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_pago, $data_limite_cross_docking, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataColeta($id, $data_coleta)
    {

        $sql = "UPDATE orders SET data_coleta = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_coleta, $id));
        return ($result == true) ? true : false;
    }

    /* get the orders data */
    public function getOrdersSemFreteData($offset = 0, $orderby = '')
    {

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter = "";
        }
        //$more = ($this->data['usercomp'] == 1) ? " WHERE paid_status = 101 ": " WHERE paid_status = 101 AND company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND o.company_id = " . $this->data['usercomp'] : " AND o.store_id = " . $this->data['userstore']);

        //  $sql = "SELECT * FROM orders WHERE paid_status = 101 ".$more.$filter.$orderby." LIMIT 200 OFFSET ".$offset;
        $sql = 'SELECT o.*, s.name as store FROM orders o, stores s  WHERE o.paid_status = 101 AND o.store_id = s.id ' . $more . $filter . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersSemFreteCount($filter = "")
    {
        //    get_instance()->log_data('Orders','count',json_encode($filter),"I");

        //$more = ($this->data['usercomp'] == 1) ? "WHERE paid_status = 101 ": " WHERE paid_status = 101 AND company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND o.company_id = " . $this->data['usercomp'] : " AND o.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders o, stores s  WHERE paid_status = 101 AND o.store_id = s.id  " . $more . $filter;
        // get_instance()->log_data('Orders','count',json_encode($sql),"I");
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }
    public function getOrdersSemFreteByLike($likeData = [], $whereData = [], $order_by = 'id', $order_dir = 'asc')
    {
        $whereData['o.paid_status'] = 101;
        if ($this->data['usercomp'] != 1) {
            $whereData['o.company_id'] = $this->data['usercomp'];
            if ($this->data['userstore'] != 0) {
                $whereData['o.store_id'] = $this->data['userstore'];
            }
        }
        $dados = $this->db->select('o.*,s.name as store')->from('orders o')->join('stores s', 's.id = o.store_id');
        if (!empty($likeData)) {
            $this->db->group_start()
                ->or_like($likeData)
                ->group_end();
        }

        if (isset($this->data['where_in'])) {
            foreach ($this->data['where_in'] as $field => $values) {
                $this->db->where_in($field, $values);
            }
        }

        $dados = $this->db->order_by($order_by, $order_dir)->where($whereData)->get()->result_array();
        return $dados;
    }

    public function updateNovoFrete($order_id, $paid_status, $novopreco)
    {
        $sql = "UPDATE orders SET paid_status =?, frete_real=? WHERE id = ?";
        $result = $this->db->query($sql, array($paid_status, $novopreco, $order_id));
        return ($result == true) ? true : false;
    }

    public function getOrdensFreteEntregueData($offset = 0, $procura = '', $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT o.*, s.name AS loja FROM orders o";
        $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
        $sql .= " WHERE o.paid_status = 60 " . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdensFreteEntregueCount($procura = '')
    {
        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status = 60 ";
        } else {
            $sql = "SELECT count(*) as qtd FROM orders o ";
            $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
            $sql .= " WHERE paid_status = 60 " . $procura;
        }

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos com alguma ação pendente
     *
     * @return int
     */
    public function getOrderPendingAction()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE (paid_status = 3 OR paid_status = 99)" . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos aguardando NF
     *
     * @return int
     */
    public function getOrderWaitingInvoice()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status = 3" . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos entregues
     * @return int
     */
    public function getOrderDelivered()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE (paid_status = 6 OR paid_status = 60)" . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos cancelados
     *
     * @return int
     */
    public function getOrderCanceled()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd  FROM orders WHERE paid_status in (95,96,97,98,99) " . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos com o último dia de postagem
     *
     * @return int
     */
    public function getOrderLastPostDay()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE DATE_FORMAT(data_limite_cross_docking, '%Y-%m-%d') = CURDATE()" . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    /**
     * Total de pedidos que estão com a postagem atrasada
     * Código de status igual de (3, 4, 50, 51, 52, 53, 54, 56, 57, 101) serão considerados
     *
     * @return int
     */
    public function getOrderDelayedPost($useMore = true, $returnResult = false)
    {
        $more = "";

        if ($useMore) {
            $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);
        }

        $sql = "SELECT * FROM orders WHERE data_limite_cross_docking <= CURDATE() AND paid_status in (3, 4, 40, 41, 43, 50, 51, 52, 53, 54, 56, 57, 101)" . $more;
        $query = $this->db->query($sql);
        return $returnResult ? $query->result_array() : $query->num_rows();
    }

    /**
     * Total de pedidos aguardando coleta
     *
     * @return int
     */
    public function getOrderAwaitingCollection()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE orders.paid_status in(4,43,51,53) " . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];

        //$countOrder = $query->num_rows();
        //$sql = "SELECT * FROM orders JOIN freights ON orders.id = freights.order_id WHERE orders.paid_status in(51,52,53) AND freights.codigo_rastreio IS NOT NULL AND (freights.link_etiqueta_a4 IS NOT NULL OR freights.link_etiqueta_termica IS NOT NULL) ".$more . " GROUP BY freights.order_id";
        //$query = $this->db->query($sql);
        //$countFreightLabel = $query->num_rows();

        //return $countOrder + $countFreightLabel;
    }

    public function insertPedidosCancelados($data = null)
    {
        $insert = $this->db->insert('canceled_orders', $data);
        $item_id = $this->db->insert_id();
        // get_instance()->log_data('Orders_item','insert',json_encode($data),"I");

        return ($item_id) ? $item_id : false;
    }

    public function getReasonsCancelOrder(int $order_id): array
    {
        return $this->db->get_where('canceled_orders', ['order_id' => $order_id])->result_array();
    }

    public function wasOrderChargedComission(int $order_id): bool
    {
        return (bool) $this->db->get_where('canceled_orders',
            ['order_id' => $order_id, 'commission_charges_attribute_value' => 1])->row_array();
    }

    public function updatePedidosCancelados($data, $id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $update = $this->db->update('canceled_orders', $data);
            return $id;
        }
        return false;
    }

    public function getOrdensCancelaMktData($offset = 0, $procura = '', $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT o.*, s.name AS loja, pc.reason, pc.date_update as data_cancelamento  FROM orders o";
        $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
        $sql .= " LEFT JOIN canceled_orders pc ON o.id=pc.order_id";
        $sql .= " WHERE o.paid_status = 98 OR o.paid_status = 99 " . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdensCancelaMktCount($procura = '')
    {
        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status = 98 ";
        } else {
            $sql = "SELECT count(*) as qtd FROM orders o ";
            $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
            $sql .= " LEFT JOIN canceled_orders pc ON o.id=pc.order_id";
            $sql .= " WHERE paid_status = 98 " . $procura;
        }

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getOrdersEtiquetas($offset = 0, $procura = '', $orderby = '')
    {

        $more = ($this->data['usercomp'] == 1) ? "" : " AND o.company_id = " . $this->data['usercomp'];

        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT o.*, s.name AS loja , oi.name as item, f.link_etiqueta_a4 as link_etiqueta_a4 , ";
        $sql .= " f.link_etiqueta_termica as link_etiqueta_termica, f.link_plp as link_plp, oi.product_id as product_id  FROM orders o";
        $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
        $sql .= " LEFT JOIN freights f ON o.id=f.order_id";
        $sql .= " LEFT JOIN orders_item oi ON f.item_id=oi.id";
        $sql .= " WHERE o.id=oi.order_id AND (o.paid_status=51 OR o.paid_status=52 OR o.paid_status=53 OR o.paid_status=4)  AND f.link_etiqueta_a4 IS NOT NULL " . $more . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersEtiquetasCount($procura = '')
    {
        $more = ($this->data['usercomp'] == 1) ? "" : " AND o.company_id = " . $this->data['usercomp'];

        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM orders o LEFT JOIN freights f ON o.id=f.order_id WHERE paid_status = 4 AND f.link_etiqueta_a4 IS NOT NULL " . $more;
        } else {
            $sql = "SELECT count(*) as qtd FROM orders o ";
            $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
            $sql .= " LEFT JOIN freights f ON o.id=f.order_id";
            $sql .= " LEFT JOIN orders_item oi ON f.item_id=oi.id";
            $sql .= " WHERE o.id=oi.order_id AND o.paid_status = 4 AND f.link_etiqueta_a4 IS NOT NULL " . $more . $procura;
        }
        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getOrdensByOriginPaidStatus($origin, $paid_status, $store_id = 0)
    {
        if ($store_id == 0) { // Pego todos os pedidos com este status de todas as lojas deste marketplace
            if (is_array($paid_status)) {
                // Cria a consulta usando IN para múltiplos statuses
                $placeholders = implode(',', array_fill(0, count($paid_status), '?'));
                $sql = "SELECT * FROM orders WHERE origin = ? AND paid_status IN ($placeholders)";
                $query = $this->db->query($sql, array_merge([$origin], $paid_status));
            } else {
                $sql = "SELECT * FROM orders WHERE origin = ? AND paid_status = ?";
                $query = $this->db->query($sql, array($origin, $paid_status));
            }
        } else { // Pego todos os pedidos com este status somente desta loja deste marketplace
            if (is_array($paid_status)) {
                // Cria a consulta usando IN para múltiplos statuses
                $placeholders = implode(',', array_fill(0, count($paid_status), '?'));
                $sql = "SELECT * FROM orders WHERE origin = ? AND paid_status IN ($placeholders) AND store_id = ?";
                $query = $this->db->query($sql, array_merge([$origin], $paid_status, [$store_id]));
            } else {
                $sql = "SELECT * FROM orders WHERE origin = ? AND paid_status = ? AND store_id = ?";
                $query = $this->db->query($sql, array($origin, $paid_status, $store_id));
            }
        }

        return $query->result_array();
    }   

    public function getOrdensEnvioMktData($offset = 0, $procura = '', $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT o.*, s.name AS loja, f.codigo_rastreio AS codigo_rastreio FROM orders o";
        $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
        $sql .= " LEFT JOIN freights f ON f.order_id=o.id";
        $sql .= " WHERE o.paid_status = 55 " . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdensWithNfeMktData($offset = 0, $procura = '', $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }
        $sql = "SELECT o.*, s.name AS loja, f.codigo_rastreio AS codigo_rastreio FROM orders o";
        $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
        $sql .= " LEFT JOIN freights f ON f.order_id=o.id";
        $sql .= " WHERE o.paid_status = 52 " . $procura . $orderby . " LIMIT 200 OFFSET " . $offset;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdensEnvioMktCount($procura = '')
    {
        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status = 55 ";
        } else {
            $sql = "SELECT count(*) as qtd FROM orders o ";
            $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
            $sql .= " LEFT JOIN freights f ON f.order_id=o.id";
            $sql .= " WHERE paid_status = 55 " . $procura;
        }

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getOrdensWithNfeMktCount($procura = '')
    {
        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status = 52 ";
        } else {
            $sql = "SELECT count(*) as qtd FROM orders o ";
            $sql .= " LEFT JOIN stores s ON s.id=o.store_id";
            $sql .= " LEFT JOIN freights f ON f.order_id=o.id";
            $sql .= " WHERE paid_status = 52 " . $procura;
        }

        $query = $this->db->query($sql, array());
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function checkStatusOrder($order_id, $status_id)
    {
        if (!is_array($status_id)) {
            $status_id = array($status_id);
        }

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM orders WHERE paid_status in ? AND id = ? {$more}";

        $query = $this->db->query($sql, array($status_id, $order_id));
        return $query->num_rows();
    }

    public function getSellsOrdersCount($store_id, $fromdate)
    {

        $sql = "SELECT count(*) as qtd FROM orders WHERE store_id = ? AND date_time > ? ";

        $query = $this->db->query($sql, array($store_id, $fromdate));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getErrorsProductsForInvoice($order_id, $store_id = null, $company_id = null)
    {
        if (!$store_id || !$company_id) {
            $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);
        } else {
            $more = "AND store_id = {$store_id} AND company_id = {$company_id}";
        }

        $errors = array();
        $sql = "SELECT * FROM orders_item WHERE order_id = ? " . $more;

        $queryI = $this->db->query($sql, array($order_id));
        foreach ($queryI->result_array() as $iten) {
            $queryP = $this->db->query("SELECT * FROM products WHERE id = ?", array($iten['product_id']));

            if ($queryP->num_rows() == 0) {
                array_push($errors, array('product_id' => $iten['product_id'], 'product_url' => base_url("products/update/{$iten['product_id']}"), 'message' => 'Produto não encontrado'));
                continue;
            }

            $result = $queryP->first_row();

            if (strlen($result->NCM) != 8) {
                array_push($errors, array('product_id' => $iten['product_id'], 'product_url' => base_url("products/update/{$iten['product_id']}"), 'message' => 'NCM inválido'));
            }

        }

        return $errors;
    }

    public function verifyStatus($order_id)
    {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->first_row()->paid_status;
    }

    public function getOrdersDataView($offset = 0, $procura = '', $orderby = '',$limit=200)
    {
        if ($offset == '') {
            $offset = 0;
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter = "";
        }

        if (isset($this->data['queryDash'])) $filter = $this->data['queryDash'];

        $joinStores = "LEFT JOIN stores s ON s.id=o.store_id";
        if(strpos($filter, 'phase.id') !== false) {
            $joinStores = "LEFT JOIN stores s ON s.id = o.store_id
            LEFT JOIN phases phase ON s.phase_id = phase.id";
        }

        //$more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE o.company_id = " . $this->data['usercomp'] : " WHERE o.store_id = " . $this->data['userstore']);

        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }
        if ($orderby == '') {
            $orderby = "ORDER BY  o.id DESC ";
        }

        $sql = "SELECT o.*, c.cpf_cnpj, s.name as store, f.ship_company, f.prazoprevisto
         FROM orders o
         LEFT JOIN clients c ON o.customer_id =c.id
         LEFT JOIN freights f ON f.id = (SELECT f1.id FROM freights AS f1 WHERE f1.order_id = o.id LIMIT 1)
         {$joinStores}
         " . $more . $filter .
        //    " GROUP BY o.id, ship_company " .
        $orderby . " LIMIT {$limit} OFFSET " . $offset;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersDataViewCount($filter = "")
    {
        //$more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE o.company_id = " . $this->data['usercomp'] : " WHERE o.store_id = " . $this->data['userstore']);

        $joinStores = "LEFT JOIN stores s ON s.id=o.store_id";
        if(strpos($filter, 'phase.id') !== false) {
            $joinStores = "LEFT JOIN stores s ON s.id = o.store_id
            LEFT JOIN phases phase ON s.phase_id = phase.id";
        }
        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }

        $sql = "SELECT count(*) as qtd FROM orders o
         			LEFT JOIN clients c ON o.customer_id =c.id
         			LEFT JOIN freights f ON f.id = (SELECT f1.id FROM freights AS f1 WHERE f1.order_id = o.id LIMIT 1)
         			{$joinStores}
         			" . $more . $filter;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function getStoresForFilter()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE orders.company_id = " . $this->data['usercomp'] : " WHERE orders.store_id = " . $this->data['userstore']);

        $sql = "SELECT DISTINCT(stores.name), stores.id FROM orders ";
        $sql .= " JOIN stores ON stores.id = orders.store_id" . $more;
        $sql .= " ORDER BY stores.name";
        $query = $this->db->query($sql);

        return $query->result_array();
    }

    public function getFreightsForFilter()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : " WHERE company_id = " . $this->data['usercomp'];

        $sql = "SELECT DISTINCT(ship_company) FROM freights" . $more;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    public function getOrdersInTransport()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        $sql = "SELECT count(*) as qtd FROM orders WHERE paid_status in (5, 45, 55) " . $more;
        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];

        //   $countOrder = $query->num_rows();

        //   $more .= " GROUP BY freights.order_id";
        //   $sql = "SELECT * FROM orders JOIN freights ON orders.id = freights.order_id WHERE orders.paid_status in(51,52,53) AND freights.codigo_rastreio IS NOT NULL AND (freights.link_etiqueta_a4 IS NOT NULL OR freights.link_etiqueta_termica IS NOT NULL)".$more;
        //   $query = $this->db->query($sql);
        //   $countFreightLabel = $query->num_rows();

        //   $count = $countOrder - $countFreightLabel;
        //   return $count < 0 ? 0 : $count;
    }

    public function getQualityStoreStatus()
    {
        $dateStart = date('Y-m-d', strtotime('-1 months', strtotime(date('Y-m-d'))));
        $dateFinish = date('Y-m-d');

        $queryAllOrders = $this->getOrdersCount(" AND o.date_time BETWEEN '{$dateStart}' AND '{$dateFinish}'");
        $queryAllOrdersDelayedPost = $this->getOrdersCount(" AND o.date_time BETWEEN '{$dateStart}' AND '{$dateFinish}' AND o.order_delayed_post = 1");

        if ($queryAllOrders == 0 || $queryAllOrdersDelayedPost == 0) {
            return 0;
        }

        return ($queryAllOrdersDelayedPost * 100) / $queryAllOrders;
    }

    public function updateOrderDelayedPost($order)
    {
        if ($order) {
            $this->db->where('id', $order);
            return $this->db->update('orders', array('order_delayed_post' => 1));
        }
        return false;
    }

    public function getPedidosCanceladosByOrderId($order_id)
    {
        $sql = " SELECT p.*, u.username,u.company_id FROM canceled_orders p LEFT JOIN users u ON u.id=p.user_id WHERE p.order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->row_array();
    }

    public function getEtiquetasGeneratePLP(int $sgp = 1)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        $sql = "SELECT orders.id, orders_item.sku, orders_item.name as name_item, orders_item.product_id, orders.date_time, orders.customer_name, orders.gross_amount, orders.net_amount, stores.name as store
                FROM `orders`
                JOIN `stores` ON orders.store_id = stores.id
                JOIN `freights` ON orders.id = freights.order_id
                JOIN `orders_item` ON freights.item_id = orders_item.product_id
                WHERE freights.link_plp is null
                AND freights.solicitou_plp = ? 
                AND freights.sgp = ?
                AND orders.paid_status = ? 
                AND orders_item.order_id = orders.id" . $more . "
                GROUP BY freights.item_id, freights.order_id";
        $query = $this->db->query($sql, array(0, $sgp, 50));
        return $query->result_array();
    }

    public function updateAddress($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('orders', $data);
            return ($update == true) ? $id : false;
        }
    }

    public function verifyOrderOfStore($order_id)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM orders WHERE id = " . $order_id . $more;
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function setIncidentOnOrder($order_id, $has_incident)
    {
        $sql = "UPDATE orders SET has_incident = ? WHERE id = ?";
        $result = $this->db->query($sql, array($has_incident, $order_id));
        return ($result == true) ? true : false;
    }

    public function getOrdersInProgress($offset = 0, $orderby = '')
    {
        if ($offset == '') {
            $offset = 0;
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter = "";
        }

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        if ($orderby == '') {
            $orderby = "orders.id";
        }

        $sql = "select
                orders.id,
                orders.customer_name,
                orders_item.name as prod_name,
                freights.codigo_rastreio,
                freights.ship_company,
                freights.updated_date,
                freights.updated_date,
                orders.origin,
                orders.numero_marketplace,
                orders.comments_adm,
                freights.prazoprevisto
                from orders
                join orders_item on orders.id = orders_item.order_id
                left join freights on orders.id = freights.order_id
                WHERE orders.paid_status not in (6, 60, 95, 96, 97, 98, 99) {$more} {$filter}
                ORDER BY {$orderby}
                limit 200 offset {$offset}";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getFullOrdersInProgress()
    {

        $sql = "select
				orders.id,
				orders.numero_marketplace,
                orders.customer_name,
                orders_item.name as prod_name,
                freights.codigo_rastreio,
                freights.ship_company,
                freights.updated_date,
                freights.updated_date,
                orders.origin,
                orders.numero_marketplace,
                orders.comments_adm,
                freights.prazoprevisto
                from orders
                join orders_item on orders.id = orders_item.order_id
                left join freights on orders.id = freights.order_id
                WHERE orders.paid_status not in (6, 60, 95, 96, 97, 98, 99) ";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersInProgressCount($filter = "")
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND orders.company_id = " . $this->data['usercomp'] : " AND orders.store_id = " . $this->data['userstore']);

        $sql = "select *
                from orders
                join orders_item on orders.id = orders_item.order_id
                left join freights on orders.id = freights.order_id
                WHERE orders.paid_status not in (6, 60, 95, 96, 97, 98, 99) {$more} {$filter}
                group by orders.id";

        $query = $this->db->query($sql, array());
        return $query->num_rows();
    }

    public function createCommentOrderInProgress($comment, $order_id)
    {
        $sql = "UPDATE orders SET comments_adm = ? WHERE id = ?";
        return $this->db->query($sql, array($comment, $order_id));
    }

    public function setShipCompanyPreview($order_id, $ship_company_preview, $ship_service_preview, $ship_time_preview, $estimateDate = null)
    {
        $sql = "UPDATE orders SET ship_company_preview = ?, ship_service_preview = ?, ship_time_preview = ?, shipping_estimate_date = ? WHERE id = ?";
        return $this->db->query($sql, array($ship_company_preview, $ship_service_preview, $ship_time_preview, $estimateDate, $order_id));
    }

    public function ExcelListSemFrete($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM orders WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        //$more = ($this->data['usercomp'] == 1) ? "": " AND company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND o.company_id = " . $this->data['usercomp'] : " AND o.store_id = " . $this->data['userstore']);

        $sql = "SELECT ";
        $sql .= " o.id as application_id";
        $sql .= ", s.name as application_store";
        $sql .= ", s.zipcode as application_store_zipcode";
        $sql .= ", s.address as application_store_address";
        $sql .= ", s.addr_num as application_store_num";
        $sql .= ", s.addr_compl as application_store_compl";
        $sql .= ", s.addr_city as application_store_city";
        $sql .= ", s.addr_uf as application_store_uf";
        $sql .= ", i.name as application_product_name";
        $sql .= ", i.qty as application_item_qty";
        $sql .= ", i.amount as application_value_products";
        $sql .= ", DATE_FORMAT (o.date_time,'%d/%m/%Y') as application_date ";
        $sql .= ", o.origin as application_marketplace ";
        $sql .= ", o.numero_marketplace as application_order_marketplace_full ";
        $sql .= ", o.customer_name as application_client ";
        $sql .= ", c.cpf_cnpj as application_cpf_cnpj ";
        $sql .= ", o.customer_address as application_delivery_address";
        $sql .= ", o.customer_address_num as application_number";
        $sql .= ", o.customer_address_compl as application_complement";
        $sql .= ", o.customer_address_neigh as application_neighb";
        $sql .= ", o.customer_address_city as application_city";
        $sql .= ", o.customer_address_uf as application_uf";
        $sql .= ", o.customer_address_zip as application_zip_code";
        $sql .= ", o.total_order as application_total_order";
        $sql .= ", o.gross_amount as application_gross_amount";
        $sql .= ", o.discount as application_discount";
        $sql .= ", o.total_ship as application_ship_value";
        $sql .= ", DATE_FORMAT (o.data_limite_cross_docking,'%d/%m/%Y') as application_crossdocking_limit_date";
        $sql .= ", o.paid_status as application_status";
        $sql .= ", o.ship_company_preview as application_ship_company";
        $sql .= ", o.ship_service_preview as application_service";
        $sql .= ", n.nfe_num as application_nfe_num";
        $sql .= " FROM orders o ";
        $sql .= " LEFT JOIN clients c ON c.id = o.customer_id";
        $sql .= " LEFT JOIN orders_item i ON i.order_id = o.id";
        $sql .= " LEFT JOIN stores s ON s.id = o.store_id";
        $sql .= " LEFT JOIN nfes n ON n.order_id = o.id";
        $sql .= " WHERE o.paid_status = 101" . $more . " ORDER BY o.id DESC";
        get_instance()->log_data('Orders', 'export', $sql, "I");

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersByProductItem($product_id, $origin = '')
    {
        if ($origin == '') {
            $sql = "SELECT * FROM orders WHERE id IN (SELECT order_id FROM orders_item WHERE product_id =?) ORDER By date_time DESC";
            $query = $this->db->query($sql, array($product_id));
        } else {
            $sql = "SELECT * FROM orders WHERE origin = ? AND id IN (SELECT order_id FROM orders_item WHERE product_id =?) ORDER By date_time DESC";
            $query = $this->db->query($sql, array($origin, $product_id));
        }

        return $query->result_array();
    }

    public function createExchangeOrder($order_id, $order_itens)
    {
        $sql = "SELECT * FROM orders WHERE id=?";
        $query = $this->db->query($sql, array($order_id));
        $order = $query->row_array();
        $net_amount = 0;
        $gross_amount = 0;
        $service_charge = 0;
        $frete = 0;
        foreach ($order_itens as $key => $order_item) {
            $sql = "SELECT * FROM orders_item WHERE order_id=? and sku=?";
            $query = $this->db->query($sql, array($order_id, $order_item['sku']));
            $orders_it = $query->row_array();
            $gross_amount_l = floatval($orders_it['amount']) * floatval($order_item['qty']);
            $net_amount += $gross_amount_l - floatval($orders_it['discount']);
            $gross_amount += $gross_amount_l;
        }
        $total_order = $gross_amount;
        $gross_amount += $frete;
        $service_charge = $gross_amount * floatval($order['service_charge_rate']) / 100;
        $new_order = array(
            'bill_no' => $order['bill_no'] . '-Troca',
            'numero_marketplace' => $order['numero_marketplace'] . '-Troca',
            'customer_id' => $order['customer_id'],
            'customer_name' => $order['customer_name'],
            'customer_address' => $order['customer_address'],
            'customer_phone' => $order['customer_phone'],
            'date_time' => date('Y-m-d'),
            'total_order' => $total_order,
            'discount' => $order['discount'],
            'net_amount' => $net_amount,
            'total_ship' => $order['total_ship'],
            'gross_amount' => $gross_amount,
            'service_charge_rate' => $order['service_charge_rate'],
            'service_charge' => $service_charge,
            'vat_charge_rate' => $order['vat_charge_rate'],
            'vat_charge' => $order['vat_charge'],
            'paid_status' => 3,
            'user_id' => $order['user_id'],
            'company_id' => $order['company_id'],
            'origin' => $order['origin'],
            'store_id' => $order['store_id'],
            'customer_address_num' => $order['customer_address_num'],
            'customer_address_compl' => $order['customer_address_compl'],
            'customer_address_neigh' => $order['customer_address_neigh'],
            'customer_address_city' => $order['customer_address_city'],
            'customer_address_uf' => $order['customer_address_uf'],
            'customer_address_zip' => $order['customer_address_zip'],
            'customer_reference' => $order['customer_reference'],
            'order_delayed_post' => 0,
            'ship_company_preview' => $order['ship_company_preview'],
            'ship_service_preview' => $order['ship_service_preview'],
            'exchange_request' => true,
            'original_order_marketplace' => $order['numero_marketplace'],
            'data_pago' => date('Y-m-d H:i:s'),
            'data_limite_cross_docking' => get_instance()->somar_dias_uteis(date("Y-m-d"), 5, ''),
        );
        $insert = $this->db->insert('orders', $new_order);
        $new_order_id = $this->db->insert_id();
        get_instance()->log_data('Orders', 'create', json_encode($new_order), "I");
        foreach ($order_itens as $key => $order_item) {
            $sql = "SELECT * FROM orders_item WHERE order_id=? and sku=?";
            $query = $this->db->query($sql, array($order_id, $order_item['sku']));
            $orders_it = $query->row_array();
            $orders_it['order_id'] = $new_order_id;
            $orders_it['qty'] = $order_item['qty'];
            unset($orders_it['id']);
            $insert = $this->db->insert('orders_item', $orders_it);
            $this->model_products->reduzEstoque($orders_it['product_id'], $orders_it['qty'], $orders_it['variant']);
        }
        $data_canc = array(
            'order_id' => $order_id,
            'reason' => 'Troca. Novo pedido=' . $new_order_id,
            'date_update' => date("Y-m-d H:i:s"),
            'status' => '1',
            'user_id' => $this->session->userdata('id'),
        );
        $this->insertPedidosCancelados($data_canc);
        $this->updatePaidStatus($order_id, '97');

        $this->load->model('model_integrations_webhook');
        
        if ($this->model_integrations_webhook->storeExists($new_order['store_id'])) {
            
            $this->load->library('ordersmarketplace');
            
            $store_id_wh = $new_order['store_id'];
            $typeIntegration = "pedido_criado";
            $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$new_order);
        }

        return $new_order_id;
    }

    public function getOrdersByFilter($filter)
    {
        $sql = "SELECT * FROM orders WHERE {$filter}";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function saveHistoryChangeSeller($order_id, $store_id, $justification)
    {
        $dataInsert = array(
            'order_id' => $order_id,
            'store_id' => $store_id,
            'justification' => $justification,
            'user_id' => $this->session->userdata('id') ?? 0,
        );

        return $this->db->insert('history_change_seller', $dataInsert);
    }

    public function countHistoryChangeSeller($order_id)
    {
        if (!$order_id) {
            return false;
        }

        $sql = "SELECT * FROM history_change_seller WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id));
        return $query->num_rows();
    }

    public function updateItenByOrderAndId($order_item_id, $data)
    {
        if ($order_item_id) {
            $this->db->where('id', $order_item_id);
            $update = $this->db->update('orders_item', $data);
            return $update;
        }
        return false;
    }

    public function createOrderToIntegration($order_id, $company_id, $store_id, $paid_status, $new_order)
    {
        $dataInsert = array(
            'order_id' => $order_id,
            'company_id' => $company_id,
            'store_id' => $store_id,
            'paid_status' => $paid_status,
            'new_order' => $new_order,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        return $this->db->insert('orders_to_integration', $dataInsert);
    }

    public function updateOrderToIntegrationByOrderAndStatus($order_id, $store_id, $paid_status, $data)
    {
        if ($order_id && $store_id && $paid_status && $data) {
            $this->db->where(['order_id' => $order_id, 'paid_status' => $paid_status, 'store_id' => $store_id]);
            $update = $this->db->update('orders_to_integration', $data);
            return $update;
        }
        return false;
    }

    public function updateDataEnvioStatus55($id, $data_envio, $status)
    {
        $sql = "UPDATE orders SET data_envio = ?, paid_status = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_envio, $status, $id));
        return ($result == true) ? true : false;
    }

    public function updateDataEntregaStatus60($id, $data_entrega, $status)
    {
        $sql = "UPDATE orders SET data_entrega = ?, paid_status = ? WHERE id = ?";
        $result = $this->db->query($sql, array($data_entrega, $status, $id));
        return ($result == true) ? true : false;
    }

    public function updateOrderIncidence($id, $user_id, $incidence)
    {
        if (!$id) {
            return false;
        }

        $sql = "UPDATE orders SET incidence_message = ?, incidence_user = ? WHERE id = ?";
        $result = $this->db->query($sql, array($incidence, $user_id, $id));
        return ($result == true) ? true : false;
    }

    public function getEtiquetasCarrier(array $stores = array(), array $shipCompany = array()): array
    {
        $this->db->select('*, s.name as store, o.store_id as store_id, o.freight_accepted_generation')
            ->join('orders o', 'o.id = f.order_id')
            ->join('stores s', 's.id = o.store_id')
            ->join('nfes n', 'n.order_id = o.id')
            ->where('f.sgp !=', '1')
            ->where_in('o.paid_status', [4,5,40,43,51,53,55,58,59])
            ->group_by('f.codigo_rastreio');

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('o.company_id', $this->data['usercomp']);
            } else {
                $this->db->where('o.store_id', $this->data['userstore']);
            }
        }

        if (count($stores) && !empty($stores[0])) {
            $this->db->where_in('o.store_id', $stores);
        }
        if (count($shipCompany) && !empty($shipCompany[0])) {
            $this->db->where_in('f.ship_company', $shipCompany);
        }

        return $this->db->get('freights f')->result_array();
    }

    public static function isFreightAcceptedGeneration($order): bool
    {
        return ((int)$order['freight_accepted_generation']) === 1;
    }

    public function getEtiquetasWithoutCarrier(array $stores = array()): array
    {
        $this->db->select('o.store_id, s.name as store, o.id as order_id, o.customer_name, o.date_time, o.data_limite_cross_docking, n.nfe_num')
            ->join('stores s', 's.id = o.store_id')
            ->join('nfes n', 'n.order_id = o.id')
            ->where('o.freight_accepted_generation', 0)
            ->where_in('o.paid_status', [41, 50, 80]);

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('o.company_id', $this->data['usercomp']);
            } else {
                $this->db->where('o.store_id', $this->data['userstore']);
            }
        }

        if (count($stores) && !empty($stores[0])) {
            $this->db->where_in('o.store_id', $stores);
        }

        return $this->db->get('orders o')->result_array();
    }

    public function getOrdersPaidWithoutPayment($int_to = null)
    {
    	if (is_null($int_to)) {
    		$sql = "SELECT o.*
                FROM orders o
                LEFT JOIN orders_payment p ON o.id = p.order_id
                WHERE o.paid_status not in (1, 96)
                AND p.id is null";
        	$query = $this->db->query($sql);
    	}
		else {
			$sql = "SELECT o.*
                FROM orders o
                LEFT JOIN orders_payment p ON o.id = p.order_id
                WHERE o.paid_status not in (1, 96)
                AND p.id is null AND origin = ?";
       		 $query = $this->db->query($sql, array($int_to));
		}
        
        return $query->result_array();
    }

    public function getOrdersForCancelIntelipost()
    {
        $sql = "SELECT o.*
                FROM orders o
                JOIN freights f ON o.id = f.order_id
                WHERE o.paid_status = ?
                AND f.sgp = ?
                AND f.ship_company = ?
                GROUP BY f.order_id";
        $query = $this->db->query($sql, array(98, 4, 'Intelipost'));
        return $query->result_array();
    }

    public function getOrdersProgressPayment($int_to=null)
    {
    	if (is_null($int_to)) {

            //Correção Grupo Soma
            $sqlsc = "SELECT * FROM settings WHERE name = ?";
            $querysc = $this->db->query($sqlsc, ['sellercenter']);
            $rowsc = $querysc->row_array();

            if($rowsc['value'] == "somaplace"){
                $sql = "SELECT * FROM orders WHERE paid_status in (1,2)";
            }else{
                $sql = "SELECT * FROM orders WHERE paid_status = 2";
            }

        	$query = $this->db->query($sql);
    	}else {
    		$sql = "SELECT * FROM orders WHERE paid_status in (1,2) AND origin = ?";
        	$query = $this->db->query($sql, $int_to);
    	}
        return $query->result_array();
    }

    public function getAddressPickUpByOrder()
    {
        $sql = "SELECT
                    frete_ocorrencias.nome,
                    frete_ocorrencias.data_ocorrencia,
                    frete_ocorrencias.mensagem,
                    frete_ocorrencias.addr_place,
                    frete_ocorrencias.addr_name,
                    frete_ocorrencias.addr_num,
                    frete_ocorrencias.addr_cep,
                    frete_ocorrencias.addr_neigh,
                    frete_ocorrencias.addr_city,
                    frete_ocorrencias.addr_state,
                    freights.order_id
                FROM orders
                JOIN freights ON orders.id = freights.order_id
                JOIN frete_ocorrencias on freights.id = frete_ocorrencias.freights_id
                WHERE orders.paid_status = 58
                AND (
                    (frete_ocorrencias.codigo IN (24,48) AND tipo IN ('BDE', 'BDI', 'BDR'))
                    OR (frete_ocorrencias.codigo IN (0,1,2,3,14) AND tipo IN ('LDI'))
                )";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdensByOriginPaidStatusCompanyId($origin, $paid_status, $company_id)
    {

        $sql = "SELECT * FROM orders WHERE origin = ? AND paid_status = ? AND company_id=?";
        $query = $this->db->query($sql, array($origin, $paid_status, $company_id));

        return $query->result_array();
    }
    public function getDataOrdersIntegrations($order_id)
    {
        $retorno = $this->db
            ->select(
                array(
                    'orders.id as order_id',
                    'orders.numero_marketplace as numero_marketplace',
                    'orders.customer_name as name_order', //Nome do Cliente (Entrega)
                    'orders.customer_address as address_order', //Endereo do Cliente (Entrega)
                    'orders.customer_phone as phone_order', //Telefone do Cliente (Entrega)
                    'orders.customer_address_num', //Número do Cliente (Entrega)
                    'orders.customer_address_compl', //Complemento do Cliente (Entrega)
                    'orders.customer_address_neigh', //Bairro do Cliente (Entrega)
                    'orders.customer_address_city', //Cidade do Cliente (Entrega)
                    'orders.customer_address_uf', //Estado do Cliente (Entrega)
                    'orders.customer_address_zip', //CEP do Cliente (Entrega)
                    'orders.customer_reference', //Referência do Cliente (Entrega)
                    'orders.data_entrega',
                    'orders.data_envio',
                    'orders.data_coleta',
                    'orders.data_pago',
                    'orders.company_id',
                    'orders.order_id_integration',
                    'orders.customer_phone as phone_order',
                    'orders.date_time as date_created',
                    'orders.gross_amount as gross_amount',
                    'orders.discount as discount_order',
                    'orders.total_ship as total_ship',
                    'orders.ship_company_preview as ship_company',
                    'orders.ship_service_preview as ship_service',
                    'orders.frete_real',
                    'orders.paid_status',
                    'orders.total_order',
                    'orders.net_amount',
                    'orders.origin as origin',
                    'orders.service_charge as taxa',
                    'clients.id as cod_client',
                    'clients.customer_name as name_client',
                    'clients.customer_address as address_client',
                    'clients.addr_num as num_client',
                    'clients.addr_compl as compl_client',
                    'clients.addr_neigh as neigh_client',
                    'clients.addr_city as city_client',
                    'clients.addr_uf as uf_client',
                    'clients.zipcode as cep_client',
                    'clients.phone_1 as phone_client_1',
                    'clients.phone_2 as phone_client_2',
                    'clients.email as email_client',
                    'clients.cpf_cnpj as cpf_cnpj_client',
                    'clients.ie as ie_client',
                    'clients.rg as rg_client',
                    'clients.country as country_client',
                    // 'orders_item.id as id_iten_product',
                    // 'orders_item.product_id as id_product',
                    // 'orders_item.sku as sku_product',
                    // 'orders_item.name as name_product',
                    // 'orders_item.un as un_product',
                    // 'orders_item.qty as qty_product',
                    // 'orders_item.rate as rate_product',
                    // 'orders_item.discount as discount_product',
                    // 'orders_item.variant as variant_product',
                    // 'orders_item.pesobruto as peso_product',
                    // 'products.price as price_product',
                    // 'products.product_id_erp  as product_id_erp ',
                    'orders_payment.id as id_payment',
                    'orders_payment.parcela as parcela_payment',
                    'orders_payment.data_vencto as vencto_payment',
                    'orders_payment.valor as valor_payment',
                    'orders_payment.forma_desc as forma_payment',
                    'nfes.nfe_num',
                )
            )
            ->from('orders')
        // ->join('orders_item', 'orders.id = orders_item.order_id')
        // ->join('products', 'products.id = orders_item.product_id')
            ->join('orders_payment', 'orders.id = orders_payment.order_id', 'left')
            ->join('clients', 'orders.customer_id = clients.id', 'left')
            ->join('nfes', 'orders.id = nfes.order_id', 'left')
            ->where(
                array(
                    'orders.store_id' => $this->store,
                    'orders.id' => $order_id,
                )
            )
            ->get()
            ->row_array();
        // print($this->db->last_query());
        return $retorno;
    }
    public function getOrdersIntegrations($store)
    {
        return $this->db
            ->from('orders_to_integration')
            ->where(array(
                'store_id' => $store,
                'new_order' => 1,
            ))
            ->where_in('paid_status', array(3))
            ->get()
            ->result_array();
    }
    public function updateOrderIdIntegrationByOrderID($order_id, $order_id_integration)
    {
        $this->db
            ->from('orders')
            ->where(array(
                'id' => $order_id,
            ))
            ->update('orders', array(
                'order_id_integration' => $order_id_integration,
            ));
    }
    public function updateDataAndStatusByIdIntegrationAndStoreIdAndPaidStatus($idIntegration, $store, $paid_status)
    {
        if ($paid_status == 3) {
            $arrUpdate = array(
                'new_order' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            );

            $update = $this->db->where(
                array(
                    'id' => $idIntegration,
                    'store_id' => $store,
                )
            )->update('orders_to_integration', $arrUpdate);

            return $update ? true : false;
        }

        return false;
    }
    public function saveOrderIdIntegrationByOrderIDAndStoreId($order_id, $store, $id_bseller)
    {
        $this->db->where(
            array(
                'id' => $order_id,
                'store_id' => $store,
            )
        )->update('orders', array('order_id_integration' => $id_bseller));
    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int         $orderId        Código do pedido
     * @param   int         $store          Código da loja
     * @param   int         $status         Código do status
     * @param   int|null    $verifyStatus   Código do status para verificação
     * @param   bool        $cancelado
     * @return  bool                        Retorna o status da atualização
     */
    public function updateStatusForOrder(int $orderId, int $store, int $status, int $verifyStatus = null, bool $cancelado = false): bool
    {
        $where = array(
            'id' => $orderId,
            'store_id' => $store,
        );
        if ($verifyStatus) {
            $where['paid_status'] = $verifyStatus;
        }

        if ($cancelado) {
            //atualiza a tabela orders_to_integration
            $this->db->where(array('order_id' => $orderId, 'store_id' => $store))->update('orders_to_integration', array('paid_status' => $status));
        }

        return (bool)$this->db->where($where)->update('orders', array('paid_status' => $status));
    }
    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    public function createNfe($data, $store)
    {
        $sqlNfe = $this->db->insert_string('nfes', $data);
        $insertNfe = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) {
            return false;
        }

        return $this->model_orders->updateStatusForOrder($data['order_id'], $store, 52, 3);
    }

    public function getArrayPaidStatus()
    {
        return $this->PAID_STATUS;
    }

    public function getMandanteFretePedido($id = null)
    {
        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');


        $irrfDescontado = $this->model_settings->getSettingDatabyName('irrf_painel_financeiro');
        $irrfValorPercentual = $this->model_settings->getSettingDatabyName('irrf_valor_painel_financeiro');

        if ($id == null) {
            $retorno['tipo_frete'] = "Conecta Lá";
            $retorno['expectativaReceb'] = "0";
            return $retorno;
        } else {

            $case_frete_100 = '';
            if($frete_100_canal_seller_centers_vtex == 1){
                $case_frete_100 = '                    
                    WHEN O.service_charge_freight_value = 100 THEN 
                    CASE  WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    else
                        ROUND(( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    end
                ';
            }

            $sql = "SELECT
                    CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end as tipo_frete,
                    CASE ".$case_frete_100." WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        CASE WHEN O.origin = 'B2W' THEN
                                CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-29 23:59:59' THEN
                                    ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ),2)
                                
                                ELSE
                                    ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                END
                            ELSE
                                ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                            END
                        ELSE
                            CASE WHEN O.origin = 'B2W' THEN
                                CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                    ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ) - O.total_ship,2)
                                
                                ELSE
                                    ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                END
                            ELSE
                                ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                            END
                    END ";

            /*if($irrfDescontado['status'] == 1){
                $sql .= " + ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2)";
            }*/
            $sql .= " AS expectativaReceb,";

            $case_frete_100 = '';
            if($frete_100_canal_seller_centers_vtex == 1){
                $case_frete_100 = '                    
                    WHEN O.service_charge_freight_value = 100 THEN 
                        CASE WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                            ROUND(( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                        else
                            ROUND(( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)                        
                        end
                ';
            }

            $sql .= "  CASE ".$case_frete_100." WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        CASE WHEN O.origin = 'B2W' THEN
                                CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                    ROUND( ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ),2)                             
                                ELSE
                                    ROUND( ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                END
                            ELSE
                                ROUND( ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                            END
                        ELSE
                            CASE WHEN O.origin = 'B2W' THEN
                                CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                    ROUND( ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ) ,2)                               
                                ELSE
                                    ROUND( ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ) ,2)
                                END
                            ELSE
                                ROUND( (  ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ) ,2)
                            END
                    END ";

            /*if($irrfDescontado['status'] == 1){
                $sql .= " - ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2)";
            }*/

            $sql .= " AS taxa_descontada,";
            $sql .= " CASE WHEN O.origin = 'B2W' AND date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-29 23:59:59' then 'Especial' else 'Normal' end as tipo_taxa
                    FROM orders O
                    INNER JOIN stores S ON S.id = O.store_id
                    LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights WHERE freights.order_id = ?) FR ON FR.order_id = O.id
                    WHERE O.id = ?";
            $query = $this->db->query($sql, array($id, $id));
            $result = $query->row_array();

            if ($result) {
                return $result;
            } else {
                $retorno['tipo_frete'] = "Conecta Lá";
                $retorno['expectativaReceb'] = "0";
                return $retorno;
            }

        }

    }
    public function getOrdersByStore($store)
    {
        return $this->db->select('*')->from(Model_orders::TABLE)->where(['store_id' => $store])->get()->result_array();
    }

    public function getOrderToNotificationByUser($user_id)
    {
        $user = $this->db->from("users")->where('id', $user_id)->get()->row_array();
        $result = $this->db->select("*")->from("orders");
        $result = $result->where([
            'date_format(data_pago,\'%Y-%m-%d\') >=' => '2021-06-30',
        ]);
        if ($user['store_id'] == 0) {
            return [];
        } else {
            $result = $result->where('store_id', $user['store_id']);
        }
        $result = $result->where('paid_status', '3');

        return $result->get()->result_array();
    }
    public function updateOrderById($id, $data)
    {
        return $this->db->update('orders', $data, ['id' => $id]);
    }

    public function getOrderByIdAndStore($order_id, $store_id)
    {
        if ($order_id && $store_id) {
            // verifica se existe algum log para não duplicar
            return $this->db->get_where('orders',
                array(
                    'id' => $order_id,
                    'store_id' => $store_id
                )
            )->row_array();
        }
        return false;
    }

    // get the tracking code by orders
    public function getItemsFreightsGroupCodeTracking($order_id = null, $inResend = null)
    {
        if (!$order_id) {
            return false;
        }
        $whereResend = '';
        if ($inResend !== null) {
            if ($inResend) {
                $whereResend = 'AND in_resend_active = 1';
            } else {
                $whereResend = 'AND in_resend_active = 0';
            }

        }

        $sql = "SELECT * FROM freights WHERE order_id = ? {$whereResend} GROUP BY codigo_rastreio ORDER BY id";
        $query = $this->db->query($sql, array($order_id));
        return $query->result_array();
    }
	
	// check is there is new orders in a period of hours from a marketplace 
    public function getLastOrdersInTime($int_to, $hours)
    {
        $sql = "SELECT * FROM orders WHERE origin = ? AND date_time >= date_sub(NOW(), interval ? hour) LIMIT 1";
        $query = $this->db->query($sql, array($int_to, $hours));
        return $query->result_array();
    }

    public function getOrdersByStoreId(int $storeId, int $offset = 0, int $limit = 0)
    {
        $sql = "SELECT * FROM orders WHERE store_id = ? LIMIT ?,?";
        $query = $this->db->query($sql, array($storeId, $offset, $limit));
        return $query->result_array();
    }

    public static function getOpenedOrderStatus()
    {
        return [
            OrderStatusConst::WAITING_PAYMENT,
            OrderStatusConst::PROCESSING_PAYMENT,
            OrderStatusConst::WAITING_INVOICE,
            OrderStatusConst::WAITING_SHIPPING,
            OrderStatusConst::SHIPPED_IN_TRANSPORT,
            OrderStatusConst::WAITING_TRACKING,
            OrderStatusConst::WITH_TRACKING_WAITING_SHIPPING,
            OrderStatusConst::SHIPPED_IN_TRANSPORT_45,
            OrderStatusConst::INVOICED_WAITING_TRACKING,
            OrderStatusConst::PLP_SEND_TRACKING_MKTPLACE,
            OrderStatusConst::INVOICED_SEND_INVOICE_MKTPLACE,
            OrderStatusConst::WAITING_SHIPPING_TO_TRACKING,
            OrderStatusConst::SHIPPED_IN_TRANSPORT_NOTIFY_MKTPLACE,
            OrderStatusConst::PROCESSING_INVOICE,
            OrderStatusConst::INVOICE_WITH_ERROR,
            OrderStatusConst::WAITING_WITHDRAWAL,
            OrderStatusConst::DEVOLUTION_IN_TRANSPORT,
            OrderStatusConst::MISPLACEMENT_IN_TRANSPORT,
            OrderStatusConst::ERROR_FREIGHT_CONTRACTING,
            OrderStatusConst::CANCELLATION_REQUESTED,
            OrderStatusConst::WITHOUT_FREIGHT_QUOTE,
        ];
    }

    public function setNewDiscountOnOrder(int $orderId, int $orderItemId, string $newDiscount, string $newDiscountItem): void
    {
        $this->load->model('model_settings');
        $sellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter');

        if ($sellerCenter === 'somaplace' && $newDiscount > 0) {
            $sql = "UPDATE orders SET net_amount = net_amount-? WHERE id = ?";
            $this->db->query($sql, array($newDiscount, $orderId));
        }

        if ($newDiscount > 0) {
            $sql = "UPDATE orders SET discount = discount+? WHERE id = ?";
            $this->db->query($sql, array($newDiscount, $orderId));

            $sql = "UPDATE orders_item SET discount = discount+? WHERE id = ?";
            $this->db->query($sql, array($newDiscountItem, $orderItemId));
        }

    }

    //braun
    public function addOrderDiscount($data, $order_id)
    {

        $fields = [];

        $sql = "select order_id from campaign_v2_orders where order_id = ".$order_id;
        $query = $this->db->query($sql);
        $order_exists = $query->result_array();

        if ($order_exists){

            $sql = "UPDATE campaign_v2_orders SET ";

            foreach ($data as $column => $value) {
                $fields[] = $column." = ".$column." + ".$value;
            }

            $sql .= implode(',', $fields)." where order_id = ".$order_id;

        }else{

            $sql = "INSERT INTO campaign_v2_orders (order_id, ".implode(',', array_keys($data)).") 
                            VALUES (".$order_id.",".implode(',', $data).")";

        }

        $this->db->query($sql);

    }

    //braun
    public function addOrderDiscountCampaings($data, $order_id, $campaign_id)
    {
        if(empty($data))
            return false;

        $fields = [];

        // unset($data['total_pricetags']);
        // unset($data['total_products']);
        // unset($data['total_channe']);

        $sql = "select id from campaign_v2_orders_campaigns where order_id = ".$order_id." and campaign_id = ".$campaign_id;
        $query = $this->db->query($sql);
        $order_exists = $query->result_array();
        
        if (empty($order_exists))
        {
            $sql = "insert into campaign_v2_orders_campaigns (order_id, campaign_id, ".implode(',', array_keys($data)).") VALUES (".$order_id.", ".$campaign_id.",".implode(',', $data).")";
        }
        else
        {
            $sql = "update campaign_v2_orders_campaigns set ";
            
            foreach ($data as $k => $v)
            {
                $fields[] = $k." = ".$k." + ".$v;
            }

            $sql .= implode(',', $fields)." where order_id = ".$order_id." and campaign_id = ".$campaign_id;            
        }

        $this->db->query($sql);
    }
  
    public function atualizamdrpayment($mdr, $paymentID){

          $sql = "update orders_payment set taxa_cartao_credito = '$mdr' where payment_id = UPPER(REPLACE('$paymentID','-','')) or transaction_id = UPPER(REPLACE('$paymentID','-',''))";

          return $this->db->query($sql);

    }

    /**
     * Recupera dodos do pedido pelo código do pedido na integradora.
     *
     * @param   string      $orderIntegration   Código do pedido na integradora (orders.order_id_integration).
     * @param   int|null    $store              Código da loja (stores.id).
     * @return  array|null                      Dados do pedido.
     */
    public function getOrderByOrderIdIntegration(string $orderIntegration, int $store = null): ?array
    {
        $where = array('order_id_integration' => $orderIntegration);

        if ($store) {
            $where['store_id'] = $store;
        }

        return $this->db->get_where('orders', $where)->row_array();
    }

    public function getOrdersInProgressInteraction($int_to = null, $lastMonthOrder = 3)
    {
        /*if ($int_to === 'CasaeVideo') {
            $lastMonthOrder = 6;
        }*/

        $this->db->select('orders_payment.*, orders.origin, orders.date_time')
            ->from('orders')
            ->join('orders_payment', 'orders.id = orders_payment.order_id')
            ->where("orders.date_time BETWEEN (CURRENT_TIMESTAMP - INTERVAL $lastMonthOrder MONTH) AND CURRENT_TIMESTAMP")
            ->where('date_time >=', '2022-01-03 00:00:00')
            ->where_not_in('orders.paid_status', array(6, 95, 97));

        if (!is_null($int_to)) {
            $this->db->where('orders.origin', $int_to);
        }

        return $this->db->get()->result_array();
    }

    public function getOrdersWithoutCaptureDate($int_to = null)
    {
        $this->db->select('orders_payment.*, orders.origin, orders.date_time')
            ->from('orders')
            ->join('orders_payment', 'orders.id = orders_payment.order_id')
            ->where('orders_payment.capture_date IS NULL')
            ->where_in('orders.paid_status', array(6,60,59,58,57,56,55,53,52,51,50,45,43,40,5,4,3));

        if (!is_null($int_to)) {
            $this->db->where('orders.origin', $int_to);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Recupera o status do pedido na hora que atualiza
     *
     * @param   int     $idOrder    Código do pedidos
     * @return  array
     */
    public function getOrderStatusNow(int $idOrder): array
    {
        if($idOrder){
            $sql = "SELECT status, date_status_update  FROM order_status WHERE order_id = ?";
            $query = $this->db->query($sql, $idOrder);
            return $query->result_array();
        }

        return [];
    }

    public function getAllOrdersDatabyBill($origin = null, $id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM orders WHERE origin = ? AND bill_no = ?";
            $query = $this->db->query($sql, array($origin, $id));
            return $query->result_array();
        }        
    }

    public function getDetalheTaxas(int $orderId): array
    {
        $somaPLace = $this->model_settings->getValueIfAtiveByName('sellercenter');

        $result = [];

        $sql = "
            SELECT *, co.total_rebate AS trebate FROM orders o
            LEFT JOIN campaign_v2_orders co ON co.order_id = o.id     
            LEFT JOIN campaign_v2_orders_campaigns coc ON co.order_id = coc.order_id
            LEFT JOIN campaign_v2 c ON c.id = coc.campaign_id
            WHERE o.id = ? GROUP BY co.id
        ";

        $taxas = $this->db->query($sql, array($orderId));

        $this->load->model('model_campaigns_v2');

        foreach($taxas->result() as $taxa)
        {
            $service_charge_rate          = $taxa->service_charge_rate;
            $service_charge_freight_value = $taxa->service_charge_freight_value;

            if ($taxa->freight_seller == 0 && $taxa->service_charge_freight_value == 100)
            {
                $service_charge_freight_value = 0;
            }

            $comissao_frete = $taxa->total_ship * ($service_charge_freight_value/100);

            $total_rebate = $taxa->trebate == null ? 0 : $taxa->trebate * ($taxa->service_charge_rate/100);

            if ($somaPLace && $somaPLace == 'somaplace')
            {
                $comissao_produto = ($taxa->net_amount - $taxa->total_ship ) * ($taxa->service_charge_rate/100);
            }
            else
            {
                $comissao_produto = ($taxa->gross_amount - $taxa->total_ship ) * ($taxa->service_charge_rate/100);
            }

            $comissao_campanha = $taxa->total_channel * ($taxa->service_charge_rate / 100);
            $tax_pricetags     = $taxa->total_pricetags * ($taxa->service_charge_rate / 100);
            $reembolso_mkt     = $taxa->total_channel;

            //fin-685
            if (ENVIRONMENT == 'development2')
            {
                $reembolso_mkt = ($taxa->total_channel - ($taxa->total_channel * ($taxa->service_charge_rate / 100))) + ($taxa->total_pricetags - $tax_pricetags);
            }

            $valor_total = ( $comissao_produto + $comissao_frete + $comissao_campanha) - ( $taxa->comission_reduction + $total_rebate + $reembolso_mkt);

            //fin-685
            if (ENVIRONMENT == 'development2')
            {
                $valor_total = ($comissao_produto + $comissao_frete + $comissao_campanha + $tax_pricetags) - ($taxa->comission_reduction + $total_rebate + $reembolso_mkt);
            }

            $array = array(
                'campanha_id'         => $taxa->campaign_id,
                'campanha_name'       => $taxa->name,
                'comissao_produto'    => $comissao_produto,
                'comissao_frete'      => $comissao_frete,
                'reducao_comissao'    => $taxa->comission_reduction,
                'total_rebate'        => $total_rebate,
                'total_desconto'      => $valor_total,
                'comissao_campanha'   => $comissao_campanha,
                'reembolso_mkt'       => $reembolso_mkt,
//				'pricetags_comission' => $taxa->total_pricetags - ($taxa->total_pricetags * ($service_charge_rate / 100))
//              'pricetags_comission' => $taxa->total_pricetags * ($service_charge_rate / 100) - COMENTADO POIS A FAST PEDIU PARA RETIRAR A COMISSÃO DO CUPOM
                'pricetags_comission' => 0
            );

            $array['type'] = null;

            if ($total_rebate > 0)
            {
                $array['type'] = 'campaign';
            }

            if ($taxa->comission_reduction > 0)
            {
                $array['type'] = 'campaign';
            }

            array_push($result, $array);
        }

        return $result;
    }

    public function calculateRefundCommission(int $orderId, bool $calculateCampaign = true): float
    {
        $values = $this->getDetalheTaxas($orderId);
        $commission = 0;
        foreach ($values as $conta) {
            $partial = $conta['comissao_produto'] + $conta['comissao_frete'] + $conta['comissao_campanha'];
            if (!$calculateCampaign) {
                $partial -= $conta['reembolso_mkt'];
            }
            $commission += $partial;
        }

        return round($commission * -1, 2);
    }

    /**
     * Recupera os dados para o modal de Detalhamento de taxas
     *
     * @param int $idorder Código do pedido
     * @return array
     */
    public function getDetalheTaxasModalOrders(int $orderId): array
    {
        $somaPlace = $this->model_settings->getValueIfAtiveByName('sellercenter');

        $result = [];
        $result['campaigns'] = [];
        $result['total_shipping_commission'] = 0;

        $sql = "
        SELECT 
            o.service_charge_freight_value, 
            o.freight_seller, 
            o.total_ship, 
            o.service_charge_rate,
            coc.campaign_id, 
            coi.total_rebate, 
            coi.total_reduced_marketplace, 
            coi.total_reduced, 
            coi.channel_discount,
            oi.id as order_item_id,
            oi.qty, 
            oi.amount, 
            oi.name AS product_name, 
            oi.product_id AS product_id, 
            oi.sku,
            c.name AS campaign_name, 
            c.id AS campaign_id
        FROM orders o
        LEFT JOIN orders_item oi ON o.id = oi.order_id
        LEFT JOIN campaign_v2_orders_items coi ON coi.item_id = oi.id
        LEFT JOIN campaign_v2_orders_campaigns coc ON o.id = coc.order_id
            AND oi.order_id = coc.order_id 
            AND coi.campaign_v2_id = coc.campaign_id
        LEFT JOIN campaign_v2 c ON c.id = coi.campaign_v2_id
        WHERE o.id = ?
    ";

        $taxas = $this->db->query($sql, [$orderId]);

        $groupedProducts = [];

        foreach ($taxas->result() as $taxa) {
            $serviceChargeFreightValue = ($taxa->freight_seller == 0 && $taxa->service_charge_freight_value == 100)
                ? 0
                : $taxa->service_charge_freight_value;

            $commision = $this->model_commissioning_orders_items->getCommissionByOrderAndItem($orderId, $taxa->order_item_id);
            if ($commision) {
                $taxa->service_charge_rate = $commision['comission'];
                $commision = $this->model_commissionings->getById($commision['commissioning_id']);
            }

            $comissaoFrete = $taxa->total_ship * ($serviceChargeFreightValue / 100);
            $totalRebate = ($taxa->total_rebate ?? 0) * ($taxa->service_charge_rate / 100);
            $comissaoProduto = $taxa->amount * ($taxa->service_charge_rate / 100);
            $comissaoCampanha = $taxa->channel_discount * ($taxa->service_charge_rate / 100);
            $reembolsoMkt = $taxa->channel_discount ?? 0;

            $valorTotal = ($comissaoProduto + $comissaoFrete + $comissaoCampanha)
                - ($taxa->total_reduced + $totalRebate + $reembolsoMkt);

            $campaignData = [
                'idcampaign'        => $taxa->campaign_id,
                'name'              => $taxa->campaign_name,
                'product_name'      => $taxa->product_name,
                'qty'      => $taxa->qty,
                'sku'               => $taxa->sku,
                'service_charge_rate' => $taxa->service_charge_rate,
                'comissao_produto'  => $comissaoProduto,
                'comissao_frete'    => $comissaoFrete,
                'reducao_comissao'  => $taxa->total_reduced,
                'total_rebate'      => $totalRebate,
                'total_desconto'    => $valorTotal,
                'comissao_campanha' => $comissaoCampanha,
                'reembolso_mkt'     => $reembolsoMkt,
                'type'              => ($totalRebate > 0 || $taxa->total_reduced > 0) ? 'campaign' : null,
            ];

            // Agrupamento por SKU
            if (!isset($groupedProducts[$taxa->sku])) {
                $groupedProducts[$taxa->sku] = [
                    'product_id'            => $taxa->product_id,
                    'sku'            => $taxa->sku,
                    'product_name'   => $taxa->product_name,
                    'qty'   => $taxa->qty,
                    'total_rebate'   => 0,
                    'comissao_frete' => 0,
                    'comissao_produto' => 0,
                    'comissao_campanha' => 0,
                    'total_desconto' => 0,
                    'service_charge_rate' => $taxa->service_charge_rate,
                    'campaigns'      => [],
                ];
            }

            // Atualiza totais no agrupamento
            $groupedProducts[$taxa->sku]['total_rebate'] += $totalRebate;
            $groupedProducts[$taxa->sku]['comissao_frete'] = $comissaoFrete;
            $groupedProducts[$taxa->sku]['comissao_produto'] = $comissaoProduto;
            $groupedProducts[$taxa->sku]['comissao_campanha'] += $comissaoCampanha;
            $groupedProducts[$taxa->sku]['total_desconto'] += $valorTotal;

            // Adiciona a campanha ao agrupamento
            $groupedProducts[$taxa->sku]['campaigns'][] = $campaignData;
            $groupedProducts[$taxa->sku]['custom_comission'] = $commision;
            $groupedProducts[$taxa->sku]['has_campaigns'] = false;
            if ($campaignData && $campaignData['idcampaign'] && !$groupedProducts[$taxa->sku]['has_campaigns']){
                $groupedProducts[$taxa->sku]['has_campaigns'] = $campaignData;
            }
        }

        // Ajuste para calcular o total das taxas no topo
        $totalTaxas = $comissaoFrete;

        foreach ($groupedProducts as $sku => &$product) {

            $totalTaxesProduct = 0;

            foreach ($product['campaigns'] as $k => &$campaign) {

                $campaignTotalTaxes = 0;

                if ($k == 0){
                    $totalTaxesProduct += $campaign['comissao_produto'];
                    $campaignTotalTaxes += $campaign['comissao_produto'];
                    $result['total_shipping_commission'] = $campaign['comissao_frete'];
                }

                if ($campaign['comissao_campanha']){
                    $totalTaxesProduct += $campaign['comissao_campanha'];
                    $campaignTotalTaxes += $campaign['comissao_campanha'];
                }

                if ($campaign['reembolso_mkt']){
                    $totalTaxesProduct -= $campaign['reembolso_mkt'];
                    $campaignTotalTaxes -= $campaign['reembolso_mkt'];
                }

                if ($campaign['reducao_comissao']){
                    $totalTaxesProduct -= $campaign['reducao_comissao'];
                    $campaignTotalTaxes -= $campaign['reducao_comissao'];
                }

                if ($campaign['total_rebate']){
                    $totalTaxesProduct -= $campaign['total_rebate'];
                    $campaignTotalTaxes -= $campaign['total_rebate'];
                }

                $campaign['total_taxes'] = $campaignTotalTaxes;

            }

            $totalTaxas += $totalTaxesProduct;

            // Adiciona o total calculado para o produto
            $product['total_taxes'] = $totalTaxesProduct;
        }

        $result['total_taxes'] = $totalTaxas;

        // Organiza o resultado
        foreach ($groupedProducts as $groupedProduct) {
            $result['campaigns'][] = $groupedProduct;
        }

        return $result;

    }

    public function getDetalheDescontos(int $orderId): array {

        $result = [];

        $sql = "SELECT * FROM campaign_v2_orders WHERE order_id = ?";
        $pedido = $this->db->query($sql, array($orderId))->row();
        //if(isset($pedido->total_promotions) && $pedido->total_promotions > 0){

        $sql = "SELECT *, p.promotion_group_id AS pid, oi.amount AS valor_item FROM orders_item oi 
            JOIN promotions p ON p.product_id = oi.product_id
            JOIN promotions_group pg ON pg.id = p.promotion_group_id
            JOIN campaign_v2_orders co ON co.order_id = oi.order_id 
            WHERE oi.order_id = ?";
        $promotions = $this->db->query($sql, array($orderId))->result();
        foreach($promotions as $promotion){

            $total = $promotion->total_promotions;
            if($total > 0){
                array_push($result,
                    array(
                        'type'                  => 'promotion',
                        'campanha_id'           => $promotion->pid,
                        'campanha_name'         => $promotion->nome,
                        'desconto_promocao'     => $promotion->total_promotions,
                        'desconto_campanha_seller'      => 0,
                        'desconto_campanha_marketplace' => 0,
                        'desconto_cupons'               => 0,
                        'desconto_marketplace'          => 0,
                        'total_desconto'        => $total
                    )
                );
            }

        }
        //}

        //if(isset($pedido->total_campaigns) && $pedido->total_campaigns > 0){

        // BUG PARA ACERTAR O CALCULO DO PRICETAG SEM TER CAMPANHA 11/09/2023
//            if (ENVIRONMENT == 'development')
//			{
        $sql = "SELECT DISTINCT *, co.total_seller AS tseller, co.total_channel AS tchannel, co.total_pricetags AS tprice_tags, c.id AS cid
                    FROM orders_item oi 
                    left JOIN campaign_v2_orders co ON co.order_id = oi.order_id   
                    left join campaign_v2_orders_campaigns coc on coc.order_id = co.order_id 
                    left JOIN campaign_v2 c ON c.id = coc.campaign_id 
                    WHERE co.order_id = ? GROUP BY co.id";
//            }else
//            {
//                $sql = "SELECT DISTINCT *, co.total_seller AS tseller, co.total_channel AS tchannel, co.total_pricetags AS tprice_tags, c.id AS cid FROM campaign_v2_orders_campaigns coc
//                JOIN campaign_v2 c ON c.id = coc.campaign_id
//                JOIN campaign_v2_orders co ON co.order_id = coc.order_id
//                WHERE co.order_id = ? GROUP BY co.id";
//            }

        $promotions = $this->db->query($sql, array($orderId))->result();
        foreach($promotions as $promotion){
            $total = $promotion->tprice_tags + $promotion->total_campaigns;
            if(($promotion->tseller > 0 || $promotion->tchannel > 0 || $promotion->tprice_tags > 0)){
                array_push($result,
                    array(
                        'type'                          => 'campaign',
                        'campanha_id'                   => $promotion->cid,
                        'campanha_name'                 => $promotion->name,
                        'desconto_campanha_seller'      => $promotion->tseller,
                        'desconto_campanha_marketplace' => $promotion->tchannel,
                        'desconto_cupons'               => $promotion->tprice_tags,
                        'desconto_marketplace'          => $promotion->total_campaigns,
                        'desconto_promocao'             => 0,
                        'total_desconto'                => ($promotion->tprice_tags + $promotion->total_campaigns)
                    )
                );
            }
        }
        //}

        return $result;
    }


    /**
     * Recupera os dados para o modal de Detalhamento de descontos
     *
     * @param int $idorder Código do pedido
     * @return array
     */
    public function getDetalheDescontosModalOrders(int $orderId): stdClass {

        $sql = "SELECT DISTINCT * FROM campaign_v2_orders WHERE order_id = ?";
        $result = $this->db->query($sql, array($orderId))->row();
        $result->campaigns = [];

        $sql = "SELECT *, 
                    oi.name as product_name, 
                    oi.qty, 
                    coi.seller_discount AS tseller, 
                    coi.channel_discount AS tchannel, 
                    coi.total_discount AS tdiscount, 
                    co.total_pricetags AS tprice_tags, 
                    c.id AS cid
            FROM orders_item oi 
            left JOIN campaign_v2_orders co ON co.order_id = oi.order_id   
            LEFT JOIN campaign_v2_orders_items coi ON (coi.item_id = oi.id)
            left join campaign_v2_orders_campaigns coc on (coc.order_id = co.order_id AND coc.campaign_id = coi.campaign_v2_id) 
            left JOIN campaign_v2 c ON c.id = coc.campaign_id 
            WHERE co.order_id = ? AND coc.total_discount IS NOT NULL";

        $promotions = $this->db->query($sql, array($orderId))->result();
        foreach($promotions as $promotion){
            if(($promotion->tseller > 0 || $promotion->tchannel > 0 || $promotion->tprice_tags > 0)){
                array_push($result->campaigns,
                    array(
                        'sku' => $promotion->sku,
                        'product_name' => $promotion->product_name,
                        'qty' => $promotion->qty,
                        'campanha_id' => $promotion->cid,
                        'campanha_name' => $promotion->name,
                        'desconto_campanha_seller' => $promotion->tseller,
                        'desconto_campanha_marketplace' => $promotion->tchannel,
                        'desconto_cupons' => $promotion->tprice_tags,
                        'desconto_marketplace' => $promotion->total_campaigns,
                        'total_campaign' => $promotion->tdiscount,
                        'total_desconto' => ($promotion->tprice_tags + $promotion->total_campaigns)
                    )
                );
            }
        }

        return $result;
    }

    public function getOrdersPaidWithoutPayment_with96($int_to = null, $offset = '', $limit = '')
    {
        $limit_sql = '';
        if (!empty($limit) && is_numeric($limit) && !empty($offset) && is_numeric($offset)) {
            $limit_sql = " LIMIT $offset, $limit";
        }

        if (is_null($int_to)) {
            $sql = "SELECT o.*
                FROM orders o
                LEFT JOIN orders_payment p ON o.id = p.order_id
                WHERE o.paid_status
                AND p.id is null 
                AND o.date_time > DATE_SUB(NOW(), INTERVAL 1 MONTH)" . $limit_sql;
            $query = $this->db->query($sql);
        }
        else {
            $sql = "SELECT o.*
                FROM orders o
                LEFT JOIN orders_payment p ON o.id = p.order_id
                WHERE o.paid_status
                AND p.id is null AND origin = ?
                AND o.date_time > DATE_SUB(NOW(), INTERVAL 1 MONTH)" . $limit_sql;
            $query = $this->db->query($sql, array($int_to));
        }

        return $query->result_array();
    }

    public function getOrderBynumeroMarketplaceAndOrigin(string $numeroMarketplace, string $origin = null)
    {
        $this->db->where('numero_marketplace', '"'.$numeroMarketplace.'"');

        if ($origin) {
            $this->db->where('origin', $origin);
        }

        return $this->db->get('orders')->row_array();
    }

    public function listOrdersCanceledPreToAnonymize(int $days = 30, int $offset = 0, int $limit = 0)
    {
        if($days){
            $sql = "select o.id as order_id, o.customer_id as client_id
            from orders o 
                join clients c on c.id = o.customer_id 
            where o.anonymized = 0 and paid_status in (96, 97) and date_cancel <= NOW() - INTERVAL ? DAY
            limit ?,?";
            $query = $this->db->query($sql, array($days, $offset, $limit));
            return $query->result_array();
        }
    }

    public function anonymizeByOrderId($order_id)
	{
		if($order_id) {
			$data = array(
                'customer_name' 	        => '***********',
                'customer_address' 	        => '***********',
                'customer_phone' 	        => '***********',
                'customer_address_num' 	    => '***********',
                'customer_address_compl' 	=> '***********',
                'customer_address_neigh' 	=> '***********',
                'customer_address_city' 	=> '***********',
                'customer_address_uf' 	    => '***********',
                'customer_reference' 	    => '***********',
                'anonymized'                => 1
			);

            $this->db->where('id', $order_id);
            $update = $this->db->update('orders', $data);
            return ($update == true) ? true : false;
        }
	}

    /**
     * Return the orders paid, wich was not paid in conciliation
     * @param array $storeIds
     * @param null $orderId
     * @param array|null $installments_number
     * @param string $status
     * @param array|null $order_date
     * @param bool $anticipatedOnly
     * @param int|null $simulationId
     * @return mixed
     */
    public function getOrdersNotPaidConciliationByStore(array $storeIds = [],
                                                        $orderId=null,
                                                        array $installments_number=null,
                                                        string $status=AnticipationStatusFilterEnum::NORMAL,
                                                        array $order_date = null,
                                                        bool $anticipatedOnly = false,
                                                        int $simulationId=null)
    {

        $return = $this->db->select(self::TABLE.'.id, stores.name AS store, orders.date_time, orders.numero_marketplace, 
        orders_conciliation_installments.current_installment, orders_conciliation_installments.total_installments,
        orders_conciliation_installments.data_ciclo, orders_conciliation_installments.installment_value, orders_conciliation_installments.anticipated,
        (SELECT sum(installment_value) FROM orders_conciliation_installments WHERE orders_conciliation_installments.order_id = orders.id AND orders_conciliation_installments.paid = 1) value_paid,
        (SELECT count(*) FROM orders_conciliation_installments WHERE orders_conciliation_installments.order_id = orders.id AND orders_conciliation_installments.anticipated = 1) installments_anticipated,
        (SELECT sum(orders_simulations_anticipations_store.amount-orders_simulations_anticipations_store.anticipation_fee-orders_simulations_anticipations_store.fee) 
            FROM orders_simulations_anticipations_store 
            JOIN simulations_anticipations_store ON (orders_simulations_anticipations_store.simulations_anticipations_store_id = simulations_anticipations_store.id) 
            WHERE orders_simulations_anticipations_store.order_id = orders.id AND simulations_anticipations_store.anticipation_status IN ("pending", "approved")) value_paid_anticipated,
        (SELECT sum(installment_value) FROM orders_conciliation_installments WHERE orders_conciliation_installments.order_id = orders.id AND orders_conciliation_installments.paid = 0) value_not_paid,
        (SELECT SUM(anticipation_fee) + SUM(fee) FROM orders_simulations_anticipations_store WHERE order_id = orders.id AND orders_simulations_anticipations_store.simulations_anticipations_store_id IN (SELECT id FROM simulations_anticipations_store WHERE simulations_anticipations_store.anticipation_status IN ("pending", "approved")) ) AS anticipation_taxes, 
        valor_repasse_ajustado,
        (SELECT SUM(anticipation_fee) FROM orders_simulations_anticipations_store WHERE order_id = orders.id AND orders_simulations_anticipations_store.simulations_anticipations_store_id IN (SELECT id FROM simulations_anticipations_store WHERE simulations_anticipations_store.anticipation_status IN ("pending", "approved")) ) AS total_anticipation_fee, 
        (SELECT SUM(fee) FROM orders_simulations_anticipations_store WHERE order_id = orders.id ) AS total_fee')
            ->from(self::TABLE)
            ->join('stores', 'stores.id = ' . self::TABLE . '.store_id')
            ->join('orders_conciliation_installments', 'orders_conciliation_installments.order_id = ' . self::TABLE . '.id')
            ->where_in('paid_status', [OrderStatusConst::DELIVERED,OrderStatusConst::DELIVERED_NOTIFY_MKTPLACE])
            ->group_by('orders.id');

        $return->where(self::TABLE.".store_id IN (".implode(',', $storeIds).")");

        if (!$anticipatedOnly){
            $return->where('orders_conciliation_installments.paid = 0')
                ->order_by('orders_conciliation_installments.anticipated');
        }

        if ($orderId){
            $return->where("(orders.id = '$orderId' OR orders.numero_marketplace = '$orderId')");
        }

        if ($installments_number){
            $return->where_in('orders_conciliation_installments.total_installments', $installments_number);
        }

        if ($status && !$anticipatedOnly){

            if ($status == AnticipationStatusFilterEnum::NORMAL){
                $return->where(self::TABLE.".id NOT IN (SELECT orders_simulations_anticipations_store.order_id 
                FROM orders_simulations_anticipations_store 
                JOIN simulations_anticipations_store ON (simulations_anticipations_store.id = orders_simulations_anticipations_store.simulations_anticipations_store_id)
                 WHERE simulations_anticipations_store.anticipation_status IN ('".AnticipationStatusEnum::APPROVED."', '".AnticipationStatusEnum::REFUSED."', '".AnticipationStatusEnum::PENDING."'))");
            }
            if ($status == AnticipationStatusFilterEnum::APPROVED){
                $return->where(self::TABLE.".id IN (SELECT orders_simulations_anticipations_store.order_id 
                FROM orders_simulations_anticipations_store 
                JOIN simulations_anticipations_store ON (simulations_anticipations_store.id = orders_simulations_anticipations_store.simulations_anticipations_store_id)
                 WHERE simulations_anticipations_store.anticipation_status IN ('".AnticipationStatusEnum::APPROVED."'))");
            }
            if ($status == AnticipationStatusFilterEnum::IN_ANTICIPATION){
                $return->where(self::TABLE.".id IN (SELECT orders_simulations_anticipations_store.order_id 
                FROM orders_simulations_anticipations_store 
                JOIN simulations_anticipations_store ON (simulations_anticipations_store.id = orders_simulations_anticipations_store.simulations_anticipations_store_id)
                 WHERE simulations_anticipations_store.anticipation_status IN ('".AnticipationStatusEnum::PENDING."'))");
            }
            if ($status == AnticipationStatusFilterEnum::REFUSED){
                $return->where(self::TABLE.".id IN (SELECT orders_simulations_anticipations_store.order_id 
                FROM orders_simulations_anticipations_store 
                JOIN simulations_anticipations_store ON (simulations_anticipations_store.id = orders_simulations_anticipations_store.simulations_anticipations_store_id) 
                WHERE simulations_anticipations_store.anticipation_status IN ('".AnticipationStatusEnum::REFUSED."'))");
            }

        }

        if ($order_date){

            if ($order_date['start']){
                $return->where("orders.date_time >= '{$order_date['start']}'");
            }
            if ($order_date['end']){
                $return->where("orders.date_time <= '{$order_date['end']}'");
            }

        }

        if ($simulationId || $anticipatedOnly){
            $join = $simulationId ? 'AND simulations_anticipations_store_id = '.$simulationId : '';
            $return->join('orders_simulations_anticipations_store', 'orders_simulations_anticipations_store.order_id = orders.id '.$join);
            $join = $anticipatedOnly ? 'AND simulations_anticipations_store.anticipation_status IN ("'.AnticipationStatusEnum::PENDING.'", "'.AnticipationStatusEnum::APPROVED.'")' : '';
            $return->join('simulations_anticipations_store', 'simulations_anticipations_store.id = orders_simulations_anticipations_store.simulations_anticipations_store_id '.$join);
        }

        return $return->get()->result_array();

    }

    public function findOrderWithInstallments(int $orderId): ?array
    {

        return $this->db->select(self::TABLE.".*, 
        orders.gross_amount total_order,
        orders.date_time order_date,
        orders.data_mkt_delivered order_delivered_date,
        (SELECT SUM(valor_repasse) FROM orders_conciliation_installments WHERE order_id = $orderId) as valor_repasse")
            ->from(self::TABLE)
            ->where('id', $orderId)
            ->get()
            ->row_array();

    }

    public function findOrdersWithoutOrdersPaymentDate($date_start = null): ?array
    {
		$where = '';

		if ($date_start)
		{
			$where = " and date(date_time) >= '".$date_start."' ";
		}

        /*return $this->db->select(self::TABLE.".id")
            ->from(self::TABLE)
            ->join('orders_payment_date', self::TABLE.'.id = orders_payment_date.order_id', 'left')
            ->where('(orders_payment_date.id IS NULL AND (data_mkt_delivered IS NOT NULL OR data_mkt_sent IS NOT NULL)'.$where.') 
            OR `'.self::TABLE.'`.`id` NOT IN (SELECT order_id FROM orders_conciliation_installments)'.$where)
            ->get()
            ->result_array();*/

        $sql = "select o.id
                from orders o
                left join orders_payment_date opd on opd.order_id = o.id 
                where opd.order_id is null and data_mkt_sent is not null ".$where.
                "UNION 
                select o.id
                from orders o
                inner join orders_payment_date opd on opd.order_id = o.id 
                where opd.data_pagamento_marketplace is null and data_mkt_sent is not null ".$where.
                "UNION 
                select o.id
                from orders o
                left join orders_payment_date opd on opd.order_id = o.id 
                where opd.order_id is null and data_mkt_delivered is not null ".$where.
                "UNION 
                select o.id
                from orders o
                inner join orders_payment_date opd on opd.order_id = o.id 
                where opd.data_pagamento_marketplace is null and data_mkt_delivered is not null ".$where;

        return $this->db->query($sql)->result_array();              


    }

    public function findOrdersWithoutSalesChannel(): ?array
    {

        $sql = "SELECT * FROM orders WHERE sales_channel IS NULL";

        return $this->db->query($sql)->result_array();


    }

    public function findOrdersWithoutOrdersCancelDate($date_start = null): ?array
    {
		$where = '';

		if ($date_start)
		{
			$where = " and date(date_time) >= '".$date_start."' ";
		}

        $sql = "select o.id
                from orders o
                left join orders_payment_date opd on opd.order_id = o.id 
                where opd.order_id is null and date_cancel is not null ".$where.
                "UNION 
                select o.id
                from orders o
                inner join orders_payment_date opd on opd.order_id = o.id 
                where opd.data_cancelamento_marketplace is null and date_cancel is not null ".$where;

        return $this->db->query($sql)->result_array();

    }

    public function runSelectDataPagamentoMarketplaceByOrderId(int $orderId): ?array
    {

        return $this->db->query("SELECT data_pagamento_marketplace($orderId) AS data_pagamento_marketplace FROM orders WHERE id = '$orderId'")->row_array();

    }

    public function runSelectDataCancelamentoMarketplaceByOrderId(int $orderId): ?array
    {

        return $this->db->query("SELECT data_cancelamento_marketplace($orderId) AS data_cancelamento_marketplace FROM orders WHERE id = '$orderId'")->row_array();

    }

    public function getSignatureContractDate($store_id){
        $this->db->select('contract_signatures.store_id AS loja_id, signature_date');
        $this->db->from('contract_signatures');
        $this->db->join('contracts', 'contracts.id = contract_signatures.contract_id');
        $this->db->join('attribute_value', 'attribute_value.id = contracts.document_type');
        $this->db->where('contract_signatures.store_id', $store_id);
        $this->db->like('attribute_value.value', "Contrato de Antecipação");
        $this->db->where('contract_signatures.signature_date IS NOT NULL');
        $query = $this->db->get();
        $signature = $query->row();              
        if(!$signature){
            return null;
        }
        return $signature->signature_date;
    }

    public function getAnticipationTransferOrders($store_id = null, $limit, $date_query)
    {
             
        $joinStore = ""; $whereStore = "";
        // Checa se a loja existe
        if(!is_null($store_id)){
            $this->db->select('id');
            $this->db->from('stores');
            $this->db->where('id', $store_id);
            $query = $this->db->get();
            $store = $query->row();                           
            if(!$store){
                return null;
            }        
            // PEGA A DATA DA ASSIANTURA DO CONTRATO
            $signature_date = $this->getSignatureContractDate($store_id); 
            $orderCreated = date('Y-m-d H:i:s', strtotime($signature_date. ' + 1 days'));  
            $whereStore = " AND date_time > '".$orderCreated."' AND store_id = ".$store_id." ";            
        }else{
            $joinStore = " JOIN contract_signatures ON contract_signatures.store_id = orders.store_id 
            JOIN contracts ON contracts.id = contract_signatures.contract_id 
            JOIN attribute_value ON attribute_value.id = contracts.document_type 
            JOIN stores ON stores.id = orders.store_id"; 
            $whereStore = " AND contract_signatures.signature_date IS NOT NULL 
            AND attribute_value.value LIKE 'Contrato de Antecipação' AND date_time >= DATE(signature_date + INTERVAL 1 DAY) 
            AND stores.flag_antecipacao_repasse LIKE 'S'";            
        }        
                
        $sql = "SELECT orders.store_id, orders.id, orders.numero_marketplace, origin, 
        CASE WHEN orders.freight_seller > 0 THEN 
            CONCAT('R$ ', FORMAT((gross_amount - service_charge) , 2))
        ELSE 
            CONCAT('R$ ', FORMAT((gross_amount - (service_charge + total_ship)) , 2))
        END 
        AS expectativa_repasse
        FROM orders
        LEFT JOIN chamado_marketplace_orders ON chamado_marketplace_orders.id = orders.id
        LEFT JOIN anticipation_transfer ON anticipation_transfer.order_id = orders.id
        $joinStore
        WHERE chamado_marketplace_orders.ativo IS NULL 
        AND orders.paid_status = 6
        AND anticipation_transfer.order_id IS NULL 
        AND data_mkt_delivered IS NOT NULL AND NOW() > DATE(data_mkt_delivered + INTERVAL 8 DAY)
        $whereStore
        $date_query
        $limit";                    
        $query = $this->db->query($sql);        
        $orders = $query->result();                           
        if(!$orders){
            return null;
        }
        
        return $orders;                

    }

    public function checkOrderToAnticipation($order_id = null){

        $errors = [];

        $sql = "SELECT * FROM orders WHERE id = ?";
        $query = $this->db->query($sql, array($order_id));
        $order = $query->row();                           
        if(!$order){
            array_push($errors, 'Pedido não encontrado.');
        }else{
            $signature_date = $this->getSignatureContractDate($order->store_id);
            $signature_date = date('Y-m-d H:i:s', strtotime($signature_date. ' + 8 days')); 
            $sql = "
            SELECT * FROM orders
            LEFT JOIN chamado_marketplace_orders ON chamado_marketplace_orders.id = orders.id            
            WHERE chamado_marketplace_orders.id IS NOT NULL AND ativo = 1 AND orders.id = ?";
            $query = $this->db->query($sql, array($order_id));
            $orders = $query->result();                           
            if($orders){
                array_push($errors, 'Esse pedido encontra-se bloqueado.');
            }

            $sql = "
            SELECT * FROM orders            
            LEFT JOIN anticipation_transfer ON anticipation_transfer.order_id = orders.id
            WHERE anticipation_transfer.order_id IS NOT NULL AND orders.id = ?";
            $query = $this->db->query($sql, array($order_id));
            $order = $query->row();                           
            if($order){
                array_push($errors, 'Esse pedido já foi antecipado em '.date("d/m/Y H:i:s", strtotime($order->data_antecipacao)).'.');
            }
        }

        if(count($errors) > 0){                        
            return ['status' => false, 'errors' => ['order_id' => $order_id, 'errors' => $errors]];
        }else{
            return ['status' => true];
        }

    }

    public function saveOrderAnticipated($pedidos = []){
        $now = date('Y-m-d H:i:s');
        foreach($pedidos as $pedido){   
            
            $sql = "SELECT *, 
            CASE WHEN orders.freight_seller > 0 THEN 
                ROUND((gross_amount - service_charge), 2) 
            else 
                ROUND((gross_amount - (service_charge + total_ship)),2) 
            end as expectativa_repasse
            FROM orders WHERE id = ?";
            $query = $this->db->query($sql, array($pedido));
            $order = $query->row(); 

            $this->db->insert('anticipation_transfer', [
                'order_id' => $pedido,
                'data_antecipacao' => $now,
                'valor_antecipado' => $order->expectativa_repasse
            ]);
            
            $this->db->insert('iugu_repasse', [
                'order_id' => $pedido,
                'data_split' => $now,                
                'numero_marketplace' => $order->numero_marketplace,
                'data_transferencia' => $now,
                'data_repasse_conta_corrente' => date("Y-m-d H:i:s", strtotime($now . "+1 day")),
                'conciliacao_id' => 0,
                'valor_parceiro' => $order->expectativa_repasse,
                'current_installment' => 1,
                'total_installments' => 1,
                'total_paid' => $order->expectativa_repasse
            ]);
        } 
    }

    public function getAnticipationTransferOrderValue($order_id = null){
        $sql = "SELECT * FROM anticipation_transfer WHERE order_id = ?";
        $query = $this->db->query($sql, array($order_id))->row();
        if(!$query) {
            return null;
        }			
        return $query->valor_antecipado;						
    }

    public function checkIfOrderCanBeAnticipated($order_id = null){

        $sql = "SELECT store_id FROM orders WHERE id = ?";
        $query = $this->db->query($sql, array($order_id));
        $order = $query->row();                           
        if(!$order){
            return false;
        }else{

            $sql = "SELECT * FROM stores WHERE id = ? AND flag_antecipacao_repasse LIKE 'S'";
            $query = $this->db->query($sql, array($order->store_id));
            $store = $query->row();                           
            if(!$store){
                return false;
            }

            $signature_date = $this->getSignatureContractDate($order->store_id);
            $signature_date = date('Y-m-d H:i:s', strtotime($signature_date. ' + 8 days')); 
            $sql = "
            SELECT orders.id FROM orders
            LEFT JOIN chamado_marketplace_orders ON chamado_marketplace_orders.id = orders.id            
            WHERE chamado_marketplace_orders.id IS NOT NULL AND ativo = 1 AND orders.id = ?";
            $query = $this->db->query($sql, array($order_id));
            $orders = $query->result();                           
            if($orders){
                return false;
            }

            $sql = "
            SELECT orders.id FROM orders            
            LEFT JOIN anticipation_transfer ON anticipation_transfer.order_id = orders.id
            WHERE anticipation_transfer.order_id IS NOT NULL AND orders.id = ?";
            $query = $this->db->query($sql, array($order_id));
            $order = $query->row();                           
            if($order){
                return false;
            }
        }

        return true;
        
    }

    public function updateOrdersPayment($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('orders_payment', $data);
			return ($update == true) ? $id : false;
		}
	}

    public function getFreightDetails($order_id = null) {
        $sql = "SELECT qs.cost AS qs_cost,
                    qs.retorno AS qs_retorno,
                    qs.service_method AS qs_service,
                    qs.provider AS qs_provider,
                    qs.order_id AS qs_order,
                    os.company_id AS os_company,
                    os.store_id AS os_store,
                    sc.id AS sc_id,
                    sc.name AS sc_name,
                    sc.slc_tipo_cubage AS sc_cubage,
                    sc.cubage_factor AS sc_factor,
                    sc.ad_valorem AS sc_valorem,
                    sc.gris AS sc_gris,
                    sc.toll AS sc_toll,
                    sc.shipping_revenue AS sc_revenue,
                    sc.freight_seller AS sc_freight
                FROM quotes_ship qs
                JOIN orders os ON os.id = qs.order_id
                JOIN shipping_company sc ON os.store_id = sc.store_id AND qs.service_method = os.ship_service_preview
                WHERE os.id = ?";
        $query = $this->db->query($sql, array($order_id))->row();
        if (!$query) {
            return null;
        }

        return json_encode($query);
    }

    public function getOrderByFilter(array $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        /**
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        return $this->db->get('orders')->result_array();
    }

    public function getFetchOrdersWithLabelShippingCompanyData(?int $offset = 0, ?int $limit = 200, array $order_by = array(), string $search_text = null, array $filters = [], bool $return_count = false, $fields_order = array())
    {
        if (!empty($search_text) && strlen($search_text) >= 2) {
            $arr_filter_search_text = array();
            foreach ($fields_order as $field_order) {
                if (!empty($field_order)) {
                    $arr_filter_search_text[$field_order] = $search_text;
                }
            }

            $this->db->group_start();
            $this->db->or_like($arr_filter_search_text);
            $this->db->group_end();
        }

        /**
         *
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         *
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('o.company_id', $this->data['usercomp']);
            } else {
                $this->db->where('o.store_id', $this->data['userstore']);
            }
        }

        $this->db->select('*, s.name as store, o.store_id as store_id, o.freight_accepted_generation')
            ->join('orders o', 'o.id = f.order_id')
            ->join('stores s', 's.id = o.store_id')
            ->join('nfes n', 'n.order_id = o.id')
            ->where('f.sgp !=', '1')
            ->where_in('o.paid_status', [4,5,40,43,51,53,55,58,59]);


        if ($return_count) {
            /* 
            A CONTAGEM DE PEDIDOS NA TELA DE ETIQUETAS NÃO ESTAVA BATENDO COM O NUMERO DE REGISTRO EM TELA
            ALTERANDO O AGRUPAMENTO DA CONSULTA PARA O CODIGO DE RASTREIO, A CONTAGEM BATE NORMALMENTE 
            */
            //$this->db->group_by('o.id');
            $this->db->group_by('f.codigo_rastreio');
        } else {
            $this->db->group_by('f.codigo_rastreio');
        }

        if (!empty($order_by)) {
            $this->db->order_by($order_by[0], $order_by[1]);
        }

        if (!is_null($limit) && !is_null($offset)) {
            $this->db->limit($limit, $offset);
        }

        return $return_count ? $this->db->get('freights f')->num_rows() : $this->db->get('freights f')->result_array();
    }

    public function getFetchOrdersWithoutLabelShippingCompanyData(?int $offset = 0, ?int $limit = 200, array $order_by = array(), string $search_text = null, array $filters = [], bool $return_count = false, $fields_order = array())
    {
        if (!empty($search_text) && strlen($search_text) >= 2) {
            $arr_filter_search_text = array();
            foreach ($fields_order as $field_order) {
                if (!empty($field_order)) {
                    $arr_filter_search_text[$field_order] = $search_text;
                }
            }

            $this->db->group_start();
            $this->db->or_like($arr_filter_search_text);
            $this->db->group_end();
        }

        /**
         *
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         *
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        $this->db->select('o.store_id, o.integration_logistic, s.name as store, o.id as order_id, o.customer_name, o.date_time, o.data_limite_cross_docking, n.nfe_num')
            ->join('stores s', 's.id = o.store_id')
            ->join('nfes n', 'n.order_id = o.id')
            ->where('o.freight_accepted_generation', 0)
            ->where_in('o.paid_status', [41, 50, 80]);

            $this->db->group_start();
            $this->db->where('o.integration_logistic IS NULL', NULL, FALSE);
            $this->db->or_where('o.integration_logistic !=', 'sgpweb');
            $this->db->group_end();

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('o.company_id', $this->data['usercomp']);
            } else {
                $this->db->where('o.store_id', $this->data['userstore']);
            }
        }

        if (!empty($order_by)) {
            $this->db->order_by($order_by[0], $order_by[1]);
        }

        if (!is_null($limit) && !is_null($offset)) {
            $this->db->limit($limit, $offset);
        }

        return $return_count ? $this->db->get('orders o')->num_rows() : $this->db->get('orders o')->result_array();
    }

    public function getOrdersDataByIds(array $id): array
    {
        return $this->db->where_in('id', $id)->get('orders')->result_array();
    }

      /* get the orders data */
    public function getOrdersDataCanbePrint($offset = 0, $id = null, $limit = true)
    {

        if ($id) {
            $sql = "SELECT * FROM orders WHERE order_id_integration  = ? AND paid_status IN (" . implode(', ', $this->PAID_STATUS['can_printing_tag']) . ")";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        if (isset($this->data['ordersfilter'])) {
            $filter = $this->data['ordersfilter'];
        } else {
            $filter = "";
        }

        //$more = ($this->data['usercomp'] == 1) ? "": " WHERE company_id = ".$this->data['usercomp'];
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE o.company_id = " . $this->data['usercomp'] : " WHERE o.store_id = " . $this->data['userstore']);

        if (($more == "") && ($filter != "")) {
            $filter = "WHERE " . substr($filter, 4);
        }
        $limit = $limit ? "LIMIT 200 OFFSET {$offset}" : "";

        $sql = "SELECT o.*, c.cpf_cnpj FROM orders o LEFT JOIN clients c ON o.customer_id =c.id " . $more . $filter . " ORDER BY o.id DESC " . $limit;
        get_instance()->log_data('Orders', 'count', json_encode($sql));
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrdersMultiChannelFulfillmentByPaidStatus(int $paid_status): array
    {
        return $this->db
            ->select('o.*')
            ->join('stores s', 's.id = o.store_id')
            ->where(array(
                's.type_store'  => 2,
                'o.paid_status' => $paid_status
            ))
            ->get('orders o')
            ->result_array();
    }

    public function runSelectDataFechamentoFiscalByOrderId(int $orderId): ?array
    {

        return $this->db->query("SELECT data_fechamento_fiscal($orderId) AS data_fechamento_fiscal FROM orders WHERE id = '$orderId'")->row_array();

    }

    public function getOrdersWithoutPayment(string $int_to = null, int $limit = 200, int $last_id = 0): array
    {
        if (!is_null($int_to)) {
            $this->db->where('origin', $int_to);
        }

        return $this->db->select('o.paid_status, o.numero_marketplace, o.origin, o.id')
            ->join('orders_payment p', 'o.id = p.order_id', 'left')
            ->where('p.id is null', null, false)
            ->where('o.date_time > DATE_SUB(NOW(), INTERVAL 1 MONTH)', null, false)
            ->where('o.id >', $last_id)
            ->order_by('o.id', 'ASC')
            ->limit($limit)
            ->get('orders o')
            ->result_array();
    }

    public function getOrdersWithoutPaymentAndNotCancelledAndComplete(string $int_to = null, int $limit = 200, int $last_id = 0): array
    {
        if (!is_null($int_to)) {
            $this->db->where('origin', $int_to);
        }

        return $this->db->select('o.paid_status, o.numero_marketplace, o.origin, o.id')
            ->join('orders_payment p', 'o.id = p.order_id', 'left')
            ->where('p.id is null', null, false)
            ->where('o.date_time > DATE_SUB(NOW(), INTERVAL 1 MONTH)', null, false)
            ->where(array(
                'o.id >' => $last_id,
                'o.is_incomplete' => false
            ))
            ->where_not_in('o.paid_status', array(
                $this->PAID_STATUS['canceled_by_seller'],
                $this->PAID_STATUS['canceled_before_payment'],
                $this->PAID_STATUS['canceled_after_payment']
            ))
            ->order_by('o.id', 'ASC')
            ->limit($limit)
            ->get('orders o')
            ->result_array();
    }

    //get loja para pegar os campos obrigatorios, 
    public function getAllOrdersMissingFieldsByOrder(array $store_ids, int $last_id = 0) {
        $this->db->select('o.id as order_id, o.store_id, op.nsu, op.gateway_tid, op.autorization_id, op.first_digits, op.last_digits, o.origin, o.numero_marketplace')
            ->from('orders_payment op')
            ->join('orders o', 'o.id = op.order_id', 'left')
            ->where_in('o.store_id', $store_ids)
            ->where('DATE(o.date_time) >=', '2025-04-15') // apenas pedidos de hoje(dia da implementação) em diante
            ->where('o.order_id_integration IS NULL', null, false) // apenas pedidos que ainda não foram integrados, ve o id certo
            ->where('o.id >', $last_id);

        return $this->db->get()->result_array();
    }
    public function getPaymentMethodByOrderId(int $orderId): ?string {
        return $this->db->select('forma_id')
                        ->from('orders_payment')
                        ->where('order_id', $orderId)
                        ->get()
                        ->row('forma_id');
    }
    /**
     * Retorna apenas os campos informados de orders_payment de um pedido
     */
    public function getFieldsFromOrdersPayment(int $order_id, array $campos): array
    {
        if (empty($campos)) {
            return [];
        }
    
        // Mapeamento dos campos que possuem nomes diferentes no banco
        $mapeamento = [
            'tid' => 'gateway_tid',
            'authorization_id' => 'autorization_id'
        ];
    
        $select = [];
    
        foreach ($campos as $campo) {
            if (isset($mapeamento[$campo])) {
                // Faz o alias para que o retorno continue com o nome esperado no código
                $select[] = "{$mapeamento[$campo]} AS {$campo}";
            } else {
                $select[] = $campo;
            }
        }
    
        $this->db->select(implode(', ', $select));
        $this->db->where('order_id', $order_id);
    
        $result = $this->db->get('orders_payment')->row_array();
    
        return $result ?? [];
    }


    public function getOrdersPaidAndCancelBetweenDates(string $start_date, string $end_date, int $limit = 0, int $offset = 0): array
    {
        return $this->db
            ->select('id')
            ->where_in('paid_status', $this->PAID_STATUS['orders_cancel_after_paid'])
            ->where("date_cancel between '$start_date' and '$end_date'", null, false)
            ->where('data_pago IS NOT NULL', null, false)
            ->limit($limit, $offset)
            ->order_by('id', 'ASC')
            ->get('orders')
            ->result_array();
    }

    public function findOrdersWithoutOrdersPaymentDateFechamentoFiscal($date_start = null): ?array
    {
		$where = '';

		if ($date_start)
		{
			$where = " and date(date_time) >= '".$date_start."' ";
		}

        $sql = "select o.id
                from orders o
                left join orders_payment_date opd on opd.order_id = o.id 
                where opd.order_id is null and data_pago is not null ".$where.
                "UNION 
                select o.id
                from orders o
                inner join orders_payment_date opd on opd.order_id = o.id 
                where opd.data_fechamento_fiscal is null and data_pago is not null ".$where;

        return $this->db->query($sql)->result_array();



    }

    public function getOrdersItemDataByOrderIdProductId($order_id = null, $product_id = null){

        if($order_id == null || $product_id == null){
            return false;
        }

        $where = " and order_id = ".$order_id." and product_id = ".$product_id;

        $sql = "select * from orders_item oi where 1=1 ".$where;

        return $this->db->query($sql)->row_array(); 


    }

    public function findOrdersWithoutOrdersCancelDateFiscal($date_start = null): ?array
    {
		$where = '';

		if ($date_start)
		{
			$where = " and date(date_time) >= '".$date_start."' ";
		}

        $sql = "select o.id
                from orders o
                left join orders_payment_date opd on opd.order_id = o.id 
                where opd.order_id is null and date_cancel is not null ".$where.
                "UNION 
                select o.id
                from orders o
                inner join orders_payment_date opd on opd.order_id = o.id 
                where opd.data_fechamento_fiscal_cancelamento is null and date_cancel is not null ".$where;

        return $this->db->query($sql)->result_array();

    }

    public function runSelectDataFechamentoFiscalCancelamentoByOrderId(int $orderId): ?array
    {

        return $this->db->query("SELECT data_fechamento_fiscal_cancelamento($orderId) AS data_fechamento_fiscal_cancelamento FROM orders WHERE id = '$orderId'")->row_array();

    }

    public function deletePaymentDate(int $orderId): bool
    {
        return (bool)$this->db->delete('orders_payment_date', ['order_id' => $orderId]);
    }

    public function getOrdersByOriginAndBillNo(string $origin, string $bill_no): array
    {
        return $this->db->get_where('orders', array(
            'origin' => $origin,
            'bill_no' => $bill_no
        ))->result_array();
    }

    public function getOrderPastStatusesByOriginAndBillNo(string $origin, string $bill_no, array $paid_status): ?array
    {
        return $this->db
            ->select('o.*')
            ->join('order_status os', 'o.id = os.order_id')
            ->where('o.bill_no', $bill_no)
            ->where('o.origin', $origin)
            ->where_in('os.status', $paid_status)
            ->get('orders o')
            ->row_array();
    }

    public function insertOrderCancellationTuna($data = null)
    {    
        $order_id = $this->db->insert('orders_cancellation_tuna', array(
            'order_id'   => $data['order_id'],
            'sku'           => $data['sku'],
            'status'        => 0,
        ));

        return ($order_id) ? $order_id : false;
    }

    public function getOrderCancellationTuna($id){
        $sql = "SELECT * FROM orders_cancellation_tuna WHERE order_id = ? GROUP BY sku";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function updateOrderCancellationTuna($id): bool{
        $sql = "UPDATE orders_cancellation_tuna SET `status` = 1 WHERE id = ?";
        $result = $this->db->query($sql, array($id));
        return ($result == true) ? true : false;
    }

    public function setReverseDiscountOnOrder(int $orderId): boolean
    {
        $this->load->model('model_settings');
        $sellerCenter = $this->model_settings->getValueIfAtiveByName('sellercenter');

        //Busca as informações de campanha e pricetag
        $sqlCampanha = "select distinct cvo.* from campaign_v2_orders cvo where cvo.order_id = ?";
        $query = $this->db->query($sqlCampanha, array($orderId));
        $campanhas =  $query->row_array();

        if(!$campanhas){
            return true;
        }

        if ($sellerCenter === 'somaplace') {
            $sql = "UPDATE orders SET net_amount = round(gross_amount-?,2) WHERE id = ?";
            $this->db->query($sql, array($campanhas['total_pricetags'], $orderId));
        }

        $sql = "UPDATE orders SET discount = ? WHERE id = ?";
        $this->db->query($sql, array($campanhas['total_pricetags'], $orderId));

        // Agora busca todas as informações de itens em campanha e desfaz os descontos para mantar o preço original do desconto (vindo do pricetag)
        $sqlCampanhaItem = "select distinct cvoi.campaign_v2_id, cvoi.item_id, cvoi.total_discount
                            from campaign_v2_orders_items cvoi
                            inner join orders_item oi on oi.id = cvoi.item_id 
                            where oi.order_id = ?";
        $query = $this->db->query($sql, array($orderId));
        $campanhasItens =  $query->result_array();

        if(!$campanhasItens){
            return true;
        }

        foreach($campanhasItens as $campanhasItem){
            $sql = "UPDATE orders_item SET discount = round(discount-?,2) WHERE id = ?";

            if(!$this->db->query($sql, array($campanhasItem['total_discount'], $campanhasItem['item_id']))){
                return false;
            }

        }

        return true;


    }

    public function updateShippingData($order_id, $updateData) {
        $this->db->where('id', $order_id);
        return $this->db->update('orders', $updateData);
    }
    public function updateOrderStatus($order_id, $status) {
        $updateData = [
            'paid_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $order_id);
        return $this->db->update('orders', $updateData);
    }
   public function createInvoice($invoiceData, array $items = []) {
        $insert = $this->db->insert('orders_invoices', $invoiceData);
        if (!$insert) {
            return false;
        }

        $invoiceId = $this->db->insert_id();

        if ($invoiceId && !empty($items)) {
            foreach ($items as $item) {
                if (!isset($item['sku'])) {
                    continue;
                }

                $orderItem = $this->db->select('id, qty')
                    ->from('orders_item')
                    ->where('order_id', $invoiceData['order_id'])
                    ->where('sku', $item['sku'])
                    ->get()
                    ->row_array();

                if (!$orderItem) {
                    continue;
                }

                $qty = $item['quantity'] ?? $orderItem['qty'];

                $this->db->insert('orders_invoice_items', [
                    'invoice_id'    => $invoiceId,
                    'order_item_id' => $orderItem['id'],
                    'qty_invoiced'  => $qty,
                ]);
            }
        }

        return $invoiceId ?: false;
    }

    public function getInvoicedQuantitiesByOrder(int $order_id): array {
        $result = $this->db->select('orders_item.sku, SUM(orders_invoice_items.qty_invoiced) as qty')
            ->from('orders_invoice_items')
            ->join('orders_item', 'orders_item.id = orders_invoice_items.order_item_id')
            ->where('orders_item.order_id', $order_id)
            ->group_by('orders_item.sku')
            ->get()
            ->result_array();

        $map = [];
        foreach ($result as $row) {
            $map[$row['sku']] = (int)$row['qty'];
        }
        return $map;
    }



    public function getInvoiceItems(int $invoiceId): array {
        return $this->db->select('orders_item.sku, orders_invoice_items.qty_invoiced as qty')
            ->from('orders_invoice_items')
            ->join('orders_item', 'orders_item.id = orders_invoice_items.order_item_id')
            ->where('orders_invoice_items.invoice_id', $invoiceId)
            ->get()
            ->result_array();
    }

    public function getInvoicesByOrderId(int $orderId): array {
        $invoices = $this->db->where('order_id', $orderId)
            ->get('orders_invoices')
            ->result_array();

        foreach ($invoices as &$invoice) {
            $invoice['items'] = $this->getInvoiceItems($invoice['id']);
        }

        return $invoices;

    }

    public function createInvoiceItem($data) {
        return $this->db->insert('orders_invoice_items', $data);
    }

    public function getInvoiceItem($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('orders_invoice_items');
        return $query->row_array();
    }

    public function getInvoiceItemsByInvoice($invoice_id) {
        $this->db->where('invoice_id', $invoice_id);
        $query = $this->db->get('orders_invoice_items');
        return $query->result_array();
    }

    public function updateInvoiceItem($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('orders_invoice_items', $data);
    }

    public function deleteInvoiceItem($id) {
        $this->db->where('id', $id);
        return $this->db->delete('orders_invoice_items');
    }

    public function getOrderByBillNo($bill_no) {
        $this->db->where('bill_no', $bill_no);
        $query = $this->db->get('orders');
        return $query->row_array();
    }

    public function getOrdersByMultisellerNumber($order_mkt_multiseller) {
        $this->db->where('order_mkt_multiseller', $order_mkt_multiseller);
        $query = $this->db->get('orders');
        return $query->result_array();
    }


}

class OrderStatusConst {

    const WAITING_PAYMENT = 1; // Aguardando Pagamento (Não foi pago ainda)
    const PROCESSING_PAYMENT = 2; // Processando Pagamento
    const WAITING_INVOICE = 3; // Aguardando Faturamento (Pedido foi pago, está aguardando ser faturado)
    const WAITING_SHIPPING = 4; // Aguardando Coleta/Envio (Aguardando o seller enviar ou transportadora coletar)
    const SHIPPED_IN_TRANSPORT = 5; // Em Transporte (Pedido já foi enviado ao cliente)
    const DELIVERED = 6; // Entregue (Pedido entregue ao cliente)
    const WAITING_TRACKING = 40; // Aguardando Rastreio (Pedido faturado, aguardando envio de rastreio)
    const WITH_TRACKING_WAITING_SHIPPING = 43; // Aguardando Coleta/Envio (Pedido com rastreio, aguardando ser coletado/enviado)
    const SHIPPED_IN_TRANSPORT_45 = 45; // Em Transporte (Pedido já foi enviado ao cliente)
    const INVOICED_WAITING_TRACKING = 50; // Aguardando Seller Emitir Etiqueta (Pedido faturado, contratando frete)
    const PLP_SEND_TRACKING_MKTPLACE = 51; // PLP gerada (Enviar rastreio para o marketplace)
    const INVOICED_SEND_INVOICE_MKTPLACE = 52; // Pedido faturado (Enviar NF-e para o marketplace)
    const WAITING_SHIPPING_TO_TRACKING = 53; // Aguardando Coleta/Envio (Aguardando pedido ser postado/coletado para rastrear)
    const SHIPPED_IN_TRANSPORT_NOTIFY_MKTPLACE = 55; // Em Transporte (Avisar o marketplace que o pedido foi enviado)
    const PROCESSING_INVOICE = 56; // Processando Nota Fiscal (Processando NF aguardando envio (módulo faturador))
    const INVOICE_WITH_ERROR = 57; // Nota Fiscal Com Erro (Problema para faturar o pedido (módulo faturador))
    const WAITING_WITHDRAWAL = 58; // Aguardando Retirada (Pedido aguardando retirada em alguma agência)
    const DEVOLUTION_IN_TRANSPORT = 59; // Em Transporte (Pedido com extravio/devolução ao rementente)
    const MISPLACEMENT_IN_TRANSPORT = 59; // Em Transporte (Pedido com extravio/devolução ao rementente)
    const DELIVERED_NOTIFY_MKTPLACE = 60; // Entregue (Avisar ao marketplace que foi entregue)
    const PARTIALLY_INVOICED = 62; // Faturado Parcialmente
    const ERROR_FREIGHT_CONTRACTING = 80; // Problema na contratação do frete
    const CANCELLATION_REQUESTED = 90; // Solicitado Cancelamento
    const CANCELED_BY_SELLER = 95; // Cancelado Pelo Seller (Cancelado pelo vendedor)
    const CANCELED_BEFORE_PAYMENT = 96; // Cancelado (Cancelado antes de realizar o pagamento)
    const CANCELED_AFTER_PAYMENT = 97; // Cancelado (Cancelado após o pagamento)
    const CANCEL_AT_CARRIER = 98; // Cancelar na Transportadora (Cancelar rastreio na transportadora (não correios))
    const CANCEL_AT_MKTPLACE = 99; // Cancelar no Marketplace (Avisar o cancelamento para o marketplace)
    const WITHOUT_FREIGHT_QUOTE = 101; // Sem Cotação de Frete (Deve fazer a contratação do frete manual (não correios))

    public static function statusDescription(int $code): string
    {
        switch ($code) {
            case self::WAITING_PAYMENT:
                return "Aguardando Pagamento (Não foi pago ainda)";
            case self::PROCESSING_PAYMENT:
                return "Processando Pagamento";
            case self::WAITING_INVOICE:
                return "Aguardando Faturamento (Pedido foi pago, está aguardando ser faturado)";
            case self::WAITING_SHIPPING:
                return "Aguardando Coleta/Envio (Aguardando o seller enviar ou transportadora coletar)";
            case self::SHIPPED_IN_TRANSPORT:
                return "Em Transporte (Pedido já foi enviado ao cliente)";
            case self::DELIVERED:
                return "Entregue (Pedido entregue ao cliente)";
            case self::WAITING_TRACKING:
                return "Aguardando Rastreio (Pedido faturado, aguardando envio de rastreio)";
            case self::WITH_TRACKING_WAITING_SHIPPING:
                return "Aguardando Coleta/Envio (Pedido com rastreio, aguardando ser coletado/enviado)";
            case self::SHIPPED_IN_TRANSPORT_45:
                return "Em Transporte (Pedido já foi enviado ao cliente)";
            case self::INVOICED_WAITING_TRACKING:
                return "Aguardando Seller Emitir Etiqueta (Pedido faturado, contratando frete)";
            case self::PLP_SEND_TRACKING_MKTPLACE:
                return "PLP gerada (Enviar rastreio para o marketplace)";
            case self::INVOICED_SEND_INVOICE_MKTPLACE:
                return "Pedido faturado (Enviar NF-e para o marketplace)";
            case self::WAITING_SHIPPING_TO_TRACKING:
                return "Aguardando Coleta/Envio (Aguardando pedido ser postado/coletado para rastrear)";
            case self::SHIPPED_IN_TRANSPORT_NOTIFY_MKTPLACE:
                return "Em Transporte (Avisar o marketplace que o pedido foi enviado)";
            case self::PROCESSING_INVOICE:
                return "Processando Nota Fiscal (Processando NF aguardando envio (módulo faturador))";
            case self::INVOICE_WITH_ERROR:
                return "Nota Fiscal Com Erro (Problema para faturar o pedido (módulo faturador))";
            case self::WAITING_WITHDRAWAL:
                return "Aguardando Retirada (Pedido aguardando retirada em alguma agência)";
            case self::DEVOLUTION_IN_TRANSPORT:
                return "Em Transporte (Pedido com extravio/devolução ao rementente)";
            case self::MISPLACEMENT_IN_TRANSPORT:
                return "Em Transporte (Pedido com extravio/devolução ao rementente)";
            case self::DELIVERED_NOTIFY_MKTPLACE:
                return "Entregue (Avisar ao marketplace que foi entregue)";
            case self::PARTIALLY_INVOICED:
                return "Faturado Parcialmente";
            case self::ERROR_FREIGHT_CONTRACTING:
                return "Problema na contratação do frete";
            case self::CANCELLATION_REQUESTED:
                return "Solicitado Cancelamento";
            case self::CANCELED_BY_SELLER:
                return "Cancelado Pelo Seller (Cancelado pelo vendedor)";
            case self::CANCELED_BEFORE_PAYMENT:
                return "Cancelado (Cancelado antes de realizar o pagamento)";
            case self::CANCELED_AFTER_PAYMENT:
                return "Cancelado (Cancelado após o pagamento)";
            case self::CANCEL_AT_CARRIER:
                return "Cancelar na Transportadora (Cancelar rastreio na transportadora (não correios))";
            case self::CANCEL_AT_MKTPLACE:
                return "Cancelar no Marketplace (Avisar o cancelamento para o marketplace)";
            case self::WITHOUT_FREIGHT_QUOTE:
                return "Sem Cotação de Frete (Deve fazer a contratação do frete manual (não correios))";
            default:
                return "Situação com código {$code} não mapeada";
        }
    }

}

// Antes de adicionar uma função ao final desta classe, verifique se estará dentro da chave de model_orders
// A chave acima é referente a classe OrderStatusConst, a model_orders é mais acima ! ! ! ! !
