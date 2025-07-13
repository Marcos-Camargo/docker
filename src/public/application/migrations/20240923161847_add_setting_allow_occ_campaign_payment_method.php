<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('settings', 'name', 'allow_occ_campaign_payment_method')){
            $this->db->query("INSERT INTO `settings` (`name`, `friendly_name`, `value`, `status`, `user_id`, `setting_category_id`) VALUES ('allow_occ_campaign_payment_method', 'Ativa/Inativa Campanhas OCC por meio de pagamento', '1', '2', '1', '3')");
        }
	}

	public function down()	{
        $this->db->query('DELETE FROM settings WHERE name = "allow_occ_campaign_payment_method"');
    }
};