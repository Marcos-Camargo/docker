<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		$this->db->where(['id' => '1000008'])->update("conciliacao", array(
			'status_repasse' => '21'
		));
	}

	public function down()	{
	}
};