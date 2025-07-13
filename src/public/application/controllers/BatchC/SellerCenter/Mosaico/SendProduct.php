<?php
require APPPATH . "controllers/BatchC/SellerCenter/Mosaico/Main.php";

/**
 * Mosaico disponibiliza o envio de batches.
 * Consumirá diretamente da fila de produtos padrão (Tabela queue_products_marketplace)
 * Caso seja necessário envio em menor fluxo, é possível migrar a lógica de envio para a API, assim como as outras integrações.
 * Mosaico irá realizar notificações via WebHook de processamento.
 * A atualização de último envio será realizada na chamada da API para criação do body.
 * 
 * @property     Model_integration_ticket               $model_integration_ticket 
 * @property     Model_integrations                     $model_integrations 
 * @property     Model_queue_products_marketplace       $model_queue_products_marketplace
 * @property     Model_settings                         $model_settings
 * @property     Model_stores                           $model_stores
 * @property     model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 */
class SendProduct extends BatchBackground_Controller
{

    /**
     * @var 	 array	 	Dados de autenticação do marketplace.
     */
    private $auth_data;

    /**
     * @var 	 string	 	Nome da integração do marketplace.
     */
    private $int_to;

    /**
     * @var 	 array	 	Dados gerais da integração com o marketplace. 
     */
    private $integration_main;

    /**
     * @var 	 string	 	URL do procesos interno.
     */
    private $process_url;

    // Status da fila.
    const NOVO = 0;
    const PROCESSANDO = 1;

    // Valores referentes ao envio.
    // O tamanho máximo de um batch por padrão é 500, podendo ser enviados até 10 batches em uma execução.
    const BATCH_SIZE = 500;
    const MAX_ITERATIONS = 10;
    const MAX_PAYLOAD_SIZE_MB = 1.5;

    // API de Sellers para homolog é mocada, temos apenas um seller para testes.
    const HOMOLOG_SELLER_ID = "23295";

    // Número de minutos para aguardar a retirada da fila.
    const RESET_STALE_AFTER = 60;

