<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table magalupay_subaccount
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'store_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,

			),
			'public_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'reference_key' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => FALSE,

			),
			'bank_account_document_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'bank_account_bank_code' => array(
				'type' => 'VARCHAR',
				'constraint' => ('10'),
				'null' => TRUE,

			),
			'bank_account_bank_agency' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'bank_account_bank_agency_digit' => array(
				'type' => 'VARCHAR',
				'constraint' => ('5'),
				'null' => TRUE,

			),
			'bank_account_account' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'bank_account_account_digit' => array(
				'type' => 'VARCHAR',
				'constraint' => ('5'),
				'null' => TRUE,

			),
			'bank_account_bank_account_type' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => TRUE,

			),
			'recipient_config_auto_anticipate' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'recipient_config_auto_transfer' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'recipient_config_transfer_periodicity' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => TRUE,

			),
			'recipient_config_transfer_days' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'recipient_config_transfer_weekday' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => TRUE,

			),
			'terms_conditions_accept' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'terms_conditions_fatca' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'webhook_url' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'create_bank_account' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'create_seller_access' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'reprove_source' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'reprove_reason' => array(
				'type' => 'VARCHAR',
				'constraint' => ('1000'),
				'null' => TRUE,

			),
			'auth_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'product_key' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'document_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'status' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'person_type' => array(
				'type' => 'VARCHAR',
				'constraint' => ('10'),
				'null' => TRUE,

			),
			'created_at' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'updated_at' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'analysis_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('1000'),
				'null' => TRUE,

			),
			'recipient_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'additional_step' => array(
				'type' => 'VARCHAR',
				'constraint' => ('1000'),
				'null' => TRUE,

			),
			'analysis' => array(
				'type' => 'VARCHAR',
				'constraint' => ('1000'),
				'null' => TRUE,

			),
			'info_pj_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_company_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_trading_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_phone_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'info_pj_site' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_email' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'info_pj_register_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'info_pj_address_zipcode' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'info_pj_address_street' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_address_complement' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_address_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'info_pj_address_city' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_address_district' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_address_state' => array(
				'type' => 'VARCHAR',
				'constraint' => ('5'),
				'null' => TRUE,

			),
			'info_pj_legal_person_full_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_legal_person_document_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'info_pj_legal_person_birth_date' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'info_pj_legal_person_mother_full_name' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_legal_person_phone_number' => array(
				'type' => 'VARCHAR',
				'constraint' => ('20'),
				'null' => TRUE,

			),
			'info_pj_legal_person_email' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'info_pj_legal_person_position' => array(
				'type' => 'VARCHAR',
				'constraint' => ('255'),
				'null' => TRUE,

			),
			'info_pj_business_category' => array(
				'type' => 'VARCHAR',
				'constraint' => ('30'),
				'null' => TRUE,

			),
			'`date_create` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp'
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("magalupay_subaccount", TRUE);
		$this->db->query('ALTER TABLE  `magalupay_subaccount` ENGINE = InnoDB');
	 }

	public function down()	{
		### Drop table magalupay_subaccount ##
		$this->dbforge->drop_table("magalupay_subaccount", TRUE);

	}
};