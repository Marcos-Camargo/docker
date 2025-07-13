<?php

/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class ExternalGatewayBatch
 */
class ExternalGatewayBatch extends GenericBatch
{

    /**
     * @var ExternalGatewayLibrary $integration
     */
    private $integration;

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
        $this->load->model('model_gateway');
        $this->load->model('model_legal_panel');
        $this->load->model('model_repasse');

        //Libraries
        $this->load->library('ExternalGatewayLibrary');
        $this->integration = new ExternalGatewayLibrary();
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function resetNegativePayments($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::EXTERNO;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

		$this->integration->processNegativePayments(null, $params);

        $log_name = $this->logName;

        $this->endJob();
		//die('finalizando');
    }

    public function resetNegativePaymentsFiscal($id = null, $params = null): void
    {
        $this->startJob(__FUNCTION__, $id, $params);

        $gateway_name = Model_gateway::EXTERNO;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

		$this->integration->processNegativePaymentsFiscal(null, $params);

        $log_name = $this->logName;

        $this->endJob();
		//die('finalizando');
    }
}
