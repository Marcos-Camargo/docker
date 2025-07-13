<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $integration    = $this->db->get_where('integration_logistic', array('integration' => 'correios', 'store_id' => 0))->row_array();
        $seller_center  = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();

        if (!in_array($seller_center['value'], array(
            'decathlon',
            'vertem',
            'sicoob',
            'keeprunningbrasil',
            'fibracirurgica',
            'somaplace'
        ))) {
            echo " - Não deve executar a migration para o cliente.";
            return;
        }

        if (!$integration) {
            echo " - Cliente não tem integração com correios no monolíto.";
            return;
        }

        $integration_id     = $integration['id_integration'];
        $integration_name   = $integration['integration'];

        $this->db
            ->where('credentials IS NULL', NULL, FALSE)
            ->where('integration', 'sgpweb')
            ->update('integration_logistic', array('id_integration' => $integration_id, 'integration' => $integration_name));

        // Pedidos para trocar de sgpweb para correios.
        $sub_query = $this->db->select('o.id')
            ->where_not_in('o.paid_status', [95,96,97,98,99,60,6,81,8])
            ->join('freights f', 'o.id = f.order_id', 'left')

            // [INÍCIO] Se for MS retirar esse trecho
            ->join('integration_logistic il', 'il.store_id = o.store_id')
            ->where('il.credentials IS NULL', NULL, FALSE)
            // [FIM] Se for MS retirar esse trecho

            ->where('o.integration_logistic', 'sgpweb')
            ->group_start()
                ->where('f.sgp', 1)
                ->or_where('f.sgp IS NULL', NULL, FALSE)
            ->group_end()
            ->get('orders o')->result_array();

        // Divide o resultado em bloco de 200, para atualizar 200 por vez.
        $result_orders = array_chunk($sub_query, 200);

        // Atualização a cada 200 pedidos.
        foreach ($result_orders as $order_id) {
            $order_id = array_map(function ($order_id) {
                return $order_id['id'];
            }, $order_id);

            // Atualiza o campo sgp apra 7.
            $this->db->where_in("order_id", $order_id)
                ->update('freights', array(
                    'sgp' => 7
                ));

            // Atualiza o campo integration_logistic para correios.
            $this->db->where_in('id', $order_id)
                ->update('orders', array(
                    'integration_logistic' => 'correios'
                ));
        }
	}

	public function down()	{}
};