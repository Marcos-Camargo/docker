<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {


        if (!$this->dbforge->index_exists('index_model_id_user_id', 'campaign_v2_logs')){
            ## Create index index_model_id_user_id ##
            $this->db->query('CREATE INDEX index_model_id_user_id ON campaign_v2_logs (model_id, user_id);');
        }
        if (!$this->dbforge->index_exists('index_name_active', 'attributes')){
            ## Create index index_name_active ##
            $this->db->query('CREATE INDEX index_name_active ON attributes (name, active);');
        }

    }

	public function down()	{
		### Drop index index_pathProd ##
        $this->db->query('DROP INDEX index_model_id_user_id ON campaign_v2_logs;');
        $this->db->query('DROP INDEX index_name_active ON attributes;');

	}

};