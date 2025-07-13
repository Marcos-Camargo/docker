<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->column_exists("stock_updated_before", "products")) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `stock_updated_before` DATETIME NULL AFTER `stock_updated_at`;");
        }

        $this->db->query("CREATE TRIGGER `ProductsStockUpdateBefore` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
			IF NEW.stock_updated_at != OLD.stock_updated_at THEN
				SET NEW.stock_updated_before = OLD.stock_updated_at;
			END IF;
		END");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER `ProductsStockUpdateBefore`;");

        if ($this->dbforge->column_exists("stock_updated_before", "products")) {
            $this->dbforge->drop_column("products", "stock_updated_before");
        }
    }
};