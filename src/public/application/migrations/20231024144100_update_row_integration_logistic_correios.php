<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
        $this->db->update(
            "integrations_logistic",
            array(
                'fields_form' => '{"user":{"name":"application_user","type":"text"},"password":{"name":"application_password","type":"password"},"post_card":{"name":"application_post_card","type":"text"},"contract":{"name":"application_contract","type":"text"},"type_contract":{"name":"application_type_contract","type":"radio","values":{"application_old":"old","application_new":"new"}},"services":{"name":"application_services","type":"checkbox","values":{"application_service_correios_mini":"mini","application_service_correios_pac":"pac","application_service_correios_sedex":"sedex"}}}'
            ), array(
                'name' => 'correios'
            )
        );
    }

	public function down()	{
        $this->db->update(
            "integrations_logistic",
            array(
                'fields_form' => '{"user":{"name":"application_user","type":"text"},"password":{"name":"application_password","type":"password"},"post_card":{"name":"application_post_card","type":"text"},"contract":{"name":"application_contract","type":"text"},"dr_se":{"name":"application_contract_dr_se","type":"text"},"type_contract":{"name":"application_type_contract","type":"radio","values":{"application_old":"old","application_new":"new"}},"services":{"name":"application_services","type":"checkbox","values":{"application_service_correios_mini":"mini","application_service_correios_pac":"pac","application_service_correios_sedex":"sedex"}}}'
            ), array(
                'name' => 'correios'
            )
        );
	}
};