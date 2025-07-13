<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'migrate_status' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
                'default' => 0
            )
        );

        if (!$this->dbforge->column_exists('migrate_status', 'seller_migration_register')) {
            $this->dbforge->add_column('seller_migration_register', $fieldUpdates);
        }

        $this->db->where('end_date IS NOT NULL', null, false)
            ->where('status', 1)
            ->update('seller_migration_register', array('migrate_status' => 1));
    }

    public function down()
    {
        $this->dbforge->drop_column('seller_migration_register', 'migrate_status');
    }
};