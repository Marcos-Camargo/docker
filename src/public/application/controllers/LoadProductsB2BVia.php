<?php

use \Integration_v2\viavarejo_b2b\Controllers\ImportLoadFileController;
use \Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;
use \Integration_v2\viavarejo_b2b\Resources\Mappers\FileNameMapper;
use \libraries\Helpers\StringHandler;

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Controllers/ImportLoadFileController.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php";
require_once APPPATH . "libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FileNameMapper.php";
require_once APPPATH . "libraries/Helpers/StringHandler.php";

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class LoadProductsB2BVia
 * @property Model_api_integrations $model_api_integrations
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_job_schedule $model_job_schedule
 * @property ImportLoadFileController $importLoadFileController
 * @property FlagMapper $flagMapper
 * @property FileNameMapper $fileNameMapper
 */
class LoadProductsB2BVia extends Admin_Controller
{
    const TMP_PATH = "%s/assets/files/products_via/tmp/sellercenter/files/import/%s/%s";

    const FILES_PATH = FCPATH . "assets/files/products_via";
    const FILE_PROCESSING_TYPE = 'batch';

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

    public function __construct()
    {
        parent::__construct();
        ini_set('display_errors', 0);
        $this->not_logged_in();

        ini_set('max_execution_time', '900');
        set_time_limit(900);
        ini_set('memory_limit', '1024M');
        $this->data['page_title'] = $this->lang->line('application_load_products_via_b2b');
        $this->company_id = $this->data['usercomp'] = $this->session->userdata('usercomp');
        $this->store_id = $this->data['userstore'] = $this->session->userdata('userstore');
        $this->user = [
            'id' => $this->session->userdata('id'),
            'email' => $this->session->userdata('email'),
            'username' => $this->session->userdata('username'),
        ];

        $this->load->model('model_api_integrations');
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_job_schedule');

        $this->importLoadFileController = new ImportLoadFileController(
            $this->model_csv_to_verifications,
            $this->model_job_schedule
        );

        $this->flagMapper = new FlagMapper();
        $this->fileNameMapper = new FileNameMapper();

        $this->baseDir = FCPATH;
        $this->baseDir = strlen($this->baseDir) >= strripos($this->baseDir, '/') ? substr($this->baseDir, 0, strripos($this->baseDir, '/')) : $this->baseDir;
        $this->baseUrl = get_instance()->config->config['base_url'] ?? base_url();
        $this->dateFolder = date('Y-m-d', strtotime('now'));
    }

