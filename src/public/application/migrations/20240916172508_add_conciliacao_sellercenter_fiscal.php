<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		if (!$this->db->table_exists('conciliacao_sellercenter_fiscal')){

			## Create Table conciliacao_sellercenter_fiscal
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => FALSE,
					'auto_increment' => TRUE
				),
				'lote' => array(
					'type' => 'VARCHAR',
					'constraint' => ('255'),
					'null' => FALSE,

				),
				'`data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'store_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
					'null' => TRUE,

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
				'order_id' => array(
					'type' => 'INT',
					'constraint' => ('11'),
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
				'`data_pedido` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'`data_entrega` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
				'data_report' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,

				),
				'data_ciclo' => array(
					'type' => 'VARCHAR',
					'constraint' => ('20'),
					'null' => TRUE,

				),
				'status_conciliacao' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => 'Conciliação Ciclo',

				),
				'valor_pedido' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_produto' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_frete' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_percentual_produto' => array(
					'type' => 'VARCHAR',
					'constraint' => ('4'),
					'null' => TRUE,
					'default' => '0',

				),
				'valor_percentual_frete' => array(
					'type' => 'VARCHAR',
					'constraint' => ('4'),
					'null' => TRUE,
					'default' => '0',

				),
				'valor_comissao_produto' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_comissao_frete' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_comissao' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_repasse' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

				),
				'valor_repasse_ajustado' => array(
					'type' => 'VARCHAR',
					'constraint' => ('50'),
					'null' => TRUE,
					'default' => '0.00',

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
					'type' => 'VARCHAR',
					'constraint' => ('1'),
					'null' => TRUE,
					'default' => '1',

				),
				'observacao' => array(
					'type' => 'VARCHAR',
					'constraint' => ('5000'),
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
				'current_installment' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,
					'default' => '1',

				),
				'total_installments' => array(
					'type' => 'TINYINT',
					'constraint' => ('4'),
					'null' => FALSE,
					'default' => '1',

				),
			));
			$this->dbforge->add_key("id",true);
			$this->dbforge->create_table("conciliacao_sellercenter_fiscal", TRUE);
			$this->db->query('ALTER TABLE  `conciliacao_sellercenter_fiscal` ENGINE = InnoDB');
		}
	 }

	public function down()	{
		### Drop table conciliacao_sellercenter_fiscal ##
		$this->dbforge->drop_table("conciliacao_sellercenter_fiscal", TRUE);

	}
};