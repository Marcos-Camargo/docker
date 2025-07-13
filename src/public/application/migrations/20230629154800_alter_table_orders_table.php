<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'integration_logistic' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => TRUE,
                'default' => NULL
            )
        );

        if (!$this->dbforge->column_exists('integration_logistic', 'orders')) {
            $this->dbforge->add_column('orders', $fieldUpdates);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('orders', 'integration_logistic');
    }
};