    public function index()
    {
        if (!in_array('b2b_integration_via', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $viaIntegrations = [];
        foreach (array_flip(FlagMapper::ENABLED_FLAGS) as $flag) {
            $integration = $this->model_api_integrations->getIntegrationByCompanyId($this->company_id, FlagMapper::getIntegrationNameFromFlag($flag));
            if(empty($integration)) continue;
            if (Model_api_integrations::isActiveIntegration($integration)) {
                $integration['credentials'] = json_decode($integration['credentials'], true);
                $integration['download_file_types'] = FileNameMapper::ENABLED_DOWNLOAD_FILE_TYPES;
                $integration['title'] = $integration['credentials']['name'];
                $integration['link'] = $integration['credentials']['flag'];
                $integration['flag'] = $integration['credentials']['flag'];
                $files = [];
                if(isset($integration['credentials']['meta']['files'])){
                    $files = $integration['credentials']['meta']['files'];
                    foreach ($files ?? [] as $type => $file) {
                        $module = $this->fileNameMapper->mapFileType($type);
                        $date = explode(' ', $file['lastImportationDate']);
                        $updatedDate = date('Y-m-d', strtotime(dateBrazilToDateInternational($date[0]))) . " {$date[1]}";
                        $queues = $this->model_csv_to_verifications->getAllByCriteria([
                            'store_id' => $integration['store_id'],
                            'module' => $module,
                            'final_situation' => ['wait', 'processing'],
                            'update_at >=' => $updatedDate,
                            'checked' => 0
                        ], true);
                        $files[$type]['title'] = FileNameMapper::ENABLED_DOWNLOAD_FILE_TYPES[$type];
                        $files[$type]['pendingFiles'] = count($queues);
                    }
                }
                foreach ($integration['download_file_types'] as $d => $f) {
                    if (!isset($files[$d])) {
                        $integration['download_file_types'][$d] = ['title' => FileNameMapper::ENABLED_DOWNLOAD_FILE_TYPES[$d]];
                        continue;
                    }
                    $integration['download_file_types'][$d] = $files[$d];
                }
                array_push($viaIntegrations, $integration);
            }
        }
        if(empty($viaIntegrations)) {
            $this->session->set_flashdata('error', 'Nenhuma configuração de integração com a <b>VIA</b> encontrada para essa empresa.');
            redirect('dashboard', 'refresh');
        }
        $this->data['integrations'] = $viaIntegrations;
        $this->render_template('via_b2b/index', $this->data);
    }

    protected function retrieveTempPathFromBase($base)
    {
        return sprintf(self::TMP_PATH . "/{$this->company_id}/{$this->store_id}/%s", $base, 'viab2b', 'zip', $this->dateFolder);
    }

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

    public function uploadFile_get($flag = null)
    {
        redirect('LoadProductsB2BVia/index', 'refresh');
    }

    public function uploadFile($flag = null)
    {
        if (!in_array('b2b_integration_via', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->currentFlag = $flag;
        $this->company_id = $this->postClean("company_id", true) ?? $this->company_id;
        $this->store_id = $this->postClean("store_id", true) ?? $this->store_id;

        try {
            $this->validateFlagStore();
            if (!isset($_FILES['file_upload']['name'])) {
                throw new ErrorException('Nenhum arquivo selecionado para upload.');
            }
            $config['upload_path'] = $this->retrieveTempPathFromBase($this->baseDir);
            if (!FileDir::createDir($config['upload_path'])) {
                throw new Exception('Error on create tmp directory');
            }
            $fileName = $_FILES['file_upload']['name'];
            $fileExt = substr($fileName, strrpos($fileName, '.') + 1);
            $fileName = substr($fileName, 0, strrpos($fileName, '.'));
            $config['file_name'] = StringHandler::slugify($fileName) . ".{$fileExt}";
            $fullPath = "{$config['upload_path']}/{$config['file_name']}";
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $config['allowed_types'] = 'zip';
            $config['max_size'] = '10000000';
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('file_upload')) {
                $error = $this->upload->display_errors();
                throw new Exception("Error on upload file to tmp directory: {$error}");
            }
            if (!file_exists($fullPath)) {
                throw new Exception("Error on check upload file exists on tmp directory: {$fullPath}");
            }
            $this->importLoadFileController->setFlagName($this->currentFlag);
            $result = $this->handleZippedFile($fullPath);
            $this->session->set_flashdata('success', current($result)['msg']);
            redirect('LoadProductsB2BVia/index', 'refresh');
            return;
        } catch (Throwable $e) {
            if ($e instanceof ErrorException) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect('LoadProductsB2BVia/index', 'refresh');
                return;
            }
            $this->session->set_flashdata('error', 'Ocorreu algum problema na extração e processamento dos arquivos. ' . $e->getMessage());
            redirect('LoadProductsB2BVia/index', 'refresh');
            return;
        }
    }

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
            $fileUrl = FileNameMapper::buildFileDownloadUrl($flag, $fileType, $partnerId);

            $this->importLoadFileController->setFlagName($fileUrl);
            $tmpDir = $this->retrieveTempPathFromBase($this->baseDir);
            if(!FileDir::createDir($tmpDir)) {
                throw new Exception("Error on create tmp dir '{$tmpDir}'");
            }

            $destinationPath = sprintf("%s/%s.zip", $tmpDir, $fileType);
            if (!file_exists($destinationPath)) {
                $redirectUrl = $this->retrieveDownloadFileUrl($fileUrl);
                $result = $this->importLoadFileController->downloadFileFromUrl($redirectUrl, $destinationPath);
                if (!$result) {
                    throw new Exception("Error on download file '{$fileType}.zip' from VIA: O arquivo não está disponível no momento");
                }
            }
            return $destinationPath;
        } catch (Throwable $e) {
            if (file_exists($destinationPath)) unlink($destinationPath);
            throw new Exception($e->getMessage());
        }
    }

    public function retrieveDownloadFileUrl($fileUrl)
    {
        $baseDir = in_array(ENVIRONMENT,  ['development', 'development_gcp'])
            ? ($this->baseUrl ?? base_url()) : ImportLoadFileController::SERVER_ADDRESS_DOWNLOAD;
        $baseDir = !empty(getenv('DOCKER_INTERNAL_BASE_DIR')) ? getenv('DOCKER_INTERNAL_BASE_DIR') : $baseDir;
        return ImportLoadFileController::buildRedirectDownloadUrl($baseDir, $fileUrl);
    }

    public function retrieveUrlTmpFileByFilePath($filePath)
    {
        if (file_exists($filePath)) {
            return str_replace($this->baseDir, $this->baseUrl, $filePath);
        }
        throw new Exception("File '{$filePath}' doesn't exists.");
    }

