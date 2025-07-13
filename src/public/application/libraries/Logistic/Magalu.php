<?php

require APPPATH."libraries/Integration_v2/magalu/Resources/Auth.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;

class Magalu extends Logistic
{
    private $model_products_instanced = false;
    public $endpoint = 'https://b2b.magazineluiza.com.br/api/v1';

    /**
     * Instantiate a new Integration_v2 instance.
     */
    public function __construct(array $option)
    {
        parent::__construct($option);
    }

    /**
     * Define as credenciais para autenticar na API.
     */
    public function setAuthRequest()
    {

        $token = $this->generateToken();

        $auth['headers']['Authorization'] = "Bearer $token";

        $this->authRequest = $auth;

    }

    private function generateToken(): ?string
    {

        // Consulta dados de credenciais em cache.
        $key_redis = "$this->sellerCenter:integration_logistic:$this->logistic:token";
        $data_redis = \App\Libraries\Cache\CacheManager::get($key_redis);
        if ($data_redis !== null) {
            $data_redis = json_decode($data_redis, true);
            $access_token = $data_redis['access_token'];
            $expirationAccessTokenDate = $data_redis['expirationAccessTokenDate'];

            // Token ainda é válido.
            if (strtotime($expirationAccessTokenDate) > strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {
                return $access_token;
            }
        }

        $credentials_integration = $this->credentials;

        // Se não existe token, data de expiração ou a data já expirou, gera um novo token.
        if (empty($this->credentials['access_token'])
            || empty($this->credentials['expirationAccessToken'])
            || strtotime($this->credentials['expirationAccessToken']) < strtotime(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL))) {

            $user = $this->credentials['magalu_username'] ?? null;
            $password = $this->credentials['magalu_password'] ?? null;

            // Informação pendente.
            if (empty($user) || empty($password)) {
                throw new InvalidArgumentException("Usuário ou senha inválido.");
            }

            // Consulta o novo token.
            $response = $this->request('POST', '/oauth/token', [
                'json' => [
                    'username' => $user,
                    'password' => $password
                ]
            ]);

            $content = Utils::jsonDecode($response->getBody()->getContents(), true);

            $access_token = $content['access_token'] ?? null;
            $expirationAccessToken = $content['expires_in'] ?? null;

            // Conseguiu recuperar o token e data de expiração.
            if ($access_token && $expirationAccessToken) {
                $credentials_update = array(
                    'access_token' => $access_token,
                    'expirationAccessToken' => $expirationAccessToken,
                    'expirationAccessTokenDate' => date('Y-m-d H:i:s', strtotime("+{$content['expires_in']} minutes"))
                );
                $this->credentials = $credentials_integration = array_merge($this->credentials, $credentials_update);
                // Salva o token e a data de expiração no banco para não precisar gerar o token novamente, caso não tenha cache.
                if (!$this->ms_shipping_carrier->use_ms_shipping) {
                    $this->db->where([
                        'integration' => $this->logistic, 'store_id' => $this->freightSeller ? $this->store : 0
                    ])->update('integration_logistic',
                        array('credentials' => json_encode($credentials_integration, JSON_UNESCAPED_UNICODE)));
                }

                \App\Libraries\Cache\CacheManager::setex($key_redis, json_encode($credentials_integration, JSON_UNESCAPED_UNICODE),
                    $expirationAccessToken);

            } else {
                throw new InvalidArgumentException("Não foi possível identificar o Token, Data de expiração ou DR");
            }
        }

        return $credentials_integration['access_token'] ?? null;

    }

    /**
     * Efetua a criação/alteração do centro de distribuição na integradora, quando usar contrato seller center.
     */
    public function setWarehouse()
    {
    }

