<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table campaign_v2_vtex_campaigns_logs
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
				'constraint' => ('10'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'conectala_request' => array(
				'type' => 'LONGTEXT',
				'null' => TRUE,

			),
			'vtex_response_code' => array(
				'type' => 'LONGTEXT',
				'null' => TRUE,

			),
			'vtex_response_body' => array(
				'type' => 'LONGTEXT',
				'null' => TRUE,

			),
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
			'`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("campaign_v2_vtex_campaigns_logs", TRUE);
		$this->db->query('ALTER TABLE  `campaign_v2_vtex_campaigns_logs` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `campaign_v2_vtex_campaigns_logs` ADD CONSTRAINT `campaign_v2_vtex_campaigns_logs_cv2_id` FOREIGN KEY (`campaign_v2_id`) REFERENCES `campaign_v2` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	 }

	public function down()	{
		### Drop table campaign_v2_vtex_campaigns_logs ##
		$this->dbforge->drop_table("campaign_v2_vtex_campaigns_logs", TRUE);

	}
};