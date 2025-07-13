<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->query("INSERT INTO `cut_date_cycle` (`cut_date`) VALUES ('Data Entrega')");
        $this->db->query("INSERT INTO `cut_date_cycle` (`cut_date`) VALUES ('Data Envio')");
    }

    public function down()
    {
        $this->db->query('DELETE FROM cut_date_cycle WHERE cut_date like "Data Entrega";');
        $this->db->query('DELETE FROM cut_date_cycle WHERE cut_date like "Data Envio";');
    }
};