    /**
     * Cotação.
     *
     * @param  array  $dataQuote  Dados para realizar a cotação.
     * @param  bool  $moduloFrete  Dados de envio do produto por módulo frete.
     * @return  array
     */
    public function getQuote(array $dataQuote, bool $moduloFrete = false): array
    {
        $seller = "magazineluiza";

        $arrSkuProductId = array();
        $items = [];

        foreach ($dataQuote['items'] as $sku) {
            $sku_seller = $sku['skuseller'];
            $product_id = $dataQuote['dataInternal'][$sku['sku']]['prd_id'];

            /*
            if ($sku['variant'] === '') {
                $dataProduct = $this->db->select('sku, product_id_erp as id_erp')->get_where('products', array('id' => $product_id))->row_array();
            } else {
                $dataProduct = $this->db->select('sku, variant_id_erp as id_erp')->get_where('prd_variants', array('prd_id' => $product_id, 'variant' => $sku['variant']))->row_array();
            }

            if (!$dataProduct) {
                continue;
            }

            $idErpExplode = explode('_', $dataProduct['id_erp']);
            $seller = $idErpExplode[1];
            $sku_seller = $idErpExplode[0];
            */

            try {
                $check_availability = $this->getAvailability($sku_seller, $seller, $sku['quantidade'], $dataQuote['dataInternal'][$sku['sku']]['qty_atual'], $sku['valor'], $product_id, $sku['variant']);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $arrSkuProductId[] = array(
                'skumkt'        => $sku['sku'],
                'prd_id'        => $product_id,
                'sku_seller'    => $sku_seller,
                'availability'  => $check_availability
            );

            if (!$check_availability) {
                continue;
            }

            $items[] = [
                'prd_id' => $product_id,
                'sku' => "{$sku_seller}_$seller", // 081534600_magazineluiza
                'id_erp' => $sku_seller, // 081534600
                'seller' => $seller, // magazineluiza
                'quantity' => $sku['quantidade'],
                'price' => roundDecimal($sku['valor'])
            ];

        }

        $services = $this->quoteUnit($dataQuote['zipcodeRecipient'], $items, $arrSkuProductId);

        return array(
            'success' => true,
            'data' => array(
                'services' => $services
            )
        );
    }


    /**
     * @param string $zipcodeRecipient
     * @param array $items
     * @param  array  $arrSkuProductId  Código de SKU e PRD_ID para gerar o retorno.
     * @return  array
     */
    private function quoteUnit(string $zipcodeRecipient, array $items, array $arrSkuProductId): array
    {
        try {
            $cartMd5 = md5(json_encode($items));
            $key_redis = "$this->sellerCenter:integration_logistic:$this->logistic:cart:$cartMd5:zipcode:$zipcodeRecipient";

            $body = array(
                'json' => array(
                    'zipcode' => $zipcodeRecipient,
                    'items' => array_map(function($item) {
                        return [
                            'sku'       => $item['id_erp'],
                            'seller'    => $item['seller'],
                            'quantity'  => $item['quantity'],
                        ];
                    }, $items)
                )
            );

            $response = $this->request('POST', "/shipping/cart", $body);
            $quoteResponse = Utils::jsonDecode($response->getBody()->getContents());
        } catch (Throwable $exception) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Magalu\n".$exception->getMessage());
        }

        // não encontrou transportadora
        if (!isset($quoteResponse->deliveries)) {
            throw new InvalidArgumentException("Ocorreu um problema para realiza a cotação na Magalu\n".Utils::jsonEncode($quoteResponse));
        }

        $cart_id = $quoteResponse->cart_id;

        //Mantendo o carrinho em cache durante 30 minutos
        \App\Libraries\Cache\CacheManager::setex($key_redis, $cart_id, 60 * 30);

        $services = array();
        foreach ($quoteResponse->deliveries as $service) {
            if ($service->status != 'available') {
                continue;
            }

            $package_sku_service = array();
            foreach ($service->items as $item) {
                $package_sku_service[] = $item->sku;
            }

            foreach ($package_sku_service as $sku) {
                $skuProductId = getArrayByValueIn($arrSkuProductId, $sku, 'sku_seller');

                if (empty($skuProductId) || !$skuProductId['availability']) {
                    continue;
                }

                foreach ($service->modalities as $modality) {
                    $services[] = array(
                        'prd_id'    => $skuProductId['prd_id'],
                        'skumkt'    => $skuProductId['skumkt'],
                        'quote_id'  => $service->id,
                        'method_id' => $modality->id,
                        'value'     => $modality->amount,
                        'deadline'  => $modality->shipping_time->max_value,
                        'method'    => $modality->name,
                        'provider'  => $modality->type,
                        'quote_json'=> json_encode($service, JSON_UNESCAPED_UNICODE)
                    );
                }
            }
        }

        return $services;
    }

    /**
     * Recupera o estoque de produto(s)
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param   string  $sku    código SKU
     * @param   string  $seller Seller na integradora
     * @param   int     $stock  Quantidade em estoque
     * @param   float   $price  Preço de venda
     * @return  bool            Retorna se o sku está disponível para compra.
     */
    public function getAvailability(string $sku, string $seller, int $stock, int $real_stock, float $price, int $product_id, ?int $variant): bool
    {
        $urlGetSku = "/products/pricing_and_availability";

        try {
            $request = $this->request('POST', $urlGetSku, array('json' => array(
                'products' => array(
                    array(
                        'sku'       => $sku,
                        'seller_id' => $seller
                    )
                ),
                'show_payment_methods' => false
            )));
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($response) || !is_array($response)){
            throw new InvalidArgumentException("Não encontrado disponibilidade para $sku " . json_encode($response));
        }

        foreach ($response as $pricing_and_availability) {
            if ($pricing_and_availability->sku == $sku) {
                $availability = $pricing_and_availability->availability === 'in stock';
                $active = $pricing_and_availability->active;
                $stock_availability = $stock <= $pricing_and_availability->stock_count;
                $this->setNewStockAndPrice($real_stock, $pricing_and_availability->stock_count, $price, $pricing_and_availability->price, $product_id, $variant);
                return $availability && $active && $stock_availability;
            }
        }

        throw new InvalidArgumentException("Não encontrado disponibilidade para $sku " . json_encode($response));
    }

    private function setNewStockAndPrice(float $real_stock, float $stock, float $real_price, float $price, int $product_id, ?int $variant)
    {
        $real_stock = (int)$real_stock;
        $stock = (int)$stock;
        $real_price = roundDecimal($real_price);
        $price = roundDecimal($price);

        if ($real_stock != $stock) {
            if (!$this->model_products_instanced) {
                $this->model_products_instanced = true;
                $this->load->model('model_products');
            }

            // Produto simples
            if (is_null($variant) || $variant === '') {
                $this->model_products->update(array('qty' => $stock), $product_id, 'Alteração via cotação de frete');
            } else {
                $this->model_products->updateVar(array('qty' => $stock), $product_id, $variant, 'Alteração via cotação de frete');
            }
        }

        if ($real_price != $price) {
            if (!$this->model_products_instanced) {
                $this->model_products_instanced = true;
                $this->load->model('model_products');
            }

            // Produto simples
            if (is_null($variant) || $variant === '') {
                $this->model_products->update(array('price' => $price), $product_id, 'Alteração via cotação de frete');
            } else {
                $this->model_products->updateVar(array('price' => $price), $product_id, $variant, 'Alteração via cotação de frete');
            }
        }
    }
}