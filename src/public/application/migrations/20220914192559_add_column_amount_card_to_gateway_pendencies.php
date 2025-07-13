<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'amount_card' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => TRUE,
                'default' => NULL,
                'after' => 'amount'
            )
        );

        if (!$this->dbforge->column_exists('amount_card', 'gateway_pendencies'))
        {
            $this->dbforge->add_column('gateway_pendencies', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('gateway_pendencies', 'amount_card');
	}
};