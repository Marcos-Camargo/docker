<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->query("INSERT INTO `settings` (`id`, `name`, `value`, `status`, `user_id`, `date_updated`) VALUES ('', 'jwt_token_ms_shipping', '', '1', '1', CURRENT_TIMESTAMP);");
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'jwt_token_ms_shipping';");
    }
};