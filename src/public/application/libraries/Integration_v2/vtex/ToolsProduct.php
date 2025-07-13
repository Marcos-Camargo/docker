<?php

namespace Integration\Integration_v2\vtex;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;

class ToolsProduct extends Product_v2
{
    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Define os atributos para o produto
     *
     * @param   string      $productId          Código do produto (products.id)
     * @param   string      $productIntegration Código do produto na integradora
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        $productIntegration = str_replace('P_', '', $productIntegration);
        $urlSpecification = "api/catalog_system/pvt/products/$productIntegration/specification";

        try {
            $request = $this->request('GET', $urlSpecification);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] $message - $urlSpecification\n";
            return []; // retorna false, pois, ocorreu um problema na consulta
        }

        $specifications = Utils::jsonDecode($request->getBody()->getContents());
        $responseSpecification = array();

        if (count($specifications) > 0) {
            foreach ($specifications as $specification) {
                $value = implode(', ', array_filter($specification->Value, function ($attribute) {
                    return !empty($attribute);
                }));
                if ($value != '') {
                    $responseSpecification[$specification->Name] = $value;
                }
            }
        }
        return $responseSpecification;
    }

    /**
     * Define os atributos para o sku
     *
     * @param   string      $productId      Código do produto (products.id)
     * @param   string      $skuIntegration Código do produto na integradora
     * @return  array|null                  ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeSku(string $productId, string $skuIntegration): ?array
    {
        return [];
        $urlSpecification = "api/catalog_system/pvt/products/$skuIntegration/specification";

        try {
            $request = $this->request('GET', $urlSpecification);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] $message - $urlSpecification\n";
            return []; // retorna false, pois, ocorreu um problema na consulta
        }

        $content = $request->getBody()->getContents();
        if (empty($content)) {
            return [];
        }

        $specifications = Utils::jsonDecode($content);
        $responseSpecification = array();

        if (count($specifications) > 0) {
            foreach ($specifications as $specification) {
                if (!empty($specification->Text)) {
                    try {
                        $data_specification = $this->getDataAttribute($specification->FieldId);
                    } catch (Exception $exception) {
                        continue;
                    }

                    $responseSpecification[$data_specification->Name] = $specification->Text;
                }
            }
        }

        return $responseSpecification;
    }

    /**
     * Consulta os dados do atributo.
     *
     * @param   int     $attribute_id   Código do atributo
     * @return  object
     *
     * @throws  Exception
     */
    public function getDataAttribute(int $attribute_id): object
    {
        $urlSpecification = "api/catalog/pvt/specification/$attribute_id";

        try {
            $request = $this->request('GET', $urlSpecification);
            return Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * Recupera dados do produto na integradora
     *
     * @param   string $id Código do sku do produto
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "api/catalog_system/pvt/sku/stockkeepingunitbyid/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] ". $message."\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        return Utils::jsonDecode($request->getBody()->getContents());
    }

    /**
     * Formata os dados do produto para criar ou atualizar
     *
     * @param   array|object $payload   Dados do produto para formatação
     * @param   mixed        $option    Dados opcionais para auxílio na formatação
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $idSku = $option === null ? $payload->items[0]->itemId : $payload->items[$option]->itemId;
        $productSku = $this->getDataProductIntegration($idSku);

        // Produto não está na política comercial.
        if (
            !isset($productSku->SalesChannels) ||
            !in_array($this->credentials->sales_channel_vtex, (array)$productSku->SalesChannels)
        ) {
            throw new InvalidArgumentException("SKU ($idSku) Não está na política comercial");
        }

        // garantia
        $guarantee = 0;
        if (isset($payload->Garantia[0]) || isset($payload->garantia[0])) {
            $guaranteeValid = $payload->Garantia[0] ?? $payload->garantia[0] ?? 0;
            $guarantee      = onlyNumbers($guaranteeValid);

            if ($guarantee > 0 && likeText("%ano%", strtolower($guaranteeValid))) {
                $guarantee *= 12;
            }
            if ($guarantee > 0 && likeText("%dia%", strtolower($guaranteeValid))) {
                $guarantee /= 30;
                $guarantee = (int)$guarantee;
            }
        }

        $getPriceERP = $this->getPriceErp($option === null ? $payload->items : $payload->items[$option]->itemId);
        $getStockERP = $this->getStockErp($option === null ? $payload->items : $payload->items[$option]->itemId);

        if ($getPriceERP === false || $getPriceERP === null || $getStockERP === false || $getStockERP === null) {
            throw new InvalidArgumentException("SKU ($idSku) Não foi possível obter preço e/ou estoque.");
        }

        $priceERP = 0;
        $listPriceERP = 0;
        $stockERP = 0;
        if ($getPriceERP) {
            $priceERP = $getPriceERP['price_product'];
            $listPriceERP = $getPriceERP['listPrice_product'];
        }
        if ($getStockERP) {
            $stockERP = $getStockERP['stock_product'];
        }

        $category = (array)$payload->categories;
        $productFormatted = array(
            'name'                      => array('value' => $option === null ? $payload->productName : $payload->items[$option]->nameComplete, 'field_database' => 'name'),
            'status'                    => array('value' => 1, 'field_database' => 'status'),
            'sku'                       => array('value' => $option === null ? 'P_'.$payload->productId : $payload->items[$option]->itemId, 'field_database' => 'sku'),
            'unity'                     => array('value' => $option === null ? $payload->items[0]->measurementUnit : $payload->items[$option]->measurementUnit , 'field_database' => 'attribute_value_id'),
            'price'                     => array('value' => $priceERP, 'field_database' => 'price'),
            'list_price'                => array('value' => $listPriceERP, 'field_database' => 'list_price'),
            'stock'                     => array('value' => $stockERP, 'field_database' => 'qty'),
            'ncm'                       => array('value' => '', 'field_database' => 'NCM'), // não tenho certeza
            'origin'                    => array('value' => 0, 'field_database' => 'origin'),
            'ean'                       => array('value' => $option === null ? $payload->items[0]->ean : $payload->items[$option]->ean, 'field_database' => 'EAN'),
            'net_weight'                => array('value' => $productSku->Dimension->weight/1000, 'field_database' => 'peso_liquido'),
            'gross_weight'              => array('value' => $productSku->Dimension->weight/1000, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'          => array('value' => $productSku->ManufacturerCode, 'field_database' => 'codigo_do_fabricante'),
            'description'               => array('value' => $payload->description, 'field_database' => 'description'),
            'guarantee'                 => array('value' => $guarantee, 'field_database' => 'garantia'),
            'brand'                     => array('value' => $payload->brand, 'field_database' => 'brand_id'),
            'height'                    => array('value' => $productSku->Dimension->height, 'field_database' => 'altura'),
            'depth'                     => array('value' => $productSku->Dimension->length, 'field_database' => 'profundidade'),
            'width'                     => array('value' => $productSku->Dimension->width, 'field_database' => 'largura'),
            'items_per_package'         => array('value' => 1, 'field_database' => 'products_package'),
            'category'                  => array('value' => ltrim(rtrim($category[0] ?? '','/'),'/'), 'field_database' => 'category_id'),
            'images'                    => array('value' => $this->getImagesFormatted($productSku->Images), 'field_database' => NULL),
            'variations'                => array('value' => $option === null ? $this->getVariationFormatted($payload->items) : array(), 'field_database' => 'has_variants'),
            'extra_operating_time'      => array('value' => 0, 'field_database' => 'prazo_operacional_extra')
        );

        $productFormatted['net_weight']['value'] = roundDecimal($productFormatted['net_weight']['value'] ?? 0, 3) > 0 ? $productFormatted['net_weight']['value'] : $productSku->Dimension->weight;
        $productFormatted['gross_weight']['value'] = roundDecimal($productFormatted['gross_weight']['value'] ?? 0, 3) > 0 ? $productFormatted['gross_weight']['value'] : $productSku->Dimension->weight;
        // define valores para estoque e preço das variações
        foreach ($productFormatted['variations']['value'] as $keyVariation => $variation) {

            if (array_key_exists($variation['sku'], $getPriceERP['price_variation'])) {
                $productFormatted['variations']['value'][$keyVariation]['price'] = $getPriceERP['price_variation'][$variation['sku']];
            }
            if (array_key_exists($variation['sku'], $getPriceERP['listPrice_variation'])) {
                $productFormatted['variations']['value'][$keyVariation]['list_price'] = $getPriceERP['listPrice_variation'][$variation['sku']];
            }
            if (array_key_exists($variation['sku'], $getStockERP['stock_variation'])) {
                $productFormatted['variations']['value'][$keyVariation]['stock'] = $getStockERP['stock_variation'][$variation['sku']];
            }
        }

        return $productFormatted;
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param   array|string|int    $id Código do sku do produto.
     * @return  array|null|bool     Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id)
    {
        $priceProd  = 0;
        $listPriceProd = 0;
        $skuProduct = array();
        $priceSkus  = array();
        $listPriceSkus  = array();
        $body       = array();

        if (is_numeric($id)) $skuProduct = [$id];
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->itemId;
            }
        }

        foreach ($skuProduct as $sku) {
            $body[] = array(
                'id'        => $sku,
                'quantity'  => 1,
                'seller'    => 1
            );
        }

        $urlSimulation = "api/fulfillment/pvt/orderForms/simulation";
        $querySimulation = array(
            'query' => array(
                'affiliateId'   => $this->credentials->affiliate_id_vtex,
                'sc'            => $this->credentials->sales_channel_vtex
            ),
            'json' => array(
                'items' => $body
            )
        );

        try {
            $request = $this->request('POST', $urlSimulation, $querySimulation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] $message - ".Utils::jsonEncode($querySimulation)."\n";
            return false; // retorna false, pois, ocorreu um problema na consulta
        }

        $dataPrice = Utils::jsonDecode($request->getBody()->getContents());

        // encontrou um erro de produto indisponível
        // Ex.: Item Cama Sofá Noah com Pés em Madeira Natural — Branco Fosco não encontrado ou indisponível
        if (
            isset($dataPrice->messages[0]->code) &&
            $dataPrice->messages[0]->code === 'ORD027'
        ) {
            return null; // retorna nulo para não ter alteração no preço do SKU
        }

        // não encontrou o preço na resposta
        if (!isset($dataPrice->items)) {
            return false; // retorna false, pois, ocorreu um problema na consulta
        }

        foreach ($dataPrice->items as $item) {

            $price = $item->price ?? 0;
            $listPrice = $item->listPrice ?? 0;

            $priceSkus[$item->id] = moneyVtexToFloat($price);
            $listPriceSkus[$item->id] = moneyVtexToFloat($listPrice);

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = moneyVtexToFloat($price);
            }
            if ($listPrice > $listPriceProd) {
                $listPriceProd = moneyVtexToFloat($listPrice);
            }

        }

        return array(
            'price_product'     => $priceProd,
            'listPrice_product' => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'   => $priceSkus
        );
    }

    /**
     * @param   array   $payload    Dados da variação para formatação
     * @param   mixed   $option     Dados opcionais para auxílio na formatação
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        $arrVariation = array();

        foreach ($payload as $variation) {
            $arrTempVariation = array();
            foreach ($variation->variations as $typeVariation) {
                $arrTempVariation[$typeVariation] = $variation->$typeVariation[0] ?? '';
            }
            $arrVariation[] = array(
                'id'            => $variation->itemId,
                'sku'           => $variation->itemId,
                'stock'         => 0,
                'price'         => 0,
                'list_price'    => 0,
                'ean'           => $variation->ean ?? null,
                'images'        => $this->getImagesFormatted($variation->images ?? array()),
                'variations'    => $arrTempVariation
            );
        }

        return $arrVariation;
    }

    /**
     * @param   array   $payload    Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $arrImages = array();

        foreach ($payload as $image) {
            $arrImages[] = $image->imageUrl ?? $image->ImageUrl;
        }

        return $arrImages;
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array|null|bool         Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id)
    {
        $stockProd  = 0;
        $skuProduct = array();
        $stockSkus  = array();
        $body       = array();

        if (is_numeric($id)) $skuProduct = [$id];
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->itemId;
            }
        }

        foreach ($skuProduct as $sku) {
            $body[] = array(
                'id'        => $sku,
                'quantity'  => 1,
                'seller'    => 1
            );
        }

        $urlSimulation = "api/fulfillment/pvt/orderForms/simulation";
        $querySimulation = array(
            'query' => array(
                'affiliateId'   => $this->credentials->affiliate_id_vtex,
                'sc'            => $this->credentials->sales_channel_vtex
            ),
            'json' => array(
                'items' => $body
            )
        );

        try {
            $request = $this->request('POST', $urlSimulation, $querySimulation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] $message - ".Utils::jsonEncode($querySimulation)."\n";
            return false; // retorna false, pois, ocorreu um problema na consulta
        }

        $dataStock = Utils::jsonDecode($request->getBody()->getContents());

        // encontrou um erro de produto indisponível
        // Ex.: Item Cama Sofá Noah com Pés em Madeira Natural — Branco Fosco não encontrado ou indisponível
        if (
            isset($dataStock->messages[0]->code) &&
            $dataStock->messages[0]->code === 'ORD027'
        ) {
            return null; // retorna nulo para não ter alteração no estoque do SKU
        }

        // não encontrou o estoque na resposta
        if (!isset($dataStock->items) || !isset($dataStock->logisticsInfo)) {
            return false; // retorna false, pois, ocorreu um problema na consulta
        }

        foreach ($dataStock->logisticsInfo as $key => $logistic) {
            $stockSkus[$dataStock->items[$key]->id] = $logistic->stockBalance ?? 0;

            // recupera e soma o estoque de todos os itens
            $stockProd += $logistic->stockBalance ?? 0;
        }

        return array(
            'stock_product'     => $stockProd,
            'stock_variation'   => $stockSkus
        );
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     *  @return  array|null|false        Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro
     */
    public function getPriceStockErp($id)
    {
        $stockProd  = 0;
        $priceProd  = 0;
        $listPriceProd = 0;
        $skuProduct = array();
        $stockSkus  = array();
        $priceSkus  = array();
        $listPriceSkus  = array();
        $body       = array();

        if (is_numeric($id)) $skuProduct = [$id];
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->itemId;
            }
        }

        foreach ($skuProduct as $sku) {
            $body[] = array(
                'id'        => $sku,
                'quantity'  => 1,
                'seller'    => 1
            );
        }

        $urlSimulation = "api/fulfillment/pvt/orderForms/simulation";
        $querySimulation = array(
            'query' => array(
                'affiliateId'   => $this->credentials->affiliate_id_vtex,
                'sc'            => $this->credentials->sales_channel_vtex
            ),
            'json' => array(
                'items' => $body
            )
        );

        try {
            $request = $this->request('POST', $urlSimulation, $querySimulation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            // $message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //echo "[LINE: " . __LINE__ . "] $message - ".Utils::jsonEncode($querySimulation)."\n";
            return false; // retorna false, pois, ocorreu um problema na consulta
        }

        $dataPriceStock = Utils::jsonDecode($request->getBody()->getContents());

        // encontrou um erro de produto indisponível.
        // Ex.: Item Cama Sofá Noah com Pés em Madeira Natural — Branco Fosco não encontrado ou indisponível.
        if (
            isset($dataPriceStock->messages[0]->code) &&
            $dataPriceStock->messages[0]->code === 'ORD027'
        ) {
            return null; // retorna nulo para não ter alteração no estoque do SKU.
        }

        // não encontrou o estoque na resposta
        if (!isset($dataPriceStock->items) || !isset($dataPriceStock->logisticsInfo)) {
            return false; // retorna false, pois, ocorreu um problema na consulta.
        }

        // ler o logisticsInfo para recuperar o estoque
        foreach ($dataPriceStock->logisticsInfo as $key => $logistic) {
            $stockSkus[$dataPriceStock->items[$key]->id] = $logistic->stockBalance ?? 0;

            // recupera e soma o estoque de todos os itens
            $stockProd += $logistic->stockBalance ?? 0;
        }
        foreach ($dataPriceStock->items as $item) {
            $price = $item->price ?? 0;
            $listPrice = $item->listPrice ?? 0;

            $priceSkus[$item->id] = moneyVtexToFloat($price);
            $listPriceSkus[$item->id] = moneyVtexToFloat($listPrice);

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = moneyVtexToFloat($price);
            }
            if ($listPrice > $listPriceProd) {
                $listPriceProd = moneyVtexToFloat($listPrice);
            }
        }

        return array(
            'stock_product'     => $stockProd,
            'stock_variation'   => $stockSkus,
            'price_product'     => $priceProd,
            'listPrice_product'   => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'   => $priceSkus
        );
    }
}