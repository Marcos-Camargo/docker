<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("
            INSERT INTO job_schedule
            (module_path, module_method, params, status, finished, error, error_count, error_msg, date_start, date_end, server_id, alert_after, start_alert, server_batch_ip)
            select 'Script/FixTrashedSkuIntegration', 'run', alfi.store_id, 0, 0, NULL, 0, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, null, NULL, NULL FROM anymarket_log_fix_id AS alfi
            JOIN prd_to_integration pti ON pti.prd_id = alfi.prd_id
            WHERE alfi.copied = 0
              AND alfi.error != 'Imagem com erro do nome da pasta'
              AND alfi.error NOT LIKE '%Não foi possível enviar o produto pra lixeira%'
              AND alfi.created_at > '2025-06-24 00:00:00'
              AND alfi.existing is null
            GROUP BY alfi.store_id
            ORDER BY alfi.id ASC;
        ");
    }

	public function down()	{
	}
};