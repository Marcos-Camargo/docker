<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->where('name', 'loja_integrada')
            ->update('integration_erps', [
                'name' => 'lojaintegrada',
            ]);

	 }

	public function down()	{
        $this->db->where('name', 'lojaintegrada')
            ->update('integration_erps', [
                'name' => 'loja_integrada',
            ]);
	}
};