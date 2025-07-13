<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		## Create column integration_erps.only_store
        $fieldUpdate = array(
            'only_store' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0',
                'after' => 'active'
            )
        );
        if (!$this->dbforge->column_exists('only_store', 'integrations_logistic')) {
            $this->dbforge->add_column('integrations_logistic', $fieldUpdate);
        }
        $this->db->query("UPDATE integrations_logistic SET only_store = 1 WHERE name IN ('precode', 'pluggto', 'anymarket', 'vtex', 'viavarejo_b2b', 'tray', 'tiny', 'hub2b')");
	 }

	public function down()	{
        $this->dbforge->drop_column('integrations_logistic', 'only_store');
	}
};