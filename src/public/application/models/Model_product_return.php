<?php
/*

Model de Acesso ao BD para tabela de devolução de produtos

 */

class Model_product_return extends CI_Model
{

    /**
     * @var string $TO_HIRE A contratar
     */
    public const TO_HIRE = 'a_contratar';

    /**
     * @var string $COLLECTED Coletado
     */
    public const COLLECTED = 'coletado';

    /**
     * @var string $CANCELED Concelado
     */
    public const CANCELED = 'cancelado';

    /**
     * @var string $REFUNDED Devolvido
     */
    public const REFUNDED = 'devolvido';

    public const REFUNDED_PARCIAL = 'devolvido parcialmente';

    const TABLE = 'product_return';
    public function __construct()
    {
        parent::__construct();
    }
    public function create($data)
    {
        $insert = $this->db->insert(self::TABLE, $data);
        
		return $insert ? $this->db->insert_id() : false;
    }
    
    public function getOrderIdAndStatus($orderID, $skuMarketplace, $status ){
        return $this->db->from(self::TABLE)->where(array(
            'order_id'          => $orderID, 
            'status'            => $skuMarketplace, 
            'sku_marketplace'   => $status)
            )->get()->result_array();
    }

    public function excelList()
    {
        $sql = "SELECT 
                    product_return.*, 
                    orders_item.product_id, 
                    orders.numero_marketplace, 
                    (SELECT stores.name FROM stores WHERE stores.id = orders.store_id) AS store_name 
                FROM product_return 
                LEFT JOIN orders ON orders.id = product_return.order_id 
                LEFT JOIN orders_item ON orders_item.sku = product_return.sku_marketplace AND orders_item.order_id = product_return.order_id 
                ORDER BY product_return.id ASC";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function fetchReturnedProducts(string $where = null, int $limit = null, int $offset = null)
    {
        $limitClause = '';
        if (!empty($limit)) {
            $limitClause = "LIMIT $limit";
            if (!empty($offset) || $offset === 0) {
                $limitClause .= " OFFSET $offset";
            }
        }

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? "orders.company_id = {$this->data['usercomp']}" : "orders.store_id = {$this->data['userstore']}");

        if (!empty($where)) {
            //$where = "WHERE $where";
            if (!empty($more)) {
                $where .= " AND $more";
            }
        } elseif (!empty($more)) {
            $where = "WHERE $more";
        }

        $sql = "SELECT
                product_return.id,
                product_return.order_id,
                product_return.devolution_invoice_number,
                product_return.return_invoice_file,
                product_return.return_total_value,
                product_return.logistic_operator_type,
                product_return.status,
                product_return.sku_marketplace,
                product_return.product_id,
                orders.numero_marketplace,
                orders.product_return_status,
                (SELECT stores.name FROM stores WHERE stores.id = orders.store_id) AS store_name
            FROM product_return
            JOIN orders ON orders.id = product_return.order_id
            $where
            ORDER BY product_return.id ASC
            $limitClause";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    // Retorna o número de registros da tabela.
    public function getTotalReturnedProducts(string $where = "") {
        $sql = "SELECT count(*) AS qtd 
                FROM product_return 
                LEFT JOIN orders ON orders.id = product_return.order_id 
                $where";

        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
    }

    // Retorna o URI completo, incluindo a informação sobre o protocolo usado (se é HTTP ou HTTPS).
    public function getURIPath()
    {
        $filename_return = "http://";
        if (isset($_SERVER['HTTPS'])) {
            $filename_return = 'https://';
        }

        if (strpos(strtolower($_SERVER['PHP_SELF']), 'fase1')) {
            $filename_return .= 'localhost/fase1';
        } else {
            $filename_return .= $_SERVER['SERVER_NAME'] . '/app';
        }

        return $filename_return;
    }

    public function getReturnedOrderInformation($id)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND pd.company_id = {$this->data['usercomp']}" : " AND pd.store_id = {$this->data['userstore']}");

        $sql = "SELECT 
                    pr.*, 
                    pd.name, 
                    pd.principal_image, 
                    pd.price 
                FROM product_return pr 
                JOIN products pd ON pd.id = pr.product_id 
                WHERE pr.id = $id AND pr.order_id = pr.order_id $more";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getOrderInformation($order_id)
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND o.company_id = {$this->data['usercomp']}" : " AND o.store_id = {$this->data['userstore']}");

        $sql = "SELECT 
                    o.id, 
                    o.bill_no, 
                    s.name, 
                    n.nfe_num,
                    n.chave
                FROM orders o 
                JOIN stores s ON s.id = o.store_id 
                JOIN nfes n ON n.order_id = o.id 
                WHERE o.id = '$order_id' $more";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProvidersList()
    {
        $sql = "SELECT 
                    p.id, 
                    p.name 
                FROM shipping_company p 
                ORDER BY p.name ASC";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function newReturnedProduct(array $product)
    {
        $insert_data = array();
        if ($product['oplogistico'] != "") {
            $logistic_operator_type = $product['oplogistico'];
        }

        if ($product['shipping_co'] != "") {
            $logistic_operator_name = $product['shipping_co'];
        }

        if ($product['return_date'] != "") {
            $devolution_request_date = $product['return_date'];
        }

        if ($product['return_nfe_number'] != "") {
            $devolution_invoice_number = $product['return_nfe_number'];
        }

        if ($product['return_nfe_emission_date'] != "") {
            $devolution_emission_date = dateTimeBrazilToDateInternational($product['return_nfe_emission_date']);
        }

        $return_shipping_value = 0;
        if ($product['return_price'] != "") {
            $return_shipping_value = $product['return_price'];
        }

        if ($product['return_reason'] != "") {
            $motive = $product['return_reason'];
        }

        if ($product['ship_service_preview'] != "") {
            $reverse_logistic_code = $product['ship_service_preview'];
        }

        $return_invoice_file = "";
        if ($product['upload_nfe'] != "") {
            $return_invoice_file = $product['upload_nfe'];
        }

        $correction_letter = "";
        if ($product['upload_letter'] != "") {
            $correction_letter = $product['upload_letter'];
        }

        $return_action = "create";
        if ($product['return_action'] != "") {
            $return_action = $product['return_action'];
        }

        $complete_order = 0;
        if ($product['complete_order'] != "") {
            $complete_order = $product['complete_order'];
        }

        $inserted_rows = "";
        for ($index = 0; $index < count($product['products_return']); $index++) {
            $order_id = $product['products_return'][$index]["order_id"];
            $store_id = $product['products_return'][$index]["store_id"];
            $quantity_in_order = $product['products_return'][$index]["order_quantity"];

            $product_id = $product['products_return'][$index]["product_id"];
            $variant = isset($product['products_return'][$index]["variant"]) ? $product['products_return'][$index]["variant"]  : null;
            $product_quantity = $product['products_return'][$index]["product_quantity"];
            $price = $product['products_return'][$index]["price"];
            $sku_marketplace = $product['products_return'][$index]["sku"];

            if ($variant === '') {
                $variant = null;
            }

            $return_total_value = $product_quantity * $price;

            if ($return_action == 'create') {
                $this->db->insert('product_return', array(
                    'order_id' => $order_id,
                    'logistic_operator_type' => $logistic_operator_type ?? '',
                    'logistic_operator_name' => $logistic_operator_name ?? '',
                    'status' => 'a_contratar',
                    'reverse_logistic_code' => $reverse_logistic_code ?? '',
                    'devolution_invoice_number' => $devolution_invoice_number ?? '',
                    'return_nfe_emission_date' => $devolution_emission_date ?? '',
                    'return_total_value' => $return_total_value,
                    'devolution_request_date' => $devolution_request_date ?? '',
                    'return_shipping_value' => $return_shipping_value,
                    'sku_marketplace' => $sku_marketplace,
                    'quantity_requested' => $product_quantity,
                    'quantity_in_order' => $quantity_in_order,
                    'motive' => $motive ?? '',
                    'store_id' => $store_id,
                    'return_invoice_file' => $return_invoice_file,
                    'correction_letter' => $correction_letter,
                    'product_id' => $product_id,
                    'variant' => $variant
                ));

                $return_status = 20;
                if ($complete_order == 1) {
                    $return_status = 30;
                }

                $this->db->where('id', $order_id)->update('orders', ['product_return_status' => $return_status]);

                $return_status = $this->db->select('id')->where('order_id', $order_id)->order_by('id', 'DESC')->limit('')->get('product_return')->result_array();
                $product_return_id = $return_status[0]['id'];

                $date_time = date("Y-m-d H:i:s");
                $this->db->insert("product_return_history", array(
                    'order_id' => $order_id,
                    'product_return_id' => $product_return_id,
                    'date_log' => $date_time,
                    'action' => 'a_contratar'
                ));
            } else if ($return_action == 'edit') {
                $sql = "UPDATE product_return 
                        SET 
                            devolution_invoice_number = '$devolution_invoice_number', 
                            logistic_operator_type = '$logistic_operator_type', 
                            logistic_operator_name = '$logistic_operator_name', 
                            reverse_logistic_code = '$reverse_logistic_code', 
                            devolution_request_date = '$devolution_request_date', 
                            return_shipping_value = '$return_shipping_value', 
                            motive = '$motive'";

                if (!empty($return_invoice_file)) {
                    $sql .= ", return_invoice_file = '$return_invoice_file'";
                }

                if (!empty($correction_letter)) {
                    $sql .= ", correction_letter = '$correction_letter'";
                }

                $sql .= " WHERE order_id = $order_id";
                $update = $this->db->query($sql);
            }
            $inserted_rows++;
        }

        return $inserted_rows;
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE product_return 
                SET status = '$status' 
                WHERE id = $id";
        $update = $this->db->query($sql);

        $sql = "SELECT pr.id, pr.order_id, 
                (SELECT os.product_return_status FROM orders os WHERE os.id = pr.order_id) AS return_status
                FROM product_return pr
                WHERE pr.id = $id";
        $query = $this->db->query($sql);

        $return_status = $query->result_array();
        $product_id = $return_status[0]['id'];
        $order_id = $return_status[0]['order_id'];
        $return_status = (string) $return_status[0]['return_status'];
        $return_status = substr($return_status, 0, 1);

        /*
        - 0: Não está em devolução;
        - 2: Pedido parcial devolvido;
        - 3: Pedido total devolvido;
        - 20: A contratar devolução parcial do pedido;
        - 21: Coletada devolução parcial do pedido;
        - 22: Cancelada devolução parcial do pedido;
        - 30: A contratar devolução total do pedido;
        - 31: Coletada devolução total do pedido;
        - 32: Cancelada devolução total do pedido;
        */

        $status_code = 0;
        if (($status == 'a_contratar') && ($return_status == "2")) {
            $status_code = 20;
        } else if (($status == 'coletado') && ($return_status == "2")) {
            $status_code = 21;
        } else if (($status == 'cancelado') && ($return_status == "2")) {
            $status_code = 22;
        } else if (($status == 'devolvido') && ($return_status == "2")) {
            $status_code = 2;
        } else if (($status == 'a_contratar') && ($return_status == "3")) {
            $status_code = 30;
        } else if (($status == 'coletado') && ($return_status == "3")) {
            $status_code = 31;
        } else if (($status == 'cancelado') && ($return_status == "3")) {
            $status_code = 32;
        } else if (($status == 'devolvido') && ($return_status == "3")) {
            $status_code = 3;
        }

        $sql = "UPDATE orders 
                SET product_return_status = '$status_code' 
                WHERE id = $order_id";
        $update = $this->db->query($sql);

        $date_time = date("Y-m-d H:i:s");
        $sql = "INSERT INTO product_return_history (
                    order_id, 
                    product_return_id, 
                    date_log, 
                    action
                )
                VALUES (
                    '$order_id', 
                    '$product_id', 
                    '$date_time',
                    '$status'
                )";

        $query = $this->db->query($sql);
    }

    public function statusReturnedOrder($order_id)
    {
        $sql = "SELECT status 
                FROM product_return 
                WHERE order_id = '$order_id'";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getActiveChargeBackRule($marketPlace){
        $sql = "SELECT * FROM settings_return_chargeback_rules WHERE marketplace_int_to = ? AND active = ?";
        return $this->db->query($sql, array($marketPlace, 1))->row();
    }

    public function getProductsToReturn(){
        $sql = "SELECT *, o.freight_seller AS tipo_frete, pr.id AS pr_id, total_ship, net_amount, o.id AS orderId, o.data_mkt_delivered AS data_de_entrega FROM product_return pr
        JOIN orders o ON o.id = pr.order_id        
        JOIN stores s ON s.id = pr.store_id
        WHERE NOT EXISTS(
            SELECT * FROM orders_item oi 
            JOIN campaign_v2_orders_items coi ON coi.item_id = oi.id 
            WHERE oi.product_id = pr.product_id AND oi.order_id = pr.order_id
        ) 
        AND status = ? AND refunded_check = ? AND o.id = ?";
        return $this->db->query($sql, array('devolvido', 0, 799))->result();
    }

    public function isAfterPaymentCicle($orderId, $product_return_id, $data_entrega)
    {

        $sql = "SELECT * FROM product_return_history prh
        WHERE prh.action LIKE ? AND prh.product_return_id = ?";
        $devolucao = $this->db->query($sql, array('devolvido', $product_return_id))->row();
        $data_devolucao = $devolucao->date_log;

        $dia_devolucao = date("d", strtotime($data_devolucao));
        $dia_entrega = date("d", strtotime($data_entrega));

        //echo "Dia entrega ".$data_entrega."\n";
        //echo "Dia devolução ".$data_devolucao."\n";

        $sql = "SELECT * FROM param_mkt_ciclo pmc";
        $ciclos = $this->db->query($sql)->result();
        foreach ($ciclos as $ciclo) {

            $inicio = $ciclo->data_inicio;
            $fim = $ciclo->data_fim;
            //echo "Inicio ciclo: ".$inicio."\n";
            //echo "Fim ciclo: ".$fim."\n";

            $intervalo_ciclo = [];

            if ($inicio > $fim) {
                for ($i = $inicio; $i < 32; $i++)
                    array_push($intervalo_ciclo, (int)$i);
                for ($i = 1; $i < $fim + 1; $i++)
                    array_push($intervalo_ciclo, (int)$i);
            } else {
                for ($i = $inicio; $i < $fim + 1; $i++)
                    array_push($intervalo_ciclo, (int)$i);
            }
            //var_dump($intervalo_ciclo);
            if (in_array($dia_devolucao, $intervalo_ciclo) && in_array($dia_entrega, $intervalo_ciclo)) {
                return true;
            }

        }
        return false;

    }

    public function updateCheckedProduct($product_id){
        $this->db->where('id', $product_id)
            ->update('product_return', ['refunded_check' => 1]);
    }

    /*
     * Esse método verifica se a quantidade de produtos de um pedido registrado na tabela product_return
     * confere com a quantidade de produtos registrado em orders_item.
     * */
    public function checkQtdProductInProductReturn($qtd_in_order, $order_id){
        $quantidade = $this->getQtdProductsInOrder($order_id);
        return $quantidade == $qtd_in_order;
    }

    public function getQtdProductsInOrder($order_id){
        $sql = "SELECT SUM(qty) AS total FROM orders_item oi WHERE oi.order_id = ?";
        $qtd = $this->db->query($sql, array($order_id))->row();
        return $qtd->total;
    }

    public function getById(int $id)
    {
        return $this->db->where('id', $id)->get('product_return')->row_array();
    }

    public function getByOrderId(int $order_id)
    {
        return $this->db->where('order_id', $order_id)->get('product_return')->result_array();
    }

    public function updateById(int $id, array $data): bool
    {
        return (bool)$this->db->where('id', $id)->update('product_return', $data);
    }

    public function getByOrderAndProductAndVariant(int $order_id, int $product_id, ?int $variant = null): ?array
    {
        $this->db->where(array(
            'order_id' => $order_id,
            'product_id' => $product_id
        ));

        if (!is_null($variant)) {
            $this->db->where('variant', $variant);
        }

        return $this->db->get('product_return')->row_array();
    }

    public function updateByOrderId(int $order_id, array $data): bool
    {
        return $this->db->where('order_id', $order_id)->update('product_return', $data);
    }

    public function updateByOrderIdAndSkuMkt(int $order_id, string $skumkt, array $data): bool
    {
        return $this->db->where(array(
            'order_id' => $order_id,
            'sku_marketplace' => $skumkt
        ))->update('product_return', $data);
    }

    public function getOrderId($orderID, $skuMarketplace ){
        return $this->db->from(self::TABLE)->where(array(
            'order_id'          => $orderID,
            'sku_marketplace'    => $skuMarketplace)
            )->get()->row_array();
    }


}
