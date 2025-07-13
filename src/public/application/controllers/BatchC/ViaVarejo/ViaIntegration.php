<?php
/*
 
Realiza a integração no via varejo

*/   
require 'ViaAttributes.php';

class ViaIntegration {
        
    private $url_api_v2 = 'https://api-mktplace.viavarejo.com.br/api/v2';
    private $url_api_v2_development = 'https://api-mktplace.viavarejo.com.br/api/v2';
    private $url_api_v3 = 'https://api-mktplace.viavarejo.com.br/api/v4/api-front-categories-v3/jersey';
    private $url_api_v4 = 'https://api-mktplace.viavarejo.com.br/api/v4/api-front-importer-v4/jersey';
    private $url_api_v4_development = 'https://api-mktplace.viavarejo.com.br/api/v4/api-front-importer-v4/jersey';
    private $url_api_v4_offer = 'http://api-mktplace.viavarejo.com.br/api/v4/api-front-offer-v4/jersey';    

    public function __construct() 
    { 
        echo '[VIA VAREJO]['. strtoupper(__CLASS__) .'] '. strtoupper(__FUNCTION__) . PHP_EOL;
    }

    function getUrlAPI_V2() 
    { 
        return (ENVIRONMENT === 'production') ? $this->url_api_v2 : $this->url_api_v2_development; 
    }

    function getUrlAPI_V3() 
    {
        return $this->url_api_v3;
    }

    function getUrlAPI_V4() 
    { 
        return (ENVIRONMENT === 'production') ? $this->url_api_v4 : $this->url_api_v4_development; 
    }

    function getUrlAPI_V4_Offer()
    {
        return $this->url_api_v4_offer;
    }

    function register($authorization, $sku, $product, $variants, $brand, $category_id, $attributes, $attributes_variants = null)
    {
        $attributes_via = $this->getAttributesVia($authorization, $category_id);

        $warranty = $this->getWarranty($product, $attributes_via);

        if ($warranty !== false) {
            array_push($attributes, $warranty);
        }

        $attr_mkt = $this->getAttributes($attributes, $attributes_via);
        $variants_mkt = $this->getVariants($variants, $attributes_via);

        $url = $this->getUrlAPI_V4() . '/import/itens';
        $payload = $this->castRegister($sku, $product, $variants_mkt, $brand, $category_id, $attr_mkt, $attributes_variants);
        
        echo $sku . ' - JSON: '. json_encode($payload) . PHP_EOL;

        $retorno = $this->postRequest($url, $authorization, $payload);

        return $retorno;
    }

    function update($authorization, $sku, $product) 
    {
        return $this->updateBF($authorization, $sku, $product);
        if (($product['qty'] > 0) && ($product['status'] == 1)) {
            sleep(1);
            $response = $this->enableAll($authorization, $sku);
        }
        else if ($product['status'] != 1) {
            $response = $this->disableAll($authorization, $sku);
        }
        return $this->updateStock($authorization, $sku, $product);
    }

    function updateBF($authorization, $sku, $product) 
    {
        if ($product['status'] != 1) {
            return $this->resetStock($authorization, $sku, $product);
        }
        else {
            return $this->updateStock($authorization, $sku, $product);
        }
    }

    function updateStock($authorization, $sku, $product)
    {
        $url = $this->getUrlAPI_V2() . "/sellerItems/". $sku .'/stock';
        echo ' UPDATE Stock sku: '. $sku . ' - Qty: '. $product['qty'] . ' Waiting... ';
        $response = $this->putRequest($url, $authorization, $this->castStock($product));
        echo ' - Response: '. $response['httpcode'];
        return $response;
    }

    function resetStock($authorization, $sku, $product) 
    {
        $product = array();
        $product["qty"] = 0; 
        $product['prazo_operacional_extra'] = 0;
        return $this->updateStock($authorization, $sku, $product);
    }

    function status($authorization, $sku, $site, $status)
    {
        $url = $this->getUrlAPI_V2() . "/sellerItems/". $sku .'/status';
        $response = $this->putRequest($url, $authorization, $this->castStatus($site, $status));
        return $response;
    }

    function disableAll($authorization, $sku)
    {
        $product = array();
        $product["qty"] = 0; 
        $product['prazo_operacional_extra'] = 0;
        return $this->resetStock($authorization, $sku, $product);

        $response = $this->status($authorization, $sku, 'EX', false);
        $response = $this->status($authorization, $sku, 'PF', false);
        $response = $this->status($authorization, $sku, 'CB', false);

        return $response;
    }

