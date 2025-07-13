<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {    
    $this->db->query("INSERT INTO `settings` (`name`, `value`, `status`, `user_id`) VALUES ('accept_order_without_payment_data', 'Libera pedidos sem dados de pagamento para liberação de pagamento', '2', '1')");
  }

  public function down()
  {
    $this->db->query('DELETE FROM settings WHERE name like "accept_order_without_payment_data";');
  }
};
