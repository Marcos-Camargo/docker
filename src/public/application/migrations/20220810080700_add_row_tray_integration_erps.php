<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("INSERT INTO integration_erps (`description`,`name`,`type`,`hash`,`active`,`support_link`,`image`) VALUES ('Tray','tray',1,NULL,0,NULL,'tray.png');");
	 }

	public function down()	{
        $this->db->query("DELETE FROM integration_erps WHERE `name` = 'tray';");
	}
};