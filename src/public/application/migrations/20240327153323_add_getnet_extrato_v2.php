<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table getnet_extrato_v2
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'order_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => TRUE,

			),
			'numero_marketplace' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'valor_total_item' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_type_register' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_order_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_marketplace_subsellerid' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_marketplace_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_transaction_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_confirmation_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_product_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_transaction_type' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_number_installments' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_nsu_host' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_acquirer_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_card_payment_amount' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_sum_details_card_payment_amount' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_marketplace_original_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_transaction_status_code' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_transaction_sign' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_terminal_nsu' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_reason_message' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_authorization_code' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_payment_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_terminal_identification' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_nsu_tef' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_entry_mode' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_transaction_channel' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_capture' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_payment_tag' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_truncated_card_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'summary_prepaid_card' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_type_register' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_marketplace_schedule_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_marketplace_subsellerid' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_release_status' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_cpfcnpj_subseller' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_cancel_custom_key' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_cancel_request_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_marketplace_transaction_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_transaction_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_confirmation_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_item_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_number_installments' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_installment' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_installment_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_installment_amount' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_subseller_rate_amount' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_subseller_rate_percentage' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_payment_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_subseller_rate_closing_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_subseller_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_seller_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_transaction_sign' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_item_id_mgm' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_payment_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_payment_tag' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_item_split_tag' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_payment_plan_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_boleto_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_our_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_boleto_payment_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'details_reference_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'json_retorno' => array(
				'type' => 'VARCHAR',
				'constraint' => ('5000'),
				'null' => TRUE,

			),
			'chave_md5' => array(
				'type' => 'VARCHAR',
				'constraint' => ('32'),
				'null' => TRUE,

			),
			'`date_insert` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("getnet_extrato_v2", TRUE);
		$this->db->query('ALTER TABLE  `getnet_extrato_v2` ENGINE = InnoDB');
		$this->db->query("CREATE INDEX getnet_extrato_v2_chave_md5_IDX ON `getnet_extrato_v2` (`chave_md5`);");
		$this->db->query("CREATE INDEX getnet_extrato_v2_order_id_json_IDX ON `getnet_extrato_v2` (`order_id`);");
	 }

	public function down()	{
		### Drop table getnet_extrato_v2 ##
		$this->dbforge->drop_table("getnet_extrato_v2", TRUE);

	}
};