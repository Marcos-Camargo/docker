<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if ($this->db->where('name', 'server_async_job_quantity_to_update_products_anymarket')->get('settings')->num_rows() === 0) {
            $this->db->insert('settings', array(
                'name' => "server_async_job_quantity_to_update_products_anymarket",
                'value' => '{"all":10}',
                'status' => 2,
                'user_id' => 1,
                'setting_category_id' => 4,
                'friendly_name' => 'Irá criar paralelismo no job de atualização/criação de produto da anymarket',
                'description' => 'Irá criar paralelismo no job de atualização/criação de produto da anymarket. Informe no json o código da loja para criar uma exceção, mas sempre mantendo o "all", como: {"all":10,"29":50}'
            ));
        }
    }

	public function down()	{
        $this->db->where('name', 'server_async_job_quantity_to_update_products_anymarket')->delete('settings');
	}
};