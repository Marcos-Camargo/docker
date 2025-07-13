<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        if ($this->db->where('name', 'hire_automatic_freight')->get('settings')->num_rows() === 0) {

            // Se for conectala, entra como ativo.
            $sellerCenter = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();
            $status = $sellerCenter && $sellerCenter['value'] === 'conectala' ? 1 : 2;

            $this->db->insert('settings', array(
                'name' => "hire_automatic_freight",
                'value' => 'Quando habilitado, a contratação do frete para os gateways, não precisarão ser aprovadas.',
                'status' => $status,
                'user_id' => 1
            ));
        }
	 }

	public function down()	{
        $this->db->where('name', 'hire_automatic_freight')->delete('settings');
	}
};