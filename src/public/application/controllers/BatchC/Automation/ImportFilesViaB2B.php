<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use Integration\Integration_v2;
use Integration\Integration_v2\viavarejo_b2b\ToolsProduct;
use Integration_v2\viavarejo_b2b\Services\ProductAvailabilityService;
use Integration_v2\viavarejo_b2b\Services\ProductCatalogCreateService;
use Integration_v2\viavarejo_b2b\Services\ProductCatalogUpdateService;
use Integration_v2\viavarejo_b2b\Services\ProductStockService;

use Integration_v2\viavarejo_b2b\Resources\Factories\XMLDeserializerFactory;
use Integration_v2\viavarejo_b2b\Resources\Mappers\XML\AvailabilityDeserializer;
use Integration_v2\viavarejo_b2b\Resources\Mappers\XML\BaseObjectDeserializer;
use Integration_v2\viavarejo_b2b\Resources\Parsers\DTO\AvailabilityCollectionDTO;
use Integration_v2\viavarejo_b2b\Resources\Parsers\DTO\AvailabilityDTO;
use \Integration_v2\viavarejo_b2b\Controllers\ImportLoadFileController;
use \Integration_v2\viavarejo_b2b\Resources\Mappers\FileNameMapper;
use \Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/ToolsProduct.php';
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductCatalogCreateService.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductCatalogUpdateService.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductAvailabilityService.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Services/ProductStockService.php";

require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Controllers/ImportLoadFileController.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FileNameMapper.php";
require_once APPPATH . "libraries/Helpers/StringHandler.php";
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Factories/XMLDeserializerFactory.php';
require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLWrapper.php';
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Parsers/DTO/AvailabilityCollectionDTO.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/AvailabilityDeserializer.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Parsers/DTO/AvailabilityDTO.php";

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class ImportFilesViaB2B
 * Importação automática dos arquivos de cadastro de produtos, disponibilidade, estoque e preço da Via Varejo
 * Executa 1 vez por semana
 *
 * php index.php BatchC/Automation/ImportFilesViaB2B run
 *
 * @property Model_api_integrations $model_api_integrations
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_job_schedule $model_job_schedule
 *
 * @property ImportLoadFileController $importLoadFileController
 * @property FlagMapper $flagMapper
 * @property FileNameMapper $fileNameMapper
 *
 * @property Client $client
 *
 * @property ToolsProduct $toolsProduct
 * @property ProductCatalogCreateService $ProductCatalogCreateService
 * @property ProductCatalogUpdateService $productCatalogUpdateService
 * @property ProductAvailabilityService $productAvailabilityService
 * @property ProductStockService $productStockService
 */
class ImportFilesViaB2B extends BatchBackground_Controller
{
    const TMP_PATH = "%s/assets/files/products_via/tmp/sellercenter/files/import/%s/%s";

    const FILES_PATH = FCPATH . "assets/files/products_via";
    const FILE_PROCESSING_TYPE = 'batch';

    const FILE_NOT_AVAILABLE_ERROR = 'O arquivo não está disponível no momento';

    private $fileProcessingType = self::FILE_PROCESSING_TYPE;

    private $company_id;
    private $store_id;

    private $integration;

    private $currentFlag;
    private $user;

    private $baseDir;
    private $baseUrl;

    private $dateFolder;

    private $fileMetaLog;

    private $fileType;

    private $storesVia;

    private $client;

    private $flagId = 0;

    private $deserializer;

    private $campaignId = null;

    private $toolsProduct;
    private $ProductCatalogCreateService;
    private $productCatalogUpdateService;
    private $productAvailabilityService;
    private $productStockService;

    private $integrationData;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('FileDir');

        ini_set('max_execution_time', '900');
        set_time_limit(900);
        ini_set('memory_limit', '1024M');

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_api_integrations');
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_job_schedule');

        $this->toolsProduct = new ToolsProduct();
        $this->deserializer = new BaseObjectDeserializer();

        $this->importLoadFileController = new ImportLoadFileController(
            $this->model_csv_to_verifications,
            $this->model_job_schedule
        );

        $this->flagMapper = new FlagMapper();
        $this->fileNameMapper = new FileNameMapper();

        $this->fileType = $this->fileNameMapper::MAP_FILE_NAME_URL;

        $this->baseDir = FCPATH;
        $this->baseDir = strlen($this->baseDir) >= strripos($this->baseDir, '/') ? substr($this->baseDir, 0, strripos($this->baseDir, '/')) : $this->baseDir;
        $this->baseUrl = get_instance()->config->config['base_url'] ?? base_url();
        $this->dateFolder = date('Y-m-d', strtotime('now'));

