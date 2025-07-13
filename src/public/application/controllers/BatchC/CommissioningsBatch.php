<?php
/** @noinspection PhpUndefinedFieldInspection */
require APPPATH."controllers/BatchC/GenericBatch.php";

/**
 * Class ConciliationInstallmentsBatch
 */
class CommissioningsBatch extends GenericBatch
{

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => true
        );

        $this->session->set_userdata($logged_in_sess);

        $this->load->library('checkCommissioningChanges');

    }

    /**
     * @param  null  $id
     * @param  null  $params
     */
    public function run($id = null, $date_start = null): void
    {

        echo "Starting Job".PHP_EOL;

        $this->setIdJob($id);

        if ($date_start == null || $date_start == 'null') {
            $date_start = null;
        }

        $lib = new \CheckCommissioningChanges();
        $lib->processCommissionings();

        echo "Job Ended".PHP_EOL;

        $this->gravaFimJob();

    }

}
