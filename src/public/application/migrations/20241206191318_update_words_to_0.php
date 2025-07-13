<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		$this->db->query("UPDATE stores SET inscricao_estadual = 0 WHERE inscricao_estadual NOT REGEXP '[0-9]'");
	}

	public function down()	{
	}
};