        $this->client = new Client([
            'verify' => false,
            'timeout' => 900,
            'connect_timeout' => 900,
        ]);
    }


    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool Estado da execução
     */
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

        try {
            $this->storesVia = $this->model_api_integrations->getIntegrationByStoreVia();
            // aqui efetuamos o download, extracao de arquivos e insercao dos na fila e importacao
            foreach ($this->storesVia as $key => $store) {
                $flag = substr($store["integration"], 14);
                $this->company_id = $store["company_id"];
                $this->store_id = $store["store_id"];
                $companyId = $this->company_id;
                $storeId = $this->store_id;
                echo "\n\nImportação de catalogo para loja: {$store["nome_loja"]} \n";
                foreach ($this->fileType as $fileType => $typeFile) {
                    echo "Catálogo de produto ({$typeFile})\n";
                    $fullPath = $this->retrieveFileFromPlataformByFlagAndFileType($flag, $fileType, $storeId, $companyId);
                    if (isset($fullPath)) {
                        echo "Extraindo arquivos...\n";
                        $result['file_url'] = $this->retrieveUrlTmpFileByFilePath($fullPath);
                        $result['messages'] = $this->handleZippedFile($fullPath);
                        $result['info'] = $this->fileMetaLog;
                    }
                }
            }
            if (isset($result)) {
                $this->updateCatalogVia();
            }

        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    /**
     * Método responsável por baixar arquivos da via
     *
     * @param string  $flag     Bandeira -> pontofrio, casasbahia, extra
     * @param string  $fileType tipo de arquivo
     * @param int     $storeId  id da loja
     * @param int     $companyId  id da empresa
     * @return string
     */
    public function retrieveFileFromPlataformByFlagAndFileType($flag, $fileType, $storeId = null, $companyId = null)
    {
        $this->currentFlag = $flag;
        $this->company_id = $companyId ?? $this->company_id;
        $this->store_id = $storeId ?? $this->store_id;

        try {
            $integration = $this->validateFlagStore();
            $this->company_id = $integration['company_id'] ?? $this->company_id;
            $this->store_id = $integration['store_id'] ?? $this->store_id;
            $credentials = json_decode($integration['credentials'], true);
            $partnerId = $credentials['partnerId'] ?? 0;
            $this->campaignId = $credentials['campaign'] ?? null;
            $fileUrl = FileNameMapper::buildFileDownloadUrl($flag, $fileType, $partnerId);

            $this->importLoadFileController->setFlagName($fileUrl);
            $tmpDir = $this->retrieveTempPathFromBase($this->baseDir);
            if (!FileDir::createDir($tmpDir)) {
                throw new Exception("Error on create tmp dir '{$tmpDir}' \n");
            }

            $destinationPath = sprintf("%s/%s.zip", $tmpDir, $fileType);
            if (!file_exists($destinationPath)) {
                $redirectUrl = $this->retrieveDownloadFileUrl($fileUrl);
                echo "Baixando arquivos... ";
                $result = $this->downloadFileFromUrl($redirectUrl, $destinationPath);
                if (!$result) {
                    throw new Exception("Error no download do arquivo '{$fileType}.zip' from VIA: O arquivo não está disponível no momento");
                }
                echo "OK! \n";
            }
            return $destinationPath;
        } catch (Throwable $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
            die;
        }
    }

    /**
     * Método responsável por validar bandeira da loja
     *
     * @param
     * @return string
     */
    public function validateFlagStore()
    {
        $integration = $this->model_api_integrations->getIntegrationByCompanyId($this->company_id, FlagMapper::getIntegrationNameFromFlag($this->currentFlag));
        if ($integration && $integration['store_id'] != $this->store_id) {
            throw new ErrorException(
                sprintf(
                    "A bandeira <b>%s</b> corresponde a loja <b>%s #%s</b>, selecione a bandeira correta.",
                    FlagMapper::ENABLED_FLAGS[$this->currentFlag],
                    $integration['store_name'],
                    $integration['store_id']
                )
            );
        }
        $this->integration = $integration;
        return $integration;
    }

    /**
     * Método responsável por pegar caminho da pasta tmp da loja
     *
     * @param $base caminho da pasta tmp
     * @return string
     */
    protected function retrieveTempPathFromBase($base)
    {
        return sprintf(self::TMP_PATH . "/{$this->company_id}/{$this->store_id}/%s", $base, 'viab2b', 'zip', $this->dateFolder);
    }

    /**
     * Método responsável por recuperar url dos arquivos de download e fazer redirect para download dos arquivos
     *
     * @param $fileUrl url do arquivo pasta tmp
     * @return string
     */
    public function retrieveDownloadFileUrl($fileUrl)
    {
        $baseDir = in_array(ENVIRONMENT, ['development', 'development_gcp'])
        ? ($this->baseUrl ?? base_url()) : ImportLoadFileController::SERVER_ADDRESS_DOWNLOAD;
        $baseDir = !empty(getenv('DOCKER_INTERNAL_BASE_DIR')) ? getenv('DOCKER_INTERNAL_BASE_DIR') : $baseDir;
        return ImportLoadFileController::buildRedirectDownloadUrl($baseDir, $fileUrl);
    }

    /**
     * Método responsável por fazer requisicao na via e baixar arquivos na pasta tmp
     *
     * @param $url          url da via
     * @param $destination  pasta de destino onde arquivos seram baixados
     * @return string
     */
    public function downloadFileFromUrl($url, $destination)
    {
        try {
            $resource = \GuzzleHttp\Psr7\Utils::tryFopen($destination, 'w+');
            $response = $this->client->request('GET', $url, [
                'sink' => $resource,
                'synchronous' => true,
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Erro ao baixar arquivo em {$url}: {$response->getStatusCode()}");
            }
            $content = $response->getBody()->getContents();
            if (is_string($content) && strcasecmp(trim($content), self::FILE_NOT_AVAILABLE_ERROR) === 0) {
                throw new \Exception("Erro ao baixar arquivo em {$url}: {$content}");
            }
        } catch (\Exception | ClientException $e) {
            if (file_exists($destination)) {
                unlink($destination);
            }

            $message = $e->getMessage();
            if ($e instanceof ClientException) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            if (strpos(strtolower($message), strtolower(self::FILE_NOT_AVAILABLE_ERROR)) !== false) {
                $fileType = $this->fileNameMapper->getFileTypeByPart($destination);
                throw new \Exception("A VIA ainda não diponibilizou o arquivo {$fileType}.zip para download, tente novamente mais tarde.");
            }
            throw new \Exception("Erro ao baixar arquivo em {$url}: {$message}");
        }

        return true;
    }

    /**
     * Método responsável por recuperar arquivo Url Tmp por caminho de arquivo
     *
     * @param $filePath  caminho do arquivo
     * @return string
     */
    public function retrieveUrlTmpFileByFilePath($filePath)
    {
        if (file_exists($filePath)) {
            return str_replace($this->baseDir, $this->baseUrl, $filePath);
        }
        throw new Exception("Arquivo {$filePath} não existe.");
    }

    /**
     * Método responsável por lidar com arquivo compactado
     *
     * @param $filePath  caminho do arquivo
     * @return object
     */
    protected function handleZippedFile($filePath)
    {
        try {
            $extractedFiles = $this->processZippedFile($filePath);
            if (empty($extractedFiles)) {
                $fileInfo = pathinfo($filePath);
                throw new ErrorException("Não foram encontrados arquivos válidos para extração no arquivo {$fileInfo['basename']}\n");
            }
            return $this->handleExtractedFiles($extractedFiles);
        } catch (Throwable $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por processar arquivo compactado
     *
     * @param $pathToFile  pasta dos arquivos
     * @return object
     */
    protected function processZippedFile($pathToFile)
    {
        $destinationPath = sprintf(self::TMP_PATH . "/{$this->company_id}/{$this->store_id}/%sx%s", $this->baseDir, 'viab2b', 'xml', strtotime("now"), rand(100, 999));
        $this->importLoadFileController->extractZipFile($pathToFile, $destinationPath);
        $originPath = $destinationPath;
        $destinationPath = self::FILES_PATH . "/{$this->company_id}/{$this->store_id}";
        return $this->importLoadFileController->moveAndUpdateFilesPath($originPath, $destinationPath);
    }

    /**
     * Método responsável por lidar com arquivos extraídos e adicionalos a fila de importacao
     *
     * @param $files  pasta dos arquivos
     * @return object
     */
    protected function handleExtractedFiles(array $files = [])
    {
        $date = date('d/m/Y H:i:s');
        if ($this->fileProcessingType == 'batch') {
            $this->store_id = $integration['store_id'] ?? $this->store_id;
            $this->company_id = $integration['company_id'] ?? $this->company_id;
            $options['store']['id'] = $this->store_id;
            $options['user'] = $this->user;
            echo "Adicionando arquivos na fila de importação...";
            $queuedFiles = $this->importLoadFileController->sendFilesToQueue($files, $options);
            if (empty($queuedFiles)) {
                throw new ErrorException('Nenhum arquivo foi colocado na fila para processamento.');
            }
            $options = ['company_id' => $this->company_id ?? 0];

            $fileUploaded = current($queuedFiles)['upload_file'];
            $flag = $this->flagMapper->getFlagByPart($fileUploaded);
            $flagName = FlagMapper::ENABLED_FLAGS[$flag] ?? '';

            $fileType = $this->importLoadFileController->getFileType() ?? '';
            if (!empty($fileType)) {
                $filesMeta = [
                    $fileType => [
                        'lastImportationDate' => $date,
                        'filesCreationDate' => date('d/m/Y H:i:s', strtotime($this->importLoadFileController->getFileCreationDate())),
                        'totalFiles' => count($queuedFiles),
                        'pendingFiles' => count($queuedFiles),
                    ],
                ];
                $this->fileMetaLog = $filesMeta[$fileType];
                $credentials = json_decode($this->integration['credentials'], true);
                $meta = $credentials['meta'] ?? [];
                $files = array_merge($meta['files'] ?? [], $filesMeta);
                $credentials['meta'] = array_merge($meta, ['files' => $files]);
                $data = [
                    'id' => $this->integration['id'],
                    'credentials' => json_encode($credentials),
                ];
                $this->model_api_integrations->update($this->integration['id'], $data);
            }
            echo "OK! \n";
            return $queuedFiles;
        }
        throw new Exception('Rotina de processamento não implementada.');
    }

    /**
     * Método responsável por iniciar importacao de arquivos adicionados a fila de importacao
     *
     * @param
     * @return object
     */
    public function updateCatalogVia()
    {
        try {
            foreach ($this->fileType as $fileType => $typeFile) {
                switch ($fileType) {
                    case "B2BCompleto":
                        $module = 'ProductsImportViaB2BComplete';
                        $this->createProduct($module);
                        break;
                    case "B2BParcial":
                        $module = 'ProductsImportViaB2BPartial';
                        $this->updateProduct($module);
                        break;
                    case "B2BDisponibilidade":
                        $module = 'ProductsImportViaB2BAvailability';
                        $this->updateAvailability($module);
                        break;
                    case "B2BEstoque":
                        $module = 'ProductsImportViaB2BStock';
                        $this->updateStock($module);
                        break;
                    default:
                        throw new ErrorException("Não foi possível identificar o tipo de execução.");
                }
            }
        } catch (Throwable $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por cadastradas produtos
     *
     * @param $module modulo de execucao = ProductsImportViaB2BComplete
     * @return
     */
    public function createProduct($module)
    {
        try {
            echo "\n\nINICIANDO CADASTRO DE PRODUTOS...\n\n";
                // Verificar se existe arquivo json e xml para ler
                // Caso exista, deverá ser excluído os arquivo json para ler somente os xml.
                // Pois foi enviado uma nova carga.
                $getLastRows = $this->model_csv_to_verifications->getlastRowsXmlAndJsonFile($module, $this->store_id);
                if ($getLastRows['xml'] && $getLastRows['json'] && $getLastRows['xml'] > $getLastRows['json']) {
                    $this->model_csv_to_verifications->removeFileJsonByModuleAndStore($module, $this->store_id);
                }

                $queues = $this->model_csv_to_verifications->getDontChecked(false, $module, $this->store_id);
                foreach ($queues as $queue) {
                    $queueId = $queue['id'];
                    if (!file_exists($queue['upload_file'])) {
                        throw new ErrorException("Arquivo não encontrado no caminho especificado: {$queue['upload_file']}\n");
                    }
                    echo "Ler arquivo ID:$queueId  PATH:{$queue['upload_file']}\n";

                    // Recupera os dados do arquivo.
                    $path_info = pathinfo($queue['upload_file']);
                    // Atualiza os dados do registro na tabela.
                    $this->model_csv_to_verifications->update(['checked' => 1, 'final_situation' => 'processing'], $queueId);

                    // Verifica se precisa converter o xml em json.
                    $deserializedFile = $this->deserializeXMLFile($queue['upload_file']);

                    // Se o formato json, ele não vai ter a data de criação no campo 'form_data'.
                    if (!empty($queue['form_data'])) {
                        $this->deserializer->setCreationDateAttributeValue(json_decode($queue['form_data'])->creationDate);
                    }

                    try {
                        $this->fetchStore();
                        $this->toolsProduct->setAuth($this->store_id);
                    } catch (InvalidArgumentException $exception) {
                        $this->toolsProduct->log_integration(
                            "Erro para executar a integração",
                            "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                            "E"
                        );
                        throw new InvalidArgumentException($exception->getMessage());
                    }

                    $this->toolsProduct->setFlagId($this->flagId)->setFlagName($this->flagName);
                    $this->initializeServiceProviderCreate();
                    $productsError = $this->handleDeserializedXMLCreate($deserializedFile);
                    if (!empty($productsError)) {
                        $newPathFile = "{$path_info["dirname"]}/{$path_info["filename"]}.json";
                        file_put_contents($newPathFile, json_encode($productsError, JSON_UNESCAPED_UNICODE));

                        if ($path_info['extension'] === "xml") {
                            chmod($newPathFile, 0775);
                            $this->model_csv_to_verifications->create(
                                array(
                                    'upload_file' => $newPathFile,
                                    'user_id' => $queue['user_id'],
                                    'username' => $queue['username'],
                                    'user_email' => $queue['user_email'],
                                    'usercomp' => $queue['usercomp'],
                                    'allow_delete' => $queue['allow_delete'],
                                    'module' => $queue['module'],
                                    'form_data' => json_encode(array('creationDate' => datetimeBrazil($this->deserializer->getCreationDateAttributeValue(), null))),
                                    'store_id' => $queue['store_id'],
                                )
                            );
                        } else {
                            $this->model_csv_to_verifications->update(['update_at' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)], $queueId);
                        }
                    }

                    if (empty($productsError) || $path_info['extension'] === "xml") {
                        $this->model_csv_to_verifications->update(['final_situation' => 'success'], $queueId);
                    } else {
                        $this->model_csv_to_verifications->update(['checked' => 0, 'final_situation' => 'wait'], $queueId);
                    }
                    // Se a rotina não ultrapassou 4 minutos de processamento, ler o próximo arquivo.
                    // Isso por a rotina ser executada a cada 5 minutos.
                    $start_date = new DateTime(date(DATETIME_INTERNATIONAL, time()));
                    $end_date = $start_date->diff(new DateTime(date(DATETIME_INTERNATIONAL, $startTime)));
                    if ($end_date->i >= 4) {
                        break;
                    }
                    echo "Processamento menor que 4 minutos, será feito a leitura do próximo arquivo\n";
                }
        } catch (Throwable | Exception | ErrorException | InvalidArgumentException $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por desserializar arquivo XML da fila de importacao
     *
     * @param $filePath pasta dos arquivos
     * @return object
     */
    public function deserializeXMLFile(string $filePath): object
    {
        $path_info = pathinfo($filePath);
        $this->deserializer = XMLDeserializerFactory::provideDeserializerByFilePath($filePath);

        if ($path_info['extension'] === "xml") {
            $this->deserializer->deserialize(SimpleXMLWrapper::loadFile($filePath));
        } else {
            $this->deserializer->deserialize(json_decode(file_get_contents($filePath)));
        }
        return $this->deserializer->getDeserializedObject();
    }

    /**
     * Método responsável por buscar loja
     *
     * @param 
     * @return
     */
    public function fetchStore()
    {
        $this->flagId = $this->getFlagId();
        $this->flagName = $this->flagMapper->getFlagById($this->flagId);
        if (!empty($this->store_id)) {
            $this->integrationData = $this->model_api_integrations->getIntegrationByStoreId($this->store_id);
            if (!isset($this->integrationData['id'])
            ) {
                $this->store_id = 0;
            }
        }

        if (empty($this->store_id)) {
            $integration = FlagMapper::getIntegrationNameFromFlag($this->flagName);
            $this->integrationData = $this->model_api_integrations->getIntegrationByCompanyId($this->company_id, $integration);
            if (isset($this->integrationData['id'])
                && strpos($this->integrationData['integration'], $this->flagName) !== false
            ) {
                $this->store_id = $this->integrationData['store_id'];
            }
        }
    }

    /**
     * Método responsável por obter ID da bandeira
     *
     * @param 
     * @return
     */
    public function getFlagId()
    {
        return $this->deserializer->getFlagIdAttributeValue();
    }

    /**
     * Método responsável por instanciar ProductCatalogCreateService
     *
     * @param 
     * @return
     */
    public function initializeServiceProviderCreate()
    {
        $this->ProductCatalogCreateService = new ProductCatalogCreateService($this->toolsProduct);
    }

    /**
     * Método responsável por lidar com Desserializar XML para cadastro dos produtos
     *
     * @param $object objeto contendo xml ja manipulado
     * @return
     */
    public function handleDeserializedXMLCreate(object $object)
    {
        return $this->ProductCatalogCreateService->handleWithRawObject($object);
    }

    /**
     * Método responsável por atualizar produtos
     *
     * @param $module modulo de execucao = ProductsImportViaB2BPartial
     * @return
     */
    public function updateProduct($module)
    {
        try {
            echo "\n\nINICIANDO UPDATE DE PRODUTOS...\n\n";

                $getLastRows = $this->model_csv_to_verifications->getlastRowsXmlAndJsonFile($module, $this->store_id);
                if ($getLastRows['xml'] && $getLastRows['json'] && $getLastRows['xml'] > $getLastRows['json']) {
                    echo "Foi adicionado novos arquivos xml. Deverá colocar todos os arquivos jsons como cancelados.\n\n";
                    $this->model_csv_to_verifications->removeFileJsonByModuleAndStore($module, $this->store_id);
                }

                $queues = $this->model_csv_to_verifications->getDontChecked(false, $module, $this->store_id);
                foreach ($queues as $queue) {
                    $queueId = $queue['id'];
                    if (!file_exists($queue['upload_file'])) {
                        throw new ErrorException("Arquivo não encontrado no caminho especificado: {$queue['upload_file']}\n");
                    }
                    echo "Ler arquivo ID:$queueId  PATH:{$queue['upload_file']}\n";

                    // Recupera os dados do arquivo.
                    $path_info = pathinfo($queue['upload_file']);
                    // Atualiza os dados do registro na tabela.
                    $this->model_csv_to_verifications->update(['checked' => 1, 'final_situation' => 'processing'], $queueId);

                    // Verifica se precisa converter o xml em json.
                    $deserializedFile = $this->deserializeXMLFile($queue['upload_file']);

                    // Se o formato json, ele não vai ter a data de criação no campo 'form_data'.
                    if (!empty($queue['form_data'])) {
                        $this->deserializer->setCreationDateAttributeValue(json_decode($queue['form_data'])->creationDate);
                    }

                    try {
                        $this->fetchStore();
                        $this->toolsProduct->setAuth($this->store_id);
                    } catch (InvalidArgumentException $exception) {
                        $this->toolsProduct->log_integration(
                            "Erro para executar a integração",
                            "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                            "E"
                        );
                        throw new InvalidArgumentException($exception->getMessage());
                    }

                    $this->toolsProduct->setFlagId($this->flagId)->setFlagName($this->flagName);
                    $this->initializeServiceProviderUpdate();
                    $productsError = $this->handleDeserializedXMLUpdate($deserializedFile);
                    if (!empty($productsError)) {
                        $newPathFile = "{$path_info["dirname"]}/{$path_info["filename"]}.json";
                        file_put_contents($newPathFile, json_encode($productsError, JSON_UNESCAPED_UNICODE));

                        if ($path_info['extension'] === "xml") {
                            chmod($newPathFile, 0775);
                            $this->model_csv_to_verifications->create(
                                array(
                                    'upload_file' => $newPathFile,
                                    'user_id' => $queue['user_id'],
                                    'username' => $queue['username'],
                                    'user_email' => $queue['user_email'],
                                    'usercomp' => $queue['usercomp'],
                                    'allow_delete' => $queue['allow_delete'],
                                    'module' => $queue['module'],
                                    'form_data' => json_encode(array('creationDate' => datetimeBrazil($this->deserializer->getCreationDateAttributeValue(), null))),
                                    'store_id' => $queue['store_id'],
                                )
                            );
                        } else {
                            $this->model_csv_to_verifications->update(['update_at' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)], $queueId);
                        }
                    }

                    if (empty($productsError) || $path_info['extension'] === "xml") {
                        $this->model_csv_to_verifications->update(['final_situation' => 'success'], $queueId);
                    } else {
                        $this->model_csv_to_verifications->update(['checked' => 0, 'final_situation' => 'wait'], $queueId);
                    }
                    // Se a rotina não ultrapassou 4 minutos de processamento, ler o próximo arquivo.
                    // Isso por a rotina ser executada a cada 5 minutos.
                    $start_date = new DateTime(date(DATETIME_INTERNATIONAL, time()));
                    $end_date = $start_date->diff(new DateTime(date(DATETIME_INTERNATIONAL, $startTime)));
                    if ($end_date->i >= 4) {
                        break;
                    }
                    echo "Processamento menor que 4 minutos, será feito a leitura do próximo arquivo\n";
                }
        } catch (Throwable | Exception | ErrorException | InvalidArgumentException $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por instanciar ProductCatalogUpdateService
     *
     * @param 
     * @return
     */
    public function initializeServiceProviderUpdate()
    {
        $this->productCatalogUpdateService = new ProductCatalogUpdateService($this->toolsProduct);
    }

    /**
     * Método responsável por lidar com Desserializar XML para atualizacao dos produtos
     *
     * @param $object objeto contendo xml ja manipulado
     * @return
     */
    public function handleDeserializedXMLUpdate(object $object)
    {
        $this->productCatalogUpdateService->handleWithRawObject($object);
    }

    /**
     * Método responsável por atualizar preco dos produtos
     *
     * @param $module modulo de execucao = ProductsImportViaB2BAvailability
     * @return
     */
    public function updateAvailability($module)
    {
        try {
            echo "\n\nVERIFICANDO PREÇO DE PRODUTOS...\n\n";
                $getLastRows = $this->model_csv_to_verifications->getlastRowsXmlAndJsonFile($module, $this->store_id);
                if ($getLastRows['xml'] && $getLastRows['json'] && $getLastRows['xml'] > $getLastRows['json']) {
                    echo "Foi adicionado novos arquivos xml. Deverá colocar todos os arquivos jsons como cancelados.\n\n";
                    $this->model_csv_to_verifications->removeFileJsonByModuleAndStore($module, $this->store_id);
                }

                $queues = $this->model_csv_to_verifications->getDontChecked(false, $module, $this->store_id);
                foreach ($queues as $queue) {
                    $queueId = $queue['id'];
                    if (!file_exists($queue['upload_file'])) {
                        throw new ErrorException("Arquivo não encontrado no caminho especificado: {$queue['upload_file']}\n");
                    }
                    echo "Ler arquivo ID:$queueId  PATH:{$queue['upload_file']}\n";

                    // Recupera os dados do arquivo.
                    $path_info = pathinfo($queue['upload_file']);
                    // Atualiza os dados do registro na tabela.
                    $this->model_csv_to_verifications->update(['checked' => 1, 'final_situation' => 'processing'], $queueId);

                    // Verifica se precisa converter o xml em json.
                    $deserializedFile = $this->deserializeXMLFile($queue['upload_file']);

                    // Se o formato json, ele não vai ter a data de criação no campo 'form_data'.
                    if (!empty($queue['form_data'])) {
                        $this->deserializer->setCreationDateAttributeValue(json_decode($queue['form_data'])->creationDate);
                    }

                    $this->toolsProduct->setFlagId($this->flagId)->setFlagName($this->flagName);
                    $this->initializeServiceProviderAvailability();
                    $productsError = $this->handleDeserializedXMLAvailability($deserializedFile);
                    if (!empty($productsError)) {
                        $newPathFile = "{$path_info["dirname"]}/{$path_info["filename"]}.json";
                        file_put_contents($newPathFile, json_encode($productsError, JSON_UNESCAPED_UNICODE));

                        if ($path_info['extension'] === "xml") {
                            chmod($newPathFile, 0775);
                            $this->model_csv_to_verifications->create(
                                array(
                                    'upload_file' => $newPathFile,
                                    'user_id' => $queue['user_id'],
                                    'username' => $queue['username'],
                                    'user_email' => $queue['user_email'],
                                    'usercomp' => $queue['usercomp'],
                                    'allow_delete' => $queue['allow_delete'],
                                    'module' => $queue['module'],
                                    'form_data' => json_encode(array('creationDate' => datetimeBrazil($this->deserializer->getCreationDateAttributeValue(), null))),
                                    'store_id' => $queue['store_id'],
                                )
                            );
                        } else {
                            $this->model_csv_to_verifications->update(['update_at' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)], $queueId);
                        }
                    }

                    if (empty($productsError) || $path_info['extension'] === "xml") {
                        $this->model_csv_to_verifications->update(['final_situation' => 'success'], $queueId);
                    } else {
                        $this->model_csv_to_verifications->update(['checked' => 0, 'final_situation' => 'wait'], $queueId);
                    }
                    // Se a rotina não ultrapassou 4 minutos de processamento, ler o próximo arquivo.
                    // Isso por a rotina ser executada a cada 5 minutos.
                    $start_date = new DateTime(date(DATETIME_INTERNATIONAL, time()));
                    $end_date = $start_date->diff(new DateTime(date(DATETIME_INTERNATIONAL, $startTime)));
                    if ($end_date->i >= 4) {
                        break;
                    }
                    echo "Processamento menor que 4 minutos, será feito a leitura do próximo arquivo\n";
                }
                echo "Preços atualizados com sucesso.\n";
        } catch (Throwable | Exception | ErrorException | InvalidArgumentException $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por instanciar ProductAvailabilityService
     *
     * @param 
     * @return
     */
    public function initializeServiceProviderAvailability()
    {
        $this->toolsProduct->setCampaignId($this->campaignId);
        $this->toolsProduct->setIgnoreIntegrationLogTypes([
            Integration_v2::LOG_TYPE_ERROR,
        ]);
        $this->productAvailabilityService = new ProductAvailabilityService($this->toolsProduct);
    }

    /**
     * Método responsável por lidar com Desserializar XML para atualizar preco dos produtos
     *
     * @param $object objeto contendo xml ja manipulado
     * @return
     */
    public function handleDeserializedXMLAvailability(object $object)
    {
        $availabilities = new AvailabilityCollectionDTO();
        foreach ($object->{AvailabilityDeserializer::NODE_CAMPAIGN_LIST} ?? [] as $campaign) {
            if ($campaign->IdCampanha != $this->campaignId) continue;
            foreach ($campaign->{AvailabilityDeserializer::NODE_PRODUCT_LIST} ?? [] as $productCampaign) {
                foreach ($object->{AvailabilityDeserializer::NODE_PRODUCT_LIST} ?? [] as $product) {
                    if ($productCampaign->codigo != $product->codigo) continue;
                    $availabilities->add(new AvailabilityDTO(
                        $productCampaign->codigo,
                        decimalNumber($product->precoDe),
                        decimalNumber($productCampaign->precoPor),
                        $productCampaign->disponibilidade,
                        $this->campaignId,
                        $this->flagId
                    ));
                }
            }
        }
        $this->productAvailabilityService->handleWithRawObject($availabilities);
    }

    /**
     * Método responsável por atualizar estoque dos produtos
     *
     * @param $module modulo de execucao = ProductsImportViaB2BStock
     * @return
     */
    public function updateStock($module)
    {
        try {
             echo "\n\nINICIANDO UPDATE ESTOQUE DE PRODUTOS...\n\n";
                $getLastRows = $this->model_csv_to_verifications->getlastRowsXmlAndJsonFile($module, $this->store_id);
                if ($getLastRows['xml'] && $getLastRows['json'] && $getLastRows['xml'] > $getLastRows['json']) {
                    echo "Foi adicionado novos arquivos xml. Deverá colocar todos os arquivos jsons como cancelados.\n\n";
                    $this->model_csv_to_verifications->removeFileJsonByModuleAndStore($module, $this->store_id);
                }

                $queues = $this->model_csv_to_verifications->getDontChecked(false, $module, $this->store_id);
                foreach ($queues as $queue) {
                    $queueId = $queue['id'];
                    if (!file_exists($queue['upload_file'])) {
                        throw new ErrorException("Arquivo não encontrado no caminho especificado: {$queue['upload_file']}\n");
                    }
                    echo "Ler arquivo ID:$queueId  PATH:{$queue['upload_file']}\n";

                    // Recupera os dados do arquivo.
                    $path_info = pathinfo($queue['upload_file']);
                    // Atualiza os dados do registro na tabela.
                    $this->model_csv_to_verifications->update(['checked' => 1, 'final_situation' => 'processing'], $queueId);

                    // Verifica se precisa converter o xml em json.
                    $deserializedFile = $this->deserializeXMLFile($queue['upload_file']);

                    // Se o formato json, ele não vai ter a data de criação no campo 'form_data'.
                    if (!empty($queue['form_data'])) {
                        $this->deserializer->setCreationDateAttributeValue(json_decode($queue['form_data'])->creationDate);
                    }

                    $this->toolsProduct->setFlagId($this->flagId)->setFlagName($this->flagName);
                    $this->initializeServiceProviderUpdateStock();
                    $productsError = $this->handleDeserializedXMLUpdateStock($deserializedFile);
                    if (!empty($productsError)) {
                        $newPathFile = "{$path_info["dirname"]}/{$path_info["filename"]}.json";
                        file_put_contents($newPathFile, json_encode($productsError, JSON_UNESCAPED_UNICODE));

                        if ($path_info['extension'] === "xml") {
                            chmod($newPathFile, 0775);
                            $this->model_csv_to_verifications->create(
                                array(
                                    'upload_file' => $newPathFile,
                                    'user_id' => $queue['user_id'],
                                    'username' => $queue['username'],
                                    'user_email' => $queue['user_email'],
                                    'usercomp' => $queue['usercomp'],
                                    'allow_delete' => $queue['allow_delete'],
                                    'module' => $queue['module'],
                                    'form_data' => json_encode(array('creationDate' => datetimeBrazil($this->deserializer->getCreationDateAttributeValue(), null))),
                                    'store_id' => $queue['store_id'],
                                )
                            );
                        } else {
                            $this->model_csv_to_verifications->update(['update_at' => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL)], $queueId);
                        }
                    }

                    if (empty($productsError) || $path_info['extension'] === "xml") {
                        $this->model_csv_to_verifications->update(['final_situation' => 'success'], $queueId);
                    } else {
                        $this->model_csv_to_verifications->update(['checked' => 0, 'final_situation' => 'wait'], $queueId);
                    }

                    // Se a rotina não ultrapassou 4 minutos de processamento, ler o próximo arquivo.
                    // Isso por a rotina ser executada a cada 5 minutos.
                    $start_date = new DateTime(date(DATETIME_INTERNATIONAL, time()));
                    $end_date = $start_date->diff(new DateTime(date(DATETIME_INTERNATIONAL, $startTime)));
                    if ($end_date->i >= 4) {
                        break;
                    }
                    echo "Processamento menor que 4 minutos, será feito a leitura do próximo arquivo\n";
                }
                echo "Estoques atualizados com sucesso.\n";
        } catch (Throwable | Exception | ErrorException | InvalidArgumentException $e) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$e->getMessage()}\n";
        }
    }

    /**
     * Método responsável por instanciar ProductStockService
     *
     * @param 
     * @return
     */
    public function initializeServiceProviderUpdateStock()
    {
        $this->toolsProduct->setIgnoreIntegrationLogTypes([
            Integration_v2::LOG_TYPE_ERROR,
        ]);
        $this->productStockService = new ProductStockService($this->toolsProduct);
    }

    /**
     * Método responsável por lidar com Desserializar XML para atualizar estoque dos produtos
     *
     * @param $object objeto contendo xml ja manipulado
     * @return
     */
    public function handleDeserializedXMLUpdateStock(object $object): array
    {
        return $this->productStockService->handleWithRawObject($object);
    }
}
