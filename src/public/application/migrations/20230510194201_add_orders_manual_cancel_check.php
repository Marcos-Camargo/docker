<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fields = array(
            'manual_cancel' => array('type' => 'INT', 'constraint' => '5', 'default' => 0)
        );
        $this->dbforge->add_column('canceled_orders', $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column('canceled_orders', 'manual_cancel');
    }
};