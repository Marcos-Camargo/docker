<?php 

 require APPPATH . "controllers/Api/FreteConectala.php";


class FreteNM extends FreteConectala 
{
    private $mkt;

    public function __construct() 
    {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'NM', 'channel' => 'NM');
    }

    public function index_post() 
    {
        $timeStart = microtime(true);
        $data = json_decode(file_get_contents('php://input'), true);

        if (!array_key_exists ('destinationZip', $data))
            return $this->returnError('Sem o parametro shipping_zip_code. Recebido: '.print_r($data,true));

        if (!array_key_exists ('volumes', $data))
            return $this->returnError('Sem o parametro sku. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['destinationZip']), FILTER_SANITIZE_NUMBER_INT);
        $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

        $items      = array();
        $skusKey    = array();
        foreach ($data['volumes'] as $vol) {
            array_push($items, array(
                'sku' => $vol['sku'],
                'qty' => (int)$vol['quantity']
            ));
            array_push($skusKey, $vol['sku']);
        }

        $quote = $this->calculofrete->formatQuote($this->mkt, $items, $zip);

        if (!$quote['success'])
            return $this->returnError($quote['data']['message']);

        $freights        = array();
        $taxTotal        = 0;
        $totalPriceQuote = 0;
        $totalDeadline   = null;
        $providerQuote   = array();
        // por default iremos retornar o melhor preço até o módulo frete ficar pronto.
        foreach ($quote['data']['services'] as $service) {
            // $tax = $this->calculofrete->calculaTaxa($quote['data']['totalPrice']);
            $tax = 0;
            array_push($freights, array(
                'shippingCost'       => $service['value'] + $tax,
                'deliveryTime'       => $service['deadline'],
                'shippingEstimateId' => $service['method'],
                'shippingMethodName' => $service['provider']
            ));

            $taxTotal += $tax;
            $totalPriceQuote += $service['value'];

            if ($totalDeadline === null || $totalDeadline < $service['deadline']) $totalDeadline = $service['deadline'];

            if (!in_array($service['provider'], $providerQuote)) array_push($providerQuote, $service['provider']);
        }

        $response = array();
        $response['shippingQuotes'] = $freights;

        $timeFinish = microtime(true);
        $this->db->insert('quotes_correios', array(
            'marketplace'   => $this->mkt['channel'],
            'zip'           => $zip,
            'sku'           => json_encode($skusKey),
            'price'         => $tempValue ?? 0,
            'time'          => $tempDeadLine ?? 0,
            'service'       => $tempProvider ?? '',
            'frete_taxa'    => $taxTotal,
            'response'      => json_encode($freights, true),
            'response_time' => ($timeFinish-$timeStart)*1000
        ));

        return $this->response($response, REST_Controller::HTTP_OK);

    }

    /**
     * Retorno de erro quando não é possível realizar a cotação
     *
     * @param  string $message  Mensagem retornada da cotação
     * @return null             Retorna o json espero pelo marketplace quando encontra um erro
     */
    private function returnError(string $message)
    {
        $this->log_data('api', "frete{$this->mkt['channel']}", $message, 'E');

        $returnError = array(
            'shippingQuotes' => array(
                array(
                    'shippingCost'              => 8888,
                    'deliveryTime'              => 99,
                    'shippingEstimateId'        => '76429',
                    'shippingMethodId'          => '',
                    'shippingMethodName'        => 'Não TEM',
                    'shippingMethodDisplayName' => ''
                )
            )
        );

        return $this->response($returnError, REST_Controller::HTTP_OK);
    }
}