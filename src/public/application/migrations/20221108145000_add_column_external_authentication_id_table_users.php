<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up() {
		## Create column users.external_authentication_id
        if ($this->db->where(['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'external_authentication_id'])->get('INFORMATION_SCHEMA.COLUMNS')->num_rows() === 0) {
            $fields = array(
                'external_authentication_id' => array(
                    'type' => 'int',
                    'constraint' => ('11'),
                    'null' => true,
                    'default' => null
                )
            );
            $this->dbforge->add_column("users", $fields);
            $this->db->query('ALTER TABLE `users` ADD CONSTRAINT `users_external_authentication_foreign` FOREIGN KEY (`external_authentication_id`) REFERENCES `externals_authentication` (`id`)');
           
        }

	}

	public function down()	{
        ### Drop column users.external_authentication_id ##
        $this->dbforge->drop_column("users", 'external_authentication_id');
	}
};