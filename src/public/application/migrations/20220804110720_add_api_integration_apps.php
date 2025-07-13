<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration {

	public function up() {

		## Create Table api_integration_apps
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,
				'auto_increment' => TRUE
			),
			'parent_app_id' => array(
				'type' => 'INT',
				'constraint' => 11,
				'null' => FALSE,
				'default' => '0',

			),
			'app_id' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
			'app_env_id' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
			'name' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
			'code' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
			'status' => array(
				'type' => 'TINYINT',
				'constraint' => 2,
				'null' => FALSE,
				'default' => '1',

			),
			'visible' => array(
				'type' => 'TINYINT',
				'constraint' => 1,
				'null' => FALSE,
				'default' => '1',

			),
			'banner_img' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

			),
			'integration_type' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,

            ),
            'data_config' => array(
                'type' => 'BLOB',
                'null' => TRUE,

            ),
            '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
            '`updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP '
        ));
        $this->dbforge->add_key("id", true);
        $this->dbforge->create_table("api_integration_apps", TRUE);
        $this->db->query('ALTER TABLE `api_integration_apps` ENGINE = InnoDB');
    }

    public function down()
    {
        ### Drop table api_integration_apps ##
        $this->dbforge->drop_table("api_integration_apps", TRUE);
    }
};