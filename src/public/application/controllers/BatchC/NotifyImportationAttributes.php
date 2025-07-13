<?php

use League\Csv\Reader;
use League\Csv\Statement;
use phpDocumentor\Reflection\Types\This;

class NotifyImportationAttributes extends BatchBackground_Controller
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
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_csv_import_attributes_products');
        $this->load->model('model_settings');
        
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath, 'assets');
        $this->serverpath = substr($serverpath, 0, $pos);
    }
    // php index.php BatchC/Automation/ImportCSVAutomationPhases run null null
    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            get_instance()->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        get_instance()->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        
        $records = $this->model_csv_import_attributes_products->getNotSent();
        foreach ($records as $record) {
            if ($record['valid'] == false) {
                $this->makeAndSendNotification($record);
            }
        }
        /* encerra o job */
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    public function makeAndSendNotification($record) {
        $errors_transformation = $this->model_csv_import_attributes_products->getErrorsTransformation($record['id']);
        $info_by_line = [];
        
        $data = [];
        $data['filename']  = $record['name_original'];
        $data['created_date'] = $record['date_create'];

        foreach ($errors_transformation as $error) {
            $info_by_line[] = ['type' => 'err', 'info' => $error['message']];
        }

        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if (!$sellercenter) {
            $sellercenter = 'conectala';
        }

        $subject = 'Erro na importação do arquivo '. $record['name_original'];
        $data['info_by_line'] = $info_by_line;
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/csv_import_attributes_report.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/csv_import_attributes_report',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/csv_import_attributes_report',$data,TRUE);
        }
        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }
		
		$this->sendEmailMarketing($record['email'], $subject, $body, $from, $record['path']);
        echo "Email enviado para ". $record['email']. "\n"; 
        $this->model_csv_import_attributes_products->markSent($record['id']);
		
    }
}
