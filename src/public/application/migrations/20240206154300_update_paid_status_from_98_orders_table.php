<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $orders = $this->db->select('id')->where('paid_status', 98)->get('orders')->result_array();
        $this->load->library('ordersMarketplace');

        foreach ($orders as $order) {
            $sellerCancel   = false;
            $regOrderCancel = $this->model_orders->getPedidosCanceladosByOrderId($order['id']);

            if ($regOrderCancel && $regOrderCancel['penalty_to'] == '1-Seller') {
                $sellerCancel = true;
            }

            $this->model_orders->updatePaidStatus($order['id'], $sellerCancel ? 95 : 97);
        }
	}

	public function down()	{}
};