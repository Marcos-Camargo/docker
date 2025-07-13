<?php

use Integration\Integration_v2\Order_v2;

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/tray/Queue/BaseQueueHandler.php";
require_once APPPATH . "libraries/Integration_v2/Order_v2.php";

/**
 * Class OrderQueueHandler
 * @property Order_v2 $toolsProvider
 */
class OrderQueueHandler extends BaseQueueHandler
{

    protected function getProvider(): \Integration\Integration_v2
    {
        if ($this->toolsProvider === null || !isset($this->toolsProvider)) {
            $this->toolsProvider = new Order_v2();
            $this->toolsProvider->setJob(get_class($this));
            $this->toolsProvider->startRun($this->storeId);
            $this->toolsProvider->setToolsOrder();
        }
        return $this->toolsProvider;
    }

    protected function queueDataHandler(array $queueNotification)
    {
        $data = $queueNotification['data'] ?? (object)[];        

        $this->toolsProvider->toolsOrder->orderIdIntegration = $data->scope_id;
        $storeOwnLogistic = $this->toolsProvider->getStoreOwnLogistic();
        
        if ($data->act == "updated") {
            // Dados do pedido no seller center.
            try {
                $dataOrder = $this->toolsProvider->getOrderByOrderIntegration($data->scope_id);
                if (!$dataOrder) {
                    $this->toolsProvider->toolsOrder->setShippingError('Pedido não localizado.');
                    throw new Exception("Pedido ($data->scope_id) não localizado");
                }
                $dataOrder = $this->toolsProvider->getOrder($dataOrder['id']);
            } catch (InvalidArgumentException $exception) {
                $this->toolsProvider->toolsOrder->setShippingError('Pedido não localizado.');
                throw new Exception("Pedido ($data->scope_id) não localizado");
            }

            // Dados do pedido na integradora.
            try {
                $dataOrderIntegration = $this->toolsProvider->toolsOrder->getOrderIntegration($data->scope_id);
            } catch (InvalidArgumentException $exception) {
                $this->toolsProvider->toolsOrder->setShippingError('Pedido não localizado.');
                throw new Exception("Pedido ($data->scope_id) não localizado");
            }

            $this->toolsProvider->toolsOrder->orderId = $dataOrder->code;
            $this->toolsProvider->setUniqueId($this->toolsProvider->toolsOrder->id);

            $status = $dataOrderIntegration->OrderStatus->id;
            $nameStatusUpdated = null;

            if ($status === 'invoiced') {
                if ($dataOrder->status->code != 3) {
                    return;
                }

                if ($dataOrder->invoice) {
                    return;
                }

                if ($dataOrderIntegration->has_invoice != 1) {
                    $this->toolsProvider->toolsOrder->setShippingError('Pedido sem dados de nota fiscal');
                    throw new Exception("Pedido ($data->scope_id) ainda não faturado.");
                }
        
                $order = $dataOrderIntegration->id;
                try {
                    $request = $this->toolsProvider->request('GET', "orders/{$order}/invoices");
                } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                    throw new InvalidArgumentException($exception->getMessage());
                }
        
                $invoiceResponse = Utils::jsonDecode($request->getBody()->getContents());
                $invoiceResponse = $invoiceResponse->OrderInvoice;

                if (!isset($invoiceResponse->key)    || empty($invoiceResponse->key) ||
                !isset($invoiceResponse->id)     || empty($invoiceResponse->id) ||
                !isset($invoiceResponse->number) || empty($invoiceResponse->number)
                ) {
                    $this->toolsProvider->toolsOrder->setShippingError('Os dados de nota fiscal estão incompletos, reveja: Chave, Número, id.');
                    throw new Exception("Pedido ($data->scope_id) com os dados de fatura faltantes. Confirme os seguintes dados: Chave, Número, id.");
                }
                

                try {
                    $dateEmission = dateFormat($invoiceResponse->issue_date, DATETIME_INTERNATIONAL, null);
                } catch (Exception $exception) {
                    $this->toolsProvider->toolsOrder->setShippingError('Data de emissão em um formato inválido, preencha no formato dd/mm/aaaa hh:ii:ss.');
                    throw new Exception("Pedido ($data->scope_id) com a Data de emissão inválida.");
                }

                $dataInvoice = array(
                    'date'      => $dateEmission,
                    'value'     => roundDecimal($invoiceResponse->value),
                    'serie'     => (int)clearBlanks($invoiceResponse->serie),
                    'number'    => (int)clearBlanks($invoiceResponse->number),
                    'key'       => clearBlanks($invoiceResponse->key)
                );

                try {
                    $this->toolsProvider->setInvoiceOrder($dataInvoice);
                } catch (InvalidArgumentException $exception) {
                    $this->toolsProvider->toolsOrder->setShippingError($exception->getMessage());
                    throw new Exception($exception->getMessage());
                }
            }
            elseif (
                $storeOwnLogistic && $status === 'shipping_informed' && $dataOrder->status->code == 40 ||
                $storeOwnLogistic && $status === 'shipped' && $dataOrder->status->code == 40
            ) {
                $nameStatusUpdated = 'Aguardando Coleta/Envio';
                if ($dataOrder->shipping->tracking_code) {
                    return;
                }

                try {
                    $tracking = $this->toolsProvider->toolsOrder->getTrackingIntegration($this->toolsProvider->toolsOrder->orderIdIntegration, $dataOrder->items);
                } catch (InvalidArgumentException $exception) {
                    $this->toolsProvider->log_integration("Erro na atualização do pedido ({$this->toolsProvider->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsProvider->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    throw new Exception($exception->getMessage());
                }

                // Não encontrou rastreio para os itens.
                if (!count($tracking)) {
                    $this->toolsProvider->toolsOrder->setShippingError('Pedido sem informações de rastreio');
                    throw new Exception("Pedido ($data->scope_id). Sem informações de rastreio");
                }

                try {
                    $this->toolsProvider->setTrackingOrder($tracking, $this->toolsProvider->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    if ($exception->getMessage() !== 'Order already has a tracking') {
                        $this->toolsProvider->toolsOrder->setShippingError($exception->getMessage());
                    }
                    $this->toolsProvider->log_integration("Erro na atualização do pedido ({$this->toolsProvider->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$this->toolsProvider->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    throw new Exception($exception->getMessage());
                }
            }
            elseif ($storeOwnLogistic && $status === 'shipped' && $dataOrder->status->code == 43) {
                if ($dataOrder->shipping->shipped_date) {
                    return;
                }

                $dateShipped = $dataOrderIntegration->date_shipped ?? '';

                if (empty($dateShipped)) {
                    $this->toolsProvider->toolsOrder->setShippingError('Não foi possível obter a data de envio do pedido.');
                    throw new Exception("Não foi possível obter a data de envio do pedido ($data->scope_id)");
                }

                $nameStatusUpdated = 'Em Transporte em: ' . dateFormat($dateShipped, DATETIME_BRAZIL, null);

                try {
                    $this->toolsProvider->setShippedOrder($dateShipped, $this->toolsProvider->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    $this->toolsProvider->toolsOrder->setShippingError($exception->getMessage());
                    throw new Exception($exception->getMessage());
                }
            }
            elseif ($storeOwnLogistic && $status === 'delivered' && $dataOrder->status->code == 45) {
                if ($dataOrder->shipping->delivered_date) {
                    return;
                }

                $dateDelivered = $dataOrderIntegration->sending_date ?? '';

                if (empty($dateDelivered)) {
                    $this->toolsProvider->toolsOrder->setShippingError('Não foi possível obter a data de entrega do pedido.');
                    throw new Exception("Não foi possível obter a data de entrega do pedido ($data->scope_id)");
                }

                $nameStatusUpdated = 'Entregue em: ' . dateFormat($dateDelivered, DATETIME_BRAZIL, null);

                try {
                    $this->toolsProvider->setDeliveredOrder($dateDelivered, $this->toolsProvider->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    $this->toolsProvider->toolsOrder->setShippingError($exception->getMessage());
                    throw new Exception($exception->getMessage());
                }
            }

            if ($nameStatusUpdated) {
                $this->toolsProvider->log_integration("Pedido ({$this->toolsProvider->toolsOrder->orderId}) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido {$this->toolsProvider->toolsOrder->orderId}, foi atualizado para <strong>$nameStatusUpdated</strong></li></ul>", "S");
            }
        }
    }

}