<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->query("
            DELETE FROM order_payment_transactions opt WHERE opt.order_id IN (SELECT op.order_id
            FROM orders_payment op
            WHERE op.data_vencto BETWEEN ? AND ?
            GROUP BY op.order_id,op.parcela,op.valor,op.payment_id,op.autorization_id,op.payment_transaction_id
            HAVING COUNT(op.order_id) > ?
            ORDER BY COUNT(op.order_id) DESC)
        ", array('2023-12-04', '2024-01-20', 1));

        $this->db->query("INSERT INTO queue_payments_orders_marketplace (status, order_id, numero_marketplace) 
            SELECT 0, o.id, o.numero_marketplace
            FROM orders_payment op 
            JOIN orders o ON o.id = op.order_id
            WHERE op.data_vencto BETWEEN ? AND ? AND (op.forma_id = ? OR op.forma_desc = ?)
            GROUP BY o.id
        ", array('2023-12-04', '2024-01-20', '', ''));

        $this->db->query("
            DELETE FROM orders_payment WHERE data_vencto BETWEEN ? AND ? AND (forma_id = ? OR forma_desc = ?)
        ", array('2023-12-04', '2024-01-20', '', ''));

		$payments = $this->db->query("SELECT order_id,COUNT(order_id) AS qtd 
            FROM orders_payment 
            WHERE data_vencto BETWEEN ? AND ?
            GROUP BY order_id,parcela,valor,payment_id,autorization_id,payment_transaction_id
            HAVING COUNT(order_id) > ? 
            ORDER BY COUNT(order_id) DESC", array('2023-12-04', '2024-01-20', 1))->result_array();

        foreach ($payments as $payment) {
            $this->db->delete('orders_payment', ['order_id' => $payment['order_id']], $payment['qtd'] - 1);
        }
	}

	public function down()	{}
};