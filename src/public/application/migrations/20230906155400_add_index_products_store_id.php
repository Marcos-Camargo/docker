
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->index_exists('index_store_id', 'products')) {
            $this->db->query('ALTER TABLE `products` ADD INDEX `index_store_id` (`store_id`, `id`);');
        }
	}

	public function down()
    {
        $this->db->query('ALTER TABLE `products` DROP INDEX `index_store_id`;');
	}
};
