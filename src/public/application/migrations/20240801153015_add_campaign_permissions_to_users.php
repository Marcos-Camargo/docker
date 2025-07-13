<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $result = $this->db->query("SELECT * FROM `groups`");

        foreach ($result->result_array() as $item){
            $permissions = unserialize($item['permission']);
            if (in_array('createCampaigns', $permissions) && !in_array('approveCampaignCreation', $permissions)){
                $permissions[] = 'approveCampaignCreation';
                $newPermissions = serialize($permissions);
                $this->db->query("UPDATE `groups` SET permission = '".$newPermissions."' WHERE id = ".$item['id']);
            }
        }

    }

	public function down()	{
	}
};