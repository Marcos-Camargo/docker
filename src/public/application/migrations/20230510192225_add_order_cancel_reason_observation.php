<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fields = array(
            'observation' => array('type' => 'VARCHAR', 'constraint' => '255', 'default' => NULL)
        );
        $this->dbforge->add_column('canceled_orders', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('canceled_orders', 'observation');
    }
};