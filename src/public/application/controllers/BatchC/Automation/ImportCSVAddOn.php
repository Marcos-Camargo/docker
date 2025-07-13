<?php

require APPPATH . "libraries/Integration_v2/internal_api/ToolsProduct.php";

use Integration\Integration_v2\internal_api\ToolsProduct;
use League\Csv\ByteSequence;
use League\Csv\Reader;
use League\Csv\TabularDataReader;

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Router $router
 *
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_attributes $model_attributes
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_category $model_category
 * @property Model_brands $model_brands
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * 
 * @property CSV_Validation $csv_validation
 * @property UploadProducts $uploadproducts
 * @property BlacklistOfWords $blacklistofwords
 *
 *
 * @property ToolsProduct $toolProduct
 */
class ImportCSVAddOn extends BatchBackground_Controller
{
    private $all_string_verification_if_utf8_encode = [
        'Nome do Item',
        'Sku do Parceiro',
        'Sku do Add-On'
    ];

    /**
     * @var object 
     */
    private $fields_csv = array(
        'store_id'  => 'ID da Loja',
        'sku'       => 'Sku do Parceiro',
        'sku_addon' => 'Sku do Add-On'
    );

    /**
     * @var array[] $format_products Dados formatados dos produtos e variações para enviar na API.
     */
    private $format_products = array();

    /**
     * @var int $store_id Código da loja.
     */
    private $store_id;

    /**
     * @var string $product_sku Código SKU do product.
     */
    private $product_sku;

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

    /**
     * @var array $product_by_sku_and_store
     */
    private $product_by_sku_and_store;

    /**
     * @var array $errors_import
     */
    private $errors_import;

    /**
     * @var array $new_file_with_error
     */
    private $new_file_with_error;

    /**
     * @var array $product_lines
     */
    private $product_lines;

    /**
     * @var TabularDataReader $products
     */
    private $products;

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
        $this->load->model('model_category');
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->library('CSV_Validation');

