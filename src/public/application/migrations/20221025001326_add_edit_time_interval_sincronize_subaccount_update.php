<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {
    $this->db->set('value', 480);
    $this->db->like('name', 'minutes_sincronize_stores_with_gateway_account', 'none');
    $this->db->update('settings');
  }

  public function down()
  {
    $this->db->set('value', 60);
    $this->db->like('name', 'minutes_sincronize_stores_with_gateway_account', 'none');
    $this->db->update('settings');
  }
};