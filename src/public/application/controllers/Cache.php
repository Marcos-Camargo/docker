<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Companhias/Empresas
 
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_settings $model_settings
 */

class Cache extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->load->model('model_settings');

        $this->data['page_title'] = $this->lang->line('application_cache');
    }

    private $keys_cache = array(
        'settings' => array(
            'settings:'
        ),
        'logistic_integration' => array(
            'integration_logistic:',
            'setting_logistic:',
            'credencials_integration:',
            'integration_logistic_seller_center:'
        ),
        'multi_cd' => array(
            'multi_channel_fulfillment:store:',
            'stores_multi_channel_fulfillment:'
        ),
        'sipping_company' => array(
            'shipping_company:',
            'shipping_companies:',
        ),
        'shipping_company_table' => array(
            'shipping:table_shipping_regions:',
            'table_shipping:',
            'frete_regiao_provider:',
        ),
        'product' => array(
            'products:'
        ),
        'region' => array(
            'state:',
            'zipcode:'
        ),
        'auction' => array(
            'auction_rule:',
        ),
        'pickup_point' => array(
            'pickup_points:',
        )
    );

    /*
     * It only redirects to the manage order page
     */
    public function index()
    {
        if (!in_array('cleanCache', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_cache');
        $this->render_template('cache/index', $this->data);
    }

    public function cleanCache(string $key): CI_Output
    {
        if (!in_array('cleanCache', $this->permission)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => $this->lang->line('messages_not_permission')
            )));
        }

        if (!array_key_exists($key, $this->keys_cache)) {
            return $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => $this->lang->line('messages_key_not_found')
            )));
        }

        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');

        foreach ($this->keys_cache[$key] as $key_cache) {
            \App\Libraries\Cache\CacheManager::deleteAllByPrefix("$sellercenter:$key_cache");
        }

        return $this->output->set_output(json_encode(array(
            'success' => true,
            'message' => $this->lang->line('messages_successfully_cleaned')
        )));
    }
}