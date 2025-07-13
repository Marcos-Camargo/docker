<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table campaign_v2_payment_methods
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('10'),
				'unsigned' => TRUE,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'campaign_v2_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'trade_policy_id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("campaign_v2_trade_policies", TRUE);
		$this->db->query('ALTER TABLE  `campaign_v2_trade_policies` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `campaign_v2_trade_policies` ADD CONSTRAINT `campaign_v2_trade_policies_cv2id` FOREIGN KEY (`campaign_v2_id`) REFERENCES `campaign_v2` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->db->query('ALTER TABLE `campaign_v2_trade_policies` ADD CONSTRAINT `campaign_v2_trade_policies_tpid` FOREIGN KEY (`trade_policy_id`) REFERENCES `vtex_trade_policies` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	 }

	public function down()	{
		### Drop table campaign_v2_payment_methods ##
		$this->dbforge->drop_table("campaign_v2_payment_methods", TRUE);

	}
};