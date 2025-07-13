<?php

use League\Csv\Reader;
use League\Csv\Statement;
use phpDocumentor\Reflection\Types\This;

class ImportCSVAutomationPhases extends BatchBackground_Controller
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
        $this->load->model('Model_csv_to_verifications_phases', 'model_csv_to_verifications_phases');
        $this->load->model('model_stores');
        $this->load->model('model_phases');
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
        $csvs_to_import = $this->model_csv_to_verifications_phases->getDontChecked();
        foreach ($csvs_to_import as $key => $csv_import) {
            $situation = $this->import_csv($csv_import);
            $this->model_csv_to_verifications_phases->setChecked($csv_import['id'], $situation);
        }
        /* encerra o job */
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    public function import_csv($csv): string
    {
        $lebel_store = 'ID da loja';
        $lebel_phase = 'Fase da loja';
        print_r("Inciando para o csv: {$csv["upload_file"]}\n");
        try {
            $datas = $this->convert_csv_to_data($csv['upload_file']);
        } catch (Exception $exception) {
            echo "Falha no processamento, malformação do csv:" . $exception->getMessage() . "\n";
            $data['username'] = $csv["username"];
            $data['type'] = "Fases";
            $data['created_date'] = $csv["created_at"];
            $data['messagem'] = 'messagem: ' . $exception->getMessage();
            $title = "Falha no processo de processamento do arquivo.";
            $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
            if (!$sellercenter) {
                $sellercenter = 'conectala';
            }
            $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
            if (!$sellercenter_name) {
                $sellercenter_name = 'Conecta Lá';
            }
            $data['sellercentername'] = $sellercenter_name;
            if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/csv_import_report_mal_formed.php')) {
                $body= $this->load->view('mailtemplate/'.$sellercenter.'/csv_import_report_mal_formed',$data,TRUE);
            }
            else {
                $body= $this->load->view('mailtemplate/default/csv_import_report_mal_formed',$data,TRUE);
            }
            $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
            if (!$from) {
                $from = 'marketing@conectala.com.br';
            }
            $this->sendEmailMarketing($csv['user_email'], $title, $body, $from, $csv["upload_file"]);
            return 'err';
        }
        $err_by_line = [];
        $info_by_line = [];
        $qtd_import = 0;
        foreach ($datas as $key => $data) {
            $store = $this->model_stores->getStoreByIdOrName($data[$lebel_store], $data[$lebel_store]);
            if (!$store) {
                $error_message = "Não há nenhuma loja com id ou nome equivalente ao passado na coluna \"ID da loja\"\n";
                echo $error_message;
                $err_by_line[] = $error_message;
                $info_by_line[] = ['type' => 'err', 'line' => $key, 'info' => $error_message];
                continue;
            }
            $phase_data = [];
            $phase_data['goal_month'] = $data["Meta"];
            $phase = $this->model_phases->getPhaseByNameOrId($data[$lebel_phase], $data[$lebel_phase]);
            if (!$phase) {
                $error_message = "Não há nenhuma Fase com id ou nome equivalente ao passado na coluna \"Fase da loja\"\nPersistindo somente a meta.";
                echo $error_message;
                $err_by_line[] = $error_message;
                $info_by_line[] = ['type' => 'err', 'line' => $key, 'info' => $error_message];
            } else {
                $phase_data['phase_id'] = $phase['id'];
            }
            log_message("error", "Testando mensagem de log.");
            $this->model_stores->update($phase_data, $store['id']);
            $this->log_data("ImportCSVAutomationPhase", "UpdatePhase_store_".$store['id'], json_encode($phase_data, JSON_UNESCAPED_UNICODE), "I");
            $info_message = "Atualização realizada com sucesso.".json_encode($phase_data, JSON_UNESCAPED_UNICODE)."\n";
            $info_by_line[] = ['type' => 'success', 'line' => $key, 'info' => $info_message];
            echo $info_message;
            $qtd_import++;
        }
        $situation = '';
        $data = [];
        if (!empty($err_by_line)) {
            $title = "Falha no processamento do csv.";
            $situation = 'err';
        } else {
            $title = "Fases explortadas com sucesso a partir do csv.";
            $situation = 'success';
        }
        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if (!$sellercenter) {
            $sellercenter = 'conectala';
        }
        $data['type'] = "Fases";
        $data['info_by_line'] = $info_by_line;
        $data['username'] = $csv["username"];
        $data['created_date'] = $csv["created_at"];
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/csv_import_report.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/csv_import_report',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/csv_import_report',$data,TRUE);
        }
        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }
        $this->sendEmailMarketing($csv['user_email'], $title, $body, $from, $csv["upload_file"]);

        printf("Exportado {$qtd_import} de itens.\n");
        return $situation;
    }

    public function convert_csv_to_data($upload_file)
    {
        $expected_headers = ["ID da loja", "Fase da loja", "Meta"];
        $myData = array();
        $csv = Reader::createFromPath($upload_file);
        $csv->setOutputBOM(Reader::BOM_UTF8);
        $csv->setOutputBOM(mb_detect_encoding(file_get_contents($upload_file)));
        $csv->setDelimiter(';'); // separados de colunas
        $csv->setHeaderOffset(0); // linha do header

        $stmt = new Statement();
        $dados = $stmt->process($csv);
        $headers = $dados->getHeader();
        foreach ($expected_headers as $expected) {
            if (!in_array($expected, $headers)) {
                $expected = '"' . implode('","', $expected_headers) . '"';
                $received = '"' . implode('","', $headers) . '"';
                throw new Exception("Falha no processamento, csv mal formatado, esperado no cabeçalho: " . $expected . "\n Recebidos:" . $received."Sendo ");
            }
        }
        foreach ($dados as $dado) {
            $dado["Fase da loja"] = preg_replace('/[[:^print:]]/', '',$dado["Fase da loja"]);
            $dado["ID da loja"] = preg_replace('/[[:^print:]]/', '',$dado["ID da loja"]);
            array_push($myData, $dado);
        }
        return $myData;
    }
}
