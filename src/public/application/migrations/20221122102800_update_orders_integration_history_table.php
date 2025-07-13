<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Update column id of orders_integration_history.
        $fieldUpdate = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
                'auto_increment' => TRUE
            )
        );
        $this->dbforge->modify_column('orders_integration_history', $fieldUpdate);
	}

	public function down() {}
};
