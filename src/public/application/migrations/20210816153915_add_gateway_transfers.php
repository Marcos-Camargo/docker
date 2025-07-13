<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        ## Create Table gateway_transfers
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'gateway_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,

            ),
            'transfer_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,

            ),
            'transfer_gateway_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => TRUE,

            ),
            'transfer_type' => array(
                'type' => 'ENUM("WALLET","BANK")',
                'null' => FALSE,

            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => FALSE,

            ),
            'fee' => array(
                'type' => 'INT',
                'constraint' => 10,
                'null' => FALSE,

            ),
            'sender_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => FALSE,

            ),
            'receiver_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => FALSE,

            ),
            'amount' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,

            ),
            'result_status' => array(
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => '0',

            ),
            'result_number' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,

            ),
            'result_message' => array(
                'type' => 'TEXT',
                'null' => TRUE,

            ),
            'funding_estimated_date' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE,

            ),
            '`date_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id",true);
        $this->dbforge->create_table("gateway_transfers", TRUE);
        $this->db->query('ALTER TABLE  `gateway_transfers` ENGINE = InnoDB');
    }

    public function down()	{
        ### Drop table gateway_transfers ##
        $this->dbforge->drop_table("gateway_transfers", TRUE);

    }

};