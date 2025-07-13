<?php


defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_api_integrations $model_api_integrations
 * @property Model_stores $model_stores
 * @property Model_integration_erps $model_integration_erps
 *
 * @property CI_Loader $load
 */
class General extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        if ($this->data['only_admin'] != 1) {
            redirect('dashboard', 'refresh');
        }

        $this->load->model('model_api_integrations');
        $this->load->model('model_stores');
        $this->load->model('model_integration_erps');
    }

    public function search(int $store_id = null)
    {
        $this->data['page_title'] = $this->lang->line('application_integration');
        $this->data['stores'] = $this->model_stores->getStoresIntegration();
        $this->data['store_id'] = $store_id;
        $this->render_template('integration_v2/index', $this->data);
    }

    public function getIntegrationByStore(int $store_id): CI_Output
    {
        $api_integrations = $this->model_api_integrations->getIntegrationByStore($store_id);

        if (!$api_integrations) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array_merge(array())));
        }

        $integration_erp_id = $api_integrations['integration_erp_id'];
        $is_backoffice = false;
        $integration_image = null;
        $integration_description = null;
        if (!is_null($integration_erp_id)) {
            $integration_erps = $this->model_integration_erps->getById($integration_erp_id);
            $erps_type[$integration_erp_id] = $integration_erps && $integration_erps->type == 1;
            $integration_image = $integration_erps->image;
            $integration_description = $integration_erps->description;
            if ($erps_type[$integration_erp_id]) {
                $is_backoffice = true;
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array_merge(array('is_backoffice' => $is_backoffice, 'integration_image' => $integration_image, 'integration_description' => $integration_description),$api_integrations)));
    }
}
