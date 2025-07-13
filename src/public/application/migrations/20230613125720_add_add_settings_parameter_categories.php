<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {
    public function up()
    {

        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('90'),
                'null' => FALSE
            ),
            'icon' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => FALSE
            ),
            '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("settings_categories", TRUE);

        $data = array('name' => 'uncategorized', 'icon' => 'personalizado');
        $this->db->insert('settings_categories', $data);
    }

    public function down()
    {
        $this->dbforge->drop_table("settings_categories", TRUE);
    }
};