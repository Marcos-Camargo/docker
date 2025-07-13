<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if(!$this->db->field_exists('seller_type', 'campaign_v2')){
        	$this->db->query('ALTER TABLE `campaign_v2` ADD COLUMN `seller_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `b2w_type`');
		}
		if(!$this->db->field_exists('store_seller_campaign_owner', 'campaign_v2')){
        	$this->db->query('ALTER TABLE `campaign_v2` ADD COLUMN `store_seller_campaign_owner` int(11) DEFAULT NULL AFTER `b2w_type`');
		}
	 }

	public function down()	{
		### Drop table anticipation_limits_store ##
		$this->dbforge->drop_column("campaign_v2", 'seller_type');
		$this->dbforge->drop_column("campaign_v2", 'store_seller_campaign_owner');
	}

};