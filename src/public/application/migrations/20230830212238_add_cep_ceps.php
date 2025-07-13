<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('cep', 'zipcode', '16206547'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "16206547",
                'city'     		=> 'Birigüi',
                'state'    		=> 'SP',
                'address'    		=> 'Alameda Bristol',
                'neighborhood'    	=> 'Parque das Árvores II',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '36200326'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "36200326",
                'city'     		=> 'Barbacena',
                'state'    		=> 'MG',
                'address'    		=> 'Rua Eduardo Prenassi',
                'neighborhood'    	=> 'Campo',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '88813138'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "88813138",
                'city'     		=> 'Criciúma',
                'state'    		=> 'SC',
                'address'    		=> 'Rua Sebastião Costa',
                'neighborhood'    	=> 'Brasília',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '74492435'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "74492435",
                'city'     		=> 'Goiânia',
                'state'    		=> 'GO',
                'address'    		=> 'Rua do Molinesia',
                'neighborhood'    	=> 'Residencial Santa Efigênia',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '89235130'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "89235130",
                'city'     		=> 'Joinville',
                'state'    		=> 'SC',
                'address'    		=> 'Rua José Satiro de Oliveira',
                'neighborhood'    	=> 'Boehmerwald',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '85819570'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "85819570",
                'city'     		=> 'Cascavel',
                'state'    		=> 'PR',
                'address'    		=> 'Rua José Caldart',
                'neighborhood'    	=> 'Maria Luiza',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '85819570'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "85819570",
                'city'     		=> 'Cascavel',
                'state'    		=> 'PR',
                'address'    		=> 'Rua José Caldart',
                'neighborhood'    	=> 'Maria Luiza',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '16206547'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "16206547",
                'city'     		=> 'Birigüi',
                'state'    		=> 'SP',
                'address'    		=> 'Alameda Bristol',
                'neighborhood'    	=> 'Parque das Árvores II',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '36200326'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "36200326",
                'city'     		=> 'Barbacena',
                'state'    		=> 'MG',
                'address'    		=> 'Rua Eduardo Prenassi',
                'neighborhood'    	=> 'Campo',
                'capital'    		=> 0
            ));
		}
        
    }

	public function down()
	{
		$this->db->where('cep', '16206547')->delete('cep');
	  	$this->db->where('cep', '36200326')->delete('cep');
	  	$this->db->where('cep', '88813138')->delete('cep');
	  	$this->db->where('cep', '74492435')->delete('cep');
	  	$this->db->where('cep', '89235130')->delete('cep');
	  	$this->db->where('cep', '85819570')->delete('cep');
	  	$this->db->where('cep', '16206547')->delete('cep');
	  	$this->db->where('cep', '36200326')->delete('cep');
	}
};