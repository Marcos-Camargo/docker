<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $fields = array(
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
                'default' => null
            )
        );
        $this->dbforge->add_column('param_mkt_ciclo', $fields);
    }

    public function down() {
        $this->dbforge->drop_column('param_mkt_ciclo', 'store_id');
    }
};
