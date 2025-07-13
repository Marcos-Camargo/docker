<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up()
	{
		// Seta todos os CSVs anteriores como já deletados.
		// Passo da migração para o bucket.
		$date = new DateTime();
		$this->db->query(
			"
			UPDATE csv_generator_export cge
			SET cge.file_delete_date = ?
			WHERE cge.file_delete_date IS NULL
			",
			$date->format('Y-m-d H:i:s')
		);
	}

	public function down() {}
};
