<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

    $data = array(
      'title'         => 'Sincronizar as subcontas com o Pagar.me - Lojas com contas marcadas como pendentes',
      'event_type'    => 60,
      'module_path'   => 'PagarMe/PagarmeBatch',
      'module_method' => 'runSyncStoresWithSubaccountsPendencies',
      'params'        => 'null',
      'start'         => '2022-11-28 23:59:00',
      'end'           => '2200-12-31 23:59:00',
      'alert_after'   => null,
    );   

    if ($this->db->table_exists('calendar_events')){
      $sql = "SELECT * FROM calendar_events WHERE module_method LIKE ?";
      $calendar_event = $this->db->query($sql, array("runSyncStoresWithSubaccountsPendencies"))->num_rows();
      if($calendar_event == 0){
        $this->db->insert('calendar_events', $data);
      }
    }
        
	 }

	public function down()	{
    $this->db->where('module_method', 'runSyncStoresWithSubaccountsPendencies')->where('event_type', 60)->delete('calendar_events');
	}
};