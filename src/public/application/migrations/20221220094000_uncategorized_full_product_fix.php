<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->where(['category_id' => '[""]', 'situacao' => 2])->update("products", array('situacao' => 1));
    }

    public function down() {}
};