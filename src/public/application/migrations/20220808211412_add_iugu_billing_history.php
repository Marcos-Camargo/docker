<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->table_exists('iugu_billing_history')){

            $fieldUpdate = array(
                'plan_id' => [
                    'type' => 'INT',
                    'constraint' => ('10'),
                    'unsigned' => TRUE,
                    'null' => true,
                ],
                'store_id' => [
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'null' => true,
                ]
            );

            $this->dbforge->modify_column('iugu_billing_history', $fieldUpdate);

            $this->db->query("UPDATE iugu_billing_history SET plan_id = null WHERE plan_id = 0");
            $this->db->query("UPDATE iugu_billing_history SET store_id = null WHERE store_id = 0");

        }

	 }

	public function down()	{
	}
};