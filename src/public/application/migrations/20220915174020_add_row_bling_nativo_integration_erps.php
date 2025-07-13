<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('description', 'Bling Nativo')->get('integration_erps')->result_array();

        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => null,
                'description'   => 'Bling Nativo',
                'type'          => 2,
                'hash'          => 'e43dc13f8a7261607df7b453f16ef5539cbb4e52',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'bling_nativo.png'
            ));
        } else {
            if (count($results) === 1) {
                $this->db->where('id', $results[0]['id'])->update('integration_erps', array('name' => null, 'hash' => 'e43dc13f8a7261607df7b453f16ef5539cbb4e52'));
            } else {
                foreach ($results as $result) {
                    if ($result['hash'] != 'e43dc13f8a7261607df7b453f16ef5539cbb4e52') {
                        $this->db->where('id', $result['id'])->delete('integration_erps');
                    } else {
                        $this->db->where('id', $result['id'])->update('integration_erps', array('name' => null));
                    }
                }
            }
        }

        // Se existe valor, mas o hash nãoo bateu, faz uma nova consulta, para garantir que vai criar.
        $results = $this->db->where('description', 'Bling Nativo')->get('integration_erps')->result_array();
        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => null,
                'description'   => 'Bling Nativo',
                'type'          => 2,
                'hash'          => 'e43dc13f8a7261607df7b453f16ef5539cbb4e52',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'bling_nativo.png'
            ));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM description WHERE `description` = 'Bling Nativo';");
	}
};