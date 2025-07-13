<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        if (!$this->dbforge->register_exists('integrations_logistic', 'name', 'correios')) {
            $this->db->insert("integrations_logistic", array(
                'name' => 'correios',
                'description' => 'Correios',
                'use_sellercenter' => 0,
                'use_seller' => 0,
                'active' => 1,
                'only_store' => 0,
                'fields_form' => '{"user":{"name":"application_user","type":"text"},"password":{"name":"application_password","type":"password"},"post_card":{"name":"application_post_card","type":"text"},"contract":{"name":"application_contract","type":"text"},"dr_se":{"name":"application_contract_dr_se","type":"text"},"type_contract":{"name":"application_type_contract","type":"radio","values":{"application_old":"old","application_new":"new"}},"services":{"name":"application_services","type":"checkbox","values":{"application_service_correios_mini":"mini","application_service_correios_pac":"pac","application_service_correios_sedex":"sedex"}}}'
            ));
        }
    }

	public function down()	{
        $this->db->query("DELETE FROM integrations_logistic WHERE `name` = 'correios'");
	}
};