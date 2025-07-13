<?php
/**
 * php index.php BatchC/Automation/CreateIntegrationApps run {ID} {PARAMS}
 */

require_once APPPATH . "libraries/Integration_v2/Applications/Controllers/IntegrationERPController.php";

use \Integration_v2\Applications\Controllers\IntegrationERPController;

/**
 * Class CreateIntegrationApps
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property Model_api_integrations $model_api_integrations
 * @property Model_settings $model_settings
 * @property Model_integration_erps $model_integration_erps
 * @property IntegrationERPController $IntegrationERPController
 */
class CreateIntegrationApps extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integration_erps');

        $this->lang->load('api', 'portuguese_br');

        $this->IntegrationERPController = new IntegrationERPController(
            $this->model_integration_erps,
            $this->model_api_integrations,
            $this->lang
        );
    }

    public function run($id = null, $params = null)
    {

        /* inicia o job */
        $this->setIdJob($id);

        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        echo "ModulePath=" . $modulePath . "\n";
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return false;
        }
        echo "Iniciando criação/atualização de aplicativos de integrações...\n";

        $config = get_instance()->config->config;
        $config['encryption_key'] = get_instance()->config->config['encryption_key'] ?? null;
        $config['sellercenter'] = $this->model_settings->getSettingDatabyName('sellercenter')['value'] ?? null;
        $this->IntegrationERPController->createIntegrationApps($config);
        echo "Finalizado criação/atualização de aplicativos de integrações...\n";
        $this->gravaFimJob();
    }

}