<?php

require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class Order
{
    /**
     * Atualiza a situação/dados do pedido.
     *
     * @param   object  $webhook
     * @param   int     $store
     * @return  void
     * @throws  Exception
     */
    public static function update(object $webhook, int $store)
    {
        $order_v2 = new Order_v2();
        $order_v2->setIntegration('pluggto');
        $order_v2->setToolsOrder();

        $order_v2->setUniqueId($webhook->id);

        try {
            $order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            if ($order_v2->store) {
                $order_v2->log_integration(
                    "Erro para receber notificação",
                    "<h4>Não foi possível receber a notificação de produto</h4> <p>{$exception->getMessage()}</p>",
                    "E"
                );
            }

            throw new Exception($exception->getMessage());
        }
        
        $order_v2->toolsOrder->orderIdIntegration = $webhook->id;
        $storeOwnLogistic = $order_v2->getStoreOwnLogistic();
        
        if ($webhook->action == "updated") {
            // Dados do pedido no seller center.
            try {
                $dataOrder = $order_v2->getOrderByOrderIntegration($webhook->id);
                if (!$dataOrder) {
                    $order_v2->toolsOrder->setShippingError('Pedido não localizado.');
                    throw new Exception("Pedido ($webhook->id) não localizado");
                }
                $dataOrder = $order_v2->getOrder($dataOrder['id']);
            } catch (InvalidArgumentException $exception) {
                $order_v2->toolsOrder->setShippingError('Pedido não localizado.');
                throw new Exception("Pedido ($webhook->id) não localizado");
            }

            // Dados do pedido na integradora.
            try {
                $dataOrderIntegration = $order_v2->toolsOrder->getOrderIntegration($webhook->id);
            } catch (InvalidArgumentException $exception) {
                $order_v2->toolsOrder->setShippingError('Pedido não localizado.');
                throw new Exception("Pedido ($webhook->id) não localizado");
            }

            $order_v2->toolsOrder->orderId = $dataOrder->code;
            $order_v2->setUniqueId($order_v2->toolsOrder->orderId);

            $status = $dataOrderIntegration->status;
            $nameStatusUpdated = null;

            if ($status === 'invoiced') {
                if ($dataOrder->status->code != 3) {
                    return;
                }

                if ($dataOrder->invoice) {
                    return;
                }

                if (!isset($dataOrderIntegration->shipments[0]->nfe_key)) {
                    $order_v2->toolsOrder->setShippingError('Pedido sem dados de nota fiscal');
                    throw new Exception("Pedido ($webhook->id) ainda não faturado.");
                }

                if (
                    empty($dataOrderIntegration->shipments[0]->nfe_key) ||
                    empty($dataOrderIntegration->shipments[0]->nfe_number) ||
                    empty($dataOrderIntegration->shipments[0]->nfe_serie) ||
                    empty($dataOrderIntegration->shipments[0]->nfe_date)
                ) {
                    $order_v2->toolsOrder->setShippingError('Os dados de nota fiscal estão incompletos, reveja: Chave, Número, Série e Data de emissão.');
                    throw new Exception("Pedido ($webhook->id) com os dados de fatura faltantes. Confirme os seguintes dados: Chave, Número, Série e Data de emissão.");
                }

                try {
                    $dateEmission = dateFormat($dataOrderIntegration->shipments[0]->nfe_date, DATETIME_INTERNATIONAL, null);
                } catch (Exception $exception) {
                    $order_v2->toolsOrder->setShippingError('Data de emissão em um formato inválido, preencha no formato dd/mm/aaaa hh:ii:ss.');
                    throw new Exception("Pedido ($webhook->id) com a Data de emissão inválida.");
                }

                $dataInvoice = array(
                    'date'      => $dateEmission,
                    'value'     => roundDecimal($dataOrderIntegration->total),
                    'serie'     => (int)clearBlanks($dataOrderIntegration->shipments[0]->nfe_serie),
                    'number'    => (int)clearBlanks($dataOrderIntegration->shipments[0]->nfe_number),
                    'key'       => clearBlanks($dataOrderIntegration->shipments[0]->nfe_key)
                );

                try {
                    $order_v2->setInvoiceOrder($dataInvoice);
                } catch (InvalidArgumentException $exception) {
                    $order_v2->toolsOrder->setShippingError($exception->getMessage());
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
                    $tracking = $order_v2->toolsOrder->getTrackingIntegration($order_v2->toolsOrder->orderIdIntegration, $dataOrder->items);
                } catch (InvalidArgumentException $exception) {
                    $order_v2->log_integration("Erro na atualização do pedido ({$order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    throw new Exception($exception->getMessage());
                }

                // Não encontrou rastreio para os itens.
                if (!count($tracking)) {
                    $order_v2->toolsOrder->setShippingError('Pedido sem informações de rastreio');
                    throw new Exception("Pedido ($webhook->id). Sem informações de rastreio");
                }

                try {
                    $order_v2->setTrackingOrder($tracking, $order_v2->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    if ($exception->getMessage() !== 'Order already has a tracking') {
                        $order_v2->toolsOrder->setShippingError($exception->getMessage());
                    }
                    $order_v2->log_integration("Erro na atualização do pedido ({$order_v2->toolsOrder->orderId})", "<h4>Não foi possível atualizar dados de rastreio do pedido {$order_v2->toolsOrder->orderId}</h4><p>{$exception->getMessage()}</p>", "E");
                    throw new Exception($exception->getMessage());
                }
            }
            elseif ($storeOwnLogistic && $status === 'shipped' && $dataOrder->status->code == 43) {
                if ($dataOrder->shipping->shipped_date) {
                    return;
                }

                $dateShipped = $dataOrderIntegration->shipments[0]->date_shipped ?? '';

                if (empty($dateShipped)) {
                    $order_v2->toolsOrder->setShippingError('Não foi possível obter a data de envio do pedido.');
                    throw new Exception("Não foi possível obter a data de envio do pedido ($webhook->id)");
                }

                $nameStatusUpdated = 'Em Transporte em: ' . dateFormat($dateShipped, DATETIME_BRAZIL, null);

                try {
                    $order_v2->setShippedOrder($dateShipped, $order_v2->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    $order_v2->toolsOrder->setShippingError($exception->getMessage());
                    throw new Exception($exception->getMessage());
                }
            }
            elseif ($storeOwnLogistic && $status === 'delivered' && $dataOrder->status->code == 45) {
                if ($dataOrder->shipping->delivered_date) {
                    return;
                }

                $dateDelivered = $dataOrderIntegration->shipments[0]->date_delivered ?? '';

                if (empty($dateDelivered)) {
                    $order_v2->toolsOrder->setShippingError('Não foi possível obter a data de entrega do pedido.');
                    throw new Exception("Não foi possível obter a data de entrega do pedido ($webhook->id)");
                }

                $nameStatusUpdated = 'Entregue em: ' . dateFormat($dateDelivered, DATETIME_BRAZIL, null);

                try {
                    $order_v2->setDeliveredOrder($dateDelivered, $order_v2->toolsOrder->orderId);
                } catch (InvalidArgumentException $exception) {
                    $order_v2->toolsOrder->setShippingError($exception->getMessage());
                    throw new Exception($exception->getMessage());
                }
            }

            if ($nameStatusUpdated) {
                $order_v2->log_integration("Pedido ({$order_v2->toolsOrder->orderId}) atualizado", "<h4>Estado do pedido atualizado com sucesso</h4> <ul><li>O estado do pedido {$order_v2->toolsOrder->orderId}, foi atualizado para <strong>$nameStatusUpdated</strong></li></ul>", "S");
            }
        }
    }
}