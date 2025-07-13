<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		// Com a data de última execução como null, irá buscar produtos de até 50 anos atrás.
		// CreateProduct da Ideris também realiza a atualização.
		// Executando isto devido a produtos que ficaram sem atualização após algum bug.
		$this->db->query(
			"UPDATE job_integration 
				SET last_run = null 
				WHERE integration = 'ideris' 
				AND job = 'CreateProduct'"
		);
	}

	public function down() {}
};
