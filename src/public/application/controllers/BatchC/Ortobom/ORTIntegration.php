<?php
/*
 
Realiza a integração na ORTOBOM SC

*/   
require 'ORTAttributes.php';

class ORTIntegration 
{    
    public $url_api = 'https://ortobomteste.conectala.com.br/app/Api/V1/';
 

    public function __construct() 
    { 
        echo '[SELLER CENTER]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__) . PHP_EOL;
    }


    function update($api_keys, $sku, $product) 
    {
        if (($product['qty'] > 0) && ($product['status'] == 1))
            $response = $this->enableAll($api_keys, $sku);
        else if ($product['status'] != 1)
            $response = $this->disableAll($api_keys, $sku);
        
        return $this->updateStock($api_keys, $sku, $product);
    }


    function removeFromLine($code = null, $api_keys = null) 
    {
        if(empty($code) || empty($api_keys))
            return false;

        $url = $this->url_api. "Orders/". $code;        
        $response = $this->delRequest($url, $api_keys);
        return $response;
        

    }


    function updateBF($api_keys, $sku, $product) 
    {
        return $this->updateStock($api_keys, $sku, $product);
    }


    function updateStock($api_keys, $sku, $product)
    {
        echo ' UPDATE Stock sku: '. $sku . ' - Qty: '. $product['qty'] . ' Waiting... ';

        $url = $this->url_api. "Products/". $sku;
        
        $response = $this->putRequest($url, $api_keys, $this->castStock($product));
        echo ' - Response: '. $response['httpcode'];
        return $response;
    }


    function resetStock($api_keys, $sku, $product) 
    {
        $product["qty"] = 0; 
        $product['prazo_operacional_extra'] = 0;
        return $this->updateStock($api_keys, $sku, $product);
    }


    function status($api_keys, $sku, $status)
    {
        $url = $this->url_api . "Products/". $sku;
        return $this->putRequest($url, $api_keys, $this->castStatus($status));
    }


    function disableAll($api_keys, $sku)
    {
        return $this->status($api_keys, $sku, false);
    }


    function enableAll($api_keys, $sku)
    {
        return $this->status($api_keys, $sku, true);
    }


    function updatePrices($api_keys, $sku, $product)
    {
        $url = $this->url_api . "Products/". $sku;
        return $this->putRequest($url, $api_keys, $this->castPrices($product['price']));
    }


    function choose($api_keys, $sku, $prd_id)
    {
        $url = $this->getUrlAPI_V4() . "/import/itens/". $prd_id ."/sku/". $sku ."/optar";
        $choice = array("resultado" => "NEW_SKU");
        $response = $this->putRequest($url, $api_keys, $choice);
        return $response;
    }


