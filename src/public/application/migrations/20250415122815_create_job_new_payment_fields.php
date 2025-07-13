<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'SellerCenter\Vtex\VtexMandatoryFields')) {
            $this->db->insert("calendar_events", array(
                'title'         => 'Adiciona e checa novos campos de pagamento.',
                'event_type'    => '10',
                'start'         => '2023-02-10 02:30:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'SellerCenter\Vtex\VtexMandatoryFields',
                'module_method' => 'run',
                'params'        => 'null',
                'alert_after'   => '60'
            ));
        }
	}

	public function down()	{
		$this->db->query("DELETE FROM calendar_events WHERE `module_path` = 'SellerCenter\Vtex\VtexMandatoryFields'");
	}
};