    /**
     * Array de URLs para a Mosaico.
     * Majoritariamente mesmos valores, mudando apenas método utilizado.
     * @var array
     */
    const REQUEST_BY_TYPE = [
        "UPSERT" => [
            "url" => "https://merchant.zoom.com.br/api/merchant/products",
            "method" => "POST"
        ],
        "INACTIVATE" => [
            "url" => "https://merchant.zoom.com.br/api/merchant/products",
            "method" => "DELETE"
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = [
            'id'         => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        ];
        $this->session->set_userdata($logged_in_sess);

        $this->load->model("model_integration_ticket");
        $this->load->model("model_integrations");
        $this->load->model("model_products");
        $this->load->model("model_queue_products_marketplace");
        $this->load->model("model_settings");
        $this->load->model("model_stores");
        $this->load->model("model_stores_multi_channel_fulfillment");

        $this->setProcessURL();
    }

    /**
     * Executa o job de envio de produtos para a Mosaico.
     * Recebe como parâmetro o int_to que será enviado.
     * Necessário que seja passado o mesmo int_to que cadastrado nos produtos.
     * 
     * php index.php BatchC/SellerCenter/Mosaico/SendProduct run null int_to
     */
    function run($id = null, $params = null)
    {
        // Inicializa o job.
        $this->setIdJob($id);
        $log_name =  __CLASS__ . '/' . __FUNCTION__;
        $modulePath = str_replace("BatchC/", '', $this->router->directory) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        try {
            $this->validateParams($params);
            $this->initializeIntegration($params);

            echo "Adicionando int_to para os produtos na fila...\n";
            $this->addIntegrationsToQueue();
            $this->model_queue_products_marketplace->resetStaleQueueItems($this->int_to, SELF::RESET_STALE_AFTER);

            echo 'Iniciando envio de produtos para o Marketplace: ' . $this->int_to . "\n";
            $this->sendProducts();
            $this->log_data('batch', $log_name, 'finish', "I");
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            $this->log_data('batch', $log_name, $e->getMessage(), "E");
        } finally {
            $this->gravaFimJob();
        }
    }

    /**
     * Popula a fila apenas com entradas com integração.
     */
    private function addIntegrationsToQueue()
    {
        $stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;

        while (true) {
            // Busca todos produtos na fila com int_to NULL.
            $queues = $this->model_queue_products_marketplace->getWithoutIntTo(SELF::BATCH_SIZE);
            if (empty($queues)) {
                break;
            }

            // Percorre cada produto e popula a fila com as integrações.
            foreach ($queues as $queue) {
                try {
                    $this->processQueueItem($queue, $stores_multi_cd);
                } catch (Exception $e) {
                    echo "Erro ao processar produto {$queue['prd_id']}: " . $e->getMessage() . "\n";
                    continue;
                }
            }
        }
    }

    /**
     * Adiciona a entrada do produto com integração na fila.
     * @property     array          $integrations Integrações para a loja.
     * @property     array          $queue Entrada original da fila.
     */
    private function addPrdIntegrationQueue($integrations, $queue)
    {
        // Percorre cada produto e cria as respectivas necessarias integrações.
        foreach ($integrations as $integration) {
            if (!$this->canEnterQueue($integration, $queue)) {
                continue;
            }

            // Caso exista entrada na fila ainda em processamento, reenvio.
            $exists = $this->model_queue_products_marketplace->getByPrdIntTo($queue["prd_id"], $integration["int_to"]);
            if ($exists) {

                // Ainda não foi enviado, não tem porque remover.
                if (!$exists["status"]) {
                    continue;
                }

                $this->model_queue_products_marketplace->remove($exists["id"]);
            }

            $data = [
                'status' => SELF::NOVO,
                'prd_id' => $queue['prd_id'],
                'int_to' => $integration['int_to'],
                'date_create' => $queue['date_create'],
            ];

            $data['id'] = $this->model_queue_products_marketplace->create($data);
        }
    }

    /**
     * Cria um map com o sku do marketplace como chave.
     * Mosaico irá retornar apenas o Sku_mkt do produto, portanto precisamos dele para o lookup.
     * 
     * @param    array{
     *              product_id: int, 
     *              sku_mkt: string,
     *              queue_id: int,
     *           } $sentMetadata 
     * 
     * @return array<string,array{
     *              product_id: int, 
     *              sku_mkt: string,
     *              queue_id: int,
     *           }>
     */
    private function buildMetadataMap($sentMetadata)
    {
        return array_column($sentMetadata, null, 'sku_mkt');
    }

    /**
     * Verifica se o produto pode ou não entrar na fila.
     * @property     array          $integration Integração para a loja.
     * @property     array          $queue Entrada original da fila.
     *
     * @return       bool
     */
    private function canEnterQueue($integration, $queue)
    {
        $prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId(
            $integration['int_to'],
            $queue['prd_id']
        );
        if (!$prd_to_integration) {
            return false;
        }
        return true;
    }

    /**
     * Verifica se a loja apresenta multi fullfillment.
     */
    private function checkMultifullfillment($prd, $store)
    {
        if (($store['type_store'] != 2) || ($store['active'] != 1)) {
            return false;
        }

        $stores_multi_cd = $this->model_settings->getStatusbyName('stores_multi_cd') == 1;
        if (!$stores_multi_cd) {
            return false;
        }

        echo "Produto de uma loja CD de Multi-CD \n";
        $multi_channel = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($store['id'], $store['company_id'], 1);

        if (!$multi_channel) {
            echo "Não consegui encontrar a loja Original para esse produto \n";
            return true;
        }
        $original_store_id = $multi_channel[0]['store_id_principal'];
        if ($prd['has_variants'] == '') {
            $prd_original = $this->model_products->getProductComplete($prd['sku'], $store['company_id'], $original_store_id);
            if ($prd_original) {
                echo "Colocando o produto " . $prd_original['id'] . " da loja Principal na fila \n";
                $queue = [
                    'status' => 0,
                    'prd_id' => $prd_original['id'],
                    'int_to' => null
                ];
                $this->model_queue_products_marketplace->create($queue);
                return true;
            }
            echo "ATENÇÂO: Não existe produto original com SKU " . $prd['sku'] . " na loja " . $original_store_id . "\n";
        } else {
            $variants  = $this->model_products->getVariants($prd['id']);

            foreach ($variants as $variant) {
                $variant_original = $this->model_products->getVariantsBySkuAndStore($variant['sku'], $original_store_id);
                if ($variant_original) {
                    echo "Colocando o produto " . $variant_original['prd_id'] . " da loja Principal na fila \n";
                    $queue = [
                        'status' => 0,
                        'prd_id' => $variant_original['prd_id'],
                        'int_to' => null
                    ];
                    $this->model_queue_products_marketplace->create($queue);
                    return false;
                }
            }
            echo "ATENÇÂO: Não existe produto original, nem variações para os SKUs " . $prd['sku'] . " na loja " . $original_store_id . "\n";
        }
        return true;
    }

    /**
     * Cria entradas do histórico de tickets.
     * 
     * @param    int            $ticketId Id do ticket salvo no banco.
     * @param    array          $results
     * @param    array          $sentMetadata
     */
    private function createHistoryEntries($ticketId, $results, $sentMetadata)
    {
        $metadata = $this->buildMetadataMap($sentMetadata);
        $historyEntries = [];

        // Percorre cada retorno da Mosaico e adiciona os dados ao array de batch para o histórico.
        foreach ($results as $ticketData) {
            $skuMkt = $ticketData["product_id"];
            if (!isset($metadata[$skuMkt])) {
                continue;
            }

            $historyEntries[] = [
                "ticket_id" => $ticketId,
                "prd_id" => $metadata[$skuMkt]["product_id"],
                "sku_mkt" => $skuMkt,
                "queue_id" => $metadata[$skuMkt]["queue_id"],
                "status" => $ticketData["message"]
            ];
        }

        // Cria as entradas do histórico de tickets.
        foreach ($historyEntries as $queueEntry) {
            $this->model_integration_ticket->createTicketHistoryEntry($queueEntry);
        }
    }

    /**
     * Cria as entradas dos tickets da Mosaico localmente.
     * Adiciona também no histórico de envio, necessário para controle de processamento.
     * 
     * @param    array          $ticketReturn
     * @param    array          $sentMetadata
     */
    private function createTicketEntries($ticketReturn, $sentMetadata)
    {
        if ($ticketReturn["statusCode"] != 200) {
            echo "Não foi possível enviar a requisição. Status: {$ticketReturn["statusCode"]}\n";
            return;
        }

        $response = $ticketReturn["response"];
        if (!isset($response['ticket'])) {
            echo "Ticket não retornado, algo deu errado.\n";
            return;
        }

        // Cria a entrada do ticket
        try {
            $ticketId = $this->model_integration_ticket->createTicket(['ticket' => $response['ticket']]);
            $this->createHistoryEntries($ticketId, $response["results"], $sentMetadata);
        } catch (Exception $e) {
            echo "Erro ao criar entradas do ticket: {$e->getMessage()}\n";
        }
    }

    /**
     * Realiza a formatação dos batches da para envio na Mosaico.
     * Adiciona metadata, separação por tipo de envio e sellerId.
     */
    private function formatBatches($batches)
    {
        $formattedBatch = [
            "UPSERT" => [],
            "INACTIVATE" => []
        ];

        // Para cada batches, formata as entradas, adicionando os bodies e metadata para o respectivo tipo de envio e seller.
        foreach ($batches as $batch) {
            $type = $batch["type"];
            $seller = $batch["seller_id"];
            $formattedBatch[$type][$seller]["bodies"][] = $batch["json_data"];
            $formattedBatch[$type][$seller]["metadata"][] = [
                'product_id' => $batch["product_id"],
                'sku_mkt' => $batch["sku_mkt"],
                'queue_id' => $batch["queue_id"]
            ];
        }
        return $formattedBatch;
    }

    /**
     * Inicializa a integração principal do marketplace.
     * @param    string         $params Parâmetro, idealmente nome do marketplace.
     * @throws   Exception
     */
    private function initializeIntegration($params)
    {
        $this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $params);
        if (empty($this->integration_main)) {
            throw new Exception("int_to $params não tem integração definida");
        }

        $this->int_to = $this->integration_main['int_to'];
        $this->auth_data = json_decode($this->integration_main['auth_data'], true);
    }

