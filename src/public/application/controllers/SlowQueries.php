<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * To enable slow queries logger, just use: saveSlowQueries(); where you need
 */
class SlowQueries extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

    }

    public function index()
    {

        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Slow Queries';

        get_instance()->load->model('model_settings');
        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

        $itens = \App\Libraries\Cache\CacheManager::getAllByPrefix("$sellercenter:slow_queries");

        $this->data['itens'] = $itens;
        $this->data['total_queries'] = count($itens);

        $this->render_template('slow_queries/index', $this->data);

    }

}
