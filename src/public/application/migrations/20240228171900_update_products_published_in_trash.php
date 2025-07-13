<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $tableExists = array_map(function($table){
            return $table['TABLE_NAME'];
        }, $this->db->query(
            "SELECT table_name 
                FROM information_schema.tables 
                WHERE table_name LIKE '%_ult_envio%' 
                   OR table_name LIKE '%_last_post%'"
        )->result_array());

        if (in_array('sellercenter_last_post', $tableExists)) {
            $this->db->query("UPDATE sellercenter_last_post
                SET skumkt = CONCAT('DEL_', skumkt), skulocal = CONCAT('DEL_', skulocal)
                WHERE sku like 'DEL_%' and skumkt not like 'DEL_%';");
        }

        if (in_array('occ_last_post', $tableExists)) {
            $this->db->query("UPDATE occ_last_post
                SET skumkt = CONCAT('DEL_', skumkt), skulocal = CONCAT('DEL_', skulocal)
                WHERE sku like 'DEL_%' and skumkt not like 'DEL_%';");
        }

        if (in_array('integration_last_post', $tableExists)) {
            $this->db->query("UPDATE integration_last_post
                SET skumkt = CONCAT('DEL_', skumkt), skulocal = CONCAT('DEL_', skulocal)
                WHERE sku like 'DEL_%' and skumkt not like 'DEL_%';");
        }

        if (in_array('vtex_ult_envio', $tableExists)) {
            $this->db->query("UPDATE vtex_ult_envio
                SET skumkt = CONCAT('DEL_', skumkt)
                WHERE sku like 'DEL_%' and skumkt not like 'DEL_%';");
        }
	 }

	public function down()	{
	}
};