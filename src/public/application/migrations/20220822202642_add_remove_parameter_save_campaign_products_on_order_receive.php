<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {
    $this->db->query('DELETE FROM settings WHERE name like "save_campaign_products_on_order_receive";');
  }

  public function down()
  {
    
  }
};
