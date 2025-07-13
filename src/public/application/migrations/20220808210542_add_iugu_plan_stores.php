<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->table_exists('iugu_plan_stores')){

            $fieldUpdate = array(
                'store_id' => [
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => FALSE,
                    'null' => FALSE,
                ]
            );

            $this->dbforge->modify_column('iugu_plan_stores', $fieldUpdate);

        }
	 }

	public function down()	{
	}
};