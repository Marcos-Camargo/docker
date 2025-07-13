<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->query("INSERT INTO `payment_gateway_settings` (`name`, `value`, `gateway_id`) VALUES ('active_transfer_tax_pagarme', '0', '2')");
    }

    public function down()
    {
        $this->db->query('DELETE FROM payment_gateway_settings WHERE name like "active_transfer_tax_pagarme";');
    }
};