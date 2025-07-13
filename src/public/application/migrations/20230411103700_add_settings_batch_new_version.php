<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->register_exists('settings','name','batch_new_version')){ 
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, `setting_category_id`, `friendly_name`, `description`) 
            VALUES ('batch_new_version', 'Se ativo usará a versão de execução de Batchs através de filas e do cluster de Batchs', '2', '1',
            7,'batch_new_version','Se ativo usará a versão de execução de Batchs através de filas e do cluster de Batchs')");
        }
	 }

	public function down()	{
		
		$this->db->query("DELETE FROM settings where name = 'batch_new_version'");

	}
};
