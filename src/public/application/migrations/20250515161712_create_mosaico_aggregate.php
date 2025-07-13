<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        ## Create Table mosaico_aggregate_merchant.
        $this->dbforge->add_field(
            [
                'id' => [
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => FALSE
                ],
                'aggregate_merchant' => [
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE // Permite criação com aggregate_merchant NULL para criação posterior na Mosaico.
                ]
            ]
        );
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("mosaico_aggregate_merchant", TRUE);        
        $this->db->query('ALTER TABLE mosaico_aggregate_merchant ADD UNIQUE(name)');
    }

    public function down()
    {
        $this->dbforge->drop_table("mosaico_aggregate_merchant", TRUE);
    }
};
