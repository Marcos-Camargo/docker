<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        ## Create Table mosaico_sc_store
        ## Tabela pivot. 
        $this->dbforge->add_field(
            [
                'store_id' => [
                    'type' => 'INT',
                    'unsigned' => TRUE,
                ],
                'sc_id' => [
                    'type' => 'INT',
                    'unsigned' => TRUE,
                ]
            ]
        );
        $this->dbforge->add_key(['store_id', 'sc_id'], true);
        $this->dbforge->create_table("mosaico_sc_store", TRUE);
    }

    public function down()
    {
        $this->dbforge->drop_table("mosaico_sc_store", TRUE);
    }
};
