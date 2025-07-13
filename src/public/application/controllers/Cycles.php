<?php
/*
Controller de Ciclos de Pagamento
*/

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_cycles $model_cycles
 * @property Model_parametrosmktplace $model_parametrosmktplace
 */
class Cycles extends Admin_Controller
{
    const CICLO_VALIDO = 1;
    const CICLO_INVALIDO = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_settings');
        $this->load->model('model_cycles');
        $this->load->model('model_stores');
        $this->load->model('model_integrations');
        $this->load->model('model_parametrosmktplace');

        $this->load->library('parser');
        $this->load->library('excel');

        $this->settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
    }

    public function index()
    {

        $this->data['page_title'] = 'Ciclos de Pagamento';
        $this->data['settingSellerCenter'] = $this->settingSellerCenter;
        $this->render_template('cycles/index', $this->data);
    }

    public function getModelCycles()
    {
        $mkts = $this->model_cycles->getModelCycles();
        header('Content-type: application/json');
        exit(json_encode($mkts));
    }

    public function saveModel()
    {

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $hiddenId = $request['vHiddenId'];
        $data_inicio = $request['vModelDataInicio'];
        $data_fim = $request['vModelDataFim'];
        $vDataPagamentoMkt = ltrim($request['vModelDataPagamentoMkt'], '0');

        $data = [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'data_pagamento' => $vDataPagamentoMkt,
        ];

        header('Content-type: application/json');

        if ($hiddenId == 0) {
            if (!$this->model_cycles->checkModelExists($data)) {
                $this->model_cycles->insertModel($data);
                exit(true);
            }
            echo $this->db->last_query();
        } else {
            $this->model_cycles->update($hiddenId, $data, $this->model_cycles::TABLE_MODEL_CICLO);
            exit(true);
        }

        exit(false);

    }

    public function saveCycle($allowSave=1)
    {

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);
        
        if (!$request) {
            $requestColumns = ['vDataInicio', 'vDataFim', 'vHiddenId', 'vMarketplace', 'vDataPagamentoMkt', 'vDataPagamentoConectala', 'vDateCut', 'vStores'];
            $request = [];
            foreach ($requestColumns as $requestColumn) {
                $request[$requestColumn] = $this->postClean($requestColumn, TRUE);
            }
        }

        header('Content-type: application/json');

        $data_inicio = $request['vDataInicio'];
        $data_fim = $request['vDataFim'];
        $hiddenId = $request['vHiddenId'];
        $mktplace_choice = $request['vMarketplace'];
        $vDataPagamentoMkt = ltrim($request['vDataPagamentoMkt'], '0');
        $vDataPagamentoConectala = ltrim($request['vDataPagamentoConectala'] ?? "0", '0');
        $vDateCut = $this->model_cycles->getCutDates(true, $request['vDateCut']);
        $vStore = empty($request['vStores']) ? null : $request['vStores'];

        $valid_cycle = $this->model_cycles->checkValidCycles($mktplace_choice, $data_inicio, $data_fim, $vStore, $hiddenId);

        if ($valid_cycle) {

            if ($allowSave){

                $this->model_cycles->saveCycle($data_inicio, $data_fim, $vDataPagamentoMkt, $vDataPagamentoConectala, $vDateCut, $vStore, $hiddenId, $mktplace_choice, $request['vStores']);

                $this->model_cycles->deletePreviousGeneratedConciliationData();

            }

            exit(json_encode('success', true));
        }

        exit(json_encode('error', true));
    }
    
    public function saveCycleApi(){}


    public function removeCycle($cycle_id)
    {
        header('Content-type: application/json');

        if ($this->model_cycles->exists($cycle_id)) {
            $this->model_cycles->update($cycle_id, array('ativo' => 0), $this->model_cycles::TABLE_CICLO);

            exit(json_encode('success', true));
        }

        exit(json_encode('error', true));
    }

    public function removeModel($cycle_id)
    {
        header('Content-type: application/json');

        if ($this->model_cycles->exists($cycle_id, $this->model_cycles::TABLE_MODEL_CICLO)) {
            $this->model_cycles->delete($cycle_id, $this->model_cycles::TABLE_MODEL_CICLO);

            exit(json_encode('success', true));
        }

        exit(json_encode('error', true));
    }

    public function removeCycles()
    {

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $this->db->trans_begin();

        if (is_array($request)) {
            foreach ($request as $cycle) {
                $cycle_id = $cycle;
                $this->model_cycles->update($cycle_id, array('ativo' => 0), $this->model_cycles::TABLE_CICLO);
            }
        }

        header('Content-type: application/json');

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            exit(json_encode('error', true));
        } else {
            $this->db->trans_commit();
            exit(json_encode('success', true));
        }

    }

    /**
     * Retorna uma lista de ciclos cadastrados agrupados por data_inicio, data_fim e data_pagamento
     *
     * @return    array
     */
    public function getCyclesRegistered()
    {

        // $cycles = $this->model_cycles->getListCycles(true);
        $cycles = $this->db->query("
        SELECT data_inicio, data_fim, data_pagamento, store_id FROM param_mkt_ciclo GROUP BY data_inicio, data_fim, data_pagamento
        UNION
        SELECT data_inicio, data_fim, data_pagamento, null FROM model_cycle GROUP BY data_inicio, data_fim, data_pagamento
        ")->result_array();

        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    public function getStoreCycleById($cycleId)
    {

        $request = [];
        $request['vCycleId'] = $cycleId;
        $request['vStore'] = null;
        $request['vInicio'] = null;
        $request['vFim'] = null;
        $request['vDataPagamento'] = null;
        $request['vDataPagamentoConectala'] = null;

        $cycles = $this->model_cycles->getListCycles(false, 'store', $request);

        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    public function getAllCyclesRegisteredByMarketplace()
    {

        $cycles = $this->model_cycles->getListCycles(false, 'mkt');

        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    public function getAllCyclesRegisteredByStore()
    {

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $cycles = $this->model_cycles->getListCycles(false, 'store', $request);

        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    public function getAllCycles()
    {

        // $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        // $request = json_decode($stream_clean, true);

        // $cycles = $this->model_cycles->getAllCycles(false, 'all', $request);
        $cycles = $this->model_cycles->getAllCycles();
        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    public function getAllCyclesByStoreToStoreScreen($storeId)
    {

        ob_start();
        $postdata = $this->postClean(NULL, TRUE);

        $request = [];
        $request['vStore'] = $storeId;
        $request['vInicio'] = null;
        $request['vFim'] = null;
        $request['vDataPagamento'] = null;
        $request['vDataPagamentoConectala'] = null;

        $cycles = $this->model_cycles->getListCycles(false, 'store', $request);

        $rows = [];

        if ($cycles) {
            foreach ($cycles as $cycle) {
                $rows[] = [
                    'id' => $cycle['pmc_id'],
                    'marketplace' => $cycle['descloja'],
                    'start_date' => $cycle['data_inicio'],
                    'end_date' => $cycle['data_fim'],
                    'payment_date' => $cycle['data_pagamento'],
                    'payment_date_conecta' => $cycle['data_pagamento_conecta'],
                    'cut_date' => $cycle['cut_date'],
                    'buttons' => '<button type="button" class="btn btn-link btn-xs row-action edit-cycle"
                                                    data-toggle="modal" data-target="#register-cycle" onclick="editThisCycle(' . $cycle["pmc_id"] . ')">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-link btn-xs row-action delete-cycle"
                                        onclick="removeCycle(' . $cycle["pmc_id"] . ')">
                                    <i class="fa fa-trash"></i>
                                </button>',
                ];
            }
        }

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => count($cycles),
            "recordsFiltered" => count($cycles),
            "data" => $rows,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    /**
     * Retorna os marketplaces
     *
     * @return    object
     */
    public function getAllMktPlace()
    {
        $mkts = $this->model_cycles->getAllMarketplaces();
        header('Content-type: application/json');
        exit(json_encode($mkts));
    }

    public function getAllStores()
    {
        $stores = $this->model_cycles->getAllStores();
        header('Content-type: application/json');
        exit(json_encode($stores));
    }

    /**
     * Retorna das datas de corte da tabela
     *
     * @return    object
     */
    public function getCyclesCutDates()
    {
        $cycles = $this->model_cycles->getCutDates();

        header('Content-type: application/json');
        exit(json_encode($cycles));
    }

    /**
     * Verifica se o ciclo existente escolhido pode ser usado pelo marketplace selecionado
     *
     */
    public function checkCycleUsed()
    {
        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $data_inicio = $request['data_inicio'];
        $data_fim = $request['data_fim'];
        $mktplace_choice = $request['mktplace'];
        $store_id = $request['store_id'] ?? null;

        exit($this->model_cycles->checkValidCycles($mktplace_choice, $data_inicio, $data_fim, $store_id));
    }

    /**
     * @throws PHPExcel_Exception
     */
    public function exportXls()
    {


        $output = rawurldecode($this->input->get('search'));
        $output = json_decode($output);

        $request = [
            'vStore' => $output->vStore,
            'vInicio' => $output->vInicio,
            'vFim' => $output->vFim,
            'vDataPagamento' => $output->vDataPagamento,
            'vDataPagamentoConectala' => $output->vDataPagamentoConectala
        ];

        $line = 1;
        $column = 0;

        // $stores = [];

        $cycles = $this->model_cycles->getListCycles(false, 'store', $request);

        $objPHPExcel = new Excel();
        $objPHPExcel->setActiveSheetIndex();

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        if ($this->settingSellerCenter == 'conectala') {
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);

        } else {
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        }

        $objPHPExcel->getActiveSheet()->getStyle('A1:J1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "LOJA");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "MARKETPLACE");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "DIA DE INÍCIO DO CICLO");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "DIA DE FIM DO CICLO");
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "DIA DE PAGAMENTO");
        if ($this->settingSellerCenter == 'conectala') {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, "DIA DE PAGAMENTO CONECTALÁ");
        }
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line++, "DIA DE CORTE");

        foreach ($cycles as $cycle) {
            // if (!in_array($cycle['name'], $stores)) {
                // $stores[] = $cycle['name'];
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $line, $cycle['name']);
                // $line++;
            // }
            $column = 1;
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['descloja']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['data_inicio']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['data_fim']);
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['data_pagamento']);
            if ($this->settingSellerCenter == 'conectala') {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['data_pagamento_conecta']);
            }
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($column++, $line, $cycle['data_usada']);
            $line++;
            $column = 0;
        }

        $filename = "ciclos_por_lojas_" . date("Y-m-d-H-i-s") . ".xlsx";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');

    }

    public function add_massive($type)
    {

        if (!in_array($type, ['marketplace', 'loja'])) {
            redirect('cycles', 'refresh');
        }

        $this->data['page_now'] = $type == 'marketplace' ? 'parameter_payment_cycles_add_massive_by_marketplace' : 'parameter_payment_cycles_add_massive_by_store';
        $this->data['page_title'] = 'Adicionar ou alterar ciclos de pagamento em massa por ' . $type;
        $this->data['by_type'] = $type;
        $this->data['sellercenter'] = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

        $this->render_template('cycles/add_massive', $this->data);

    }

    public function download_cycles($type)
    {

        if (!in_array($type, ['marketplace', 'loja'])) {
            redirect('cycles', 'refresh');
        }

        if ($type == 'marketplace') {
            $cycles = $this->model_cycles->getListCycles(false, 'mkt');
        } else {
            $cycles = $this->model_cycles->getListCycles();
        }

        if (!$cycles) {
            $this->session->set_flashdata('error', lang('application_no_cicle_found'));
            redirect('cycles', 'refresh');
        }

        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

        $columns = $this->generateRequiredColumnsByType($type, $sellercenter);

        $data = array(
            $columns,
        );

        foreach ($cycles as $cycle) {

            $row = [];

            $row[] = $cycle['descloja'];
            if ($type == 'loja') {
                $row[] = $cycle['store_id'];
            }
            $row[] = $cycle['pmc_id'];
            $row[] = $cycle['data_inicio'];
            $row[] = $cycle['data_fim'];
            $row[] = $cycle['cut_date'];
            $row[] = $cycle['data_pagamento'];
            if ($sellercenter == 'conectala') {
                $row[] = $cycle['data_pagamento_conecta'];
            }

            $data[] = $row;

        }

        $filename = 'cycles_' . $type . '_' . uniqid() . '.csv';

        $file = fopen(sys_get_temp_dir() . $filename, 'w');

        foreach ($data as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        readfile(sys_get_temp_dir() . $filename);

    }

    public function upload_massive($type, $method)
    {

        if ($method == 'success') {

            if (!$_POST['json']) {
                redirect('cycles/add_massive/' . $type, 'refresh');
            }

            $json = json_decode($_POST['json'], true);

            foreach ($json as $row) {

                $data_inicio = $row['Data Inicio Ciclo'];
                $data_fim = $row['Data Fim Ciclo'];
                $hiddenId = $row['Id Ciclo'];
                $marketplace = $this->model_cycles->getMarketplaceByName($row['Marketplace']);
                $mktplace_choice = $marketplace['id_mkt'];

                $vDataPagamentoMkt = $row['Data Pagamento Marketplace'];
                $vDataPagamentoConectala = $row['Data Pagamento Conecta la'] ?? null;
                $vDateCut = $this->model_cycles->getCutDatesByName(true, $row['Data de Corte']);
                $vStore = $row['Id Loja'] ?? null;

                $data = [
                    'data_inicio' => $data_inicio,
                    'data_fim' => $data_fim,
                    'data_pagamento' => $vDataPagamentoMkt,
                    'data_pagamento_conecta' => empty($vDataPagamentoConectala) ? null : $vDataPagamentoConectala,
                    'data_usada' => $vDateCut->cut_date,
                    'data_inclusao' => date('Y-m-d H:i:s'),
                    'store_id' => $vStore,
                    'ativo' => 1,
                ];

                if ($hiddenId == 0) {
                    $data['integ_id'] = $mktplace_choice;
                    $this->model_cycles->insert($data);
                } else {
                    $this->model_cycles->update($hiddenId, $data) . $this->model_cycles::TABLE_CICLO;
                }

            }

            $this->model_cycles->deletePreviousGeneratedConciliationData();

            $this->session->set_flashdata('success', 'Importação em massa realizada com sucesso');
            redirect('cycles?type=' . $type, 'refresh');

            return;

        }

        if ($method == 'validate') {

            if (!isset($_FILES['file'])) {
                redirect('cycles/add_massive/' . $type, 'refresh');
            }

            ob_start();

            $successItens = [];
            $errors = [];

            $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');

            $columns = $this->generateRequiredColumnsByType($type, $sellercenter);

            $rows = readTempCsv($_FILES['file']['tmp_name']);

            $line = 1; //First line = column names
            foreach ($rows as &$row) {

                $line++;

                foreach ($columns as $column) {
                    if (!isset($row[$column])) {
                        $errors[$line][] = "Coluna {$column} não informada";
                    }
                }

                //If some column was not found, skip to next row
                if (isset($errors[$line])) {
                    continue;
                }

                if ($row['Marketplace']) {
                    if (!$this->model_cycles->marketplaceNameExists($row['Marketplace'])) {
                        $errors[$line][] = "Marketplace {$row['Marketplace']} inexistente";
                    }
                } else {
                    $errors[$line][] = "Marketplace não informado";
                }

                if ($type == 'loja') {
                    if ($row['Id Loja']) {
                        if (!$this->model_stores->storeExists($row['Id Loja'])) {
                            $errors[$line][] = "Id Loja {$row['Id Loja']} inexistente";
                        }
                    } else {
                        $errors[$line][] = "Id Loja não informado para importação em massa para lojas";
                    }
                }

                $row['Data Inicio Ciclo'] = abs(intval($row['Data Inicio Ciclo']));
                $row['Data Fim Ciclo'] = abs(intval($row['Data Fim Ciclo']));
                $row['Data Pagamento Marketplace'] = abs(intval($row['Data Pagamento Marketplace']));

                if (!((int)$row['Data Inicio Ciclo'] > 0 && (int)$row['Data Fim Ciclo'] > 0)) {
                    $errors[$line][] = "Data Inicio Ciclo '{$row['Data Inicio Ciclo']}' e '{$row['Data Fim Ciclo']}' não foram informados corretamente números positivos";
                } elseif ((int)$row['Data Inicio Ciclo'] <= 0) {
                    $errors[$line][] = "Data Inicio Ciclo '{$row['Data Inicio Ciclo']}' não pode ser menor que 0";
                } elseif ((int)$row['Data Fim Ciclo'] <= 0) {
                    $errors[$line][] = "Data Fim Ciclo '{$row['Data Fim Ciclo']}' não pode ser menor que 0";
                } elseif ((int)$row['Data Inicio Ciclo'] > 31) {
                    $errors[$line][] = "Data Inicio Ciclo '{$row['Data Inicio Ciclo']}' não pode ser maior que 31";
                } elseif ((int)$row['Data Fim Ciclo'] > 31) {
                    $errors[$line][] = "Data Fim Ciclo '{$row['Data Fim Ciclo']}' não pode ser maior que 31";
                }

                if ($row['Data de Corte']) {
                    if (!$this->model_cycles->cutDateExists($row['Data de Corte'])) {
                        $errors[$line][] = "Data de Corte {$row['Data de Corte']} inexistente";
                    }
                } else {
                    $errors[$line][] = "Data de Corte não informada";
                }

                if ($row['Id Ciclo'] && !$this->model_cycles->exists($row['Id Ciclo'])) {
                    $errors[$line][] = "Ciclo {$row['Id Ciclo']} não encontrado para atualização, se você deseja cadastrar, remova esse item";
                }

                if ($row['Data Pagamento Marketplace'] && $row['Data Pagamento Marketplace'] > 31) {
                    $errors[$line][] = "Data Pagamento Marketplace: {$row['Data Pagamento Marketplace']} não pode ser maior que 31";
                } elseif (!$row['Data Pagamento Marketplace']) {
                    $errors[$line][] = "Data Pagamento Marketplace não informada";
                }

                $marketplace = $this->model_cycles->getMarketplaceByName($row['Marketplace']);
                $store_id = null;
                if ($type == 'loja') {
                    $store_id = (int)$row['Id Loja'];
                }
                $cicle_id = null;
                if ($row['Id Ciclo']) {
                    $cicle_id = $row['Id Ciclo'];
                }

                if (!$this->model_cycles->checkValidCycles($marketplace['id_mkt'], $row['Data Inicio Ciclo'], $row['Data Fim Ciclo'], $store_id, $cicle_id)) {
                    $error = "Não é possível incluir um ciclo que sobreponha um ciclo já cadastrado anteriormente para o mesmo marketplace: {$row['Marketplace']}";
                    if ($type == 'loja') {
                        $error .= " e Loja ID: {$store_id}";
                    }
                    $errors[$line][] = $error;
                }

                if (!isset($errors[$line])) {
                    $successItens[] = $row;
                }

            }

            $result = [];
            $result['rows'] = $rows;
            $result['success_itens'] = $successItens;
            $result['errors'] = $errors;

            $this->data['result'] = $result;

            $this->data['page_now'] = 'parameter_xls_validation_add_massive';
            $this->data['page_title'] = lang('application_parameter_xls_validation_add_massive');
            $this->data['by_type'] = $type;

            $this->render_template('cycles/validate_add_massive', $this->data);

        }

    }

    /**
     * @param $type
     * @param $sellercenter
     * @return array
     */
    public function generateRequiredColumnsByType($type, $sellercenter): array
    {
        $columns = [];
        $columns[] = 'Marketplace';
        if ($type == 'loja') {
            $columns[] = 'Id Loja';
        }
        $columns[] = 'Id Ciclo';
        $columns[] = 'Data Inicio Ciclo';
        $columns[] = 'Data Fim Ciclo';
        $columns[] = 'Data de Corte';
        $columns[] = 'Data Pagamento Marketplace';
        if ($sellercenter == 'conectala') {
            $columns[] = 'Data Pagamento Conecta la';
        }
        return $columns;
    }

}