    function getProdutcStatus($api_keys, $sku, $prd_id)
    {
        $url = $this->getUrlAPI_V4() . "/import/itens/statusSku?idSkusLojista=". $sku;
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getProduct($api_keys, $sku)
    {
        $url = $this->url_api .  "Products/" . $sku;
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }

    
    function getOrdersLine($api_keys)
    {
        $url = $this->url_api . "Orders";
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getOrdersList($api_keys = false, $page = 1, $per_page = 50, $start_date = false, $end_date = false)
    {
        if (!$api_keys || !$start_date || !$end_date)
            return false;

        $url = $this->url_api . "Orders/list?page=".$page."&per_page=".$per_page."&start_date=".$start_date."&end_date=".$end_date;
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getOrderItem($api_keys = false, $order_code = false)
    {
        if (!$api_keys || !$order_code)
            return false;

        $url = $this->url_api . "Orders/".$order_code;
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getOrdersNew($api_keys, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($api_keys, "new", $initialDate, $offset, $limit);
    }


    function sentOrder($api_keys, $order, $items, $nfe, $freight)
    {
        $url = $this->url_api . "Orders/".  $order["numero_marketplace"] ."/trackings/sent";
        $response = $this->postRequest($url, $api_keys, $this->castSentOrder($order, $items, $nfe, $freight));
        return $response;
    }


    function deliveredOrder($api_keys, $order, $items, $nfe, $freight)
    {
        $url = $this->url_api . "Orders/". $order["numero_marketplace"] ."/trackings/delivered";
        $response = $this->postRequest($url, $api_keys, $this->castSentOrder($order, $items, $nfe, $freight));
        return $response;
    }


    function getOrdersApproved($api_keys, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($api_keys, "approved", $initialDate, $offset, $limit);
    }


    function cancelSCOrder($api_keys, $code, $reason = 'Pedido cancelado por falta de estoque') 
    {
        $url = $this->url_api . "Orders/".$code."/canceled";
        $reason = array('order' => array('date' => date('m/d/Y h:i:s', time()), 'reason' => $reason));
        $response = $this->putRequest($url, $api_keys, $reason);
        return $response;
    }


	function cancelLocalOrder($code = null, $reason = 'Pedido cancelado por falta de estoque') 
	{
		if (empty($code))
			return false;
		
		$cancels = $this->model_orders->updateCancels($code, $reason);
        //desconhece no fluxo da loja onde realizar o cancelamento
        // $cancels = 'OK';

		return $cancels;
	}


    function getOrdersCanceled($api_keys, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($api_keys, "canceled", $initialDate, $offset, $limit);
    }


    function getOrdersSent($api_keys, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($api_keys, "sent", $initialDate, $offset, $limit);
    }


    function getOrdersStatus($api_keys, $status, $initialDate, $offset, $limit)
    {
        $initialDate = date("Y-m-d\TH:i:s",time() - 60 * 60 * 24* 15);
        $finalDate = date("Y-m-d\TH:i:s",time());

        $url = $this->url_api.'Orders';
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getOrder($api_keys, $id)
    {
        $url = $this->url_api . "Orders/" . $id;
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getCategories($api_keys)
     {
        $url = $this->url_api . "Categories";
        $response = $this->getRequest($url, $api_keys);
        return $response;
    }


    function getAttributesSC($api_keys = false, $category_id = false, $into_to_SC = false)
    {
        if(!$api_keys || !$category_id || !$into_to_SC)
            return false;

        $attributes = array();

        $url = $this->url_api . "Attributes/".$category_id."/".$into_to_SC;
        $response = $this->getRequest($url, $api_keys);

        if ($response['httpcode'] == 200) 
        {
            $attributes = json_decode($response['content'], true);
        }
        return $attributes;
    }


    function getAttributes($attributes_conectala, $attributes_sc)
    {
        $scAttributes = new ORTAttributes();

        return $scAttributes->linkAttributes($attributes_conectala, $attributes_sc);        
    }


    function getVariants($variants_conectala, $attributes_sc)
    {
        $scAttributes = new ORTAttributes();

        return $scAttributes->linkVariants($variants_conectala, $attributes_sc);
    }


    public function hasVariants($variant, $attributes)
    {
        $scAttributes = new ORTAttributes();
        return $scAttributes->hasVariant(strtoupper($variant), $attributes);
    }


    private function castStatus($status)
    {
        return array('product' => array('active' => $status ? 'enabled' : 'disabled'));
    }


    private function castStock($prd)
    {
        return array('product' => array('qty' => $prd['qty']));
    }


    private function castPrices($price)
    {
        return array('product' => array('price' => number_format ((float)$price, 2, ".", "")));
    }

    public function castSentOrder($order, $items, $nfe, $freight) 
    {
        
        $carrier = array(
            'name' => $freight['ship_company']
        );

        if ($freight['ship_company'] != 'CORREIOS') {
            $carrier['cnpj'] = str_replace('/', '', str_replace('-', '', str_replace('.', '', $freight['CNPJ'])));
        }

        $body = array(
            'items' => $items,
            'occurredAt' => (new DateTime( $order['data_envio'], new DateTimeZone('-0300')))->format(DateTime::ATOM),
            'sellerDeliveryId' => $order['id'],
            'number' => $freight['codigo_rastreio'],
            'url' => 'https://www2.correios.com.br/sistemas/rastreamento/',
            'carrier' => $carrier,
            'invoice' => array(
                'number' => $nfe['nfe_num'],
                'serie' => $nfe['nfe_serie'],
                'accessKey' => $nfe['chave']
            )
        );
        return $body;
    }



    private function putRequest($url, $api_keys, $put_data)
    {
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS	=> json_encode($put_data),
			CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
	    );
        
        $ch         = curl_init( $url );
        curl_setopt_array( $ch, $options );
        
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );

		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        if ($httpcode == 429) {
            sleep(62);
            return $this->putRequest($url, $api_keys, $put_data);
        }

        return $header;
    }


    private function postRequest($url, $api_keys, $post_data){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
            CURLOPT_POSTFIELDS	=> json_encode($post_data),
			CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
	    );
        
        $ch         = curl_init( $url );
        curl_setopt_array( $ch, $options );
        
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );

		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
        $header['content']  = $content;
        $header['reqbody']  = json_encode($post_data);
        
        if ($httpcode == 429) {
            echo ' Status Code: 429 - Waiting... ';
            sleep(62);
            echo ' Resend... ';
            return $this->postRequest($url, $api_keys, $post_data);
        }

        return $header;
    }
    

    private function getRequest($url, $api_keys)
    {
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
	    );
        
        $ch         = curl_init( $url );
        curl_setopt_array( $ch, $options );
        
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );

		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
        
        return $header;
    }


    private function delRequest($url, $api_keys)
    {
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_CUSTOMREQUEST => "DELETE",
			CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
	    );
        
        $ch         = curl_init( $url );
        curl_setopt_array( $ch, $options );
        
	    $content    = curl_exec( $ch );
		$httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err        = curl_errno( $ch );
	    $errmsg     = curl_error( $ch );
	    $header     = curl_getinfo( $ch );
        
        curl_close( $ch );

		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;

        return $header;
    }
    

    private function getHttpHeader($api_keys) 
    {
        if (empty($api_keys))
            return false;
            
        $keys = array();

        foreach ($api_keys as $k => $v)
        {
            if ($k != "api_url" && $k != "int_to")
                $keys[] = $k.':'.$v;
        }

        return $keys;        
    }
}