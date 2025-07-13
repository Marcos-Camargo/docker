<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    private $parameterName = "enable_and_show_hotjar_script";

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', $this->parameterName)) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`) VALUES ('$this->parameterName', '', '2', '1');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = '$this->parameterName';");
    }
};