<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        ## Create Table creditcard_payment_mdr

        if ($this->db->table_exists('cut_date_cycle'))
        {
            return;
        }

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'cut_date' => array(
                'type' => 'VARCHAR',
                'constraint' => ('45'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP NOT NULL',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("cut_date_cycle", TRUE);
    }


    public function down()
    {
        if ($this->db->table_exists('cut_date_cycle'))
        {
            $this->dbforge->drop_table("cut_date_cycle", TRUE);
        }
    }
};
