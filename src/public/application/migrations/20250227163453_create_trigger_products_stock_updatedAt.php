<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if (!$this->dbforge->column_exists("stock_updated_at", "products")) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `stock_updated_at` DATETIME NULL AFTER `is_variation_grouped`;");
        }

        $this->db->query("CREATE TRIGGER `ProductsStockUpdatedAt` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
			IF NEW.qty != OLD.qty THEN
				SET NEW.stock_updated_at = NOW();
			END IF;
		END");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER `ProductsStockUpdatedAt`;");

        if ($this->dbforge->column_exists("stock_updated_at", "products")) {
            $this->dbforge->drop_column("products", "stock_updated_at");
        }
    }
};
