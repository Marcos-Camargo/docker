<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        

        $use_ms_shipping = $this->db->where('name', 'use_ms_shipping')->get('settings')->row_array();
        $sellercenter = $this->db->where('name', 'sellercenter')->get('settings')->row_array();
        if ($use_ms_shipping && $use_ms_shipping['status'] == 1 && strtotime('2024-04-05 11:45:00') > strtotime(date('Y-m-d H:i:s')) && $sellercenter['value'] != 'pitstop') {
            $this->db->query('INSERT INTO queue_products_marketplace (status,prd_id) SELECT 0,prd_id FROM vtex_ult_envio WHERE data_ult_envio > "2024-04-04 11:20:00"');
        }
    }

    public function down()	{

    }

};