        $this->toolProduct = new ToolsProduct();
    }
    
    // php index.php BatchC/Automation/ImportCSVAddOn run
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

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked(false, 'AddOnSkus');
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
     * @return string
     */
    private function getProductSku(): string
    {
        return $this->product_sku;
    }

    /**
     * @param string $product_sku
     * @return void
     */
    private function setProductSku(string $product_sku)
    {
        $this->product_sku = $product_sku;
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

        // Validar se usuário é da loja. Se for administrador não faz a validação.
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
        if (empty($data['Sku do Parceiro'])) {
            throw new Exception("Sku do Parceiro deve ser informado.");
        }
        // Não existe código SKU do Add-On.
        if (empty($data['Sku do Add-On'])) {
            throw new Exception("Sku do Add-On deve ser informado.");
        }

        $this->setStoreId($store_data['id']);
        $this->setProductSku($data['Sku do Parceiro']);
    }

    /**
     * Importa o arquivo csv
     *
     * @param array $csv
     */
    public function importCsv(array $csv)
    {
        $this->errors_import        = array();
        $this->new_file_with_error  = array();
        $this->variation_lines      = array();
        $this->product_lines        = array();
        $this->products             = array();

        echo "Inciando para o csv: {$csv["upload_file"]}\n";

        $store_import = array();

        try {
            $this->products = $this->csv_validation->convertCsvToArray($csv["upload_file"]);
        } catch (Exception $e) {
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados."
            );

            $this->saveFinalImportFile($csv['id']);
            return;
        }

        foreach ($this->products as $line => $value) {
            $line = $line + 1;

            // Linha em branco.
            if ($this->csv_validation->lineEmptyCheck($value)) {
                echo "Linha $line em branco\n";
                continue;
            }

            // Faz as validações iniciais.
            try {
                $this->initValidation($csv, $value);
            } catch (Exception $exception) {
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $exception->getMessage()
                );
                $this->new_file_with_error[] = $value;
                echo $exception->getMessage() . "\n";
                continue;
            }
            $sku = $value['Sku do Parceiro'];
            $sku_add_on = $value['Sku do Add-On'];

            $this->product_lines[$sku] = $line;

            // Grava o código da loja para ser realizado a requisição loja a loja.
            if (!in_array($this->getStoreId(), $store_import)) {
                $store_import[] = $this->getStoreId();
            }

            // Define o código da loja que foi recuperado.
            $value['ID da Loja'] = $this->getStoreId();

            // Verifica se o produto já existe.
            $key_product_validation = "$sku-{$this->getStoreId()}";
            $product_exist = $this->product_by_sku_and_store[$key_product_validation] ?? null;
            if (is_null($product_exist)) {
                $product_exist = $this->product_by_sku_and_store[$key_product_validation] = !empty($this->model_products->getProductBySkuAndStore($sku, $this->getStoreId()));
            }

            // Verifica se o produto Ad-On já existe.
            $key_product_add_on_validation = "$sku_add_on-{$this->getStoreId()}";
            $product_add_on_exist = $this->product_by_sku_and_store[$key_product_add_on_validation] ?? null;
            if (is_null($product_add_on_exist)) {
                $product_add_on_exist = $this->product_by_sku_and_store[$key_product_add_on_validation] = !empty($this->model_products->getProductBySkuAndStore($sku_add_on, $this->getStoreId()));
            }

            if (!$product_exist || !$product_add_on_exist) {
                $message_error = "Sku do Parceiro ou Sku do Add-On não localizado";
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $message_error
                );
                $this->new_file_with_error[] = $value;
                echo $message_error . "\n";
                continue;
            }

            // Formata os dados e adiciona no vetor '$format_products' conforme a regra, se for para associar o sku add-on.
            $this->formatProduct($value);
        }

        foreach ($store_import as $store) {
            // Verifica se as informações da loja estão corretas.
            try {
                $this->toolProduct->startRun($store);
                $this->toolProduct->putCredentialsApiInternal($this->data_users['id']);
            } catch (InvalidArgumentException $exception) {
                $error_message = $exception->getMessage();
                echo "[ ERROR ][CHECK_STORE] Loja: $store. $error_message\n";
                $this->setErrorLine($this->format_products[$store], $error_message);
                continue;
            }

            // Add-on.
            $this->toolProduct->setJob('AddOn');
            foreach ($this->format_products[$store] as $product) {
                try {
                    $this->toolProduct->syncAddOnProduct($product);
                    echo "[SUCCESS][ADD_ON] SKU: {$product['sku']}\n";
                } catch (InvalidArgumentException $exception) {
                    $this->setLinesWithErrorToNewFile($product);
                    if (array_key_exists($product['sku'], $this->product_lines)) {
                        $this->errors_import[] = array(
                            "line" => $this->product_lines[$product['sku']],
                            "message" => $exception->getMessage()
                        );
                        echo "[ ERROR ][ADD_ON] SKU: {$product['sku']}. {$exception->getMessage()}\n";
                    }
                }
            }
        }

        // Se existem erros, criará um arquivo com as linhas com erros.
        if (!empty($this->errors_import)) {
            try {
                $this->csv_validation->createNewFileCsv(str_replace('.csv', '_with_error.csv', $csv["upload_file"]), $this->new_file_with_error, $csv["upload_file"]);
            } catch (Exception $exception) {
                $this->errors_import[] = array(
                    "line"      => 0,
                    "message"   => $exception->getMessage()
                );
            }
        }

        $this->saveFinalImportFile($csv['id']);
    }

    /**
     * Alimenta os vetores com a linha de erro e o motivo em cada linha.
     *
     * @param   array   $values
     * @param   string  $message
     * @return  void
     */
    private function setErrorLine(array $values, string $message)
    {
        foreach ($values as $value) {
            $this->errors_import[] = array(
                "line"      => $this->product_lines[$value['sku']] ?? 0,
                "message"   => $message
            );
            $this->setLinesWithErrorToNewFile($value);
        }
    }

    /**
     * Salva o processamento.
     *
     * @param   int $csv_id
     */
    private function saveFinalImportFile(int $csv_id)
    {
        $processing_response = null;
        $situation = 'success';

        if (!empty($this->errors_import)) {
            // Ordena os erros de forma crescente.
            $errors_import_to_save = array();
            foreach (array_msort($this->errors_import, array('line' => SORT_ASC)) as $data) {
                $errors_import_to_save[] = $data;
            }

            $processing_response = json_encode($errors_import_to_save, JSON_UNESCAPED_UNICODE);
            $situation = 'err';
        }

        $this->model_csv_to_verifications->setChecked($csv_id, $situation, $processing_response);
    }

    /**
     * Corrigi os dados para UTF8
     *
     * @param array $data
     */
    public function parseToUtf8(array &$data)
    {
        foreach ($this->all_string_verification_if_utf8_encode as $value) {
            if (isset($data[$value]) && false === mb_check_encoding($data[$value], 'UTF-8')) {
                $data[$value] = utf8_encode($data[$value]);
            }
        }
    }

    /**
     * Salva as linhas com erros em 'new_file_with_error'.
     *
     * @param   array   $data
     * @return  void
     */
    private function setLinesWithErrorToNewFile(array $data)
    {
        try {
            if (array_key_exists($data['sku'], $this->product_lines)) {
                $this->new_file_with_error[] = $this->products->fetchOne($this->product_lines[$data['sku']]);
            }

        } catch (\League\Csv\Exception $e) {}
    }

    /**
     * Formatar dados do produto para atualização/criação.
     *
     * @param   array   $data   Dados do produto, vindo do csv.
     */
    public function formatProduct(array $data)
    {
        if (!isset($this->format_products[$this->getStoreId()][$this->getProductSku()])) {
            $this->format_products[$this->getStoreId()][$this->getProductSku()] = array(
                'sku'       => $this->getProductSku(),
                'sku_addon' => array()
            );
        }

        $this->format_products[$this->getStoreId()][$this->getProductSku()]['sku_addon'][] = trim($data[$this->fields_csv['sku_addon']]);
    }
}
