<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        ## Create Table mosaico_sales_channel 
        $this->dbforge->add_field(
            [
                'id' => [
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'auto_increment' => TRUE
                ],
                'mosaico_id' => [
                    'type' => 'INT',
                    'constraint' => ('11'),
                    'unsigned' => TRUE,
                    'null' => FALSE,
                    'auto_increment' => FALSE,
                ],
                'mosaico_value' => [
                    'type' => 'VARCHAR',
                    'constraint' => ('255'),
                    'null' => TRUE
                ]
            ]
        );
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("mosaico_sales_channel", TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table("mosaico_sales_channel", TRUE);
    }
};
