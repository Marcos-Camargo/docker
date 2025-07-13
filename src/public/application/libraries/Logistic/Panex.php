<?php

class Panex extends Logistic
{
    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
        $this->setEndpoint('');
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {
        $auth = array();
        $this->authRequest = $auth;
    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse() {}

    /**
     * Cotação.
     *
     * @param   array   $dataQuote      Dados para realizar a cotação.
     * @param   bool    $moduloFrete    Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        return array(
            'success'   => true,
            'data'      => array(
                'services'  => array()
            )
        );
    }

    /**
     * Contrata o frete.
     *
     * @param  array	$order	Dados do pedido.
     * @param  array	$store	Dados da loja.
     * @param  array	$nfe	Dados de nota fiscal.
     * @param  array 	$client	Dados do client.
     */
    public function hireFreight(array $order, array $store, array $nfe, array $client, bool $orderFRtWaitLabel)
    {
        throw new InvalidArgumentException('Panex não configurada para realizar a contratação.');
    }

    /**
     * Consultar as ocorrências do rastreio para Panex.
     *
     * @param   array   $order  Dados do pedido.
     * @param   array   $frete  Dados do frete.
     * @return  void            Retorna o status do rastreio.
     */
    public function tracking(array $order, array $frete): void
    {
        $this->load->model('model_clients');
        $this->load->model('model_shipping_tracking_occurrence');

        // Buscando registro do cliente na tabela clients - usar função.
        $clients_data = $this->model_clients->getClientsData($order['customer_id']);

        // Criar função para buscar os registros na tabela shipping_tracking_occurence.
        $data = array(
            'tracking_code'  => $frete['codigo_rastreio'],
            'recipient_doc' => onlyNumbers($clients_data['cpf_cnpj'])
        );

        $historyVolume = $this->model_shipping_tracking_occurrence->getOccurrencesByTrackingCode($data);

        if (!$historyVolume) {
            return;
        }

        //incluindo as novas alterações
        foreach ($historyVolume as $history) {

            $dataOccurrence = array(
                'description'       => $history['complete_description'],
                'name'              => $history['description_code'],
                'code'              => $history['codigo'],
                'code_name'         => NULL,
                'type'              => NULL,
                'date'              => date('Y-m-d H:i:s', strtotime($history['occurrence_date'])),
                'statusOrder'       => $order['paid_status'],
                'freightId'         => $frete['id'],
                'orderId'           => $order['id'],
                'trackingCode'      => $frete['codigo_rastreio'],
                'address_place'     => NULL,
                'address_name'      => NULL,
                'address_number'    => NULL,
                'address_zipcode'   => NULL,
                'address_neigh'     => NULL,
                'address_city'      => NULL,
                'address_state'     => NULL
            );

            $register_result = $this->setNewRegisterOccurrence($dataOccurrence);
            if ($register_result) {
                // Implementar atualização na linha do registro.
                // Verificar se as ocorrências estão sendo alteradas.
                $this->model_shipping_tracking_occurrence->updateOccurrence($history['id']);
            }
        }
    }
}