<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if ($this->db
                ->where('description', 'Shopping de Preços')
                ->where('name IS NULL', NULL, FALSE)
                ->get('integration_erps')
                ->num_rows()
        ) {
            $this->db->where('description', 'Shopping de Preços')->update('integration_erps', array('name' => "shopping_de_pre_os"));
        }
        if ($this->db
            ->where('description', 'Bling Nativo')
            ->where('name IS NULL', NULL, FALSE)
            ->get('integration_erps')
            ->num_rows()
        ) {
            $this->db->where('description', 'Bling Nativo')->update('integration_erps', array('name' => "bling_nativo"));
        }
    }

    public function down()	{
    }
};