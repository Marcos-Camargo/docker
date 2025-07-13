<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$db_debug = $this->db->db_debug; //save setting
		$this->db->db_debug = FALSE; //disable debugging for queries
		if (!$this->db->query("UPDATE `integrations_logistic` SET `fields_form` = '{\"token\":{\"name\":\"application_token\",\"type\":\"text\"},\r\n \"cart\":{\"name\":\"application_card\",\"type\":\"text\"},\r\n \"contract\":{\"name\":\"application_contract\",\"type\":\"text\"},\r\n \"type_contract\":{\"name\":\"application_type_contract\",\"type\":\"radio\",\"values\":{\"application_old\":\"old\",\"application_new\":\"new\"}\r\n \r\n}}' WHERE `integrations_logistic`.`id` = 1;")) {
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
		$this->db->query("UPDATE `integrations_logistic` SET `fields_form` = '{\"token\":{\"name\":\"application_token\",\"type\":\"text\"},\"type_contract\":{\"name\":\"application_type_contract\",\"type\":\"radio\",\"values\":{\"application_old\":\"old\",\"application_new\":\"new\"}}}' WHERE `integrations_logistic`.`id` = 1;");
	}
};