<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("
            INSERT INTO job_schedule
            (module_path, module_method, params, status, finished, error, error_count, error_msg, date_start, date_end, server_id, alert_after, start_alert, server_batch_ip)
            select 'Script/FixTrashedSkuIntegrationv2', 'run', alfi.store_id, 0, 0, NULL, 0, NULL, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL, 0, null, NULL, NULL 
            FROM anymarket_log_fix_id AS alfi
            JOIN products AS p ON p.id = alfi.existing
            WHERE alfi.copied = 0
              AND alfi.existing IS NOT NULL
              AND p.status != 3
              AND alfi.id > 0
              AND alfi.created_at > '2025-06-24 00:00:00'
            group by alfi.store_id
            ORDER BY alfi.id;
        ");
    }

	public function down()	{
	}
};