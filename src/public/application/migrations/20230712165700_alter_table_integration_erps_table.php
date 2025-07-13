<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'provider_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE,
                'default' => NULL,
                'after' => 'image'
            )
        );

        if (!$this->dbforge->column_exists('provider_id', 'integration_erps')) {
            $this->dbforge->add_column('integration_erps', $fieldUpdates);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('integration_erps', 'provider_id');
    }
};