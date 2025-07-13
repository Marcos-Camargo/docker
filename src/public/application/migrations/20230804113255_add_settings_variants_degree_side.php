<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->register_exists('settings', 'name', 'variacao_grau')) {
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`,`setting_category_id`,`friendly_name`,`description` ) 
            VALUES ('variacao_grau', 'Habilita variacao grau', '2', '1','6','variacao_grau','Ativa a variação de grau quando necessario');");
        }
        if (!$this->dbforge->register_exists('settings', 'name', 'variacao_lado')) {
            $this->db->query("INSERT INTO settings  (`name`, `value`, `status`, `user_id`,`setting_category_id`,`friendly_name`,`description` ) 
            VALUES ('variacao_lado', 'Habilita variacao lado', '2', '1','6','variacao_lado','Ativa a variação de lado quando necessario');");
        }
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'variacao_grau';");
        $this->db->query("DELETE FROM settings WHERE `name` = 'variacao_lado';");
    }
};