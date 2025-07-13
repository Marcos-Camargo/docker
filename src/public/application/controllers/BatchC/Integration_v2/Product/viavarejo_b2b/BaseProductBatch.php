<?php

use Integration_v2\viavarejo_b2b\Resources\Factories\XMLDeserializerFactory;
use Integration\Integration_v2\viavarejo_b2b\ToolsProduct;
use Integration_v2\viavarejo_b2b\Resources\Mappers\XML\BaseObjectDeserializer;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php';

require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLDeserializer.php';
require_once APPPATH . 'libraries/Helpers/XML/SimpleXMLWrapper.php';

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/XML/BaseObjectDeserializer.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Factories/XMLDeserializerFactory.php';

require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/ToolsProduct.php';

/**
 * Class BaseProductBatch
 * @property ToolsProduct $toolsProduct
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_stores $model_stores
 * @property Model_api_integrations $model_api_integrations
 */
abstract class BaseProductBatch extends BatchBackground_Controller
{
    /**
     * @var ToolsProduct
     */
    protected $toolsProduct;

    /**
     * @var BaseObjectDeserializer
     */
    protected $deserializer;

    /**
     * @var int idLojista
     */
    protected $flagId = 0;
    protected $flagName = '';

    protected $flagMapper;

    protected $companyId = 0;
    protected $storeId = 0;

    protected $integrationData;

