<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
/**
 * Class Model_order_value_refund_on_gateways
 */
class Model_order_value_refund_on_gateways extends CI_Model
{
    const TABLE = "order_value_refund_on_gateways";

    public function __construct() {
		parent::__construct();
    }

    public function create($data): bool
    {
        return $this->db->insert(Model_order_value_refund_on_gateways::TABLE, $data);
    }

    public function update(array $data, int $id): bool
    {
        return $this->db->update(
            Model_order_value_refund_on_gateways::TABLE,
            $data,
            array(
                'id' => $id
            )
        );
    }

    public function getByOrderId(int $order_id): array
    {
        return $this->db
            ->select(Model_order_value_refund_on_gateways::TABLE.'.*, users.email as email_user')
            ->join('users', 'users.id = '.Model_order_value_refund_on_gateways::TABLE.'.user_id')
            ->where(['order_id' => $order_id])
            ->order_by(Model_order_value_refund_on_gateways::TABLE.'.id', 'ASC')
            ->get(Model_order_value_refund_on_gateways::TABLE)
            ->result_array();
    }

    public function getNotSentByOrderId(int $order_id): array
    {
        return $this->db
            ->where('refunded_at IS NULL', null, false)
            ->where('order_id', $order_id)
            ->get(Model_order_value_refund_on_gateways::TABLE)
            ->result_array();
    }

    public function getAllNotSent(): array
    {
        return $this->db
            ->where('refunded_at IS NULL', null, false)
            ->get(Model_order_value_refund_on_gateways::TABLE)
            ->result_array();
    }
}