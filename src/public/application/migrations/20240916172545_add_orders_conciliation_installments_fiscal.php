<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('orders_conciliation_installments_fiscal')){
				
			## Create Table orders_conciliation_installments_fiscal
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => ('10'),
					'unsigned' => TRUE,
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'order_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
				'store_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
				'orders_payment_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
				'current_installment' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,

				),
				'total_installments' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,

				),
				'installment_value' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => FALSE,

				),
				'paid' => array(
					'type' => 'TINYINT',
					'constraint' => ('1'),
					'null' => FALSE,
					'default' => '0',

				),
				'lote' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'seller_name' => array(
					'type' => 'VARCHAR',
					'constraint' => ('100'),
					'null' => TRUE,

				),
				'cnpj' => array(
					'type' => 'VARCHAR',
					'constraint' => ('30'),
					'null' => TRUE,

				),
				'legal_panel_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

				),
				'numero_marketplace' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'data_pedido' => array(
					'type' => 'TIMESTAMP',
					'null' => TRUE,

				),
				'data_entrega' => array(
					'type' => 'TIMESTAMP',
					'null' => TRUE,

				),
				'data_report' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'data_ciclo' => array(
					'type' => 'DATE',
					'null' => FALSE,

				),
				'status_conciliacao' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'valor_pedido' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_produto' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_frete' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_percentual_produto' => array(
					'type' => 'VARCHAR',
					'constraint' => ('4'),
					'null' => TRUE,

				),
				'valor_percentual_frete' => array(
					'type' => 'VARCHAR',
					'constraint' => ('4'),
					'null' => TRUE,

				),
				'valor_comissao_produto' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_comissao_frete' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_comissao' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_repasse' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'valor_repasse_ajustado' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'usuario' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'tipo_pagamento' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'taxa_cartao_credito' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'tratado' => array(
					'type' => 'TINYINT',
					'constraint' => ('1'),
					'null' => TRUE,

				),
				'observacao' => array(
					'type' => 'TEXT',
					'null' => TRUE,

				),
				'refund' => array(
					'type' => 'DECIMAL',
					'constraint' => ('10,2'),
					'null' => TRUE,

				),
				'digitos_cartao' => array(
					'type' => 'VARCHAR',
					'constraint' => ('20'),
					'null' => TRUE,

				),
				'anticipated' => array(
					'type' => 'TINYINT',
					'constraint' => ('3'),
					'unsigned' => TRUE,
					'null' => FALSE,
					'default' => '0',

				),
				'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("orders_conciliation_installments_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `orders_conciliation_installments_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table orders_conciliation_installments_fiscal ##
		$this->dbforge->drop_table("orders_conciliation_installments_fiscal", TRUE);

	}
};