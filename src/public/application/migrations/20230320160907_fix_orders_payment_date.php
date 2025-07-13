<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("DELETE FROM `orders_payment_date` WHERE order_id NOT IN (SELECT order_id FROM conciliacao_sellercenter INNER JOIN conciliacao ON conciliacao.lote = conciliacao_sellercenter.lote);");
        $this->db->query("SELECT data_pagamento_marketplace(id) FROM orders");
        $this->db->query("SELECT data_cancelamento_marketplace(id) FROM orders");
	}

	public function down()	{
	}
};