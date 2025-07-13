<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->column_exists('with_pendencies', 'gateway_subaccounts')) {

            $fieldNew = array(
                'with_pendencies' => array(
                    'type' => 'TINYINT',
                    'constraint' => ('1'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'default' => 0
                )
            );

            $this->dbforge->add_column('gateway_subaccounts', $fieldNew);

        }

	}

	public function down()	{
        $this->dbforge->drop_column("gateway_subaccounts", 'gateway_subaccounts');
	}

};