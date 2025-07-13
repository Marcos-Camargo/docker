<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if (!$this->dbforge->register_exists('calendar_events', 'module_path', 'ConciliationInstallmentsBatch')) {
            $this->db->insert('calendar_events', array(
                'title'         => "Job de Calculo da Data de Pagamento",
                'event_type'    => '30',
                'start'         => '2025-03-20 03:00:00',
                'end'           => '2200-12-31 23:59:00',
                'module_path'   => 'ConciliationInstallmentsBatch',
                'module_method' => 'run',
                'params'        => 'null'
            ));
        }
    }

    public function down()	{
        $this->db->where('module_path', 'ConciliationInstallmentsBatch')->delete('calendar_events');
    }
};
