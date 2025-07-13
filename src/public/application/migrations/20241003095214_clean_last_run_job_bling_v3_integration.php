<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        $this->db->update('job_integration', array(
            'last_run' => null
        ), array(
            'job_path' => 'Integration_v2/Product/bling_v3/UpdatePriceStock'
        ));
    }

    public function down() {}

};