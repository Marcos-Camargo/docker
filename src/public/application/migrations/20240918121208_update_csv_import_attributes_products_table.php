<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->query('ALTER TABLE csv_import_attributes_products DROP FOREIGN KEY csv_import_attributes_products_ibfk_1;');

        $fieldUpdate = array(
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
            ),
        );

        $this->dbforge->modify_column('csv_import_attributes_products', $fieldUpdate);
    }

    public function down() {}
};
