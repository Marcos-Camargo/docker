<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        /*$integrations = $this->db->select('id, store_id')
            ->where('integration', 'bling')
            ->get('api_integrations')
            ->result_array();

        foreach ($integrations as $integration) {
            $store_id = $integration['store_id'];

            $this->db->where('store_id', $store_id)
                ->group_start()
                    ->or_where('job_path', 'Integration_v2/Product/bling/CreateProduct')
                    ->or_where('job_path', 'Integration_v2/Product/bling/UpdateProduct')
                    ->or_where('job_path', 'Integration_v2/Product/bling/UpdatePriceStock')
                    ->or_where('job_path', 'Integration_v2/Order/CreateOrder')
                    ->or_where('job_path', 'Integration_v2/Order/UpdateStatus')
                ->group_end()
                ->delete('job_integration');

            $this->db->where('params', $store_id)
                ->group_start()
                    ->or_where('module_path', 'Integration_v2/Product/bling/CreateProduct')
                    ->or_where('module_path', 'Integration_v2/Product/bling/UpdateProduct')
                    ->or_where('module_path', 'Integration_v2/Product/bling/UpdatePriceStock')
                    ->or_where('module_path', 'Integration_v2/Order/CreateOrder')
                    ->or_where('module_path', 'Integration_v2/Order/UpdateStatus')
                ->group_end()
                ->delete('calendar_events');

            $this->db->where('params', $store_id)
                ->group_start()
                    ->or_where('module_path', 'Integration_v2/Product/bling/CreateProduct')
                    ->or_where('module_path', 'Integration_v2/Product/bling/UpdateProduct')
                    ->or_where('module_path', 'Integration_v2/Product/bling/UpdatePriceStock')
                    ->or_where('module_path', 'Integration_v2/Order/CreateOrder')
                    ->or_where('module_path', 'Integration_v2/Order/UpdateStatus')
                ->group_end()
                ->delete('job_schedule');

            $this->db->where('id', $integration['id'])->delete('api_integrations');
        }*/
    }

	public function down()	{}
};