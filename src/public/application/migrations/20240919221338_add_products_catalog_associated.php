<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {

        ## Create Table products_catalog_associated
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'catalog_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'original_catalog_product_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'catalog_product_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'product_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'store_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'company_id' => array(
                'type' => 'INT',
                'null' => FALSE,

            ),
            'status' => array(
                'type' => 'TINYINT',
                'null' => FALSE,

            ),
            '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("products_catalog_associated", TRUE);
        $this->db->query('ALTER TABLE  `products_catalog_associated` ENGINE = InnoDB');
    }

    ### Drop table products_catalog_associated
    public function down()
    {
        $this->dbforge->drop_table('products_catalog_associated');
    }
};