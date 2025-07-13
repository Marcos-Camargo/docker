<?php

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 * @property Model_integrations $model_integrations
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * @property Model_integrations_settings $model_integrations_settings
 * @property Model_control_sync_skuseller_skumkt $model_control_sync_skuseller_skumkt
 * 
 * @property CSV_Validation $csv_validation
 */
class ImportCSVSyncSkusellerSkumkt extends BatchBackground_Controller
{
    /**
     * @var int $store_id Código da loja.
     */
    private $store_id;

    /**
     * @var array $data_stores
     */
    private $data_stores;

    /**
     * @var array $data_users
     */
    private $data_users;

    /**
     * @var array $data_groups_by_user
     */
    private $data_groups_by_user;

    /**
     * @var array $data_store_by_company
     */
    private $data_store_by_company;

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

        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_control_sync_skuseller_skumkt');
        $this->load->library('CSV_Validation');
    }
    
    // php index.php BatchC/Automation/ImportCSVSyncSkusellerSkumkt run
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

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked(false, 'SyncPublishedSku');
        foreach ($csvs_to_import as $csv_import) {
            $this->importCsv($csv_import);
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    /**
     * @param int $store_id
     * @return void
     */
    private function setStoreId(int $store_id)
    {
        $this->store_id = $store_id;
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        return $this->store_id;
    }

    /**
     * Faz a validação inicial antes de ler a linha
     *
     * @param   array   $fileCsv
     * @param   array   $data
     * @throws  Exception
     */
    private function initValidation(array $fileCsv, array $data)
    {
        $user_data              = $this->data_users[$fileCsv['user_id']] ?? null;
        $user_group             = $this->data_groups_by_user[$fileCsv['user_id']] ?? null;
        $data_store_by_company  = $this->data_store_by_company[$fileCsv['user_id']] ?? null;
        $user_email             = $fileCsv["user_email"];
        $store_id               = trim($data['ID da Loja'] ?? '');
        $store_csv              = trim($data['ID da Loja'] ?? '');

        if (is_null($user_data)) {
            $user_data = $this->data_users[$fileCsv['user_id']] = $this->model_users->getUserData($fileCsv['user_id']);
            $this->data_users = $user_data;
        }

        if (is_null($user_group)) {
            $user_group = $this->data_groups_by_user[$fileCsv['user_id']] = $this->model_groups->getUserGroupByUserId($fileCsv['user_id']);
        }

        $permissions = unserialize($user_group['permission']);

        if (!in_array('syncPublishedSku', $permissions)) {
            throw new Exception("Usuário sem permissão.");
        }

        // Não encontrou o grupo de usuário.
        if (empty($user_group)) {
            throw new Exception("Grupo do usuário não encontrado.");
        }

        // Recupera o ID da loja.
        if (!empty($store_id) && !is_numeric($store_id)) {
            $store_data = $this->data_stores[$store_id] ?? null;
            if (is_null($store_data)) {
                $store_data = $this->data_stores[$store_id] = $this->model_stores->getStoreByIdOrName($store_id, $store_id);
                $this->data_stores[$store_data['id']] = $store_data;
            }
            $store_id = (string)$store_data['id'];
        }

        // Validar se usuário é da loja. Se for adminisrador não faz a validação.
        if ($user_group['only_admin'] != 1) {
            $store_to_validation = array();
            $store_to_validation[] = $user_data['store_id'];
            if ($user_data['store_id'] == 0) {
                if (is_null($data_store_by_company)) {
                    $data_store_by_company = $this->data_store_by_company[$fileCsv['user_id']] = $this->model_stores->getStoresByCompany($user_data['company_id']);
                }

                $store_to_validation = array_map(function ($item) {
                    return $item['id'];
                }, $data_store_by_company);
            }

            // O usuário não gerencia a loja do arquivo.
            if (!in_array($store_id, $store_to_validation)) {
                throw new Exception("Loja '$store_csv' não foi encontrada para o usuário '$user_email'.");
            }
        }

        $store_data = $this->data_stores[$store_id] ?? null;
        if (is_null($store_data)) {
            $store_data = $this->data_stores[$store_id] = $this->model_stores->getStoreByIdOrName($store_id, $store_id);
        }

        // Não encontrou a loja
        if (empty($store_data)) {
            throw new Exception("Loja $store_id não encontrada.");
        }

        // Não existe código SKU.
        if (empty($data['Sku no seller center'])) {
            throw new Exception("Sku no seller center deve ser informado.");
        }
        // Não existe código SKU.
        if (empty($data['Sku no marketplace'])) {
            throw new Exception("Sku no marketplace deve ser informado.");
        }
        // Não existe código SKU.
        if (!is_numeric($data['Sku no marketplace'])) {
            throw new Exception("Sku no marketplace deve ser numérico.");
        }

        $this->setStoreId($store_data['id']);
    }

    /**
     * Importa o arquivo csv
     *
     * @param array $csv
     */
    public function importCsv(array $csv)
    {
        $errors_import       = array();
        $new_file_with_error = array();
        $format_products     = array();
        $skumkt_already_used = array();

        echo "Inciando para o csv: {$csv["upload_file"]}\n";

        try {
            $products = $this->csv_validation->convertCsvToArray($csv["upload_file"]);
        } catch (Exception $e) {
            $errors_import[] = array(
                "line"      => 0,
                "message"   => "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados."
            );

            $this->saveFinalImportFile($csv['id'], $errors_import);
            return;
        }

        $integrations_settings = [];

        foreach ($products as $line => $value) {
            $line = $line + 1;
            $skumkt = $value['Sku no marketplace'];
            $skuseller = $value['Sku no seller center'];
            $int_to = $value['Marketplace'];

            // Linha em branco.
            if ($this->csv_validation->lineEmptyCheck($value)) {
                echo "Linha $line em branco\n";
                continue;
            }

            // Faz as validações iniciais.
            try {
                $this->initValidation($csv, $value);
            } catch (Exception $exception) {
                $errors_import[] = array(
                    "line"      => $line,
                    "message"   => $exception->getMessage()
                );
                $new_file_with_error[] = $value;
                echo "[ ERROR ] " . $exception->getMessage() . "\n";
                continue;
            }

            if (!array_key_exists($int_to, $integrations_settings)) {
                $integrations_settings[$int_to] = $this->model_integrations_settings->getIntegrationSettingsbyIntto($int_to);
            }

            if (empty($integrations_settings[$int_to])) {
                $new_file_with_error[] = $value;
                $errors_import[] = array(
                    "line" => $line,
                    "message" => "Não localizado a configuração do Marketplace $int_to"
                );
                echo "[ ERROR ] SKUMKT: $skumkt. Não localizado a configuração do Marketplace $int_to\n";
                continue;
            }

            if ($integrations_settings[$int_to]['skumkt_default'] !== 'sequential_id') {
                $new_file_with_error[] = $value;
                $errors_import[] = array(
                    "line" => $line,
                    "message" => 'Configuração do marketplace não está definida com padrão sequêncial.'
                );
                echo "[ ERROR ] SKUMKT: $skumkt. Configuração do marketplace não está definida com padrão sequêncial\n";
                continue;
            }

            if ($skumkt >= $integrations_settings[$int_to]['skumkt_sequential_initial_value']) {
                $new_file_with_error[] = $value;
                $errors_import[] = array(
                    "line" => $line,
                    "message" => "O sku do marketplace não deve ser maior ou igual que o valor inicial definido na configuração do marketplace. Valor inicial: {$integrations_settings[$int_to]['skumkt_sequential_initial_value']}"
                );
                echo "[ ERROR ] SKUMKT: $skumkt. SKUSELLER: $skuseller. O sku do marketplace não deve ser maior que o valor inicial definido na configuração do marketplace. Valor inicial: {$integrations_settings[$int_to]['skumkt_sequential_initial_value']}\n";
                continue;
            }

            if (in_array($skumkt, $skumkt_already_used)) {
                $new_file_with_error[] = $value;
                $errors_import[] = array(
                    "line" => $line,
                    "message" => "O sku do marketplace já está em uso em outra linha do arquivo."
                );
                echo "[ ERROR ] SKUMKT: $skumkt. SKUSELLER: $skuseller. O sku do marketplace já está em uso em outra linha do arquivo.\n";
                continue;
            }

            $skumkt_already_used[] = $skumkt;

            if ($this->model_control_sync_skuseller_skumkt->checkSkuAvaibility($this->getStoreId(), $skuseller, $int_to, $skumkt)) {
                $new_file_with_error[] = $value;
                $errors_import[] = array(
                    "line" => $line,
                    "message" => "O sku do marketplace já está em uso."
                );
                echo "[ ERROR ] SKUMKT: $skumkt. O sku do marketplace já está em uso.\n";
                continue;
            }

            $sku_mkt_avaibility = $this->model_control_sync_skuseller_skumkt->checkSkuMktAvaibilityInEveryStores($int_to, $skumkt);
            if ($sku_mkt_avaibility) {
                $sku_mkt_avaibility_store_id = $sku_mkt_avaibility['store_id'];
                $sku_mkt_avaibility_skuseller = $sku_mkt_avaibility['skuseller'];

                if ($sku_mkt_avaibility_skuseller != $skuseller || $sku_mkt_avaibility_store_id != $this->getStoreId()) {
                    $new_file_with_error[] = $value;
                    $errors_import[] = array(
                        "line" => $line,
                        "message" => "O sku do marketplace já está em uso em outro produto ou loja."
                    );
                    echo "[ ERROR ] SKUMKT: $skumkt. O sku do marketplace já está em uso em outro produto ou loja.\n";
                    continue;
                }
            }

            if (!array_key_exists($this->getStoreId(), $format_products)) {
                $format_products[$this->getStoreId()] = array(
                    'company_id' => $this->data_stores[$this->getStoreId()]['company_id'],
                    'int_to'     => $int_to,
                    'products'   => array()
                );
            }

            $format_products[$this->getStoreId()]['products'][$skuseller] = $skumkt;
        }

        foreach ($format_products as $store_id => $products) {
            $products_insert_batch = array();
            foreach ($products['products'] as $skuseller => $skumkt) {
                $products_insert_batch[] = array(
                    'store_id'   => $store_id,
                    'company_id' => $products['company_id'],
                    'skuseller'  => $skuseller,
                    'skumkt'     => $skumkt,
                    'int_to'     => $products['int_to']
                );
            }

            $products_chunk = array_chunk($products_insert_batch, 1000);
            foreach ($products_chunk as $product_chunk) {
                $this->model_control_sync_skuseller_skumkt->create_batch($product_chunk);
            }
        }

        // Se existem erros, criará um arquivo com as linhas com erros.
        if (!empty($errors_import)) {
            try {
                $this->csv_validation->createNewFileCsv(str_replace('.csv', '_with_error.csv', $csv["upload_file"]), $new_file_with_error, $csv["upload_file"]);
            } catch (Exception $exception) {
                $errors_import[] = array(
                    "line"      => 0,
                    "message"   => $exception->getMessage()
                );
            }
        }

        $this->saveFinalImportFile($csv['id'], $errors_import);
    }

    /**
     * Salva o processamento.
     *
     * @param   int     $csv_id
     * @param   array   $errors_import
     */
    private function saveFinalImportFile(int $csv_id, array $errors_import)
    {
        $processing_response = null;
        $situation = 'success';

        if (!empty($errors_import)) {
            // Ordena os erros de forma crescente.
            $errors_import_to_save = array();
            foreach (array_msort($errors_import, array('line' => SORT_ASC)) as $data) {
                $errors_import_to_save[] = $data;
            }

            $processing_response = json_encode($errors_import_to_save, JSON_UNESCAPED_UNICODE);
            $situation = 'err';
        }

        $this->model_csv_to_verifications->setChecked($csv_id, $situation, $processing_response);
    }
}
