<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('settings', 'name', 'allow_campaign_trade_policies')){
            $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('allow_campaign_trade_policies', 'Ativa/Inativa Campanhas VTEX por políticas comerciais', '2', '1')");
        }
	}

	public function down()	{
        $this->db->query('DELETE FROM settings WHERE name = "allow_campaign_trade_policies"');
    }
};