<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $client = $this->db->query("select count(id) as client from settings where value = 'mateusmais'")->result_object();

        if (isset($client[0]->client) && $client[0]->client > 0)
        {
            //gera copia da tabela orders_conciliation_installments
            $this->db->query("
				CREATE TABLE IF NOT EXISTS `orders_conciliation_installments_backup` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `order_id` int(11) DEFAULT NULL,
                      `store_id` int(11) DEFAULT NULL,
                      `orders_payment_id` int(11) DEFAULT NULL,
                      `current_installment` tinyint(4) NOT NULL,
                      `total_installments` tinyint(4) NOT NULL,
                      `installment_value` decimal(10,2) NOT NULL,
                      `paid` tinyint(1) NOT NULL DEFAULT 0,
                      `lote` varchar(255) NOT NULL,
                      `seller_name` varchar(100) DEFAULT NULL,
                      `cnpj` varchar(30) DEFAULT NULL,
                      `legal_panel_id` int(11) DEFAULT NULL,
                      `numero_marketplace` varchar(50) DEFAULT NULL,
                      `data_pedido` timestamp NULL DEFAULT NULL,
                      `data_entrega` timestamp NULL DEFAULT NULL,
                      `data_report` varchar(50) DEFAULT NULL,
                      `data_ciclo` date NOT NULL,
                      `status_conciliacao` varchar(50) DEFAULT NULL,
                      `valor_pedido` decimal(10,2) DEFAULT NULL,
                      `valor_produto` decimal(10,2) DEFAULT NULL,
                      `valor_frete` decimal(10,2) DEFAULT NULL,
                      `valor_percentual_produto` varchar(4) DEFAULT NULL,
                      `valor_percentual_frete` varchar(4) DEFAULT NULL,
                      `valor_comissao_produto` decimal(10,2) DEFAULT NULL,
                      `valor_comissao_frete` decimal(10,2) DEFAULT NULL,
                      `valor_comissao` decimal(10,2) DEFAULT NULL,
                      `valor_repasse` decimal(10,2) DEFAULT NULL,
                      `valor_repasse_ajustado` decimal(10,2) DEFAULT NULL,
                      `usuario` varchar(50) DEFAULT NULL,
                      `tipo_pagamento` varchar(50) DEFAULT NULL,
                      `taxa_cartao_credito` varchar(50) DEFAULT NULL,
                      `tratado` tinyint(1) DEFAULT NULL,
                      `observacao` text DEFAULT NULL,
                      `refund` decimal(10,2) DEFAULT NULL,
                      `digitos_cartao` varchar(20) DEFAULT NULL,
                      `anticipated` tinyint(3) unsigned NOT NULL DEFAULT 0,
                      `created_at` timestamp NULL DEFAULT current_timestamp(),
                      `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                      PRIMARY KEY (`id`) USING BTREE,
                      KEY `order_id` (`order_id`) USING BTREE,
                      KEY `store_id` (`store_id`) USING BTREE,
                      KEY `orders_payment_id` (`orders_payment_id`) USING BTREE,
                      KEY `legal_panel_id` (`legal_panel_id`) USING BTREE
                    )"
            );

            $this->db->query("INSERT INTO `orders_conciliation_installments_backup` 
				(`order_id`, 
				 `store_id`, 
				 `orders_payment_id`, 
				 `current_installment`, 
				 `total_installments`, 
				 `installment_value`, 
				 `paid`, 
				 `lote`, 
				 `seller_name`, 
				 `cnpj`, 
				 `legal_panel_id`,
                 `numero_marketplace`, 
				 `data_pedido`, 
				 `data_entrega`,
				 `data_report`,
				 `data_ciclo`,
				 `status_conciliacao`,
				 `valor_pedido`,
				 `valor_produto`,
				 `valor_frete`,
				 `valor_percentual_produto`,
				 `valor_percentual_frete`,
				 `valor_comissao_produto`,
				 `valor_comissao_frete`,
				 `valor_comissao`,
				 `valor_repasse`,
				 `valor_repasse_ajustado`,
				 `usuario`,
				 `tipo_pagamento`,
				 `taxa_cartao_credito`,
				 `tratado`,
				 `observacao`,
				 `refund`,
				 `digitos_cartao`,
				 `anticipated`,
				 `created_at`,
				 `updated_at`
				 )
				SELECT 
				 `order_id`, 
				 `store_id`, 
				 `orders_payment_id`, 
				 `current_installment`, 
				 `total_installments`, 
				 `installment_value`, 
				 `paid`, 
				 `lote`, 
				 `seller_name`, 
				 `cnpj`, 
				 `legal_panel_id`,
                 `numero_marketplace`, 
				 `data_pedido`, 
				 `data_entrega`,
				 `data_report`,
				 `data_ciclo`,
				 `status_conciliacao`,
				 `valor_pedido`,
				 `valor_produto`,
				 `valor_frete`,
				 `valor_percentual_produto`,
				 `valor_percentual_frete`,
				 `valor_comissao_produto`,
				 `valor_comissao_frete`,
				 `valor_comissao`,
				 `valor_repasse`,
				 `valor_repasse_ajustado`,
				 `usuario`,
				 `tipo_pagamento`,
				 `taxa_cartao_credito`,
				 `tratado`,
				 `observacao`,
				 `refund`,
				 `digitos_cartao`,
				 `anticipated`,
				 `created_at`,
				 `updated_at`
				FROM `orders_conciliation_installments`;"
            );

            $this->db->query("UPDATE orders_conciliation_installments set data_ciclo = '2025-02-15' where data_ciclo >= '2025-03-15'");
        }
    }

    public function down()
    {
        $this->db->query("RENAME TABLE `orders_conciliation_installments` TO `orders_conciliation_installments_rollback;");
        $this->db->query("RENAME TABLE `orders_conciliation_installments_backup` TO `orders_conciliation_installments`;");
    }
};