<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table stores_multi_channel_fulfillment
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'store_id_principal' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'store_id_cd' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE
            ),
            'zipcode_start' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => FALSE
            ),
            'zipcode_end' => array(
                'type' => 'VARCHAR',
                'constraint' => ('8'),
                'null' => FALSE
            ),
            '`date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("stores_multi_channel_fulfillment", TRUE);

        $this->db->query('CREATE INDEX ix_store_id_cd ON stores_multi_channel_fulfillment (store_id_cd);');
        $this->db->query('CREATE INDEX ix_store_id_principal_zipcode_start_end ON stores_multi_channel_fulfillment (store_id_principal, zipcode_start, zipcode_end);');
	}

	public function down()	{
        $this->db->query('ALTER TABLE `stores_multi_channel_fulfillment` DROP INDEX `ix_store_id_cd`;');
        $this->db->query('ALTER TABLE `stores_multi_channel_fulfillment` DROP INDEX `ix_store_id_principal_zipcode_start_end`;');
        $this->dbforge->drop_table("stores_multi_channel_fulfillment", TRUE);
	}
};
