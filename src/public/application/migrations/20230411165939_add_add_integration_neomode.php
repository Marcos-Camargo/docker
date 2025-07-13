<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $now = date("Y-m-d H:i:s");

        $results = $this->db->where('name', 'neomode')->get('integration_erps')->row_array();

        if (!$results) {
            $this->db->query("
                INSERT INTO integration_erps 
                    (name,description,`type`,hash,active,visible,support_link,image,user_created,user_updated,date_created,date_updated) 
                VALUES 
                    ('neomode','Neomode',2,'cbfc8904d9f9ca21d69ca864f9db61d6894d0f06',1,1,'[]','neomode.png',NULL,NULL,'$now','$now');
            ");
        } else {
            $this->db->where('id', $results['id'])->update('integration_erps', array( 'hash' => 'cbfc8904d9f9ca21d69ca864f9db61d6894d0f06'));
        }

    }

    public function down()	{
        $this->db->where('name', 'neomode')->where('hash', 'cbfc8904d9f9ca21d69ca864f9db61d6894d0f06')->delete('integration_erps');
    }
};