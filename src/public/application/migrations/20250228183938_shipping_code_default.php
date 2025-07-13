<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if (!$this->dbforge->register_exists('settings', 'name', 'shipping_code_default')){
            $this->db->query("INSERT INTO settings (`name`, `value`, `status`, `user_id`, `setting_category_id`, `friendly_name`, `description`) VALUES (
                'shipping_code_default',
                'Código de Rastreio Padrão', 
                '2',  
                '1',  
                '5',
                'Código de Rastreio Padrão',     
                'Este parâmetro utilizará o código definido no campo valor para preencher o código de rastreio na atualização de pedidos para enviado quando não for enviada um código pelas integrações que utilizam a API.'
                )"
            );
        }
	}

	public function down()	{
		$this->db->query("DELETE FROM settings where name = 'shipping_code_default'");
	}
};