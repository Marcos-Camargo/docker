<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdates = array(
            'external_integration_id' => array(
                'type' => 'INT',
                'unsigned' => FALSE,
                'constraint' => 11,
                'null' => TRUE
            )
        );

        if (!$this->dbforge->column_exists('external_integration_id', 'integration_logistic')) {
            $this->dbforge->add_column('integration_logistic', $fieldUpdates);
        }

        $this->db->query('ALTER TABLE `integration_logistic` ADD CONSTRAINT `FK_integration_logistic_external_integration_id` FOREIGN KEY (`external_integration_id`) REFERENCES `integration_erps` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE integration_logistic DROP FOREIGN KEY FK_integration_logistic_external_integration_id;');
        $this->dbforge->drop_column('integration_logistic', 'external_integration_id');
    }
};