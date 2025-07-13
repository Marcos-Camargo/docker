<?php /** @noinspection DuplicatedCode */

namespace Integration\Integration_v2;

use DOMDocument;
use DOMException;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2;
use InvalidArgumentException;
use Throwable;

require "Integration_v2.php";


class Order_v2 extends Integration_v2
{
    public $toolsOrder;

    public $can_integrate_incomplete_order = false;

    /**
     * @var string Nome do estado para geração de logs.
     */
    public $nameStatusUpdated = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('text');
    }

    /**
     * Define a classe ToolsOrder da integração da loja
     *
     * Dentro da classe deve conter obrigatoriamente os métodos
     *
     * sendOrderIntegration
     * cancelIntegration
     * getOrderIntegration
     * getInvoiceIntegration
     * getTrackingIntegration
     * getOccurrenceIntegration
     * getShippedIntegration
     * getDeliveredIntegration
     * setTrackingIntegration
     * setOccurrenceIntegration
     * setDeliveredIntegration
     *
     * -- Sem uso, o envio do pedido despachado/coleto para a integradora, é feito dentro do método setOccurrenceIntegration()
     * setShippedIntegration
     */
    public function setToolsOrder()
    {
        require APPPATH . "libraries/Integration_v2/$this->integration/ToolsOrder.php";
        $instance   = "Integration\\$this->integration\ToolsOrder";
        $this->toolsOrder = new $instance($this);
    }

    /**
     * Recupera novos pedidos já pagos para integrar
     *
     * @param   bool    $onlyNewPaid    Trazer apenas novos pedidos e já pagos
     * @param   int     $last_queue_id  Último queue_id informado
     * @return  object
     * @throws  InvalidArgumentException
     */
    public function getNewOrderToIntegration(bool $onlyNewPaid, int $last_queue_id): object
    {
        // request get new orders paid
        $urlGetNewOrders = $this->process_url."Api/V1/Orders";
        $queryGetNewOrders = array(
            'headers' => $this->credentials->api_internal,
            'query' => array(
                'queue_id'      => $last_queue_id,
                'per_page'      => 50
            )
        );

        if ($onlyNewPaid) {
            $queryGetNewOrders['query']['only_new_order'] = true;
            $queryGetNewOrders['query']['new_order'] = true;
        } else {
            $queryGetNewOrders['query']['new_order'] = false;
        }

        try {
            $request = $this->client_cnl->request('GET', $urlGetNewOrders, $queryGetNewOrders);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            // processo normal, não encontrou resultado na página
            if ($erroMessage === 'No results were found' || $erroMessage === 'Nenhum resultado foi encontrado.' || $erroMessage === 'No results were found.') {
                $erroMessage = "Não encontrou mais resultados a partir do queue_id: $last_queue_id.\n";
            }

            throw new InvalidArgumentException($erroMessage);
        }

        return Utils::jsonDecode($request->getBody()->getContents());
    }

    public function getOrderToIntegration(array $filter, int $page = 1, int $perPage = 100): object
    {
        // request get new orders paid
        $urlGetNewOrders = $this->process_url."Api/V1/Orders";
        $queryGetNewOrders = [
            'headers' => $this->credentials->api_internal,
            'query' => [
                'page' => $page,
                'per_page' => $perPage
            ]
        ];

        $queryGetNewOrders['query'] = array_merge($queryGetNewOrders['query'], $filter);

        try {
            $request = $this->client_cnl->request('GET', $urlGetNewOrders, $queryGetNewOrders);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            // processo normal, não encontrou resultado na página
            if ($erroMessage === 'No results were found' || $erroMessage === 'Nenhum resultado foi encontrado.' || $erroMessage === 'No results were found.') {
                $erroMessage = "Não encontrou mais resultados na página: $page.\n";
            }

            throw new InvalidArgumentException($erroMessage);
        }

        return Utils::jsonDecode($request->getBody()->getContents());
    }

    /**
     * Recupera dados do pedido
     *
     * @param   int     $orderId    Código do pedido
     * @return  object              Pedido
     * @throws  InvalidArgumentException
     */
    public function getOrder(int $orderId): object
    {
        // request get order by id
        $urlGetOrder = $this->process_url."Api/V1/Orders/$orderId";
        $queryOrder = array(
            'headers' => $this->credentials->api_internal,
        );

        try {
            $request = $this->client_cnl->request('GET', $urlGetOrder, $queryOrder);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        return $response->result->order;
    }

    /**
     * Recupera preço de um SKU, considerar se é variação ou não
     *
     * @param   string      $intTo  Marketplace (orders.origin)
     * @param   float       $price  Preço de venda do SKU
     * @param   int         $prdId  ID do produto (products.id)
     * @param   string|null $skuVar SKU da variação (prd_variants.variant)
     * @return  null|float          Preço do SKU
     */
    public function getPriceSku(string $intTo, float $price, int $prdId, string $skuVar = null): ?float
    {
        return $this->model_products_marketplace->getPriceProduct($prdId, $price, $intTo, $skuVar) ?? 0;
    }

    /**
     * Recupera dados de rastreio do pedido
     *
     * @param   int     $orderId    Código do pedido
     * @return  object              Pedido
     * @throws  InvalidArgumentException
     */
    public function getTrackingOrder(int $orderId): object
    {
        // request to get tracking
        $urlTracking = $this->process_url."Api/V1/Tracking/$orderId";
        $queryTracking = array(
            'headers' => $this->credentials->api_internal
        );

        try {
            $request = $this->client_cnl->request('GET', $urlTracking, $queryTracking);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        if (!$response->success) {
            throw new InvalidArgumentException("Rastreio não encontrado. " . Utils::jsonEncode($response->getBody()));
        }

        return $response->result;
    }

    /**
     * Remove todos os pedidos da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o estado da exclusão
     * @throws  InvalidArgumentException
     */
    public function removeAllOrderIntegration(int $orderId): bool
    {
        // request get order by id
        $urlRemoveOrderQueue = $this->process_url."Api/V1/Orders/$orderId/all";
        $queryRemoveOrderQueue = array(
            'headers' => $this->credentials->api_internal,
        );

        try {
            $request = $this->client_cnl->request('DELETE', $urlRemoveOrderQueue, $queryRemoveOrderQueue);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        return $response->success;
    }

    /**
     * @param   object      $order  Dados do pedido
     * @param   bool        $create É validação na criação de um novo pedido
     * @return  bool|string         Estado se o pedido está apto a ser integrado
     */
    public function checkDataOrderToIntegration(object $order, bool $create = true)
    {
        // verifica cancelado, para não integrar
        if (in_array((int)$order->status->code, array(95,96,97,98,99))) {
            if ($create) {
                $this->removeAllOrderIntegration($order->code);
                $this->log_integration("Pedido ($order->code) cancelado", "<h4>Pedido $order->code não será integrado</h4> <ul><li>Pedido com status de cancelado antes de ser integrado.</li></ul>", "S");
            }
            return 'cancel';
        }

        // pedido está sem cliente
        if (empty($order->customer->name)) {
            return 'client';
        }
        // pedido está sem itens
        if (count($order->items) === 0) {
            return 'items';
        }
        // pedido está sem pagamento/parcelas
        if (empty($order->payments->parcels)) {
            $param = $this->model_settings->getValueIfAtiveByName('order_without_payment');
            if ($param && !$this->can_integrate_incomplete_order) {
                return 'payments';
            } else {
                return true; 
            }
        }

        return true;
    }
    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku(string $sku): ?array
    {
        return $this->model_products->getProductCompleteBySkyAndStore($sku, $this->store);
    }

    /**
     * Salvar ID do pedido gerado pelo integrador
     *
     * @param int       $orderId        Código do pedido no seller center
     * @param string    $idIntegration  Código do pedido na integradora
     */
    public function saveOrderIdIntegration(int $orderId, string $idIntegration)
    {
        $this->model_orders->saveOrderIdIntegrationByOrderIDAndStoreId($orderId, $this->store, $idIntegration);
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o estado da exclusão
     * @throws  InvalidArgumentException
     */
    public function removeOrderQueue(int $orderId): bool
    {
        // request delete order by id
        $urlRemoveQueue = $this->process_url."Api/V1/Orders/$orderId";
        $queryRemoveQueue = array(
            'headers' => $this->credentials->api_internal,
        );

        try {
            $this->client_cnl->request('DELETE', $urlRemoveQueue, $queryRemoveQueue);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    /**
     * Converter um array em XML
     *
     * @param mixed $data Array com os dados para conversão
     * @param string $name Nome da chave primária
     * @param null $doc Arquivo de download(ainda não usado)
     * @param null $node
     * @return  string|false        Retorna um XML
     * @throws DOMException
     */
    public function arrayToXml($data, string $name = 'pedido', &$doc = null, &$node = null)
    {
        if ($doc === null){
            $doc = new DOMDocument('1.0','UTF-8');
            $doc->formatOutput = TRUE;
            $node = $doc;
        }

        if (is_array($data)) {
            foreach($data as $var=>$val){
                if (is_numeric($var)){
                    $this->arrayToXml($val, $name, $doc, $node);
                }else{
                    if (!isset($child)){
                        $child = $doc->createElement($name);
                        $node->appendChild($child);
                    }

                    $this->arrayToXml($val, $var, $doc, $child);
                }
            }
        } else {
            $child = $doc->createElement($name);
            $node->appendChild($child);
            $textNode = $doc->createTextNode((string)$data);
            $child->appendChild($textNode);
        }

        if ($doc === $node) {
            return $doc->saveXML();
        }

        return false;
    }

    /**
     * Recupera código do pedido na integradora
     *
     * @param   int         $orderId    Código do pedido (orders.id)
     * @return  string|null             Retorna código do pedido na integradora
     */
    public function getOrderIdIntegration(int $orderId): ?string
    {
        return $this->model_orders_to_integration->getOrderIdIntegration($orderId, $this->store);
    }

    /**
     * Recupera dados de um pedido
     *
     * @param   int         $paidStatus Código do status do pedido (orders.paid_status)
     * @return  null|string             Retorna a situação para integrar
     */
    public function getStatusIntegration(int $paidStatus): ?string
    {
        switch ($paidStatus) {
            case 3: // Recupera nota fiscal do pedido
                $status = 'invoice';
                break;
            case 95: // Envia pedido como cancelado para a integradora
            case 96: // Envia pedido como cancelado para a integradora
            case 97: // Envia pedido como cancelado para a integradora
                $status = 'cancel';
                break;
            case 53: // logística seller center (Envia dados de rastreio para a integradora)
            case 40: // logística seller (Recupera dados de rastreio para a integradora)
                $status = 'tracking';
                break;
            case 43: // logística seller (Recupera data em que o pedido foi despachado/coletado)
                $status = 'shipped';
                break;
            case 5: // logística seller center (Envia quando que o pedido foi despachado/coletado |||| Envia ocorrências de rastreio)
            case 45: // logística seller (Recupera ocorrências de rastreio |||| Recupera quando que o pedido foi entregue ao cliente)
                $status = 'in_transit';
                break;
            case 6: // logística seller center (Envia quando que o pedido foi entregue ao cliente)
                $status = 'delivered';
                break;
            default:
                $status = null;
                break;
        }

        // verifica se o estado do pedido deve ser realizado alguma ação conforme a logística da loja
        // Se for logística do seller e da integradora, não precisamos ler os estados 53,5 e 6
        if ($this->getStoreOwnLogistic()) {
            if (in_array($paidStatus, array(53,5,6))) {
                return null;
            }
        }
        // Se for logística do seller center ou não é da integradora, não precisamos ler os estados 40,43 e 45
        else {
            if (in_array($paidStatus, array(40,43,45))) {
                return null;
            }
        }

        return $status;
    }

    /**
     * Importa os dados da nota fiscal no pedido
     *
     * @param   array   $invoice    Dados da nota fiscal
     * @param int|null $orderId    Id do Pedido
     * @return  bool                Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setInvoiceOrder(array $invoice, $orderId = null): bool
    {
        $orderId = $orderId ?? $this->toolsOrder->orderId;
        // Dados para inserir a NF-e
        $arrInvoice = array(
            'order_number'      => $orderId,
            'invoce_number'     => (int)$invoice['number'],
            'price'             => roundDecimal($invoice['value']),
            'serie'             => (int)$invoice['serie'],
            'access_key'        => clearBlanks($invoice['key']),
            'emission_datetime' => date('d/m/Y H:i:s', strtotime($invoice['date'])),
            'isDelivered'       => $invoice['isDelivered']
        );

        // request to import invoice
        $urlImportInvoice = $this->process_url."Api/V1/Orders/nfe";
        $queryImportInvoice = array(
            'json' => array(
                'nfe' => array($arrInvoice)
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('POST', $urlImportInvoice, $queryImportInvoice);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $this->log_integration("Erro na atualização do pedido ($orderId)", "<h4>Não foi possível atualizar dados de faturamento do pedido $orderId</h4><p>$erroMessage</p>", "E");
            throw new InvalidArgumentException($erroMessage);
        }

        $this->log_integration("Pedido ($orderId) atualizado", "<h4>Foi atualizado dados de faturamento do pedido $orderId</h4><ul><li><strong>Chave:</strong> {$invoice['key']}</li><li><strong>Número:</strong> {$invoice['number']}</li><li><strong>Série:</strong> {$invoice['serie']}</li><li><strong>Data de Emissão:</strong> " . datetimeBrazil($invoice['date']) . "</li><li><strong>Valor:</strong> " . money($invoice['value']) . "</li></ul>", "S");

        return true;
    }

    /**
     * Importa os dados de rastreio no pedido
     *
     * @todo Melhorar a importação para que cada item possa ser enviado por transportadoras diferentes e consequentemente, ter URL de rastreio diferentes
     *
     * @param   array   $tracking   Dados de rastreio do pedido
     * @param   int     $orderId    Código do pedido
     * @return  bool                Estado da importação
     * @throws  InvalidArgumentException
     */
    public function setTrackingOrder(array $tracking, int $orderId): bool
    {
        $dataCarrier = current($tracking);

        if (empty($dataCarrier)) {
            throw new InvalidArgumentException("Rastreio não encontrado.");
        }

        $trackingOrder = array(
            'date_tracking' => $dataCarrier['generatedDate'],
            'track'         => array(
                'carrier'       => $dataCarrier['shippingCompany'],
                'carrier_cnpj'  => $dataCarrier['documentShippingCompany'] ?? '',
                'url'           => $dataCarrier['trackingUrl'] ?? null
            ),
            'items' => array(),
            'isDelivered' => $dataCarrier['isDelivered']
        );

        foreach ($tracking as $sku => $item) {
            $trackingOrder['items'][] = array(
                "sku"               => $sku,
                "qty"               => (int)$item['quantity'],
                "code"              => clearBlanks($item['trackingCode'] ?? ''),
                "method"            => clearBlanks($item['shippingMethodName'] ?? $item['shippingCompany'] ?? ''),
                "service_id"        => clearBlanks($item['shippingMethodCode'] ?? $item['shippingCompany'] ?? ''),
                "value"             => (float)$item['deliveryValue'],
                "delivery_date"     => clearBlanks($item['estimatedDeliveryDate'] ?? ''),
                "url_label_a4"      => clearBlanks($item['labelA4Url'] ?? ''),
                "url_label_thermic" => clearBlanks($item['labelThermalUrl'] ?? ''),
                "url_label_zpl"     => clearBlanks($item['labelZplUrl'] ?? ''),
                "url_plp"           => clearBlanks($item['labelPlpUrl'] ?? '')
            );
        }

        // request to import tracking
        $urlImportTracking   = $this->process_url."Api/V1/Tracking/$orderId";
        $queryImportTracking = array(
            'json' => array(
                'tracking' => $trackingOrder
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('POST', $urlImportTracking, $queryImportTracking);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    /**
     * Recupera se o seller usa logística da integradora
     *
     * @todo Recuperar dado de logística do próprio pedido. (ainda não temos qual a integradora configurada na hora do pedido)
     *
     * @return bool Logística da integradora
     */
    public function getStoreOwnLogistic(): bool
    {
        if (strpos($this->integration, 'viavarejo_b2b') !== false) {
            $this->logisticStore['type'] = $this->integration;
        }
        return $this->logisticStore['seller'] &&
            (
                strtolower($this->logisticStore['type']) === strtolower($this->integration)
                || strtolower($this->logisticStore['type']) === 'erp'
            );
    }

    /**
     * Importa a data em que o pedido foi despachado/coletado.
     *
     * @param   string  $date       Data em que o pedido foi despachado/coletado.
     * @param   int     $orderId    Código do pedido.
     * @return  bool                Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setShippedOrder(string $date, int $orderId, $isDelivered = null): bool
    {
        // request to import date shipped
        $urlDateShipped     = $this->process_url."Api/V1/Orders/$orderId/shipped";
        $queryDateShipped   = array(
            'json' => array(
                'shipment' => array(
                    'shipped_date' => $date,
                    'isDelivered' => $isDelivered
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlDateShipped, $queryDateShipped);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    /**
     * Importa ocorrências de rastreio.
     *
     * @param   array   $occurrence     Dadso de ocorrência do rastreio.
     * @param   string  $trackingCode   Código de rastreio.
     * @param   int     $orderId        Código do pedido.
     * @return  bool                    Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setOccurrenceOrder(array $occurrence, string $trackingCode, int $orderId): bool
    {
        // request to import occurrence
        $urlDateShipped     = $this->process_url."Api/V1/Tracking/occurrence/$orderId/$trackingCode";
        $queryDateShipped   = array(
            'json' => array(
                'occurrence' => array(
                    'date'          => $occurrence['date'],
                    'occurrence'    => $occurrence['occurrence'],
                    'place'         => $occurrence['place'] ?? null,
                    'street'        => $occurrence['street'] ?? null,
                    'number'        => $occurrence['number'] ?? null,
                    'zipcode'       => $occurrence['zipcode'] ?? null,
                    'neighborhood'  => $occurrence['neighborhood'] ?? null,
                    'city'          => $occurrence['city'] ?? null,
                    'state'         => $occurrence['state'] ?? null
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('POST', $urlDateShipped, $queryDateShipped);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    /**
     * Importa a data em que o pedido foi entregue ao cliente.
     *
     * @param   string  $date       Data em que o pedido foi entregue ao cliente.
     * @param   int     $orderId    Código do pedido.
     * @return  bool                Estado da importação.
     * @throws  InvalidArgumentException
     */
    public function setDeliveredOrder(string $date, int $orderId): bool
    {
        // request to import date delivered
        $urlDateDelivered   = $this->process_url."Api/V1/Orders/$orderId/delivered";
        $queryDateDelivered = array(
            'json' => array(
                'shipment' => array(
                    'delivered_date' => $date
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlDateDelivered, $queryDateDelivered);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    /**
     * Verificar se o pedido pertence a loja.
     *
     * @param   int     $orderId    Código do pedido (orders.id).
     * @return  bool                Retorna código do pedido na integradora.
     */
    public function checkOrderBelongsStore(int $orderId): bool
    {
        return (bool)$this->model_orders->getOrderByIdAndStore($orderId, $this->store);
    }

    /**
     * Recupera dados do pedido.
     *
     * @param   int               $orderId  Código do pedido (orders.id).
     * @return  array|null|false            Retorna dados do pedido no seller center.
     */
    public function getOrderByOrderId(int $orderId)
    {
        return $this->model_orders->getOrderByIdAndStore($orderId, $this->store);
    }

    /**
     * Recupera dados do pedido pelo numero do pedido no marketplace.
     *
     * @param   string      $num_mkt    Número do pedido no marketplace.
     * @return  array|null              Retorna os dados do pedido se encontrado.
     */
    public function getDataOrderByNumMkt(string $num_mkt): ?array
    {
        return $this->model_orders->getOrdersDatabyNumeroMarketplace($num_mkt, $this->store);
    }

    /**
     * Recupera dados do pedido pelo código do pedido integrado.
     *
     * @param   string      $orderIntegration   Código do pedido na integradora
     * @return  array|null                      Retorna os dados do pedido se encontrado.
     */
    public function getOrderByOrderIntegration(string $orderIntegration): ?array
    {
        return $this->model_orders->getOrderByOrderIdIntegration($orderIntegration, $this->store);
    }

    /**
     * Recupera informações do intermediador de pagamento.
     *
     * @param   string      $field  Campo para consulta do intermediador.
     * @return  string|null
     */
    public function getIntermediaryPayment(string $field): ?string
    {
        $data = $this->db->where('name', $field)->get('payment_gateway_settings')->row_object();

        return $data->value ?? null;
    }

    /**
     * Recupera preço de um SKU no banco, considerar se é variação ou não
     *
     * @param   string      $intTo  Marketplace (orders.origin)
     * @param   string      $sku    ID do produto (products.id)
     * @param   string|null $skuVar SKU da variação (prd_variants.variant)
     * @return  null|float          Preço do SKU
     */
    public function getPriceInternalSku(string $intTo, string $sku, string $skuVar = null): ?float
    {
        $product = $this->model_products->getProductBySkuAndStore($sku, $this->store);

        if (!empty($skuVar)) {
            $variation = $this->model_products->getVariationForSkuAndSkuVar($sku, $this->store, $skuVar);
            $price = $variation['price'] ?? null;
        } else {
            $price = $product['price'] ?? null;
        }

        if ($price === null) {
            return null;
        }

        return $this->model_products_marketplace->getPriceProduct($product['id'], $price, $intTo, $skuVar) ?? 0;
    }

    public function getSkuProductVariationOrder($item)
    {
        $var = $this->db
            ->get_where('prd_variants',
                array(
                    'prd_id'    => $item->product_id,
                    'variant'   => $item->variant_order
                )
            )->row_array();

        if (!$var) return false;

        return $var;
    }

    /**
     * Recupera dados do pedido pelo numero do pedido no marketplace.
     *
     * @param   int     $order_id   Código do pedido.
     * @return  string              Retorna o número do pedido no marketplace.
     */
    public function getNumeroMarketplaceByOrderId(int $order_id): string
    {
        $order = $this->model_orders->getOrdersData(0, $order_id);

        if (empty($order)) {
            throw new InvalidArgumentException("Pedido $order_id não encontrado.");
        }

        return $order['numero_marketplace'];
    }

    /**
     * Cancelar pedido no seller center
     *
     * @param   int     $orderId    Id do Pedido
     * @param   string  $date       Id do Pedido
     * @param   string  $reason     Id do Pedido
     * @throws  InvalidArgumentException
     */
    public function setCancelOrder(int $orderId, string $date, string $reason)
    {
        $arrCancel = array(
            'date'      => $date,
            'reason'    => $reason
        );

        // request to cancel order
        $urlImportCancel = $this->process_url."Api/V1/Orders/$orderId/canceled";
        $queryImportCancel = array(
            'json' => array(
                'order' => $arrCancel
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlImportCancel, $queryImportCancel);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $this->log_integration("Erro na atualização do pedido ($orderId)", "<h4>Não foi possível atualizar o pedido para cancelado para o pedido $orderId</h4><p>$erroMessage</p>", "E");
            throw new InvalidArgumentException($erroMessage);
        }

        $date = dateFormat($date, 'd/m/Y H:i:s', null);
        $this->log_integration("Pedido ($orderId) atualizado", "<h4>Pedido $orderId foi atualizado para cancelado</h4><p><b>Motivo: </b>$reason</p><p><b>Data: </b>$date</p>", "S");
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retornar o estado da exclusão
     * @throws  InvalidArgumentException
     */
    public function changeStatusNewOrder(int $orderId): bool
    {
        // request delete order by id
        $urlRemoveQueue = $this->process_url."Api/V1/Orders/change_status_new_order/$orderId";
        $queryRemoveQueue = array(
            'headers' => $this->credentials->api_internal,
        );

        try {
            $this->client_cnl->request('PATCH', $urlRemoveQueue, $queryRemoveQueue);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $erroMessage = $this->getMessageRequestApiInternal($exception->getResponse()->getBody()->getContents());
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }

        return true;
    }

    public function generateNfe(): array
    {
        $uf             = '35';
        $date           = date('ym');
        $cnpj           = '88002810000128';
        $mod            = '55';
        $serie          = str_pad(1, 3, '0', STR_PAD_LEFT);
        $number         = str_pad(rand(1, 99999), 9, '0', STR_PAD_LEFT);
        $tp_emission    = '1';
        $random_code    = rand(10000000, 99999999);
        $key            = $uf.$date.$cnpj.$mod.$serie.$number.$tp_emission.$random_code;

        $key_validate = substr($key, 0, 43);
        $weight = array(2, 3, 4, 5, 6, 7, 8, 9);
        $countWeight = 0;
        $sumWeight = 0;
        for ($i = strlen($key_validate) - 1; $i >= 0; $i--) {
            $numero = substr($key_validate, $i, 1);
            $ponderacao = (int) $numero * $weight[$countWeight];
            $sumWeight = $sumWeight + $ponderacao;
            $countWeight++;
            if ($countWeight > 7) {
                $countWeight = 0;
            }
        }
        $rest = ($sumWeight % 11);

        if ($rest == 0 || $rest == 1) {
            $digitCheck = 0;
        } else {
            $digitCheck = 11 - $rest;
        }

        return array(
            'serie'     => $serie,
            'number'    => $number,
            'key'       => $key.$digitCheck,
            'date'      => date('Y-m-d H:i:s')
        );
    }

    /**
     * Recupera e salva NFe do pedido
     *
     * @param   object $order   Dados do pedido
     * @return  bool            Estado da atualização
     */
    public function setInvoice(object $order): bool
    {
        // Pedido já tem uma NFe, atualizar o estado
        if ($order->invoice) {
            echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId} já contém NFe\n";
            return true;
        }

        try {
            $dataInvoice = $this->toolsOrder->getInvoiceIntegration($this->toolsOrder->orderIdIntegration,$this->toolsOrder->orderId);
            } catch (InvalidArgumentException $exception) {
            echo "[PROCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
            return false;
        }

        try {
            $this->setInvoiceOrder($dataInvoice);
        } catch (InvalidArgumentException $exception) {
            echo "[ERRO][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
            return false;
        }

        echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId} atualizado nota fiscal ({$dataInvoice['key']})\n";
        return true;
    }

    /**
     * Envia cancelamento do pedido para a integradora
     * @warning O cancelo só irá do seller center para a integradora. O inverso ainda não ocorre
     *
     * @return bool
     * @throws Exception
     */
    public function setCancelIntegration(): bool
    {
        $this->setNameStatusUpdated('Cancelado');

        try {
            $this->toolsOrder->cancelIntegration($this->toolsOrder->orderId, $this->toolsOrder->orderIdIntegration);
        } catch (InvalidArgumentException $exception) {
            if(strpos($exception->getMessage(), 'concluído') !== false) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. Pedido concluído, não pode ser cancelado. Removendo da fila....\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível realizar o cancelamento do pedido {$this->toolsOrder->orderId}</h4><p>O pedido já foi concluído e não pode ser cancelado.</p>", "E");
                return true;
            } elseif (strpos($exception->getMessage(), 'Changes not found in the order')) {
                echo "[ERRO][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. Pedido já cancelado, não pode ser cancelado. Removendo da fila....\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível realizar o cancelamento do pedido {$this->toolsOrder->orderId}</h4><p>O pedido já foi concluído e não pode ser cancelado.</p>", "E");
                return true;
            }
            echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
            $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível realizar o cancelamento do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
            return false;
        }

        echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId} atualizado para cancelado.\n";
        return true;
    }

    /**
     * Recupera ou envia dados de tracking
     *
     * @return bool         Estado na atualização do pedido
     * @throws Exception
     */
    public function setTracking(): bool
    {
        $this->setNameStatusUpdated('Aguardando Coleta/Envio');

        $order = $this->getOrder($this->toolsOrder->orderId);

        // logística própria - Status=40
        if ($this->getStoreOwnLogistic()) {

            // Pedido já tem um rastreio. Não deve tentar trazer novamente
            // Pedido seguirá o fluxo dele
            if ($order->shipping->tracking_code) {
                return true;
            }

            try {
                // Retornará um array onde, a chave de cada item do array será o código SKU e o valor do item um array com os dados:
                // quantity;
                // shippingCompany;
                // trackingCode;
                // trackingUrl;
                // generatedDate;
                // shippingMethodName;
                // shippingMethodCode;
                // deliveryValue;
                // documentShippingCompany;
                // estimatedDeliveryDate;
                // labelA4Url;
                // labelThermalUrl;
                // labelZplUrl;
                // labelPlpUrl
                $order->items = array_map(function ($item) use ($order) {
                    $item->{'shipping_carrier'} = $order->shipping->shipping_carrier ?? null;
                    $item->{'service_method'} = $order->shipping->service_method ?? null;
                    return $item;
                }, $order->items);
                $tracking = $this->toolsOrder->getTrackingIntegration($this->toolsOrder->orderIdIntegration, $order->items);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }

            // Não encontrou rastreio para os itens
            if (!count($tracking)) {
                echo "[PROCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId}. Sem rastreio\n";
                return false;
            }

            try {
                $this->setTrackingOrder($tracking, $this->toolsOrder->orderId);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }
        }
        // Logística do seller center - Status=53
        else {
            // Ainda sem frete
            if (!$order->shipping->tracking_code) {
                return false;
            }

            try {
                $dataTracking = $this->getTrackingOrder($this->toolsOrder->orderId);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                return false;
            }

            try {
                $this->toolsOrder->setTrackingIntegration($this->toolsOrder->orderIdIntegration, $order, $dataTracking);
            } catch (InvalidArgumentException $exception) {
                if(strpos($exception->getMessage(), 'concluído') !== false) {
                    echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. Pedido já está atualizado para Enviado. Removendo da fila....\n";
                    $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>O pedido já está atualizado.</p>", "E");
                    return true;
                } elseif (strpos($exception->getMessage(), 'Order has status shipped cannot run back to shipping_informed')) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. Pedido já está atualizado para Enviado. Removendo da fila....\n";
                    $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>O pedido já está atualizado.</p>", "S");
                    return true;
                }
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }
        }
        return true;
    }

    /**
     * Define pedido como enviado
     * Só entrará nesse método pedidos em que, a loja utilizar logística da integradora
     * Caso contrário entrará no método getSaveOrSendOccurrence, nele deverá enviar quando pedido foi despachado
     *
     * @return bool
     */
    public function setShipped(): bool
    {
        $this->setNameStatusUpdated('Em Transporte');

        $order = $this->getOrder($this->toolsOrder->orderId);

        // Logística do seller - Status=43
        if ($this->getStoreOwnLogistic()) {

            // Pedido já está com data de envio. Não deve tentar trazer novamente.
            // Pedido seguirá o fluxo dele.
            if ($order->shipping->shipped_date) {
                return true;
            }

            try {
                $shippedData = $this->toolsOrder->getShippedIntegration($this->toolsOrder->orderIdIntegration);
                $dateShipped = $shippedData['date'] ?? null;
                $isDelivered = $shippedData['isDelivered'] ?? null;
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }

            try {
                if($dateShipped){
                    $this->setShippedOrder($dateShipped, $this->toolsOrder->orderId, $isDelivered);
                }
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }

            $this->setNameStatusUpdated('Em Transporte em: ' . datetimeBrazil($dateShipped, null));
        } else {
            if (method_exists($this->toolsOrder, 'setShippedIntegration')) {
                try {
                    $this->toolsOrder->setShippedIntegration($this->toolsOrder->orderIdIntegration, $order);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                    $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível enviar a data de envio {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recupera ou salva ocorrência de frete.
     * Caso seja logística própria, também mudará o estado do pedido para entregue quando for entregue.
     *
     * @return bool
     * @throws Exception
     */
    public function setOccurrence(): bool
    {
        $order = $this->getOrder($this->toolsOrder->orderId);

        // Logística do seller - Status=45
        if ($this->getStoreOwnLogistic()) {
            try {
                /**
                 * Dados de ocorrência do pedido. Um array com as chaves 'isDelivered' e 'occurrences',
                 * onde 'occurrences' será os dados de ocorrência do rastreio, composto pelos índices 'date' e 'occurrence', sendo obrigatórios.
                 * Os índices place, street, number, zipcode, neighborhood, city, state são opcionais e possível serem enviados no método setOccurrenceOrder()
                 */
                $dataOccurrence = $this->toolsOrder->getOccurrenceIntegration($this->toolsOrder->orderIdIntegration);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar ocorrências do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }

            if (count($dataOccurrence['occurrences'])) {
                $freightCode = array();
                foreach ($dataOccurrence['occurrences'] as $trackingCode => $occurrences) {
                    foreach ($occurrences as $occurrence) {
                        if (!array_key_exists($trackingCode, $freightCode)) {
                            $dataFreight = $this->model_freights->getFreightForCodeTracking($this->toolsOrder->orderId, $trackingCode);
                            if (!$dataFreight) {
                                continue;
                            }
                            $freightCode[$trackingCode] = $dataFreight['id'];
                        }
                        // ocorrência ainda não existe
                        if (
                            !$this->model_frete_ocorrencias->getOcorrenciasByFreightIdName($freightCode[$trackingCode], $occurrence['occurrence'])
                        ) {
                            try {
                                $this->setOccurrenceOrder($occurrence, $trackingCode, $this->toolsOrder->orderId);
                            } catch (InvalidArgumentException $exception) {
                                echo "[ERRO][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                                continue;
                            }
                            $this->log_integration("Pedido ({$this->toolsOrder->orderId}) atualizado", "<h4>Foi atualizado ocorrência do rastreio ($trackingCode)</h4><ul><li>" . datetimeBrazil($occurrence['date'], null) . " - {$occurrence['occurrence']}</li></ul>", "S");
                            echo "[SUCCESS][LINE:" . __LINE__ . "] Ocorrência de rastreio do pedido ({$this->toolsOrder->orderId}) atualizado para o rastreio ($trackingCode) - {$occurrence['occurrence']}\n";
                        }
                    }
                }
            }

            // Pedido já foi entregue, deve marcar o pedido como entregue.
            if ($dataOccurrence['isDelivered']) {
                // Pedido já está com data de entregue. Não deve tentar trazer novamente.
                // Pedido seguirá o fluxo dele.
                if ($order->shipping->delivered_date) {
                    return true;
                }

                $dateDelivered = $dataOccurrence['dateDelivered'];
                if (!$dateDelivered) {
                    try {
                        $dateDelivered = $this->toolsOrder->getDeliveredIntegration($this->toolsOrder->orderIdIntegration);
                    } catch (InvalidArgumentException $exception) {
                        echo "[ERRO][LINE:" . __LINE__ . "][" . __FUNCTION__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                        $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                        return false;
                    }
                }

                if (empty($dateDelivered)) {
                    echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. Não foi possível obter a data de entrega.\n";
                    return false;
                }

                try {
                    $this->setDeliveredOrder($dateDelivered, $this->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                    $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar o pedido ({$this->toolsOrder->orderId}) para Entregue</h4><p>{$exception->getMessage()}</p>", "E");
                    return false;
                }

                $this->setNameStatusUpdated('Entregue em: ' . datetimeBrazil($dateDelivered, null));

                return true;
            } else {
                echo "[PROCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId}. Ainda não entregue ao cliente.\n";
            }
        }
        // Logística do seller center - Status=5.
        else {
            // recupera as ocorrências ainda não enviadas a integradora.
            $occurrences = $this->model_frete_ocorrencias->getOcorrenciaByOrderId($this->toolsOrder->orderId, false, true);

            if (count($occurrences)) {
                $arrOccurrence = array();
                $arrIdOccurrences = array();
                $arrNameDateOccurrences = array();
                foreach ($occurrences as $occurrence) {
                    $arrOccurrence[] = array(
                        "name"          => $occurrence['nome'],
                        "date"          => $occurrence['data_ocorrencia'],
                        'message'       => $occurrence['mensagem'],
                        'trackingCode'  => $occurrence['codigo_rastreio'],
                        'adderPlace'    => $occurrence['addr_place'],
                        'adderName'     => $occurrence['addr_name'],
                        'adderNum'      => $occurrence['addr_num'],
                        'adderCep'      => $occurrence['addr_cep'],
                        'adderNeigh'    => $occurrence['addr_neigh'],
                        'adderCity'     => $occurrence['addr_city'],
                        'adderState'    => $occurrence['addr_state']
                    );
                    $arrIdOccurrences[] = (int)$occurrence['id'];
                    $arrNameDateOccurrences[] = array(
                        'date'          => $occurrence['data_ocorrencia'],
                        'name'          => $occurrence['nome'],
                        'trackingCode'  => $occurrence['codigo_rastreio']
                    );
                }

                try {
                    $this->toolsOrder->setOccurrenceIntegration($this->toolsOrder->orderIdIntegration, $order, $arrOccurrence);
                    foreach ($arrIdOccurrences as $idOccurrence) {
                        $this->model_frete_ocorrencias->updateFreightsOcorrenciaAvisoErp($idOccurrence, 1);
                    }
                    foreach ($arrNameDateOccurrences as $nameDateOccurrence) {
                        $this->log_integration("Pedido ({$this->toolsOrder->orderId}) atualizado", "<h4>Pedido Em transporte foi atualizado a ocorrência do rastreio ({$nameDateOccurrence['trackingCode']})</h4><ul><li>" . datetimeBrazil($nameDateOccurrence['date'], null) . " - {$nameDateOccurrence['name']}</li></ul>", "S");
                    }
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId}. Atualizou ocorrências com os ID's " . Utils::jsonEncode($arrIdOccurrences) . "\n";
                } catch (InvalidArgumentException $exception) {
                    echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                    $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar ocorrência de rastreio do pedido {$this->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                }
            }

            // Pedido já foi entregue, deve remover o estado com código 5 da fila
            if ($order->shipping->delivered_date) {
                return true;
            }
            if (isset($order->shipping->shipped_date) && !empty($order->shipping->shipped_date)) {
                return $this->setShipped();
            }
        }

        // retorna falso para que, não retire o estado do pedido da fila e fique a buscar/enviar as atualizações de ocorrência
        return false;
    }

    /**
     * Quando for logística do seller center envia para o ERP que o pedido foi entregue
     *
     * @return bool
     * @throws Exception
     */
    public function setDelivered(): bool
    {
        $order = $this->getOrder($this->toolsOrder->orderId);

        // Logística do seller center - Status=6
        if (!$this->getStoreOwnLogistic()) {
            // Pedido sem data de entrega
            if (!$order->shipping->delivered_date) {
                return false;
            }

            try {
                $this->toolsOrder->setDeliveredIntegration($this->toolsOrder->orderIdIntegration, $order);
            } catch (InvalidArgumentException $exception) {
                echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
                $this->log_integration("Erro na atualização do pedido ({$this->toolsOrder->orderId})", "<h4>Não foi possível atualizar o pedido ({$this->toolsOrder->orderId}) para Entregue</h4><p>{$exception->getMessage()}</p>", "E");
                return false;
            }

            $this->setNameStatusUpdated('Entregue em: ' . datetimeBrazil($order->shipping->delivered_date, null));
        }

        return true;
    }

    /**
     * Consulta se o pedido já está cancelado na integradora
     *
     * @param   object $order   Dados do pedido
     * @return  bool            Estado da atualização
     */
    public function getCancelIntegration(object $order): bool
    {
        try {
            if ($order->status->code == 1 && method_exists($this->toolsOrder, 'checkOrderCanceled')) {
                $this->toolsOrder->orderId = $order->code;
                $dataCancel = $this->toolsOrder->checkOrderCanceled();

                if ($dataCancel) {
                    echo "[SUCCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId} cancelado na integradora\n";
                }
                return $dataCancel;
            }
        } catch (InvalidArgumentException $exception) {
            echo "[PROCESS][LINE:" . __LINE__ . "] Pedido {$this->toolsOrder->orderId}. {$exception->getMessage()}\n";
            return false;
        }
        return true;
    }

    /**
     * Define o nome do status atualizado.
     *
     * @param string|null $nameStatusUpdated
     */
    public function setNameStatusUpdated(string $nameStatusUpdated = null)
    {
        $this->nameStatusUpdated = $nameStatusUpdated;
    }

    public function validarCamposObrigatoriosEPopularObservation(int $orderId, int $store_id)
    {
        $send_new_fields_erp = $this->model_settings->getValueIfAtiveByName('send_new_fields_erp');
        if (!$send_new_fields_erp) {
            echo "A configuração está desativada no seller center. Nenhuma ação será executada\n";
            return null;
        }

        // busca os campos obrigatórios e adicionais da loja
        $mandatory = $this->model_fields_orders_mandatory->getFieldsOrdersMandatory($store_id);
        $additional = $this->model_fields_orders_add->getFieldsOrdersAdd($store_id);

        // se nenhum campo estiver configurado, retorna vazio
        if(empty($mandatory)  && empty($additional)){
            return null;
        }
        
        // mapeamento interno: nome no banco => nome padrão  lógica
        $fields_map = [
            'tid' => 'gateway_tid',
            'nsu' => 'nsu',
            'autorization_id' => 'authorization_id',
            'first_digits' => 'first_digits',
            'last_digits' => 'last_digits'
        ];

        // pega a forma de pagamento para validação condicional de campos
        $forma_pagamento = strtolower($this->model_orders->getPaymentMethodByOrderId($orderId));
        $isCartao = in_array($forma_pagamento, ['creditCard', 'debitCart', 'creditcard', 'debitcard']);

        // pega apenas os campos obrigatórios que estão marcados com 1
        $requiredFields = [];
        foreach ($mandatory as $field => $isRequired) {
            if ((int)$isRequired === 1 && isset($fields_map[$field])) {
                // ignora campos de cartão se a forma de pagamento não for cartão
                if (in_array($field, ['first_digits', 'last_digits', 'autorization_id']) && !$isCartao) {
                    continue;
                }
                $requiredFields[] = $fields_map[$field];
            }
        }

        if(empty($requiredFields) && empty($additional)){ // consulta se os dois estao vazios, se sim interrompe o processo
            return null;
        }

        // consulta os valores reais que estão na orders_payment
        $paymentData = $this->model_orders_payment->getOrderPaymentFields($orderId, $requiredFields);

        // Verifica campos obrigatórios faltando
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || $paymentData[$field] === '' || $paymentData[$field] === null) {
                $missing[] = $field;
            }
        }

        // se tiver faltando algo lança erro com log para rastreamento
        if (!empty($missing)) {
            $msg = "Não foi possível integrar o pedido ($orderId), está faltando: " . implode(', ', $missing);
            echo "[ERRO][LINE:" . __LINE__ . "][".__FUNCTION__."] Pedido {$orderId}. {$msg}\n";
            $this->log_integration("Erro na validação do pedido ({$orderId})", "<h4>Não foi possível validar os campos obrigatórios para o pedido ({$orderId})</h4><p>{$msg}</p>", "E");
            throw new InvalidArgumentException($msg);
        }

        // Gera a string com todos os campos adicionais configurados, mesmo que estejam null
        $observation = [];
        foreach ($additional as $field => $flag) {
            if ((int)$flag === 1) {
                $dbField = $fields_map[$field] ?? $field;
                $valor = $paymentData[$field] ?? $paymentData[$dbField] ?? 'null';
                $observation[] = "$field: $valor";

            }
        }

        // Salva no  observation
        return implode(', ', $observation);
    }

    public function getDataAtualizacaoForcada($store_id, $order_id) {
        //verifica se a loja tiver configurada
        $hasField = $this->model_order_to_delivered->getHasOrderToDeliveredField($store_id);

        //se não tiver, não entra na validação
        if (!$hasField) {
            return null;
        }

        //busca o número de dias configurado para forçar a atualização
        $orderDays = $this->model_order_to_delivered->getValueByField('dias_para_atualizar', $store_id);

        //busca a data de criação do pedido
        $orderCreatedAt = $this->model_order_to_delivered->getOrderDateCreate($order_id);

        //se não tiver dara de criacao ou num de dias
        if (!$orderCreatedAt || !$orderDays) {
            return null;
        }

        //coneverte a criacao do pedido p timestamp
        $createdAtTimestamp = strtotime($orderCreatedAt);

        //soma a quantidade de dias colocada com a data de criacao
        $forcedUpdateTimestamp = strtotime("+{$orderDays} days", $createdAtTimestamp);

        //se a data e hora atuais ultrapassaram/atingiram a data limite calculada.
        if (time() >= $forcedUpdateTimestamp) {
            return $forcedUpdateTimestamp;
        }
        
        //ainda não atingiu o limite, retorna null
        return null;
    }
}