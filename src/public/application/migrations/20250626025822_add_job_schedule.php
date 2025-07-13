<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->update('settings', ['status' => 1], ['name' => 'ignore_integration_inactive_store']);
        $this->db->query("
            INSERT INTO job_schedule
            (module_path, module_method, params, status, finished, error, error_count, error_msg, date_start, date_end, server_id, alert_after, start_alert, server_batch_ip)
            select 'Script/FixTrashedSkuIntegration', 'run', alfi.store_id, 0, 0, NULL, 0, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, null, NULL, NULL from anymarket_log_fix_id alfi
            group by alfi.store_id;
        ");
    }

	public function down()	{
	}
};