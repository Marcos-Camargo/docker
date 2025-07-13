<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Integracoes
 
 */

class Model_fields_orders_add extends CI_Model
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

    public function getFieldsOrdersAdd(int $store_id){
        // consulta os campos adicionais que devem aparecer na "observation"
        return $this->db->select('tid, nsu, authorization_id, first_digits, last_digits')
                    ->where('store_id', $store_id)
                    ->get('fields_orders_add')
                    ->row_array() ?? [];

    }
    

}
