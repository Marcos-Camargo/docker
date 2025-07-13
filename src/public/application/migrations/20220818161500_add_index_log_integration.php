<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if (!$this->dbforge->index_exists('filter_edit_order', 'log_integration')){

            ## Create index filter_edit_order ##
            $this->db->query('CREATE INDEX filter_edit_order ON log_integration (store_id, unique_id, job(50), date_updated);');

        }

	 }

	public function down()	{
		### Drop index filter_edit_order ##
        $this->db->query('DROP INDEX filter_edit_order ON log_integration;');

	}
};