    function enableAll($authorization, $sku)
    {
        $response = $this->status($authorization, $sku, 'EX', true);
        $response = $this->status($authorization, $sku, 'PF', true);
        $response = $this->status($authorization, $sku, 'CB', true);
        return $response;
    }

    function updatePrices($authorization, $sku, $product)
    {
        $url = $this->getUrlAPI_V4_Offer() . "/offer/price";
        $response = $this->putRequest($url, $authorization, $this->castPrices($sku, $product));
        return $response;
    }

    function updatePricesV2($authorization, $sku, $product)
    {
        $url = $this->getUrlAPI_V2() . "/sellerItems/". $sku ."/prices";
        $response = $this->putRequest($url, $authorization, $this->castPricesV2($sku, $product, 'CB'));
        if ($response['httpcode'] < 300)
            $response = $this->putRequest($url, $authorization, $this->castPricesV2($sku, $product, 'PF'));
        if ($response['httpcode'] < 300)
            $response = $this->putRequest($url, $authorization, $this->castPricesV2($sku, $product, 'EX'));
        return $response;
    }

    function choose($authorization, $sku, $prd_id)
    {
        $url = $this->getUrlAPI_V4() . "/import/itens/". $prd_id ."/sku/". $sku ."/optar";
        $choice = array("resultado" => "NEW_SKU");
        $response = $this->putRequest($url, $authorization, $choice);
        return $response;
    }

