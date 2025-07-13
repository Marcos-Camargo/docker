<?php

namespace Integration_v2\viavarejo_b2b\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Integration_v2\viavarejo_b2b\Resources\Factories\XMLDeserializerFactory;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FileNameMapper;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;

require 'system/libraries/Vendor/autoload.php';

require_once APPPATH . 'libraries/FileDir.php';

require_once APPPATH . 'libraries/Helpers/File/ZipArchiveWrapper.php';
require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLWrapper.php';

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FileNameMapper.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Factories/XMLDeserializerFactory.php';

/**
 * Class ImportLoadFileController
 * @package Integration_v2\viavarejo_b2b\Controllers
 * @property Client $client
 */
class ImportLoadFileController
{
    const SERVER_ADDRESS_DOWNLOAD = "http://conectala-via.conectala.tec.br";

    const FILE_NOT_AVAILABLE_ERROR = 'O arquivo não está disponível no momento';

    private $zipFilePath;

    private $flagName;

    private $extractDestinationPath;

    private $zipArchiveWrapper;
    private $repoQueue;
    private $jobSchedule;
    private $fileNameMapper;
    private $flagMapper;

    private $fileType;
    private $fileCreationDate;

    protected $client;

    public function __construct(
        \Model_csv_to_verifications $repoQueue,
        \Model_job_schedule $jobSchedule
    )
    {
        $this->zipArchiveWrapper = new \ZipArchiveWrapper();
        $this->fileNameMapper = new FileNameMapper();
        $this->flagMapper = new FlagMapper();
        $this->repoQueue = $repoQueue;
        $this->jobSchedule = $jobSchedule;

        $this->client = new Client([
            'verify' => false,
            'timeout' => 900,
            'connect_timeout' => 900
        ]);
    }

    public static function buildRedirectDownloadUrl($baseUrl, $redirectURL)
    {
        $qry = http_build_query(['url' => $redirectURL]);
        if (in_array(ENVIRONMENT, ['development', 'development_gcp'])) {
            return sprintf("%s/Api/Integration_v2/viavarejo_b2b/DownloadRedirect?%s", $baseUrl, $qry);
        }
        return sprintf("%s/download/file/stream?%s", $baseUrl, $qry);
    }

    public function downloadFileFromUrl($url, $destination)
    {
        try {
            $resource = \GuzzleHttp\Psr7\Utils::tryFopen($destination, 'w+');
            $response = $this->client->request('GET', $url, [
                'sink' => $resource,
                'synchronous' => true
            ]);
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("Error downloading file on {$url}: {$response->getStatusCode()}");
            }
            $content = $response->getBody()->getContents();
            if (is_string($content) && strcasecmp(trim($content), self::FILE_NOT_AVAILABLE_ERROR) === 0) {
                throw new \Exception("Error downloading file on {$url}: {$content}");
            }
        } catch (\Exception | ClientException $e) {
            if (file_exists($destination)) unlink($destination);
            $message = $e->getMessage();
            if ($e instanceof ClientException) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            if (strpos(strtolower($message), strtolower(self::FILE_NOT_AVAILABLE_ERROR)) !== false) {
                $fileType = $this->fileNameMapper->getFileTypeByPart($destination);
                throw new \Exception("A <b>VIA</b> ainda não diponibilizou o arquivo <b>{$fileType}.zip</b> para download, tente novamente mais tarde.");
            }
            throw new \Exception("Error downloading file on {$url}: {$message}");
        }

