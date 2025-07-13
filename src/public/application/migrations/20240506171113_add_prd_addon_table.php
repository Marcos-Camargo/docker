<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

		## Create Table prd_addon
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'prd_id_addon' => array(
				'type' => 'INT',
				'constraint' => ('11'),
				'null' => FALSE,
			),
            'store_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'company_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,
            ),
            'prd_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => FALSE,

            ),
			'`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
		));
		$this->dbforge->add_key("id",true);
		$this->dbforge->create_table("prd_addon", TRUE);
	 }

	public function down()	{
		### Drop table prd_addon ##
		$this->dbforge->drop_table("prd_addon", TRUE);

	}
};