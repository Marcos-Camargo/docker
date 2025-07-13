<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		
		$db_debug = $this->db->db_debug; //save setting
		$this->db->db_debug = FALSE; //disable debugging for queries
		if (!$this->db->query("ALTER TABLE `prd_variants` ADD INDEX `index_by_EAN_prd_id` (EAN, prd_id)")) {
			$error = $this->db->error(); // Has keys 'code' and 'message'
			if ($error['code'] != 1061)  { //  O indice já existe
				echo "\n********************************************************************************\n";
				echo "Deu erro no banco: ".$error['code'].': '.$error['message']."\n";
				die; 
			}
		}
		$this->db->db_debug = $db_debug;
	 }

	public function down()	{

		$this->db->query("ALTER TABLE `prd_variants` DROP INDEX `index_by_EAN_prd_id`");

	}
};