<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->where(['name' => 'sgpweb'])->update("integrations_logistic", array(
            'fields_form' => '{"token":{"name":"application_token","type":"text"},"cart":{"name":"application_card","type":"text"},"contract":{"name":"application_contract","type":"text"},"type_contract":{"name":"application_type_contract","type":"radio","values":{"application_old":"old","application_new":"new"}},"type_integration":{"name":"application_integration","type":"radio","values":{"application_sgpweb":"sgpweb","application_gestaoenvios":"gestaoenvios"}}}'
        ));
    }

    public function down() {
        $this->db->where(['name' => 'sgpweb'])->update("integrations_logistic", array(
            'fields_form' => '{"token":{"name":"application_token","type":"text"},"cart":{"name":"application_card","type":"text"},"contract":{"name":"application_contract","type":"text"},"type_contract":{"name":"application_type_contract","type":"radio","values":{"application_old":"old","application_new":"new"}}}'
        ));
    }
};