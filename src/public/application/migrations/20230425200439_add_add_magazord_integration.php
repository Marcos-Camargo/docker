<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $now = date("Y-m-d H:i:s");

        $results = $this->db->where('name', 'magazord')->get('integration_erps')->row_array();

        if (!$results) {
            $this->db->query("
                INSERT INTO integration_erps 
                    (name,description,`type`,hash,active,visible,support_link,image,user_created,user_updated,date_created,date_updated) 
                VALUES 
                    ('magazord','Magazord',2,'1310f8d305dcdc5a0c8ea59488a5f6ff5442c492',1,1,'[]','magazord.png',NULL,NULL,'$now','$now');
            ");
        } else {
            $this->db->where('id', $results['id'])->update('integration_erps', array( 'hash' => '1310f8d305dcdc5a0c8ea59488a5f6ff5442c492'));
        }

    }

    public function down()	{
        $this->db->where('name', 'magazord')->where('hash', '1310f8d305dcdc5a0c8ea59488a5f6ff5442c492')->delete('integration_erps');
    }
};