<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fields = array(
            'skumkt_default' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'null'  => TRUE,
                'default' => 'conectala'
            ),
            'skumkt_sequential_initial_value' => array(
                'type' => 'BIGINT',
                'null'  => TRUE,
                'default' => NULL
            )
        );

        $this->dbforge->add_column('integrations_settings', $fields);
	}

	public function down()	{
        $this->dbforge->drop_column('integrations_settings', 'skumkt_default');
        $this->dbforge->drop_column('integrations_settings', 'skumkt_sequential_initial_value');
    }
};