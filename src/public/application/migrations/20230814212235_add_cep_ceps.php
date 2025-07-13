<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{

		if (!$this->dbforge->register_exists('cep', 'zipcode', '88352155'))
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

		if (!$this->dbforge->register_exists('cep', 'zipcode', '17511731'))
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

		if (!$this->dbforge->register_exists('cep', 'zipcode', '17511731'))
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

		if (!$this->dbforge->register_exists('cep', 'zipcode', '17511731'))
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

	}

	public function down()
	{
		$this->db->where('cep', '86083040')->delete('cep');
	  	$this->db->where('cep', '78210862')->delete('cep');
		$this->db->where('cep', '95210164')->delete('cep');
		$this->db->where('cep', '17280350')->delete('cep');
	}
};