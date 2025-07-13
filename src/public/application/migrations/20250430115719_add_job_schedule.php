<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->query("
            INSERT INTO job_schedule
            (module_path, module_method, params, status, finished, error, error_count, error_msg, date_start, date_end, server_id, alert_after, start_alert, server_batch_ip)
            select 'Script/UpdateSkuInIntegration', 'run', ai.store_id, 0, 0, NULL, 0, NULL, now(), NULL, 0, null, NULL, NULL from api_integrations ai 
            where ai.integration = 'anymarket'
        ");
    }

	public function down()	{
	}
};