        return true;
    }

    public function setFlagName($flag)
    {
        $this->flagName = $this->flagMapper->getFlagByPart($flag);
    }

    public function getFileCreationDate() {
        return $this->fileCreationDate;
    }

    public function getFileType() {
        return $this->fileType;
    }

    public function extractZipFile($zipFilePath, $extractDestinationPath)
    {
        $this->extractDestinationPath = $extractDestinationPath;
        if (!\FileDir::clearDir($this->extractDestinationPath)) {
            throw new \Exception('Error on clear directory.');
        }
        if (!$this->zipArchiveWrapper->open($zipFilePath)) {
            throw new \Exception('Error on open zip file.');
        }
        if (!$this->zipArchiveWrapper->extractTo($this->extractDestinationPath)) {
            throw new \Exception('Error on extract zip file.');
        }
    }

    public function moveAndUpdateFilesPath($originPath, $destinationPath)
    {
        $pathsToMove = [];
        $files = \FileDir::getFiles($originPath);
        $files = $files['files'] ?? [];
        foreach ($files as $file) {
            $folder = $this->fileNameMapper->mapFileType($file);
            if (!array_key_exists($folder, array_flip(FileNameMapper::MAP_QUEUE_MODULE))) continue;
            $this->fileType = array_flip(FileNameMapper::MAP_QUEUE_MODULE)[$folder] ?? '';
            $date = date('Y-m-d');
            $addPath = "{$destinationPath}/{$date}/{$folder}";
            if (isset($pathsToMove[$originPath])) continue;
            $flagFromFile = $this->getFlagNameFromFile($file);
            if ($this->flagName && !empty($flagFromFile) && $this->flagName != $flagFromFile) {
                throw new \ErrorException(
                    sprintf(
                        "O arquivo pertence a bandeira <b>%s</b> e não corresponde ao canal selecionado: <b>%s</b>",
                        FlagMapper::ENABLED_FLAGS[$flagFromFile] ?? '',
                        FlagMapper::ENABLED_FLAGS[$this->flagName] ?? ''
                    )
                );
            }
            $flagFolder = $this->flagName ?? $flagFromFile;
            $time = strtotime('now');
            $pathsToMove[$originPath] = !empty($flagFolder) ? "{$addPath}/{$flagFolder}" : "{$addPath}/{$time}";
        }

        $movedFiles = [];
        foreach ($pathsToMove as $origin => $destination) {
            if (!\FileDir::clearDir($destination)) continue;
            if (!\FileDir::copyDir($origin, $destination)) continue;
            $movedFiles = array_merge($movedFiles, \FileDir::getFiles($destination)['files'] ?? []);
        }
        return $movedFiles;
    }

    protected function getFlagNameFromFile($filePath)
    {
        $deserializer = XMLDeserializerFactory::provideDeserializerByFilePath($filePath);
        $deserializer->setLimitProcessing(true)->deserialize(\SimpleXMLWrapper::loadFile($filePath));
        $flagId = $deserializer->getFlagIdAttributeValue();
        $this->fileCreationDate = $deserializer->getCreationDateAttributeValue();
        return $this->flagMapper->getFlagById($flagId);
    }

    public function sendFilesToQueue($files, $options = [])
    {
        $queuedFiles = [];
        foreach ($files ?? [] as $file) {
            $module = $this->fileNameMapper->mapFileType($file);
            if (!array_key_exists($module, array_flip(FileNameMapper::MAP_QUEUE_MODULE))) continue;
            $data = [
                'upload_file' => $file,
                'user_id' => $options['user']['id'] ?? 1,
                'username' => $options['user']['username'] ?? 'admin',
                'user_email' => $options['user']['email'] ?? 'batch@conectala.com.br',
                'store_id' => $options['store']['id'],
                'module' => $module,
                'final_situation' => 'wait'
            ];
            $exists = $this->repoQueue->getByCriteria([
                'upload_file' => $file,
                'final_situation' => ['wait', 'processing'],
                'checked' => 0,
                'store_id' => $data['store_id']
            ]);
            $id = $exists['id'] ?? 0;
            if ($id > 0) {
                $data['id'] = $id;
                if ($this->repoQueue->update($data, $id)) {
                    array_push($queuedFiles, $data);
                }
                continue;
            }
            if ($this->repoQueue->create($data)) {
                array_push($queuedFiles, array_merge($data, ['id' => $this->repoQueue->getInsertId()]));
            }
        }
        return $queuedFiles;
    }

    public function createScheduleJobsFromQueuedFiles($queuedFiles, $options = [])
    {
        $options['company_id'] = $options['company_id'] ?? 0;
        $scheduledJobs = [];
        $fileSize = 0;
        $interval = 1;
        foreach ($queuedFiles as $k => $queuedFile) {
            $modulePath = $this->fileNameMapper->mapFileScheduleJob($queuedFile['upload_file']);
            if (!in_array($modulePath, array_values(FileNameMapper::MAP_SCHEDULE_JOB_CLASS))) continue;
            if (!file_exists($queuedFile['upload_file'])) {
                $this->repoQueue->remove($queuedFile['id']);
                continue;
            }
            $data = [
                'module_path' => $modulePath,
                'module_method' => 'run',
                'params' => "{$queuedFile['store_id']} {$options['company_id']} {$queuedFile['id']}",
                'status' => 0,
                'finished' => 0,
                'date_start' => date('Y-m-d H:i:s', strtotime("+{$interval} minutes")),
                'date_end' => null,
            ];
            $fileSize = filesize($queuedFile['upload_file']);
            $fileSize = $fileSize > 1000000 ? $fileSize : 1000000;
            $interval += (int)(ceil($fileSize / 100000));

            $checkSchedule = $this->jobSchedule->find([
                'module_path' => $data['module_path'],
                'params' => $data['params'],
                'finished' => $data['finished'],
            ]);
            $id = $checkSchedule['id'] ?? 0;
            if ($id > 0) {
                $data['id'] = $id;
                if ($this->jobSchedule->update($data, $id)) {
                    array_push($scheduledJobs, $data);
                }
                continue;
            }
            if ($this->jobSchedule->create($data)) {
                array_push($scheduledJobs, array_merge($data, ['id' => $this->jobSchedule->getInsertId()]));
            }
        }
        return $scheduledJobs;
    }
}