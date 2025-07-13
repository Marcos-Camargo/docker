<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $fieldUpdates = array(
            'label_required' => array(
                'type' => 'TINYINT',
                'unsigned' => FALSE,
                'constraint' => 1,
                'null' => FALSE,
                'default' => 0,
                'after' => 'provider_id'
            )
        );

        if (!$this->dbforge->column_exists('label_required', 'integration_erps')) {
            $this->dbforge->add_column('integration_erps', $fieldUpdates);
        }
	}

	public function down()	{
		### Drop column catalogs
		$this->dbforge->drop_column("integration_erps", 'label_required');

	}
};