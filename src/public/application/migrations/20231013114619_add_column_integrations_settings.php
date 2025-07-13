<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	private $table_name = "integrations_settings";
	public function up() {

		$fields = array(
            'update_images_specifications' => array(
                'type' => 'TINYINT(1)',
                'default' => 1
            )
        );
		
		if(!$this->dbforge->column_exists('update_images_specifications', 'integrations_settings'))
		{
			$this->dbforge->add_column($this->table_name, $fields);
		}
	 }

	public function down()	{
		$this->dbforge->drop_table("integrations_settings", TRUE);
	}
};