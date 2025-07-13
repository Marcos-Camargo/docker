<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        $sql = "ALTER TABLE conciliacao ADD COLUMN param_mkt_ciclo_ids_adicionais TEXT NULL;";
        $this->db->query($sql);
    }

    public function down()
    {
        $sql = "ALTER TABLE conciliacao DROP COLUMN param_mkt_ciclo_ids_adicionais;";
        $this->db->query($sql);
    }
};