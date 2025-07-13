<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'quote_sequoia_table_internal'))
		{
			$this->db->insert('settings', array(
				'name'      => "quote_sequoia_table_internal",
				'value'     => 'Quando ativo irá ignorar cotação na Sequoia e irá na tabela interna.',
				'status'    => 1,
				'user_id'   => 1
			));
		}
	}

	public function down()
	{
		$this->db->query("DELETE FROM settings WHERE `name` = 'quote_sequoia_table_internal';");
	}
};