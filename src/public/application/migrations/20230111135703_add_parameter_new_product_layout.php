<?php defined('BASEPATH') or exit('No direct script access allowed');

return new
/**
 * Class
 * @property CI_DB_query_builder $db
 */
class extends CI_Migration {

    public function up()
    {
        if ($this->db->where('name', 'view_product_listing')->get('settings')->num_rows() === 0) {
            $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('view_product_listing', 'products/management/listing', '2', '1')");
        }
        if ($this->db->where('name', 'view_product_creation')->get('settings')->num_rows() === 0) {
            $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('view_product_creation', 'products/management/create', '2', '1')");
        }
        if ($this->db->where('name', 'view_product_edit')->get('settings')->num_rows() === 0) {
            $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('view_product_edit', 'products/management/edit', '2', '1')");
        }
    }

    public function down()
    {
        $this->db->query('DELETE FROM settings WHERE name = "view_product_listing";');
        $this->db->query('DELETE FROM settings WHERE name = "view_product_creation";');
        $this->db->query('DELETE FROM settings WHERE name = "view_product_edit";');
    }
};