    public function getLinkFileDownload($flag, $fileType, $storeId = null, $companyId = null)
    {
        try {
            if (!in_array('b2b_integration_via', $this->permission)) {
                throw new Exception('Permission denied!');
            }
            $result = [];
            $fullPath = $this->retrieveFileFromPlataformByFlagAndFileType($flag, $fileType, $storeId, $companyId);
            $result['file_url'] = $this->retrieveUrlTmpFileByFilePath($fullPath);
            echo json_encode($result);
        } catch (Throwable $e) {
            echo json_encode([
                'errors' => [
                    ['msg' => $e->getMessage()]
                ]
            ]);
        }
    }

    public function runImportation($flag, $fileType)
    {
        try {
            if (!in_array('b2b_integration_via', $this->permission)) {
                throw new Exception('Permission denied!');
            }
            $data = json_decode($this->input->raw_input_stream ?? '{}');
            $companyId = $data->company_id ?? $this->company_id;
            $storeId = $data->store_id ?? $this->store_id;
            $fullPath = $this->retrieveFileFromPlataformByFlagAndFileType($flag, $fileType, $storeId, $companyId);
            $result['file_url'] = $this->retrieveUrlTmpFileByFilePath($fullPath);
            $result['messages'] = $this->handleZippedFile($fullPath);
            $result['info'] = $this->fileMetaLog;
            echo json_encode($result);
        } catch (Throwable $e) {
            echo json_encode([
                'errors' => [
                    ['msg' => $e->getMessage()]
                ]
            ]);
        }
    }

    protected function handleZippedFile($filePath)
    {
        try {
            $extractedFiles = $this->processZippedFile($filePath);
            if (empty($extractedFiles)) {
                $fileInfo = pathinfo($filePath);
                throw new ErrorException("Não foram encontrados arquivos válidos para extração no arquivo <b>{$fileInfo['basename']}</b>.");
            }
            return $this->handleExtractedFiles($extractedFiles);
        } catch (Throwable $e) {
            if (file_exists($filePath)) unlink($filePath);
            throw new Exception($e->getMessage());
        }
    }

    protected function processZippedFile($pathToFile)
    {
        $destinationPath = sprintf(self::TMP_PATH . "/{$this->company_id}/{$this->store_id}/%sx%s", $this->baseDir, 'viab2b', 'xml', strtotime("now"), rand(100, 999));
        $this->importLoadFileController->extractZipFile($pathToFile, $destinationPath);
        $originPath = $destinationPath;
        $destinationPath = self::FILES_PATH . "/{$this->company_id}/{$this->store_id}";
        return $this->importLoadFileController->moveAndUpdateFilesPath($originPath, $destinationPath);
    }

    protected function handleExtractedFiles(array $files = [])
    {
        $date = date('d/m/Y H:i:s');
        if ($this->fileProcessingType == 'batch') {
            $this->store_id = $integration['store_id'] ?? $this->store_id;
            $this->company_id = $integration['company_id'] ?? $this->company_id;
            $options['store']['id'] = $this->store_id;
            $options['user'] = $this->user;
            $queuedFiles = $this->importLoadFileController->sendFilesToQueue($files, $options);
            if (empty($queuedFiles)) {
                throw new ErrorException('Nenhum arquivo foi colocado na fila para processamento.');
            }
            $options = ['company_id' => $this->company_id ?? 0];
            //$scheduledJobs = $this->importLoadFileController->createScheduleJobsFromQueuedFiles($queuedFiles, $options);
            //if (empty($scheduledJobs)) {
            //    throw new ErrorException('Nenhum job foi agendado para processamento.');
            //}
            $fileUploaded = current($queuedFiles)['upload_file'];
            $flag = $this->flagMapper->getFlagByPart($fileUploaded);
            $flagName = FlagMapper::ENABLED_FLAGS[$flag] ?? '';
            $addMessage = "Acompanhe o andamento da importação na tela de <a href='{$this->baseUrl}/integrations/log_integration'><b>Histórico de Integração</b></a>.";
            $msg = sprintf("<b>[%s]</b> - %s arquivos extraídos foram colocados na fila para processamento. %s", $flagName, count($queuedFiles), $addMessage);
            $msgSing = sprintf("<b>[%s]</b> - %s arquivo extraído foi colocado na fila para processamento. %s", $flagName, count($queuedFiles), $addMessage);

            $fileType = $this->importLoadFileController->getFileType() ?? '';
            if (!empty($fileType)) {
                $filesMeta = [
                    $fileType => [
                        'lastImportationDate' => $date,
                        'filesCreationDate' => date('d/m/Y H:i:s', strtotime($this->importLoadFileController->getFileCreationDate())),
                        'totalFiles' => count($queuedFiles),
                        'pendingFiles' => count($queuedFiles),
                    ]
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

            return [
                ['msg' => count($queuedFiles) > 1 ? $msg : $msgSing]
            ];
        }
        throw new Exception('Rotina de processamento não implementada.');
    }
}