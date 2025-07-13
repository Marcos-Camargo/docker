<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

        public function up()
        {
                if (!$this->dbforge->register_exists('settings', 'name', 'maximum_price_lock')) {
                        $this->db->query("INSERT INTO settings (name, value, status, user_id, date_updated) VALUES('maximum_price_lock', 'Informe o % para a trava de preço Máximo e ative o parâmetro', 0, 1, CURRENT_TIMESTAMP);");
                }
                if (!$this->dbforge->register_exists('settings', 'name', 'minimum_price_lock')) {
                        $this->db->query("INSERT INTO settings (name, value, status, user_id, date_updated) VALUES('minimum_price_lock', 'Informe o % para a trava de preço Mínimo e ative o parâmetro', 0, 1, CURRENT_TIMESTAMP);");
                }
        }

        public function down()
        {
                $this->db->query('DELETE FROM settings WHERE name like "maximum_price_lock";');
                $this->db->query('DELETE FROM settings WHERE name like "maximum_price_lock";');
        }
};
