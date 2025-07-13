<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
		$this->db->insert('settings', array(
			'name' => "normaliza_mensagem_rastreamento_sequoia",
			'value' => '1',
			'status' => '0',
			'user_id' => '1'
		));
	 }

	public function down()	{
        //
	}
};