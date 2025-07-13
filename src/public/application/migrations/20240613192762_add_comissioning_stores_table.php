<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
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
        $this->dbforge->create_table('commissioning_stores', TRUE);
        $this->db->query("ALTER TABLE `commissioning_stores` ADD CONSTRAINT `commissioning_stores_ibfk_1` FOREIGN KEY (`commissioning_id`) REFERENCES `commissionings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
        $this->db->query("ALTER TABLE `commissioning_stores` ADD CONSTRAINT `commissioning_stores_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
    }

    public function down() {
        ## Drop table commissioning_brands ##
        $this->dbforge->drop_table('commissioning_stores', TRUE);
    }
};
