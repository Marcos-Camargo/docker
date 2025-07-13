<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        if (!$this->db->table_exists('getnet_gatilho_lancamento')){
            // Define table fields
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE
                ),
                'order_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => FALSE
                ),
                'numero_marketplace' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => FALSE
                ),
                'reference_number' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => TRUE
                ),
                'nu_liquid' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => TRUE
                ),
                'tipo_lancamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => FALSE
                ),
                'id_lancamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => FALSE
                ),
                'status_liberacao' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => FALSE
                ),
                'data_lancamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => TRUE
                ),
                'data_lquidacao' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => TRUE
                ),
                'valor_lancamento' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => FALSE
                )
            ));

            // Define primary key
            $this->dbforge->add_key('id', TRUE);

            // Define indexes
            $this->dbforge->add_key('numero_marketplace');
            $this->dbforge->add_key('order_id');

            // Create table
            $this->dbforge->create_table('getnet_gatilho_lancamento');
        }

    }

    public function down()
    {
        $this->dbforge->drop_table("getnet_gatilho_lancamento", TRUE);
    }
};