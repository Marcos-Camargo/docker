<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{

		if (!$this->dbforge->register_exists('cep', 'zipcode', '88803170'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "88803170",
                'city'     		=> 'Criciúma',
                'state'    		=> 'SC',
                'address'    		=> 'Rua Palestina',
                'neighborhood'    	=> 'Pinheirinho',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '88801003'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "88801003",
                'city'     		=> 'Criciúma',
                'state'    		=> 'SC',
                'address'    		=> 'Rua David Cont',
                'neighborhood'    	=> 'Centro',
                'capital'    		=> 0
            ));
		}

		if (!$this->dbforge->register_exists('cep', 'zipcode', '88801013'))
		{
			$this->db->insert('cep', array(
                'zipcode'     	=> "88801013",
                'city'     		=> 'Criciúma',
                'state'    		=> 'SC',
                'address'    		=> 'Travessa Júlio César Spílere',
                'neighborhood'    	=> 'Centro',
                'capital'    		=> 0
            ));
		}

	}

	public function down()
	{
		$this->db->where('cep', '88803170')->delete('cep');
	  	$this->db->where('cep', '88801003')->delete('cep');
	  	$this->db->where('cep', '88801013')->delete('cep');
	}
};