<?php

require APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property Model_settings $model_settings
 * @property Model_company $model_company
 * @property Model_external_integration_history $model_external_integration_history
 * @property Model_parametrosmktplace $model_parametrosmktplace
 *
 * @property \Marketplaces\Utilities\Order $marketplace_order
 */
class Fastshop extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_settings');
        $this->load->model('model_company');
        $this->load->model('model_external_integration_history');
        $this->load->model('model_parametrosmktplace');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');
    }

    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function runConciliacao($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        $day_to_send_conciliation_to_external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('day_to_send_conciliation_to_external_marketplace_integration');
        if (
            $day_to_send_conciliation_to_external_marketplace_integration &&
            is_numeric($day_to_send_conciliation_to_external_marketplace_integration) &&
            $day_to_send_conciliation_to_external_marketplace_integration > 0 &&
            $day_to_send_conciliation_to_external_marketplace_integration <= 31
        ) {
            if (dateNow()->format('d') == $day_to_send_conciliation_to_external_marketplace_integration) {
                if ($external_marketplace_integration == 'fastshop') {
                    $companies = $this->model_company->getAllCompanyData();
                    foreach ($companies as $company) {
                        $company_id = $company['id'];
                        if ($company['active'] != 1) {
                            echo "[ERROR] empresa $company_id inativa\n";
                            continue;
                        }

                        if (ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') {
                            $register_id = "$company_id-".date('m/Y');
                        } else {
                            $register_id = "$company_id-".date('m/Y', strtotime(addMonthToDate(date(DATE_INTERNATIONAL), 1)));
                        }
                        $external_integration_history = $this->model_external_integration_history->getLastRowByTypeAndMethodAndRegisterId('conciliation', 'payment', $register_id);

                        try {
                            if (empty($external_integration_history) || $external_integration_history['status_webhook'] == 0) {
                                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                                $this->marketplace_order->external_integration->notifyConciliation($company_id);
                            }
                        } catch (Exception | Error $exception) {
                            echo "[ERROR] Empresa $company_id: {$exception->getMessage()}\n";
                        }
                    }
                } else {
                    echo "[ERROR] parâmetro 'external_marketplace_integration' deve estar ativo e com um valor de fastshop.\n";
                }
            } else {
                echo "[ERROR] ainda não é o dia '$day_to_send_conciliation_to_external_marketplace_integration' para enviar a notificação da conciliação.\n";
            }
        } else {
            echo "[ERROR] parâmetro 'day_to_send_conciliation_to_external_marketplace_integration' deve estar ativo e com um valor entre 1 e 28.\n";
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function runDownloadNfse($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');
        if ($external_marketplace_integration == 'fastshop') {
            $cycles     = $this->model_parametrosmktplace->getAllFiscalCyclesActive();
            $date_now   = dateNow()->format(DATE_INTERNATIONAL);
            $companies  = $this->model_company->getAllCompanyData();
            $months_to_search = 3;

            foreach ($cycles as $cycle) {
                // Leros últmo três meses
                for ($x = 0; $x < $months_to_search; $x++) {
                    $month_year = date('m-Y', strtotime("-$x month", strtotime($date_now)));

                    echo "[PROCESS] Ciclo id: $cycle[id]. Mês do ciclo: $month_year\n";

                    $conciliations = $this->db->get_where('conciliacao_fiscal', array(
                        'param_mkt_ciclo_id' => $cycle['id'],
                        'ano_mes' => $month_year
                    ))->result_array();

                    foreach ($conciliations as $conciliation) {
                        echo "[PROCESS] Lote: $conciliation[lote]\n";
                        foreach ($companies as $company) {
                            $company_id = $company['id'];
                            if ($company['active'] != 1) {
                                echo "[ERROR] empresa $company_id inativa\n";
                                continue;
                            }
                            echo "[PROCESS] Empresa: $company_id\n";

                            try {
                                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                                $this->marketplace_order->external_integration->getNfseStore($company_id, $conciliation['lote']);
                            } catch (Exception|Error $exception) {
                                echo "[ERROR] {$exception->getMessage()}\n";
                            }
                        }
                    }
                }
            }
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }
}
