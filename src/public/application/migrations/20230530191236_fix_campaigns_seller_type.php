<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->query("UPDATE campaign_v2
                            INNER JOIN campaign_v2_logs 
                            ON campaign_v2_logs.model_id = campaign_v2.id 
                            AND campaign_v2_logs.method = 'create' 
                            AND campaign_v2_logs.model = 'campaign_v2'
                            INNER JOIN users 
                            ON users.id = campaign_v2_logs.user_id
                            SET campaign_v2.seller_type = 2
                            WHERE campaign_v2.seller_type = 1 
                            AND users.store_id = 0");
    }
    public function down()	{
    }
};