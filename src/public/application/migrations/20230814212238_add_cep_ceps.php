<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('cep', 'zipcode', '86083040'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "86083040",
                'city'     		=> 'Londrina',
                'state'    		=> 'PR',
                'address'    		=> 'Rua Ody Silveira',
                'neighborhood'    	=> 'Alto da Boa Vista',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '78210862'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "78210862",
                'city'     		=> 'Cáceres',
                'state'    		=> 'MT',
                'address'    		=> 'Rua da Fé',
                'neighborhood'    	=> 'Espírito Santo',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '95210164'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "95210164",
                'city'     		=> 'Vacaria',
                'state'    		=> 'RS',
                'address'    		=> 'Rua Ipê',
                'neighborhood'    	=> 'Planalto',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '17280350'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "17280350",
                'city'     		=> 'Pederneiras',
                'state'    		=> 'SP',
                'address'    		=> 'Rua José Gonçalves Dias Norte',
                'neighborhood'    	=> 'Jardim Castelo',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '38412597'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "38412597",
                'city'     		=> 'Uberlândia',
                'state'    		=> 'MG',
                'address'    		=> 'Rua Catuaba',
                'neighborhood'    	=> 'Panorama',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '35701783'))
		{
			$this->db->insert('cep', array(
                'zipcode'     		=> "35701783",
                'city'     			=> 'Sete Lagoas',
                'state'    			=> 'MG',
                'address'    		=> 'Rua Maria Ferreira Saraiva',
                'neighborhood'    	=> 'São Cristóvão II',
                'capital'    		=> 0
            ));
		}
	}

	public function down()
	{
		$this->db->where('cep', '86083040')->delete('cep');
	  	$this->db->where('cep', '78210862')->delete('cep');
		$this->db->where('cep', '95210164')->delete('cep');
		$this->db->where('cep', '17280350')->delete('cep');
		$this->db->where('cep', '38412597')->delete('cep');
		$this->db->where('cep', '35701783')->delete('cep');
	}
};