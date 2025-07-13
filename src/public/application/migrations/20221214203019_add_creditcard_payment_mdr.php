<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        ## Create Table creditcard_payment_mdr

        if ($this->db->table_exists('creditcard_payment_mdr'))
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
            'payment_type' => array(
                'type' => 'VARCHAR',
                'constraint' => ('50'),
                'null' => FALSE
            ),
            'payment_method' => array(
                'type' => 'VARCHAR',
                'constraint' => ('50'),
                'null' => FALSE
            ),
            'parcels' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'mdr' => array(
                'type' => 'FLOAT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP NOT NULL',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("creditcard_payment_mdr", TRUE);
    }


    public function down()
    {
        if ($this->db->table_exists('creditcard_payment_mdr'))
        {
            $this->dbforge->drop_table("creditcard_payment_mdr", TRUE);
        }
    }
};
