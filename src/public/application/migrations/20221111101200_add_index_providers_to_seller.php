
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->index_exists('ix_shipping_company_active', 'shipping_company')) {
            $this->db->query('ALTER TABLE `shipping_company` ADD INDEX `ix_shipping_company_active` (`active`);');
        }
	}

	public function down()
    {
        $this->db->query('ALTER TABLE `shipping_company` DROP INDEX `ix_shipping_company_active`;');
	}
};
