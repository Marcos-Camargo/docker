<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'gift_card_provider' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => TRUE,
                'default' => NULL
            ),
            'gift_card_id' => array(
                'type' => 'VARCHAR',
                'constraint' => ('64'),
                'null' => TRUE,
                'default' => NULL
            )
        );

        foreach ($fieldUpdates as $column_name => $fieldUpdate) {
            if (!$this->dbforge->column_exists($column_name, 'orders_payment')) {
                $this->dbforge->add_column('orders_payment', array($column_name => $fieldUpdate));
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('orders_payment', 'gift_card_provider');
        $this->dbforge->drop_column('orders_payment', 'gift_card_id');
    }
};