<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        if (!$this->dbforge->index_exists('idx_products_status_image_date_update', 'products')) {
            $this->db->query("CREATE INDEX idx_products_status_image_date_update ON products (status, image, date_update);");
        }
	}

	public function down()	{
        if ($this->dbforge->index_exists('idx_products_status_image_date_update', 'products')) {
            $this->db->query('DROP INDEX idx_products_status_image_date_update ON products');
        }
	}
};