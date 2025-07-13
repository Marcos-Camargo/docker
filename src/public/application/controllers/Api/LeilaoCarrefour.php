<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Apiitem extends Admin_Controller  
{
*/   

/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apicar/freight
 * 
 */

require APPPATH . "controllers/Api/FreteConectala.php";

class LeilaoCarrefour extends FreteConectala {

    private $mkt;

    /**
     * FreteCarrefour constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('calculoFrete');
        $this->mkt = array('platform' => 'CAR', 'channel' => 'CAR');
    }

    public function auction_get()
    {
        $data = $this->input->get();
        $timeStart = microtime(true);
		
		if (!array_key_exists ('shipping_zip_code', $data))
            return $this->returnError('Sem o parametro shipping_zip_code. Recebido: '.print_r($data,true));

		if (!array_key_exists ('sku', $data))
            return $this->returnError('Sem o parametro sku. Recebido: '.print_r($data,true));

        $zip = filter_var(preg_replace('/\D/', '', $data['shipping_zip_code']), FILTER_SANITIZE_NUMBER_INT);
        // $zip = str_pad($zip, 8, 0, STR_PAD_LEFT);

		$tmpArray = explode(',',$data['sku']);
        // return $this->response($tmpArray, REST_Controller::HTTP_OK);

        $items = array();
        $skusKey  = array();
		foreach ($tmpArray as $skuqtd) {
			$temp = explode('|',$skuqtd);
            array_push($items, array(
                'sku' => $temp[0],
                'qty' => (int)$temp[1]
            ));
            array_push($skusKey, $temp[0]);
		}

        $quotesCalc = $this->calculofrete->leilao($this->mkt, $items, $zip, $timeStart);

        if (!$quotesCalc['success'])
            return $this->returnError($quotesCalc['data']['message']);



        $quotes = array();
        foreach($quotesCalc['data'] as $quote) {
            array_push($quotes, array(
                'sku_shop'              => $quote->sku,
                'quantity'              => $quote->quantity,
                'offer_quantity'        => $quote->qty_atual,
                'shipping_price'        => (float)$quote->shipping_price,
                'delivery_time'         => (int)$quote->qtd_days + (int) $quote->cross_docking,
                'shipping_type_code'    => $quote->name_provider,
                'shipping_date_options' => null,
                'deliveryWindows'       => null
            ));
        }

        $response = array(
            'freights' => $quotes
        );

        $timeFinish = (microtime(true) - $timeStart) * 1000;
        
        $this->db->insert('quotes_marketplace', array(
            'marketplace'               => $this->mkt['channel'],
            'sku'                       => json_encode($skusKey),
            'datahora'                  => date("Y/m/d h:i:sa"),
            'cep_destino'               => $zip,
            'cotacao_externa'           => $quotesCalc['microtime_externo'],
            'cotacao_externa_retornos'  => json_encode($quotesCalc['respostas_externas']),
            'cotacao_interna_retornos'  => json_encode($quotesCalc['respostas_internas']),
            'cotacao_interna'           => $quotesCalc['microtime_interno'],
            'resposta_final'            => json_encode($response),
            'tempo_total_consulta'      => $timeFinish,
            'observacao'                => 'Seller usando regra de retorno ' . $quotesCalc['regra']
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

		$json_msg = json_encode([
            'message' => 'Não foi possível realizar a consulta de preço e prazo',
            'error' => $message
            ],JSON_UNESCAPED_UNICODE);
		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_msg);
		$this->response(REST_Controller::HTTP_NOT_FOUND);	
		die;
	}
	
}