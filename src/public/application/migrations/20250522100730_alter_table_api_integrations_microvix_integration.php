<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->update(
            'integration_erps',
            ['name' => 'microvix'],
            ['name' => 'linx_microvix']
        );
    }

    public function down()
    {
        $this->db->update(
            'integration_erps',
            ['name' => 'linx_microvix'],
            ['name' => 'microvix']
        );
    }

};
