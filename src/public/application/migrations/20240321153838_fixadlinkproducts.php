<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new 

/**
 * @property prd_to_integration $prd_to_integration
 */

class extends CI_Migration
{

	public function up()
	{
		$seller_center  = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();

        if (!in_array($seller_center['value'], array(
            'Angeloni'
        ))) {
            echo " - Não deve executar a migration para o cliente.";
            return;
        }

		$products = $this->db->query(
			"select
				*
			from prd_to_integration
			where
				ad_link not LIKE '%/eletro/%'
			order by id desc;"
		)->result_array();

		foreach ($products as $key => $product) {
			$url = $product["ad_link"];
			$url_modificada = preg_replace('/(https:\/\/www\.angeloni\.com\.br)(\/.*)/', '$1/eletro$2', $url);

			$this->db->where(['id' => $product["id"]])->update("prd_to_integration", array('ad_link' => $url_modificada));
			$this->db->where(['id' => $product["prd_id"]])->update("products", array('date_update' => date('Y-m-d H:i:s')));
		}
	}

	public function down()
	{

	}
};