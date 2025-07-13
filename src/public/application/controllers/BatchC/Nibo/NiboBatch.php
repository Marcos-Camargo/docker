<?php
/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

class NiboBatch extends GenericBatch
{

    /**
     * @var NiboLibrary $nibo
     */
    private $nibo;

    public function __construct()
    {

        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

        //Models
        $this->load->model('model_settings');
        $this->load->model('model_banks');
        $this->load->model('model_stores');
        $this->load->model('model_financial_management_systems');
        $this->load->model('model_financial_management_system_stores');
        $this->load->model('model_financial_management_system_store_histories');


        //Libraries
        $this->load->library('NiboLibrary');

        $this->nibo = new NiboLibrary();

    }

    /**
     * @param null $id
     * @param null $params
     */
    public function run($id = null, $params = null): void
    {
        $this->syncStores(true, $id, $params);
        $this->syncStores(false, $id, $params);
    }

    private function syncStores(bool $onlyNotCreatedAccount = true, $id = null, $params = null): void
    {

        $this->startJob(__FUNCTION__, $id, $params);

        $systemName = Model_financial_management_systems::NIBO;

        $systemId = $this->model_financial_management_systems->getId($systemName);

        $log_name = $this->logName;

        $this->log_data('batch', $log_name, "Nibo:: Checking if API key is configured");

        //If nibo is not activated or is empty, will throw error informing that
        if (!$this->model_settings->getValueIfAtiveByName('nibo_api_key')) {
            $this->log_data(
                'batch',
                $log_name,
                "Nibo API key not configured",
                "E"
            );
            return;
        }

        if ($onlyNotCreatedAccount) {
            $stores = $this->model_stores->getStoresWithoutFinancialManagementSystem($systemId);
        } else {
            $stores = $this->model_stores->getStoresWithFinancialManagementSystemChangedLastXMinutes($systemId, 60);
        }

        $mode = $onlyNotCreatedAccount ? 'Not Created Stores' : 'Stores to Update';
        $this->log_data('batch', $log_name, 'Nibo:: Found '.count($stores).' stores, Mode: '.$mode.', System ID: '.$systemId);

        foreach ($stores as $key => $store) {

            $this->log_data('batch', $log_name, "Nibo:: Executing store {$store['id']} - {$store['name']}");
            echo $key . ' - ' . $store['name'] . ' (' . $store['id'] . ')' . PHP_EOL;

            //Creating/Updating at Nibo
            $this->nibo->createUpdateCustomer($systemId, $store);

        }

        $this->endJob();

    }

}
