<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if(!$this->db->table_exists('orders_conciliation_installments')){
            $this->db->query('CREATE TABLE `orders_conciliation_installments`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NULL DEFAULT NULL,
  `store_id` int(11) NULL DEFAULT NULL,
  `orders_payment_id` int(11) NOT NULL,
  `current_installment` tinyint(2) NOT NULL,
  `total_installments` tinyint(2) NOT NULL,
  `installment_value` decimal(10, 2) NOT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT 0,
  `lote` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `seller_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `cnpj` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `legal_panel_id` int(11) NULL DEFAULT NULL,
  `numero_marketplace` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `data_pedido` timestamp(0) NULL DEFAULT NULL,
  `data_entrega` timestamp(0) NULL DEFAULT NULL,
  `data_report` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `data_ciclo` date NOT NULL,
  `status_conciliacao` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `valor_pedido` decimal(10, 2) NULL DEFAULT NULL,
  `valor_produto` decimal(10, 2) NULL DEFAULT NULL,
  `valor_frete` decimal(10, 2) NULL DEFAULT NULL,
  `valor_percentual_produto` varchar(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `valor_percentual_frete` varchar(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `valor_comissao_produto` decimal(10, 2) NULL DEFAULT NULL,
  `valor_comissao_frete` decimal(10, 2) NULL DEFAULT NULL,
  `valor_comissao` decimal(10, 2) NULL DEFAULT NULL,
  `valor_repasse` decimal(10, 2) NULL DEFAULT NULL,
  `valor_repasse_ajustado` decimal(10, 2) NULL DEFAULT NULL,
  `usuario` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tipo_pagamento` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `taxa_cartao_credito` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `tratado` tinyint(1) NULL DEFAULT NULL,
  `observacao` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
  `refund` decimal(10, 2) NULL DEFAULT NULL,
  `digitos_cartao` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
  `updated_at` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0),
  `anticipated` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `store_id`(`store_id`) USING BTREE,
  INDEX `orders_payment_id`(`orders_payment_id`) USING BTREE,
  INDEX `legal_panel_id`(`legal_panel_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Compact');
        }
	}

	public function down()	{
        $this->dbforge->drop_table("orders_conciliation_installments", TRUE);
	}
};