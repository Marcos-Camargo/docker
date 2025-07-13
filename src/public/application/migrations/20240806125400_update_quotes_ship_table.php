<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $fieldUpdate = array(
            'oferta' => array(
                'name' => 'oferta',
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => TRUE,
            ),
        );
        $this->dbforge->modify_column('quotes_ship', $fieldUpdate);
    }

    public function down() {

        $fieldUpdate = array(
            'oferta' => array(
                'name' => 'oferta',
                'type' => 'INT',
                'null' => TRUE,
            ),
        );
        $this->dbforge->modify_column('quotes_ship', $fieldUpdate);
    }
};
