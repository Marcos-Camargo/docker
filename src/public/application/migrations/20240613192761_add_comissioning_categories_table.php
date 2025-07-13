<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        ## Create Table commissioning_categories
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'commissioning_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE
            ),
            'category_id' => array(
                'type' => 'INT',
                'constraint' => 11
            ),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => 11
            ),
            'comission' => array(
                'type' => 'DECIMAL',
                'constraint' => '5,2'
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('commissioning_categories', TRUE);
        $this->db->query("ALTER TABLE `commissioning_categories` ADD CONSTRAINT `commissioning_categories_ibfk_1` FOREIGN KEY (`commissioning_id`) REFERENCES `commissionings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
        $this->db->query("ALTER TABLE `commissioning_categories` ADD CONSTRAINT `commissioning_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
        $this->db->query("ALTER TABLE `commissioning_categories` ADD CONSTRAINT `commissioning_categories_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
    }

    public function down() {
        ## Drop table commissioning_categories ##
        $this->dbforge->drop_table('commissioning_categories', TRUE);
    }
};
