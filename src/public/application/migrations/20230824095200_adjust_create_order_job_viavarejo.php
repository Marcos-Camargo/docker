<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
        // consulta todos os jobs de pedido da via.
        $jobs = $this->db->select('ce.*')
            ->join('job_integration ji', 'ji.store_id = ce.params and ji.job_path = ce.module_path')
            ->where(array(
                'ji.job'            => 'CreateOrder',
                'ji.integration'    => 'viavarejo_b2b'
            ))
            ->get('calendar_events ce')
            ->result_array();

        foreach ($jobs as $job) {
            $start_date = explode(' ', $job['start']);
            $this->db->where('id', $job['ID'] ?? $job['id'])->update('calendar_events', array(
                'start' => $start_date[0] . ' 00:00:00'
            ));
        }
	}

	public function down() {}
};