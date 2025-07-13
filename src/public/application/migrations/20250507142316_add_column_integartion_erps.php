<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $fieldUpdates = array(
            'external_integration_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'after' => 'fields_form'
            )
        );

        if (!$this->dbforge->column_exists('external_integration_id', 'integrations_logistic')) {
            $this->dbforge->add_column('integrations_logistic', $fieldUpdates);
        }

        $this->db->query('ALTER TABLE `integrations_logistic` ADD CONSTRAINT `FK_integrations_logistic_external_integration_id` FOREIGN KEY (`external_integration_id`) REFERENCES `integration_erps` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
	}

	public function down()	{
		### Drop column catalogs
		$this->dbforge->drop_column("integrations_logistic", 'external_integration_id');

	}
};