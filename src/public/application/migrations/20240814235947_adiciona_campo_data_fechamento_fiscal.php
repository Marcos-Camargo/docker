<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	
	public function up() {
		$this->db->query("ALTER TABLE orders_payment_date ADD data_fechamento_fiscal DATETIME NULL;");
	
	}

	public function down()	{
        $this->db->query("ALTER TABLE `orders_payment_date` DROP COLUMN `data_fechamento_fiscal`;");
	}
};