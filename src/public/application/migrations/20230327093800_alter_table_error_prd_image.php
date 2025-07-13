<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'error' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('error', 'prd_image')) {
            $this->dbforge->add_column('prd_image', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('prd_image', 'error');
    }
};