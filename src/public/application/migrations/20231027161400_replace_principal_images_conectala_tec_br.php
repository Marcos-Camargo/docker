<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        $this->db->set('principal_image', 'REPLACE(principal_image, "conectala.tec.br", "conectala.com.br")', false)
            ->where('status !=', 3)
            ->update('products');

        if (ENVIRONMENT !== 'production' && ENVIRONMENT !== 'production_x') {
            echo "\nLinhas alteradas: " . $this->db->affected_rows()."\n";
        }
    }

	public function down()	{
        // Não tem
	}
};