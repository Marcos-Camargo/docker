<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->dbforge->drop_column('stores', 'transfer_interval');
        $this->db->query("DELETE FROM settings WHERE name = 'intervalo_transferencia_default'");
	}

	public function down()	{
	}
};