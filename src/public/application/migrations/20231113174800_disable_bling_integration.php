<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {


        $sellerCenter = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();

        // Clientes permitidos fazer a exclusão.
        $sellercenter_params = array(
            'balaroti',
            'casavideo',
            'decathlon',
            'somaplace',
            'pangeia',
            'polishop',
            'lojasmm',
            'sicoob',
            'sicredi',
            'vertem',
            //Shopping do Calçado
        );

        if ($sellerCenter && in_array($sellerCenter['value'], $sellercenter_params)) {
            // Ler todas as integrações bling.
            $integration_stores = $this->db->where('integration', 'bling')->get('api_integrations')->result_array();

            foreach ($integration_stores as $integration_store) {
                // Ler todos os jobs de cada integração bling.
                $jobs = $this->db->where(array(
                    'store_id'      => $integration_store['store_id'],
                    'integration'   => $integration_store['integration'],
                ))->get('job_integration')->result_array();

                foreach ($jobs as $job) {
                    // Remove o job da integração.
                    $this->db->where('id', $job['id'])->delete('job_integration');

                    // Remove o job já agendado da integração.
                    $this->db->where(array(
                        'module_path'   => $job['job_path'],
                        'params'        => $job['store_id']
                    ))->delete('job_schedule');

                    // Remove o job do calendário.
                    $this->db->where(array(
                        'module_path'   => $job['job_path'],
                        'params'        => $job['store_id']
                    ))->delete('calendar_events');
                }

                // Remove a integração bling.
                $this->db->where('id', $integration_store['id'])->delete('api_integrations');
            }

            // Inativar a integração em gestão de integração.
            $this->db->where('name', 'bling')->update('integration_erps', array('active' => false, 'visible' => false));
        }
    }

    public function down()	{
        ### Não tem rollback ##
    }

};