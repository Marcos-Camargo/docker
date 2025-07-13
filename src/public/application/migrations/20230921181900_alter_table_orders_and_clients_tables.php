<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdateClient = array(
            'birth_date' => array(
                'type' => 'DATE',
                'null' => TRUE,
            )
        );
        $fieldUpdateOrder = array(
            'quote_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE,
            )
        );

        if (!$this->dbforge->column_exists('birth_date', 'clients')) {
            $this->dbforge->add_column('clients', $fieldUpdateClient);
        }

        if (!$this->dbforge->column_exists('quote_id', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdateOrder);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('clients', 'birth_date');
        $this->dbforge->drop_column('orders', 'quote_id');
    }
};