<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		// A tela de importação de CSV para catálogos foi alterada, marco cargas anteriores como processadas para evitar problemas.
		$this->db->query(
			"
			UPDATE csv_to_verification ctv
			SET ctv.checked = 1
			WHERE ctv.module = ?
			",
			"CatalogProductMarketplace"
		);
	}

	public function down() {}
};
