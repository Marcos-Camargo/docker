<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{

		if (!$this->dbforge->register_exists('cep', 'zipcode', '35930140'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "35930140",
                'city'     		=> 'João Monlevade',
                'state'    		=> 'MG',
                'address'    		=> 'Rua Magnólia',
                'neighborhood'    	=> 'Recanto Paraíso',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '35931003'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "35931003",
                'city'     		=> 'João Monlevade',
                'state'    		=> 'MG',
                'address'    		=> 'Rua Trinta e Oito',
                'neighborhood'    	=> 'Loanda',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '77023414'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "77023414",
                'city'     		=> 'Palmas',
                'state'    		=> 'TO',
                'address'    		=> 'Quadra ARSE 92 Alameda 18',
                'neighborhood'    	=> 'Plano Diretor Sul',
                'capital'    		=> 0
            ));
		}

	}

	public function down()
	{
		$this->db->where('cep', '35930140')->delete('cep');
	  	$this->db->where('cep', '35931003')->delete('cep');
	  	$this->db->where('cep', '77023414')->delete('cep');
	}
};