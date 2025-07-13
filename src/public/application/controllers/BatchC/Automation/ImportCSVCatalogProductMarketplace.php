<?php

require APPPATH . "libraries/Integration_v2/internal_api/ToolsProduct.php";

use Integration\Integration_v2\internal_api\ToolsProduct;
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
 * @property Model_brands $model_brands
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * @property Model_catalogs $model_catalogs
 * @property Model_products_catalog $model_products_catalog
 *
 * @property CSV_Validation $csv_validation
 * @property UploadProducts $uploadproducts
 * @property BlacklistOfWords $blacklistofwords
 *
 *
 * @property ToolsProduct $toolProduct
 */
class ImportCSVCatalogProductMarketplace extends BatchBackground_Controller
{
    private $all_string_verification_if_utf8_encode = [
        'Sku do Parceiro',
        'Fabricante',
    ];

    /**
     * @var object
     */
    private $fields_csv = array(
        'store_id'                  => 'ID da Loja',
        'catalog_id'                => 'Catalogo',
        'ean'                       => 'EAN Produto de Catalogo',
        'manufacturer'              => 'Fabricante',
        'sku'                       => 'Sku do Parceiro',
        'qty'                       => 'Quantidade em estoque',
        'maximum_discount_catalog'  => 'Limite Desconto(%)',
        'extra_operating_time'      => 'Prazo Operacional em dias',
        'status'                    => 'Status(0=Inativo|1=Ativo)',
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
     * @var int $catalog_id Código do catálogo.
     */
    private $catalog_id;

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
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->model('model_catalogs');
        $this->load->model('model_products_catalog');
        $this->load->library('CSV_Validation');

        $this->toolProduct = new ToolsProduct();
    }

    // php index.php BatchC/Automation/ImportCSVCatalogProductMarketplace/run
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

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked(false, 'CatalogProductMarketplace');
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
     * @param int $catalog_id
     * @return void
     */
    private function setCatalogId(int $catalog_id)
    {
        $this->catalog_id = $catalog_id;
    }

