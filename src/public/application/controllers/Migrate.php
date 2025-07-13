<?php if (!defined('BASEPATH')) exit("No direct script access allowed");

/**
 * @property CI_Migration $migration
 */
class Migrate extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->input->is_cli_request()
        or exit("Execute via command line: php index.php migrate");

        $this->load->library('migration');
    }

    public function index()
    {
        if (!$this->migration->updateVersion()) {
            show_error($this->migration->error_string());
        }
    }

    public function create(string $tableName): void
    {

        if (!$tableName){
            exit('Favor informar o nome da tabela que deseja gerar');
        }

        $this->load->library('Sqltoci');

        $this->sqltoci->generate($tableName);

    }

    public function createClean(string $name): void
    {

        if (!$name){
            exit('Favor informar o nome da migration que deseja gerar');
        }

        $this->load->library('Sqltoci');

        $this->sqltoci->generateClean($name);

    }

    public function rollback(): void
    {

        if (!$this->migration->rollbackVersion()) {
            show_error($this->migration->error_string());
        }

    }

}