<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		$this->db->query("
		update users 
					set active = 2
		where email like '%@conectala.com.br' and email not in ('agathateixeira@conectala.com.br','alexandrealvarenga@conectala.com.br','alexiatasca@conectala.com.br','andrerisi@conectala.com.br',
		'arthurbastos@conectala.com.br','brunacaldieri@conectala.com.br','carlosjunior@conectala.com.br','carolinagarcia@conectala.com.br',
		'ceciliabonelli@conectala.com.br','dilneispancerski@conectala.com.br','fabioribeiro@conectala.com.br','fernandoschumacher@conectala.com.br',
		'filippemey@conectala.com.br','gabrielesalenave@conectala.com.br','gustavohermsdorff@conectala.com.br','higoralves@conectala.com.br',
		'jamillealmeida@conectala.com.br','jessicapillar@conectala.com.br','leticiakruse@conectala.com.br','mariafernandaribeiro@conectala.com.br',
		'monisereos@conectala.com.br','pedrobraga@conectala.com.br','pedrohenrique@conectala.com.br',
		'rafaelsouza@conectala.com.br','talitaalbino@conectala.com.br','tatiananobrega@conectala.com.br','vandressascheron@conectala.com.br',
		'vanessafilon@conectala.com.br','vivianemarczak@conectala.com.br');
		");

		$senha = "$2y$10\$TedmEKdfTDciuYJa7Mr8WuzSIoL3Mn2z5SfIMdmTJQhbO9mSjjSoS";

		$this->db->query("
		update users set 
					password = '$senha', external_authentication_id = null, last_change_password = '2023-01-01', active = 1
		where email in ('agathateixeira@conectala.com.br','alexandrealvarenga@conectala.com.br','alexiatasca@conectala.com.br','andrerisi@conectala.com.br',
		'arthurbastos@conectala.com.br','brunacaldieri@conectala.com.br','carlosjunior@conectala.com.br','carolinagarcia@conectala.com.br',
		'ceciliabonelli@conectala.com.br','dilneispancerski@conectala.com.br','fabioribeiro@conectala.com.br','fernandoschumacher@conectala.com.br',
		'filippemey@conectala.com.br','gabrielesalenave@conectala.com.br','gustavohermsdorff@conectala.com.br','higoralves@conectala.com.br',
		'jamillealmeida@conectala.com.br','jessicapillar@conectala.com.br','leticiakruse@conectala.com.br','mariafernandaribeiro@conectala.com.br',
		'monisereos@conectala.com.br','pedrobraga@conectala.com.br','pedrohenrique@conectala.com.br',
		'rafaelsouza@conectala.com.br','talitaalbino@conectala.com.br','tatiananobrega@conectala.com.br','vandressascheron@conectala.com.br',
		'vanessafilon@conectala.com.br','vivianemarczak@conectala.com.br');
		");
	}

	public function down()	{
	}
};