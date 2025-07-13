<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table campaign_v2_vtex_campaigns
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
			'occ_campaign_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('100'),
				'null' => TRUE,

			),
			'discount_type' => array(
				'type' => 'VARCHAR',
				'constraint' => ('50'),
				'null' => FALSE,

			),
			'discount_value' => array(
				'type' => 'DECIMAL',
				'constraint' => ('10,2'),
				'unsigned' => TRUE,
				'null' => FALSE,

			),
			'`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			'`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("campaign_v2_occ_campaigns", TRUE);
		$this->db->query('ALTER TABLE  `campaign_v2_occ_campaigns` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `campaign_v2_occ_campaigns` ADD CONSTRAINT `campaign_v2_occ_campaigns_cv2_id_fk` FOREIGN KEY (`campaign_v2_id`) REFERENCES `campaign_v2` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	 }

	public function down()	{
		### Drop table campaign_v2_vtex_campaigns ##
		$this->dbforge->drop_table("campaign_v2_occ_campaigns", TRUE);

	}
};