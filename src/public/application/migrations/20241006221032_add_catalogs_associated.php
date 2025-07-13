<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        ## Create Table catalogs_associated
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'catalog_id_from' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'catalog_id_to' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("catalogs_associated", TRUE);
        $this->db->query('ALTER TABLE  `catalogs_associated` ENGINE = InnoDB');
    }

    ### Drop table catalogs_associated
    public function down()
    {
        $this->dbforge->drop_table('catalogs_associated');
    }
};