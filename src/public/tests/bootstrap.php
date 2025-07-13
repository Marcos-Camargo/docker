<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('ENVIRONMENT', 'testing');
$_SERVER['CI_ENV'] = 'testing';

// Caminhos base
$system_path = realpath(__DIR__ . '/../system') . DIRECTORY_SEPARATOR;
$application_folder = realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR;

define('BASEPATH', $system_path);
define('APPPATH', $application_folder);
define('VIEWPATH', APPPATH . 'views' . DIRECTORY_SEPARATOR);
define('FCPATH', rtrim(realpath(__DIR__ . '/../../'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

// Proteção para não carregar tudo via autoload (evita conflitos em testes)
$GLOBALS['AUTOTEST_MODE'] = true;

// Carrega core do CI
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/Loader.php';
require_once BASEPATH . 'core/Config.php';
require_once BASEPATH . 'core/Controller.php';
require_once APPPATH . 'config/constants.php';

// Define get_instance manualmente, se ainda não existir
if (!function_exists('get_instance')) {
    function &get_instance()
    {
        return CI_Controller::get_instance();
    }
}

// Instancia um controller para popular o get_instance()
class TestController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load = new CI_Loader();
        $this->load->initialize();

        // 👇 Isso evita o erro atual
        $this->config = new CI_Config();
        $this->config->load('config');
    }
}

new TestController();
