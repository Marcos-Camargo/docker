
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->index_exists('index_by_int_to_skumkt_seller_id', 'vtex_ult_envio')) {
            $this->db->query('ALTER TABLE `vtex_ult_envio` ADD INDEX `index_by_int_to_skumkt_seller_id` (`int_to`, `skumkt`, `seller_id`);');
        }
	}

	public function down()
    {
        $this->db->query('ALTER TABLE `vtex_ult_envio` DROP INDEX `index_by_int_to_skumkt_seller_id`;');
	}
};