    /**
     * @return int
     */
    private function getCatalogId(): int
    {
        return $this->catalog_id;
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
        $this->parseToUtf8($data);
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

        $store_id = str_replace("\xEF\xBB\xBF", '', $store_id);

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
        if (empty($data['Sku do Parceiro'])) {
            throw new Exception("Sku do Parceiro deve ser informado.");
        }

        // Status não definido.
        if (empty($data['Status(0=Inativo|1=Ativo)'])) {
            throw new Exception("Status deve ser informado.");
        }

        // Limite de desconto não definido.
        if (empty($data['Limite Desconto(%)'])) {
            throw new Exception("Limite de desconto deve ser informado.");
        }

        $catalog = $this->model_catalogs->getCatalogData($data['Catalogo']);
        if (!$catalog) {
            $catalog = $this->model_catalogs->getCatalogByName($data['Catalogo']);
        }

        if (!$catalog) {
            throw new Exception("Catalogo informado não encontrado.");
        }

        // Não existe código SKU.
        $catalog_store = $this->model_catalogs->checkIfStoreInCatalog($store_data['id'], $catalog['id']);
        if (!$catalog_store) {
            throw new Exception("Catalogo informado não encontrado para a loja.");
        }

        $this->setStoreId($store_data['id']);
        $this->setCatalogId($catalog['id']);
        $this->setProductSku(trim($data[$this->fields_csv['sku']]));
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

            // Define o código da loja que foi recuperado.
            $value[$this->fields_csv['store_id']] = $this->getStoreId();
            $value[$this->fields_csv['catalog_id']] = $this->getCatalogId();

            $this->product_lines[$this->getProductSku() . '-' . $this->getCatalogId()] = $line;

            // Grava o código da loja para ser realizado a requisição loja a loja.
            if (!in_array($this->getStoreId(), $store_import)) {
                $store_import[] = $this->getStoreId();
            }

            // Verifica se o produto já existe.
            $key_product_validation = "{$this->getProductSku()}-{$this->getStoreId()}";
            $product_exist = $this->product_by_sku_and_store[$key_product_validation] ?? null;
            if (is_null($product_exist)) {
                $product_exist = $this->product_by_sku_and_store[$key_product_validation] = !empty($this->model_products->getProductBySkuAndStore($this->getProductSku(), $this->getStoreId()));
            }

            $this->formatProduct($value, $product_exist);
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

            // Criação de produtos.
            $this->toolProduct->setJob('CreateProduct');
            foreach ($this->format_products[$store] as $product) {
                try {
                    $this->toolProduct->createAssociateCatalogProduct($product);
                    echo "[SUCCESS][CREATE_PRODUCT] SKU: {$product['sku']} | Catalog: {$product['catalog_id']}\n";
                } catch (InvalidArgumentException $exception) {
                    $this->setLinesWithErrorToNewFile($product);
                    if (array_key_exists($product['sku'], $this->product_lines)) {
                        $this->errors_import[] = array(
                            "line" => $this->product_lines[$product['sku']],
                            "message" => $exception->getMessage()
                        );
                        echo "[ ERROR ][CREATE_PRODUCT] SKU: {$product['sku']} | Catalog: {$product['catalog_id']}. {$exception->getMessage()}\n";
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
        } catch (\League\Csv\Exception $e) {
        }
    }

    /**
     * Formatar dados do produto para atualização/criação.
     *
     * @param array $data           Dados do produto, vindo do csv.
     * @param bool  $product_exist  Produto existe
     */
    public function formatProduct(array $data, bool $product_exist)
    {
        $product = $this->convertDataCsv($data, $product_exist);
        $key_sku = $this->getProductSku() . '-' . $this->getCatalogId();

        if (!isset($this->format_products[$this->getStoreId()][$key_sku])) {
            $this->format_products[$this->getStoreId()][$key_sku] = array();
        }

        $this->format_products[$this->getStoreId()][$key_sku] = array_merge($this->format_products[$this->getStoreId()][$key_sku], $product);
    }

    /**
     * Formata os campos do CSV para o formato esperado para a importação.
     *
     * @param array $data           Dados do produto, vindo do csv.
     * @param bool  $product_exist  Produto existe
     * @return array
     */
    public function convertDataCsv(array $data, bool $product_exist): array
    {
        $convert = array(
            'catalog_id'    => $this->getCatalogId(),
            'sku'           => $this->getProductSku(),
            'store_id'      => $this->getStoreId(),
            'product_exist' => $product_exist
        );

        if (
            !empty($data[$this->fields_csv['qty']]) ||
            (
                array_key_exists($this->fields_csv['qty'], $data) &&
                $data[$this->fields_csv['qty']] == 0
            )
        ) {
            $convert['qty'] = trim($data[$this->fields_csv['qty']]);
        }

        if (!empty($data[$this->fields_csv['ean']])) {
            $convert['EAN'] = trim($data[$this->fields_csv['ean']]);
        }

        if (isset($data[$this->fields_csv['status']]) && $data[$this->fields_csv['status']] !== '') {
            $convert['status'] = trim($data[$this->fields_csv['status']]);
            if ($convert['status'] != 1) {
                $convert['status'] = 2;
            }
        }

        if (!empty($data[$this->fields_csv['manufacturer']])) {
            $convert['manufacturer'] = trim($data[$this->fields_csv['manufacturer']]);
        }

        if (
            !empty($data[$this->fields_csv['extra_operating_time']]) ||
            (
                array_key_exists($this->fields_csv['extra_operating_time'], $data) &&
                $data[$this->fields_csv['extra_operating_time']] == 0
            )
        ) {
            $convert['extra_operating_time'] = trim($data[$this->fields_csv['extra_operating_time']]);
        }

        if (!empty($data[$this->fields_csv['maximum_discount_catalog']])) {
            $convert['maximum_discount_catalog'] = trim($data[$this->fields_csv['maximum_discount_catalog']]);
        }

        return $convert;
    }
}
