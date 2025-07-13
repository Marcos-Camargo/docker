<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $stores = $this->db->query("select * from (SELECT distinct alfi.store_id FROM anymarket_log_fix_id AS alfi
            join products p on p.id = alfi.prd_id
            WHERE p.sku = ''
            group by alfi.prd_id) AS store_id
            
            UNION
            
            select * from (SELECT distinct alfi.store_id FROM anymarket_log_fix_id AS alfi
            join products p on p.id = alfi.existing 
            WHERE p.sku = '' and alfi.existing is not null
            group by alfi.existing) as store_id"
        )->result_array();

        foreach ($stores as $store) {
            $this->db->query("
            INSERT INTO job_schedule
            (module_path, module_method, params, status, finished, error, error_count, error_msg, date_start, date_end, server_id, alert_after, start_alert, server_batch_ip) values 
            ('Script/FixTrashedSkuIntegrationv3', 'run', $store[store_id], 0, 0, NULL, 0, NULL, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, null, NULL, NULL);");
        }
    }

	public function down()	{
	}
};