<?php

require APPPATH . "controllers/BatchC/GenericBatch.php";
require APPPATH . "libraries/Integration_v2/internal_api/ToolsProduct.php";

use Integration\Integration_v2\internal_api\ToolsProduct;

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 * @property Model_users $model_users
 * @property Model_groups $model_groups
 * @property Model_integrations $model_integrations
 * @property CSV_Validation $csv_validation
 * @property ToolsProduct $toolsProduct
 * @property Model_collections $model_collections
 */

/**
 * Class CollectionBatch
 */
class CollectionBatch extends GenericBatch
{

    const TABLE_NAME = 'csv_to_verification';

    /**
     * @var array $errors_import
     */
    private $errors_import = [];

    /**
     * @var array $data_groups_by_user
     */
    private $data_groups_by_user;

    /**
     * @var array $data_users
     */
    private $data_users;

    /**
     * @var array $data_store_by_company
     */
    private $data_store_by_company;

    /**
     * @var array $collections
     */
    private $collections = [];

    /**
     * @var string $int_to
     */
    private $int_to;

    /**
     * @var int $line
     */
    private $line = 0;

    /**
     * @var array $new_file_with_error
     */
    private $new_file_with_error;

    /**
     * @var ToolsProduct $toolProduct
     */
    private $toolsProduct;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

        //Models
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_groups');
        $this->load->model('model_collections');
        $this->load->model('model_integrations');

        // Libraries
        $this->toolsProduct = new ToolsProduct();
        $this->load->library('CSV_Validation');

        $this->new_file_with_error  = array();

    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @param int $line
     */
    public function setLine(int $line): void
    {
        $this->line += $line;
    }

    /**
     * @return array
     */
    private function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * @return string
     */
    private function getIntTo(): string
    {
        return $this->int_to;
    }

    /**
     * @param string $int_to
     * @return void
     */
    private function setIntTo(string $int_to): void
    {
        $this->int_to = $int_to;
    }

    /**
     * @param null $id
     * @param null $params
     */
    public function runSyncCollections($id = null, $params = null): void
    {

        $this->startJob(__FUNCTION__, $id, $params);

        $csvFiles = $this->getDontChecked();

        foreach ($csvFiles as $csvFile) {

            $this->collection = $this->csv_validation->convertCsvToArray($csvFile["upload_file"]);

            foreach ($this->collection as $line => $value) {

                $this->setLine(1);

                // Linha em branco.
                if ($this->csv_validation->lineEmptyCheck($value)) {
                    echo "Linha $line em branco\n";
                    continue;
                }

                try {
                    $this->initValidation($csvFile, $value);
                } catch (Exception $exception) {
                    $this->errors_import[] = array(
                        "line" => $this->getLine(),
                        "message" => $exception->getMessage()
                    );
                    $this->new_file_with_error[] = $value;
                    echo $exception->getMessage() . "\n";
                    continue;
                }

                try {
                    $integration = $this->processCsv();
                    if (!$integration) {
                        continue;
                    }

                    $this->setIntTo($integration->int_to);

                    $storeId = trim($value['ID da Loja'] ?? '');
                    $productSku = trim($value['Sku do Produto'] ?? '');
                    $collectionPath = trim($value['Navegacao'] ?? '');

                    if (false === mb_check_encoding($collectionPath, 'UTF-8')) {
                        $collectionPath = utf8_encode($collectionPath);
                    }

                    $collection = $this->model_collections->getCollectionActiveDatabyPath($collectionPath);
                    if (!$collection) {
                        throw new Exception("Navegação não encontrada no banco de dados.");
                    }

                    if (!array_key_exists($storeId, $this->collections)) {
                        $this->collections[$storeId] = [];
                    }

                    if (!array_key_exists($productSku, $this->collections[$storeId])) {
                        $this->collections[$storeId][$productSku] = [];
                    }

                    if (!in_array($collection['id'], $this->collections[$storeId][$productSku])) {
                        $this->collections[$storeId][$productSku][] = $collection['id'];
                    }

                } catch (Exception $exception) {
                    $this->errors_import[] = array(
                        "line" => $this->getLine(),
                        "message" => $exception->getMessage()
                    );
                    $this->new_file_with_error[] = $value;
                    echo $exception->getMessage() . "\n";
                    continue;
                }

            }

            $this->processCollections();

            $this->saveFinalImportFile($csvFile['id']);

        }

        $this->endJob();
    }

    private function processCollections()
    {
        foreach ($this->getCollections() as $key => $collection) {

            $store_id = $key;

            try {
                $this->toolsProduct->startRun($store_id);
            } catch (InvalidArgumentException $exception) {
                $this->errors_import[] = array(
                    "line" => $this->getLine(),
                    "message" => $exception->getMessage()
                );
                $this->new_file_with_error[] = $collection;
                echo $exception->getMessage() . "\n";
                continue;
            }

            foreach ($collection as $keySku => $sku) {

                $sku = $keySku;
                $marketplace = $this->getIntTo();
                $collections = $this->collections[$store_id][$sku];

                try {
                    $this->toolsProduct->processCollection($sku, $marketplace, $collections);
                } catch (Exception $exception) {
                    $this->errors_import[] = array(
                        "line" => $this->getLine(),
                        "message" => $exception->getMessage()
                    );
                    $this->new_file_with_error[] = $sku;
                    echo $exception->getMessage() . "\n";
                    continue;
                }

            }

        }
    }

    private function processCsv()
    {

        $integration = $this->model_integrations->getIntegrationActive();
        if ($integration->num_Rows() > 1) {
            throw new Exception("Sellercenter não tem permissão para realizar o upload desse arquivo.");
        }

        return $integration->row();

    }

    private function getDontChecked(): array
    {
        return $this->model_csv_to_verifications->getDontChecked(false, 'Collection');
    }

    private function initValidation($csvFile, $data)
    {

        $user_data = $this->data_users[$csvFile['user_id']] ?? null;
        $user_group = $this->data_groups_by_user[$csvFile['user_id']] ?? null;
        $data_store_by_company = $this->data_store_by_company[$csvFile['user_id']] ?? null;
        $store_id = trim($data['ID da Loja'] ?? '');
        $user_email = $csvFile["user_email"];
        $productSku = trim($data['Sku do Produto'] ?? '');

        if (is_null($user_data)) {
            $user_data = $this->data_users[$csvFile['user_id']] = $this->model_users->getUserData($csvFile['user_id']);
        }

        if (is_null($user_group)) {
            $user_group = $this->data_groups_by_user[$csvFile['user_id']] = $this->model_groups->getUserGroupByUserId($csvFile['user_id']);
        }

        // Não encontrou o grupo de usuário.
        if (empty($user_group)) {
            throw new Exception("Grupo do usuário não encontrado.");
        }

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
                    $data_store_by_company = $this->data_store_by_company[$csvFile['user_id']] = $this->model_stores->getStoresByCompany($user_data['company_id']);
                }

                $store_to_validation = array_map(function ($item) {
                    return $item['id'];
                }, $data_store_by_company);
            }

            // O usuário não gerencia a loja do arquivo.
            if (!in_array($store_id, $store_to_validation)) {
                throw new Exception("Loja '$store_id' não foi encontrada para o usuário '$user_email'.");
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
        if (empty($data['Sku do Produto'])) {
            throw new Exception("Sku do Produto deve ser informado.");
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

}