    /**
     * Instantiate a new CreateProduct instance.
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '4096M');
        $this->toolsProduct = new ToolsProduct();

        $logged_in_sess = [
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        ];

        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_api_integrations');
        $this->load->model('model_stores');

        $this->flagMapper = new FlagMapper();
        $this->toolsProduct->setJob(__CLASS__);
    }

    /**
     * Método responsável pelo "start" da aplicação
     *
     * @param string|int|null $id Código do job (job_schedule.id)
     * @param int|null $store Parâmetro opcional para execução da batch, atualmente usado para referência da loja (job_schedule.params)
     * @return bool                     Estado da execução
     */
    public function run($id = null, int $store = null): bool
    {
        $startTime = time();
        $this->storeId = $store ?? 0;
        $this->companyId = $this->model_stores->getStoresData($store)['company_id'] ?? 0;
        $log_name = __DIR__ . '/' . self::class . '/' . __FUNCTION__;

        try {
            if (empty($this->storeId)) {
                throw new ErrorException('Loja não encontrada com os parâmetros informados');
            }
            if (!$this->checkStartRun(
                $log_name,
                $this->router->directory,
                get_class($this),
                $id,
                $this->storeId
            )) {
                return false;
            }
            $this->toolsProduct->startRun($this->storeId);
            $this->toolsProduct->setDateStartJob();
            $this->toolsProduct->saveLastRun();

            switch ($this->toolsProduct->job) {
                case "CreateProduct":
                    $module = 'ProductsImportViaB2BComplete';
                    break;
                case "UpdateProduct":
                    $module = 'ProductsImportViaB2BPartial';
                    break;
                case "UpdateAvailability":
                    $module = 'ProductsImportViaB2BAvailability';
                    break;
                case "UpdateStock":
                    $module = 'ProductsImportViaB2BStock';
                    break;
                default:
                    throw new ErrorException("Não foi possível identificar o tipo de execução.");
            }

            // Verificar se existe arquivo json e xml para ler
            // Caso exista, deverá ser excluído os arquivo json para ler somente os xml.
            // Pois foi enviado uma nova carga.
            $getLastRows = $this->model_csv_to_verifications->getlastRowsXmlAndJsonFile($module, $this->storeId);
            echo "Dados dos últimos arquivos xml e json:" . json_encode($getLastRows) . "\n";
            if ($getLastRows['xml'] && $getLastRows['json'] && $getLastRows['xml'] > $getLastRows['json']) {
                echo "Foi adicionado novos arquivos xml. Deverá colocar todos os arquivos jsons como cancelados.\n";
                $this->model_csv_to_verifications->removeFileJsonByModuleAndStore($module, $this->storeId);
            }

            $queues = $this->model_csv_to_verifications->getDontChecked(false, $module, $this->storeId);
            /*if (count($queues)) {
                $queues = array($queues[0]);
            }*/

            foreach ($queues as $queue) {
                $queueId = $queue['id'];
                if (!file_exists($queue['upload_file'])) {
                    throw new ErrorException("Arquivo não encontrado no caminho especificado: {$queue['upload_file']}");
                }
                echo "Ler arquivo ID:$queueId PATH:{$queue['upload_file']}\n";

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
                } catch (InvalidArgumentException $exception) {
                    $this->toolsProduct->log_integration(
                        "Erro para executar a integração",
                        "<h4>Não foi possível iniciar as rotinas de integração</h4> <p>{$exception->getMessage()}</p>",
                        "E"
                    );
                    throw new InvalidArgumentException($exception->getMessage());
                }

                $this->toolsProduct->setFlagId($this->flagId)->setFlagName($this->flagName);
                $this->initializeServiceProvider();
                $productsError = $this->handleDeserializedXML($deserializedFile);
                if (!empty($productsError)) {
                    $newPathFile = "{$path_info["dirname"]}/{$path_info["filename"]}.json";
                    file_put_contents($newPathFile, json_encode($productsError, JSON_UNESCAPED_UNICODE));

                    if ($path_info['extension'] === "xml") {
                        chmod($newPathFile, 0775);
                        $this->model_csv_to_verifications->create(
                            array(
                                'upload_file'   => $newPathFile,
                                'user_id'       => $queue['user_id'],
                                'username'      => $queue['username'],
                                'user_email'    => $queue['user_email'],
                                'usercomp'      => $queue['usercomp'],
                                'allow_delete'  => $queue['allow_delete'],
                                'module'        => $queue['module'],
                                'form_data'     => json_encode(array('creationDate' => datetimeBrazil($this->deserializer->getCreationDateAttributeValue(), null))),
                                'store_id'      => $queue['store_id']
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
            $this->toolsProduct->saveLastRun();
        } catch (Throwable | Exception | ErrorException | InvalidArgumentException $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "] {$exception->getMessage()}\n";
            $this->log_data('batch', $log_name, "[LINE: " . __LINE__ . "] {$exception->getMessage()}", "E");
            if (isset($queue[0]['id'])) {
                $this->model_csv_to_verifications->update(['checked' => 0, 'final_situation' => 'calcelled'], $queue[0]['id']);
            }
        }
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();

        return true;
    }

    protected function getQueuedFileById($queueId, $storeId = null)
    {
        $envCriteria = ENVIRONMENT !== 'production' ? [] : [
            'checked' => 0,
            'final_situation' => 'wait'
        ];
        return $this->model_csv_to_verifications->getByCriteria([
                'id' => $queueId,

            ] + $envCriteria + ($storeId > 0 ? ['store_id' => $storeId] : [])
        );
    }

    protected function deserializeXMLFile(string $filePath): object
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

    protected function fetchStore()
    {
        $this->flagId = $this->getFlagId();
        $this->flagName = $this->flagMapper->getFlagById($this->flagId);
        if (!empty($this->storeId)) {
            $this->integrationData = $this->model_api_integrations->getIntegrationByStoreId($this->storeId);
            if (!isset($this->integrationData['id'])
                || strpos($this->integrationData['integration'], $this->flagName) === false
            ) {
                $this->storeId = 0;
            }
        }

        if (empty($this->storeId)) {
            $integration = FlagMapper::getIntegrationNameFromFlag($this->flagName);
            $this->integrationData = $this->model_api_integrations->getIntegrationByCompanyId($this->companyId, $integration);
            if (isset($this->integrationData['id'])
                && strpos($this->integrationData['integration'], $this->flagName) !== false
            ) {
                $this->storeId = $this->integrationData['store_id'];
            }
        }
    }

    protected function getFlagId()
    {
        return $this->deserializer->getFlagIdAttributeValue();
    }

    protected abstract function initializeServiceProvider();

    protected abstract function handleDeserializedXML(object $object);
}