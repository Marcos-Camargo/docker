<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->table_exists('iugu_plans')){

            $fieldUpdate = array(
                'user_id' => array(
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => FALSE,
                ),
            );

            $this->dbforge->modify_column('iugu_plans', $fieldUpdate);

        }

	 }

	public function down()	{
	}
};