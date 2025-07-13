<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Companhias/Empresas
 
 */

defined('BASEPATH') or exit('No direct script access allowed');

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Utils;

/**
 * @property Model_job_schedule $model_job_schedule
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_Calendar $model_calendar
 */
class MicroserviceMigrationClient extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->load->model('model_job_schedule');
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_calendar');

        $this->data['page_title'] = $this->lang->line('application_cache');
    }

    private array $jobs = array(
        'ApiIntegrations'               => 'DB_ExportApiIntegrations',
        'CSVToVerification'             => 'DB_ExportCSVToVerification',
        'FreteRegiaoProvider'           => 'DB_ExportFreteRegiaoProvider',
        'IntegrationLogistic'           => 'DB_ExportIntegrationLogistic',
        'IntegrationLogisticApiParam'   => 'DB_ExportIntegrationLogisticApiParameters',
        'Pivots'                        => 'DB_ExportPivots',
        'ProvidersToSeller'             => 'DB_ExportProvidersToSeller',
        'RulesSellerConditions'         => 'DB_ExportRulesSellerConditions',
        'ShippingCompany'               => 'DB_ExportShippingCompany',
        'TableShipping'                 => 'DB_ExportTableShipping',
        'PickupPoint'                   => 'DB_ExportPickupPoint'
    );

    /*
     * It only redirects to the manage order page
     */
    public function index()
    {
        $this->data['sellercenter'] = $this->model_settings->getValueIfAtiveByName('sellercenter');
        $this->data['is_production'] = in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci']);
        $this->data['page_title'] = $this->lang->line('application_migration');
        $this->render_template('microservice_migration_client/index', $this->data);
    }

    public function startMigration(string $key): CI_Output
    {
        $use_ms_shipping_replica = $this->model_settings->getValueIfAtiveByName('use_ms_shipping_replica');
        if (!$use_ms_shipping_replica) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => "O parâmetro use_ms_shipping_replica deve está ativo para realizar o processo."
            )));
        }

        $key = xssClean($key);
        // Verifica se já existe algum job agendado.
        $job = $this->model_job_schedule->getByModulePathAndStatus("Automation/MigrationMs/{$this->jobs[$key]}", [0,1,4,6]);

        if (!empty($job)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => "Já existe um processo agendado para esse job."
            )));
        }

        $data_log = array (
            'module_path'   => "Automation/MigrationMs/{$this->jobs[$key]}",
            'module_method' => 'run',
            'params'        => 'null',
            'finished'      => 0,
            'status'        => 0,
            'error_msg'     => 0,
            'date_start'    => date('Y-m-d H:i:s'),
            'alert_after'   => null,
            'server_id'     => 0
        );

        $this->model_job_schedule->create($data_log);

        $this->log_data(__CLASS__, __FUNCTION__, json_encode($data_log));

        return $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => $this->lang->line('messages_successfully_scheduled')
        )));
    }

    public function getLogLastExecution(string $key): CI_Output
    {
        $key = xssClean($key);
        $jobs = $this->model_job_schedule->getByModulePath("Automation/MigrationMs/{$this->jobs[$key]}");

        if (empty($jobs)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => 'Não foi encontrado nenhum log de job agendado.'
            )));
        }

        $job = $jobs[0];

        if ($job['error_msg'] == 0) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => 'Processo ainda não iniciado.'
            )));
        }

        $file_path = "application/logs";

        // Em produção tem pasta para diferenciar logs de batch, web, api, etc.
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $file_path .= "/batch";
        }

        $ranges = range(substr($job['error_msg'], 2, 2), date('i', strtotime($job['date_end'])));
        $content = null;

        foreach($ranges as $range) {
            $job_time = substr($job['error_msg'], 0, 2) . $range;
            $file_path_verify = $file_path . "/Automation/MigrationMs/batch_{$this->jobs[$key]}_run_$job_time.log";
            
            if (file_exists($file_path_verify)) {
                $content = file_get_contents($file_path_verify);
                break;
            }
        }

        if (is_null($content)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => "Arquivo de log não encontrado."
            )));
        }

        return $this->output->set_output(json_encode(array(
            'success'       => true,
            'message'       => $this->lang->line('messages_successfully_scheduled'),
            'file_content'  => $content
        )));
    }

    public function finishMigration(): CI_Output
    {
        // Validar jobs
        $errors = [];
        foreach ($this->jobs as $key => $job) {
            $job_status = $this->model_job_schedule->getByModulePathAndStatus("Automation/MigrationMs/$job", [0,1,4,6]);

            if (!empty($job_status)) {
                $errors[] = $key;
            }
        }

        if (!empty($errors)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => '<h4>Existe(m) job(s) em processo de execução ou aguardando ser processado.</h4><ul class="text-left"><li>' . implode('</li><li>', $errors) . '</li></ul>'
            )));
        }

        $this->model_settings->updateByName(['status' => 0], 'ms_authenticator_client_id');
        $this->model_settings->updateByName(['status' => 0], 'ms_authenticator_secret');
        $this->model_settings->updateByName(['status' => 0], 'ms_authenticator_realm');
        $this->model_settings->updateByName(['status' => 0], 'ms_authenticator_url');
        $this->model_settings->updateByName(['status' => 0], 'jwt_token_ms_shipping');
        $this->model_settings->updateByName(['status' => 0], 'microservice_api_url');

        foreach ($this->model_stores->getActiveStore() ?? [] as $store) {
            $this->model_stores->setDateUpdateNow($store['id']);
        }

        return $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => $this->lang->line('messages_successfully_updated'). ' Em até 10 minutos as lojas receberão o novo endpoint fulfillment'
        )));
    }
}