    function getProdutcStatus($authorization, $sku, $prd_id)
    {
        $url = $this->getUrlAPI_V4() . "/import/itens/statusSku?idSkusLojista=". $sku;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getProducts($authorization, $offset = 0, $limit = 10)
    {
        $url = $this->getUrlAPI_V2() .  "/sellerItems?_offset=" . $offset . "&_limit=" . $limit;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getProduct($authorization, $sku)
    {
        $url = $this->getUrlAPI_V2() .  "/sellerItems/" . $sku;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getOrdersNew($authorization, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($authorization, "new", $initialDate, $offset, $limit);
    }

    function invoiceOrder($authorization, $order, $items, $nfe)
    {
        $url = $this->getUrlAPI_V2() . "/orders/".  $order["numero_marketplace"] ."/trackings/invoice";
        $response = $this->postRequest($url, $authorization, $this->castInvoiceOrder($order, $items, $nfe));
        return $response;
    }

    function sentOrder($authorization, $order, $items, $freight)
    {
        $url = $this->getUrlAPI_V2() . "/orders/".  $order["numero_marketplace"] ."/trackings/sent";
        $response = $this->postRequest($url, $authorization, $this->castSentOrder($order, $items, $freight));
        return $response;
    }

    function deliveredOrder($authorization, $order, $items, $freight)
    {
        $url = $this->getUrlAPI_V2() . "/orders/". $order["numero_marketplace"] ."/trackings/delivered";
        $response = $this->postRequest($url, $authorization, $this->castDeliveredOrder($order, $items, $freight));
        return $response;
    }

    function getOrdersApproved($authorization, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($authorization, "approved", $initialDate, $offset, $limit);
    }

    function cancelOrder($authorization, $order) 
    {
        $url = $this->getUrlAPI_V2() . "/orders/". $order["numero_marketplace"] ."/trackings/cancel";
        $response = $this->postRequest($url, $authorization, $this->castCancelOrder($order));
        return $response;
    }

    function getOrdersCanceled($authorization, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($authorization, "canceled", $initialDate, $offset, $limit);
    }

    function getOrdersSent($authorization, $initialDate, $offset = 0, $limit = 50)
    {
        return $this->getOrdersStatus($authorization, "sent", $initialDate, $offset, $limit);
    }

    function getOrdersStatus($authorization, $status, $initialDate, $offset, $limit)
    {
        $initialDate = date("Y-m-d\TH:i:s",time() - 60 * 60 * 24* 15);
        $finalDate = date("Y-m-d\TH:i:s",time());
        $query = 
            "?purchasedAt=" . $initialDate . "," . $finalDate .
            "&_offset=" . $offset . 
            "&_limit=" . $limit;
        $url = $this->getUrlAPI_V2() . "/orders/status/" . $status . $query;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getOrder($authorization, $id)
    {
        $url = $this->getUrlAPI_V2() . "/orders/" . $id;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getTickets($authorization, $offset, $limit = 50) {
        $query = 
            "?_offset=" . $offset . 
            "&_limit=" . $limit;
        $url = $this->getUrlAPI_V2() . "/tickets" . $query;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getCategories($authorization, $offset, $limit = 50) {
        $query = 
            "?_offset=" . $offset . 
            "&_limit=" . $limit;
        $url = $this->getUrlAPI_V3() . "/categories" . $query;
        $response = $this->getRequest($url, $authorization);
        return $response;
    }

    function getAttributesVia($authorization, $category_id)
    {
        $attributes = array();
        $url = $this->getUrlAPI_V3() . "/categories/attribute?id=" . $category_id;
        $response = $this->getRequest($url, $authorization);

        if ($response['httpcode'] == 200) 
        {
            $groups = json_decode($response['content'], true);            
            foreach ($groups as $group) 
            {
                $group_attrs = $group['mpUda'];
                foreach($group_attrs as $group_attr)
                {
                    array_push($attributes, $group_attr);
                }
            }
        }
        return $attributes;
    }

    function hasSkuInMkt($authorization, $skumkt, $checkAllVariats = false)
    {
        $product_response = $this->getProduct($authorization, $skumkt);

        if ($product_response['httpcode'] <= 300) return [true, true];

        $url = $this->getUrlAPI_V4() . "/import/itens/statusSku?idSkusLojista=" . $skumkt;
        $response = $this->getRequest($url, $authorization);

        if ($response['httpcode'] == 200) 
        {
            $content = json_decode($response['content'], true);   
            
            $hasInMkt = count($content['itens']) > 0;
            $isFichaIntegrada = false;
            foreach ($content['itens'] as $key => $item)
            {
                if (!$checkAllVariats) 
                {
                    if ($item['idSkuLojista'] == $skumkt) 
                    {
                        $hasInMkt = $hasInMkt && $item['skuStatus'] != 'INVALIDO';
                        $isFichaIntegrada = $isFichaIntegrada && $item['skuStatus'] != 'FICHA_INTEGRADA';
                    }
                }
                else 
                {
                    $hasInMkt = $hasInMkt && $item['skuStatus'] != 'INVALIDO';
                    $isFichaIntegrada = $isFichaIntegrada && $item['skuStatus'] != 'FICHA_INTEGRADA';
                }
            }
            
            return [$hasInMkt, $isFichaIntegrada];
        }
        return [false, false];
    }

    function getAttributes($attributes_conectala, $attributes_via)
    {
        $viaAttributes = new ViaAttributes();

        return $viaAttributes->linkAttributes($attributes_conectala, $attributes_via);        
    }

    function getWarranty($product, $attributes_via) {
        $viaAttributes = new ViaAttributes();

        return $viaAttributes->getWarranty($product, $attributes_via);
    }

    function getVariants($variants_conectala, $attributes_via)
    {
        $viaAttributes = new ViaAttributes();

        return $viaAttributes->linkVariants($variants_conectala, $attributes_via);
    }

    public function hasVariants($variant, $attributes)
    {
        $viaAttributes = new ViaAttributes();
        return $viaAttributes->hasVariant(strtoupper($variant), $attributes);
    }

    private function castRegister($sku, $prd, $variants, $brand, $category_id, $attributes, $attributes_variants = null)
    {
        $skus = array();

        if (count($variants) == 0)
        {
            $skus = array ((object)array(
                "preco" => array (
                  "oferta" => number_format ((float)$prd['promotional_price'], 2, ",", ""),
                  "padrao" => number_format ((float)$prd['price'], 2, ",", ""),
                ),
                "estoque" => array (
                  "tempoDePreparacao" => $prd['prazo_operacional_extra'] + 5,
                  "quantidade" => $prd['qty']
                ),
                "imagens" => $this->getImages($prd),
                "dimensao" => array (
                  "largura" => number_format ((float)$prd['largura'] / 100, 2, ".", ""),
                  "altura" => number_format ((float)$prd['altura'] / 100, 2, ".", ""),
                  "peso" => number_format ((float)$prd['peso_bruto'], 2, ".", ""),
                  "profundidade" => number_format ((float)$prd['profundidade'] / 100, 2, ".", ""),
                ),
                "atributos" => $attributes['variants'],
                //"gtin" => $prd['EAN'], 
                "idSkuLojista" => $sku,
            ));
        }
        else 
        {
            foreach($variants as $variant)
            {
                $sku_variant = (object)array(
                    "preco" => array (
                      "oferta" => number_format ((float)$prd['promotional_price'], 2, ",", ""),
                      "padrao" => number_format ((float)$prd['price'], 2, ",", ""),
                    ),
                    "estoque" => array (
                      "tempoDePreparacao" => $prd['prazo_operacional_extra'] + 5,
                      "quantidade" => $variant['qty']
                    ),
                    "imagens" => $this->getImages($prd, $variant),
                    "dimensao" => array (
                      "largura" => number_format ((float)$prd['largura'] / 100, 2, ".", ""),
                      "altura" => number_format ((float)$prd['altura'] / 100, 2, ".", ""),
                      "peso" => number_format ((float)$prd['peso_bruto'], 2, ".", ""),
                      "profundidade" => number_format ((float)$prd['profundidade'] / 100, 2, ".", ""),
                    ),
                    "atributos" => $variant['attributes'],
                    // "gtin" => $prd['EAN'], 
                    "idSkuLojista" => $sku . '-' . $variant['variant'],
                );
                array_push($skus, $sku_variant);
            }
        }

        if (!is_null($attributes_variants)) {
            foreach($attributes_variants as $a) {
                foreach ($skus as $s) {
                    $attr = array(
                        'idUda' => $a['id_atributo'],
                        'valor' => $a['valor']
                    );
                    array_push($s->atributos, $attr);
                }
            }
        }

        $result = array (
            'itens' => array ((object)array(
                "idItem" => $prd['id'],
                "titulo" => $prd['name'],
                "descricao" => $prd['description'],
                "marca" => $brand,
                "idCategoria" => $category_id,
                "atributos" => $attributes['attributes'],
                "skus" => $skus,
                "garantia" => (int)$prd['garantia']
            ))
        );

        return $result;
    }

    private function castStatus($site, $status)
    {
        return array(
            'active' => $status,
            'site' => $site
        );
    }

    private function castStock($prd)
    {
        $stock = array (
            'quantity' => intval($prd['qty'])
        );

        if (!is_null($prd['prazo_operacional_extra']))
        {
            $stock['crossDockingTime'] = $prd['prazo_operacional_extra'] + 5;
        }

        return $stock;
    }

    private function castPrices($sku, $prd)
    {
        $preco = array(
            'padrao' => number_format ((float)$prd['price'], 2, ".", ""), 
            'oferta' => number_format ((float)$prd['promotional_price'], 2, ".", "")
        );
        return array (
            'idSkuLojista'=> $sku,
            'preco' => $preco
        );
    }

    private function castPricesV2($sku, $prd, $site)
    {
        return array (
            'default'=> number_format ((float)$prd['price'], 2, ".", ""), 
            'offer' => number_format ((float)$prd['promotional_price'], 2, ".", ""),
            'site' => $site
        );
    }

    public function castSentOrder($order, $items, $freight) 
    {
        $date_occurred = (new DateTime( $order['data_envio'], new DateTimeZone('-0300')))->format(DateTime::ATOM);
        
        if ((new DateTime( $order['data_envio'], new DateTimeZone('-0300'))) <= (new DateTime( $order['data_pago'], new DateTimeZone('-0300')))) {
            $date_occurred = (new DateTime( $order['data_pago'], new DateTimeZone('-0300')))->add(new DateInterval("PT4H"))->format(DateTime::ATOM);
        }

        return $this->castSentOrDeliveredOrder($order, $items, $freight, $date_occurred);
    }

    public function castDeliveredOrder($order, $items, $freight) 
    {
        $date_occurred = (new DateTime( $order['data_entrega'], new DateTimeZone('-0300')))->format(DateTime::ATOM);

        return $this->castSentOrDeliveredOrder($order, $items, $freight, $date_occurred);
    }

    public function castSentOrDeliveredOrder($order, $items, $freight, $date_occurred) 
    {
        $carrier = array(
            'name' => $freight['ship_company']
        );

        if ($freight['ship_company'] != 'CORREIOS') {
            $carrier['cnpj'] = str_replace('/', '', str_replace('-', '', str_replace('.', '', $freight['CNPJ'])));
        }

        $body = array(
            'items' => $items,
            'occurredAt' => $date_occurred,
            'sellerDeliveryId' => $order['id'],
            'number' => $freight['codigo_rastreio'],
            'url' => 'https://www2.correios.com.br/sistemas/rastreamento/',
            'carrier' => $carrier
        );
        return $body;
    }

    public function castInvoiceOrder($order, $items, $nfe) 
    {
        $arr = explode(' ', $nfe['date_emission']); 
        $date = explode('/', $arr[0]);
        $date = $date[2] . '-' . $date[1] . '-' . $date[0];
        $date_time = $date . ' ' . $arr[1];
        $date_occurred = (new DateTime($date_time, new DateTimeZone('-0300')))->format(DateTime::ATOM);

        $body = array(
            'items' => $items,
            'occurredAt' => $date_occurred,
            'invoice' => array(
                'number' => $nfe['nfe_num'],
                'serie' => $nfe['nfe_serie'],
                'accessKey' => $nfe['chave'],
                'issuedAt' => $date_occurred
            )
        );
        return $body;
    }

    private function castCancelOrder($order) 
    {
        $items = array();
        foreach($order['items'] as $item) 
        {
            $skuSellerId = substr($item['skuSellerId'], 0, strrpos($item['skuSellerId'], "-"));
            $has_item = false;
            foreach($items as $item_arr) 
            {
                if ($item_arr['skuSellerId'] == $skuSellerId) 
                {
                    $item_arr['quantity'] += 1;
                    $has_item = true;
                }
            }

            if (!$has_item) 
            {
                $has_item = false;
                $item_arr = array(
                    'skuSellerId' => $item['skuSellerId'],
                    'quantity' => 1
                );
                array_push($items, $item_arr);
            }
        }

        return array(
            'items' => $items,
            'info' => 'Pedido Cancelado'
        );
    }

    private function getImages($prd, $variant = null) 
    {
    	if (!is_null($prd['product_catalog_id'])) {
			$pathImage = 'catalog_product_image';
			$prd['image'] = $prd['product_catalog_id'];
		}
		else {
			$pathImage = 'product_image';
		}
        $imagens = array();
		if ($prd['image']!="") {
			$numft = 0;
			if (strpos("..".$prd['image'],"http")>0) {
				$fotos = explode(",", $prd['image']);	
				foreach($fotos as $foto) {
					$imagens[$numft++] = $foto;
				}
			} else {
                $variant_url = '';
                if (!is_null($variant)) {
                    $variant_url = '/'. $variant['image'];
                }
                $fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/'. $prd['image'].$variant_url);	
                $amount = 0;
				foreach($fotos as $foto) {
                    if ((strpos($foto, '.jpg') !== false) || (strpos($foto, '.png') !== false)) {
                        if (++$amount > 4) continue;
                        $imagens[$numft++] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].$variant_url.'/'. $foto);
                        $imagens[$numft - 1] = str_replace('http://localhost:8888/' , 'https://conectala.com.br/', $imagens[$numft - 1]);
					}
                }
                if (count($imagens) == 0) {
                    $fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/'. $prd['image']);	
                    $amount = 0;
                    foreach($fotos as $foto) {
                        if ((strpos($foto, '.jpg') !== false) || (strpos($foto, '.png') !== false)) {
                            if (++$amount > 4) continue;

                            $imagens[$numft++] = base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto);
                            $imagens[$numft - 1] = str_replace('http://localhost:8888/' , 'https://conectala.com.br/', $imagens[$numft - 1]);
                        }
                    }    
                }
			}	
        }
        return $imagens;
    }

    private function putRequest($url, $authorization, $put_data){
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
			CURLOPT_HTTPHEADER => $this->getHttpHeader($authorization)
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
            return $this->putRequest($url, $authorization, $put_data);
        }

        return $header;
    }

    private function postRequest($url, $authorization, $post_data){
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
			CURLOPT_HTTPHEADER => $this->getHttpHeader($authorization)
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
            return $this->postRequest($url, $authorization, $post_data);
        }

        return $header;
    }
    
    private function getRequest($url, $authorization){
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => $this->getHttpHeader($authorization)
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
            return $this->getRequest($url, $authorization);
        }

        return $header;
    }

    private function getHttpHeader($authorization) 
    {
        return array(
            'accept: application/json;charset=UTF-8',
            'content-type: application/json', 
            'client_id:  '. $authorization['client_id'],
            'access_token: '. $authorization['access_token']
        );
    }
}