    /**
     * Verifica se o payload a ser enviado é maior do que o limite definido.
     * @param    string         $payloadString payload a ser enviado, só que na forma de string.
     *
     * @return   bool
     */
    private function payloadTooBig($payloadString)
    {
        $sizeInMb = strlen($payloadString) / (1024 * 1024);
        return $sizeInMb > SELF::MAX_PAYLOAD_SIZE_MB;
    }

    /**
     * Prepara uma request curl para ser executada.
     * 
     * @param    string         $url 
     */
    private function prepareCurlHandle($url, $data)
    {
        $jsonData = json_encode($data);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout de conexão

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-local-appKey: 32322rwerwefwr2343qefasfsfa312e4rfwedsdf'
        ]);

        return $ch;
    }

    /** 
     * Realiza o merge dos resultados do processamento interno de cada produto.
     * Faz uma chamada para a API interna para gerar os bodies.
     * 
     * @param    array          $productsToSend Produtos que devem ser enviados.
     * 
     * @return   array
     */
    private function processBatchProducts($productsToSend, $batchSize = 20)
    {
        $currentBatch = [];
        $totalProducts = count($productsToSend);

        for ($i = 0; $i < $totalProducts; $i += $batchSize) {
            $batch = array_slice($productsToSend, $i, $batchSize);
            $batchResult = $this->processQueueBatch($batch);

            foreach ($batchResult as $queueId => $result) {
                if (!$result || !isset($result["response"])) {
                    continue;
                }

                if (!count($result["response"])) {
                    $this->model_queue_products_marketplace->remove($queueId);
                }

                // Adiciona o queue_id para controle dos tickets
                foreach ($result["response"] as &$response) {
                    $response["queue_id"] = $queueId;
                }

                $currentBatch = [...$currentBatch, ...$result["response"]];
            }
        }

        return $currentBatch;
    }

    /**
     * Formata o envio das batches para Mosaico.
     * Agrupa por tipo de envio e seller_id.
     * @param    array          $batches Array dos retornos da API contendo body e metadata.
     */
    private function processBatchSend($batches)
    {
        if (empty($batches)) {
            echo "Nenhum batch para processar\n";
            return;
        }

        $formattedBatch = $this->formatBatches($batches);
        $this->sendFormattedBatches($formattedBatch);
    }

    /**
     * Processa um lote  de requisições com CURL multi.
     * 
     * @param    array          $queues Array de entradas da fila para processar
     *
     * @return   array          Resultados indexados por queue_id
     */
    private function processQueueBatch($queues)
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $queueMap = [];

        // Prepara todas as requisições
        foreach ($queues as $queue) {
            $data = [
                'queue_id'   => 0,
                'product_id' => $queue['prd_id'],
                'int_to'     => $queue['int_to']
            ];

            $ch = $this->prepareCurlHandle($this->process_url . 'Api/queue/Products_Default_Mosaico', $data);

            // Adiciona ao multi handle
            curl_multi_add_handle($multiHandle, $ch);

            // Mapeia o handle para a queue
            $handleId = (int)$ch;
            $curlHandles[$handleId] = $ch;
            $queueMap[$handleId] = $queue;
        }

        // Executa todas as requisições
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Coleta os resultados de cada curl.
        $results = [];
        foreach ($curlHandles as $handleId => $ch) {
            $queue = $queueMap[$handleId];
            $response = curl_multi_getcontent($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            if ($statusCode == 200 && $response) {
                $results[$queue['id']] = [
                    "response" => json_decode($response, true),
                    "statusCode" => $statusCode
                ];

                // Atualiza status para processando
                $this->model_queue_products_marketplace->update(['status' => self::PROCESSANDO], $queue['id']);
            } else {
                echo "Erro ao buscar o produto {$queue['prd_id']}\n";
                $results[$queue['id']] = null;
            }

            // Remove do multi handle
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    /**
     * Adiciona a integração para um produto.
     * Produtos inseridos via trigger não apresentam a integração.
     * Remove caso não tenha integração.
     */
    private function processQueueItem($queue, $stores_multi_cd)
    {
        $prd = $this->model_products->getProductData(0, $queue['prd_id']);
        if (!$prd) {
            throw new Exception("Produto {$queue['prd_id']} não encontrado\n");
        }

        $integrations = $this->model_integrations->getIntegrationsbyStoreId($prd['store_id']);

        if (empty($integrations) && $stores_multi_cd) {
            $store = $this->model_stores->getStoresData($prd['store_id']);
            $this->checkMultifullfillment($prd, $store);
        }

        if (!empty($integrations)) {
            $this->addPrdIntegrationQueue($integrations, $queue);
        }

        $this->model_queue_products_marketplace->removePrdIdNull($queue['prd_id']);
    }

    /**
     * Realiza o envio dos produtos em Batch para a Mosaico.
     * @param    string         $type Tipo da request, utilizada para buscar URL e método.
     * @param    string         $sellerId Id do seller que será enviado como header.
     * @param    array          $data Dados dos produtos que serão enviados.
     */
    private function sendBatch($type, $sellerId, $data)
    {
        if (empty($data)) {
            echo "Dados vazios para envio.\n";
            return null;
        }

        $requestBody = ["products" => $data];

        $jsonData = json_encode($requestBody);
        if ($jsonData === false) {
            echo "Não foi possível relizar o encode, algo deu errado: " . json_last_error_msg() . "\n";
            print_r($data);
            return;
        }

        // Não há ambiente de homologação da Mosaico, apenas um seller para testes.
        if (!in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $sellerId = SELF::HOMOLOG_SELLER_ID;
        }

        $url = SELF::REQUEST_BY_TYPE[$type]["url"];
        $method = SELF::REQUEST_BY_TYPE[$type]["method"];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define qual método será enviado baseado no método.
        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Adiciona o header de tipo e merchant.
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Merchant: ' . $sellerId,
        ]);

        $auth = $this->auth_data["product_user_name"] . ":" . $this->auth_data["product_password"];
        curl_setopt($ch, CURLOPT_USERPWD, $auth);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $countSent = count($data);
        echo "\n\nEnviados $countSent produtos para o sellerId $sellerId com StatusCode $statusCode\n\n";

        return [
            "statusCode" => $statusCode,
            "response" => json_decode($response, true)
        ];
    }

    /**
     * Realiza o envio dos batches formatados.
     * @param    array          $formattedBatches
     */
    private function sendFormattedBatches($formattedBatches)
    {
        foreach ($formattedBatches as $type => $data) {
            foreach ($data as $sellerId => $entryData) {
                if ($this->payloadTooBig(json_encode($entryData["bodies"]))) {
                    // Divide o payload em chunks menores
                    $chunks = $this->splitPayloadIntoChunks($entryData["bodies"], $entryData["metadata"]);

                    foreach ($chunks as $chunk) {
                        $result = $this->sendBatch($type, $sellerId, $chunk['bodies']);
                        if ($result) {
                            $this->createTicketEntries($result, $chunk["metadata"]);
                        }
                    }
                } else {
                    // Payload dentro dos conformes, segue o fluxo padrão.
                    // Situação mais provável.
                    $result = $this->sendBatch($type, $sellerId, $entryData['bodies']);
                    if ($result) {
                        $this->createTicketEntries($result, $entryData["metadata"]);
                    }
                }
            }
        }
    }

    /**
     * Processa até 5000 produtos na Mosaico.
     * O envio é realizado em batches, por padrão, realiza 10 iterações de até 500 produtos cada.
     */
    private function sendProducts()
    {
        // Realiza um número limitado de iterações.
        for ($i = 0; $i < SELF::MAX_ITERATIONS; $i++) {

            $productsToSend = $this->model_queue_products_marketplace->getDataNextNew(SELF::BATCH_SIZE, null, $this->int_to);
            if (empty($productsToSend)) {
                break;
            }

            echo "Processando lote " . ($i + 1) . " com " . count($productsToSend) . " produtos\n";

            $batchToSend = $this->processBatchProducts($productsToSend);
            $this->processBatchSend($batchToSend);
        }
    }

    /**
     * Realiza o split dos bodies de forma recursiva até que consiga realizar o envio de todos chunks.
     * @param    array          $bodies Bodies que serão enviados para a integração.
     * @param    array          $metadata Array com a metadata dos produtos a serem enviados.
     */
    private function splitPayloadIntoChunks($bodies, $metadata)
    {
        $payloadSize = json_encode($bodies);

        // Se o payload não é muito grande, retorna como está
        if (!$this->payloadTooBig($payloadSize)) {
            return [[
                'bodies' => $bodies,
                'metadata' => $metadata
            ]];
        }

        $count = count($bodies);

        // Se tem apenas 1 item e ainda é muito grande, não pode dividir mais.
        // Praticamente impossível de acontecer.
        if ($count === 1) {
            echo "ERRO: Sku {$metadata['product_id']} é grande de mais para ser enviado.\n";
            return [];
        }

        // Divide pela metade
        $midpoint = intval($count / 2);

        $leftHalf = array_slice($bodies, 0, $midpoint);
        $leftHalfMetadata = array_slice($metadata, 0, $midpoint);

        $rightHalf = array_slice($bodies, $midpoint);
        $rightHalfMetadata = array_slice($metadata, $midpoint);

        // Recursivamente divide cada metade.
        $firstChunks = $this->splitPayloadIntoChunks($leftHalf, $leftHalfMetadata);
        $secondChunks = $this->splitPayloadIntoChunks($rightHalf, $rightHalfMetadata);

        return array_merge($firstChunks, $secondChunks);
    }

    /**
     * Seta a URL da API interna.
     */
    private function setProcessURL()
    {
        $this->process_url = $this->model_settings->getValueIfAtiveByName('internal_api_url');
        if (!$this->process_url) {
            $this->process_url = $this->model_settings->getValueIfAtiveByName('vtex_callback_url');
            if (!$this->process_url) {
                $this->process_url = base_url();
            }
        }
        if (substr($this->process_url, -1) !== '/') {
            $this->process_url .= '/';
        }
    }

    /**
     * Valida os parâmetros do job.
     * 
     * @param string|null $params
     * @throws Exception
     */
    private function validateParams($params)
    {
        if (is_null($params)  || ($params == 'null')) {
            throw new Exception("É OBRIGATÓRIO passar o int_to no params");
        }
    }
}
