<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $results = $this->db->where('description', 'Shopping de Preços')->get('integration_erps')->result_array();

        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => null,
                'description'   => 'Shopping de Preços',
                'type'          => 2,
                'hash'          => '6B1F5FAB-6E44-A3CB-1421-65AEF63B1727',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'shopping_de_precos.png'
            ));
        } else {
            if (count($results) === 1) {
                $this->db->where('id', $results[0]['id'])->update('integration_erps', array('name' => null, 'hash' => '6B1F5FAB-6E44-A3CB-1421-65AEF63B1727'));
            } else {
                foreach ($results as $result) {
                    if ($result['hash'] != '6B1F5FAB-6E44-A3CB-1421-65AEF63B1727') {
                        $this->db->where('id', $result['id'])->delete('integration_erps');
                    } else {
                        $this->db->where('id', $result['id'])->update('integration_erps', array('name' => null));
                    }
                }
            }
        }

        // Se existe valor, mas o hash nãoo bateu, faz uma nova consulta, para garantir que vai criar.
        $results = $this->db->where('description', 'Shopping de Preços')->get('integration_erps')->result_array();
        if (empty($results)) {
            $this->db->insert("integration_erps", array(
                'name'          => null,
                'description'   => 'Shopping de Preços',
                'type'          => 2,
                'hash'          => '6B1F5FAB-6E44-A3CB-1421-65AEF63B1727',
                'active'        => 1,
                'visible'       => 1,
                'support_link'  => '[]',
                'image'         => 'shopping_de_precos.png'
            ));
        }
	 }

	public function down()	{
        $this->db->query("DELETE FROM integration_erps WHERE `description` = 'Shopping de Preços';");
	}
};