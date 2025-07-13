<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_orders_payment extends CI_Model
{
    const TABLE = 'orders_payment';
    public function __construct()
    {
        parent::__construct();
    }

    public function getByOrderId($orderId){
        // dd($orderId);
        return $this->db->select('*')->from(Model_orders_payment::TABLE)->where(['order_id'=>$orderId])->get()->result_array();
    }

    public function getOrderPaymentByIdAndTransactionStatus(int $id, string $status)
    {
        return $this->db->where(['id' => $id, 'transaction_status' => $status])->get(Model_orders_payment::TABLE)->row_array();
    }

    public function update(array $data, int $id): bool
    {
        return (bool) $this->db->where('id', $id)->update(Model_orders_payment::TABLE, $data);
    }

    public function findMaxParcelFromOrder(int $orderId): array
    {

        $query = $this->db->select('*')->from(Model_orders_payment::TABLE)->where(['order_id'=>$orderId])->order_by('parcela', 'DESC')->get();
        $query->row_array();
        return $query->num_rows() > 0 ? $query->row_array() : [];

    }


    public function remove(int $orderId): bool
    {
        return (bool)$this->db->where('order_id', $orderId)->delete(Model_orders_payment::TABLE);
    }


    //FIN-722
    public function getDefaultMDR($order_id): float
    {
        $sql = "select
                            op.forma_id AS payment_type,
                            op.forma_desc AS payment_method,	
                            /*CASE WHEN op.parcela > 4  THEN 4
                            ELSE op.parcela
                            END AS*/ 
                            op.parcela as parcels,
                            o.gross_amount as valor_pedido
                        from
                            orders_payment op
                        inner join orders o on o.id = op.order_id
                        where
                            op.order_id = ".$order_id;
        $query = $this->db->query($sql);
        $payment_data = $query->row_array();

        $sql = "
                select 
                    mdr ,
                    type_operation,
                    type_value
                from 
                    creditcard_payment_mdr 
                where 
                    payment_type = '".$payment_data['payment_type']."'
                and 
                    payment_method = '".$payment_data['payment_method']."'
                and
                    parcels = ".$payment_data['parcels']."
                    ";
                    
        $query = $this->db->query($sql);
        $payment_mdr = $query->row_array();
        
        if(!$payment_mdr){
            return 0;
        }else{
            $mdr = 0;
            if($payment_mdr['type_operation'] == "+"){
                $mdr = round($payment_data['valor_pedido'] * ($payment_mdr['mdr']/100) + $payment_mdr['type_value'],2);
                
            }elseif($payment_mdr['type_operation'] == "x"){
                $mdr = round(($payment_data['valor_pedido'] * ($payment_mdr['mdr']/100)) + ($payment_data['valor_pedido'] * ($payment_mdr['type_value']/100)) ,2);
            }
        }

        return $mdr;

    }

    public function getByPaymentId(string $payment_id): array
    {
        return $this->db->get_where(self::TABLE, ['payment_id' => $payment_id])->result_array();
    }

    public function getOrdersByPaymentId(string $payment_id): array
    {
        return $this->db
            ->select('op.*, o.date_time, o.paid_status, o.date_cancel, o.data_pago')
            ->join('orders o', 'o.id = op.order_id')
            ->where('op.payment_id', $payment_id)
            ->get(self::TABLE . ' op')
            ->result_array();
    }

    public function getOrderPaymentFields(int $order_id, array $campos) {
        if (empty($campos)) return []; // se não tiver nenhum campo, já retorna vazio
        
        $campos[] = 'gateway_tid';
        $campos = array_filter($campos, fn($c) => $c != 'tid');

        // monta os campos que queremos puxar da tabela
        $fields = array_map(fn($c) => "op.$c", $campos);
        $fields[] = 'o.id as order_id';
        $fields[] = 'o.store_id';

        // caz o SELECT com join entre orders_payment e orders
        return $this->db->select(implode(', ', $fields))
                        ->from('orders_payment op')
                        ->join('orders o', 'o.id = op.order_id', 'left')
                        ->where('o.id', $order_id)
                        ->get()
                        ->row_array() ?? [];

    }

    public function updateFieldsOrdersPayment($order_id, array $data){
        if (empty($data)) {
            return false;
        }
    
        $this->db->where('order_id', $order_id);
        return $this->db->update('orders_payment', $data);
    }
}
