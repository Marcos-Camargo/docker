<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'active' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0',
                'after' => 'id'
            )
        );
        if (!$this->dbforge->column_exists('active', 'vtex_trade_policies')) {
            $this->dbforge->add_column('vtex_trade_policies', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('vtex_trade_policies', 'active');
    }
};