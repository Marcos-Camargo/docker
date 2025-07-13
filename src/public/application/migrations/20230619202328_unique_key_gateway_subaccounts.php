<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $this->db->query("ALTER TABLE `gateway_subaccounts` 
                                DROP INDEX `gateway_id`,
                                ADD UNIQUE INDEX `gateway_id`(`gateway_account_id`) USING BTREE,
                                ADD UNIQUE INDEX(`secondary_gateway_account_id`);");
	}

	public function down()	{

		$this->db->query("ALTER TABLE `gateway_subaccounts` 
                            DROP INDEX `gateway_id`,
                            DROP INDEX `secondary_gateway_account_id`,
                            ADD INDEX `gateway_id`(`gateway_account_id`) USING BTREE,
                            ADD INDEX `secondary_gateway_account_id`(`secondary_gateway_account_id`) USING BTREE;");

	}
};