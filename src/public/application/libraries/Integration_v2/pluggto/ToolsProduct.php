<?php

namespace Integration\Integration_v2\pluggto;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

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
     * Define os atributos para o produto.
     *
     * @param   string      $productId          Código do produto (products.id).
     * @param   string      $productIntegration Código do produto na integradora.
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"].
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        $attributes = array();
        $product = $this->getDataProductIntegration($productIntegration);

        if (!$product || !property_exists($product, 'attributes') || !count($product->attributes)) {
            return $attributes;
        }


        foreach ($product->attributes as $attribute) {
            $attributes[$attribute->label] = $attribute->value->label;
        }

        return $attributes;
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param   string $id Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "skus/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            echo "[LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($product, 'Product')) {
            return null;
        }

        return $product->Product;
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param   string $id Código do produto.
     */
    public function getDataProductIntegrationById(string $id)
    {
        $urlGetSku = "products/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            echo "[LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (!property_exists($product, 'Product')) {
            return null;
        }

        return $product->Product;
    }

    /**
     * Formata os dados do produto para criar ou atualizar.
     *
     * @param   array|object $payload   Dados do produto para formatação.
     * @param   mixed        $option    Dados opcionais para auxílio na formatação.
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $price = empty($payload->special_price ?? null) ? $payload->price : $payload->special_price;
        $stock = $payload->quantity;

        $crossDocking = (($payload->manufacture_time ?? 0) + ($payload->handling_time ?? 0));
        $crossDocking = (int)$crossDocking === 0 ? 1 : $crossDocking;

        $productFormatted = array(
            'name'                  => array('value' => $payload->name, 'field_database' => 'name'),
            'status'                => array('value' => 1, 'field_database' => 'status'),
            'sku'                   => array('value' => $payload->sku, 'field_database' => 'sku'),
            'unity'                 => array('value' => 'UN', 'field_database' => 'attribute_value_id'),
            'price'                 => array('value' => $price, 'field_database' => 'price'),
            'list_price'            => array('value' => $payload->price, 'field_database' => 'list_price'),
            'stock'                 => array('value' => $stock, 'field_database' => 'qty'),
            'ncm'                   => array('value' => $payload->ncm, 'field_database' => 'NCM'), // não tenho certeza
            'origin'                => array('value' => $payload->origin ?? 0, 'field_database' => 'origin'),
            'ean'                   => array('value' => $payload->ean, 'field_database' => 'EAN'),
            'net_weight'            => array('value' => $payload->dimension->weight, 'field_database' => 'peso_liquido'),
            'gross_weight'          => array('value' => $payload->dimension->weight, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'      => array('value' => '', 'field_database' => 'codigo_do_fabricante'),
            'description'           => array('value' => $payload->description, 'field_database' => 'description'),
            'guarantee'             => array('value' => $payload->warranty_time, 'field_database' => 'garantia'),
            'brand'                 => array('value' => $payload->brand, 'field_database' => 'brand_id'),
            'height'                => array('value' => $payload->dimension->height, 'field_database' => 'altura'),
            'depth'                 => array('value' => $payload->dimension->length, 'field_database' => 'profundidade'),
            'width'                 => array('value' => $payload->dimension->width, 'field_database' => 'largura'),
            'items_per_package'     => array('value' => null, 'field_database' => 'products_package'),
            'category'              => array('value' => $payload->categories[0]->name ?? '', 'field_database' => 'category_id'),
            'images'                => array('value' => $this->getImagesFormatted($payload->photos), 'field_database' => NULL),
            'variations'            => array('value' => $this->getVariationFormatted($payload->variations, $payload), 'field_database' => 'has_variants'),
            'extra_operating_time'  => array('value' => $crossDocking, 'field_database' => 'prazo_operacional_extra')
        );

        return $productFormatted;
    }

    /**
     * Recupera se o preço do produto
     *
     * @param   array|string|int    $id     Código do sku do produto
     * @param   float|null          $price  Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        $priceProd  = 0;
        $listPriceProd = 0 ;
        $skuProduct = array();
        $priceSkus  = array();
        $listPriceSkus  = array();

        if (!is_array($id)) {
            $skuProduct = [$id];
        }
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->codigo;
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;


            $dataPrice = $this->getDataProductIntegration($sku);

            if (!$dataPrice) {
                continue;
            }

            $price = empty($dataPrice->special_price ?? null) ? $dataPrice->price : $dataPrice->special_price;
            $listPrice = $dataPrice->price;
            $priceSkus[$sku] = roundDecimal($price);
            $listPriceSkus[$sku] = roundDecimal($dataPrice->price);

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = roundDecimal($price);
            }
            if ($listPrice > $listPriceProd) {
                $listPriceProd = roundDecimal($listPrice);
            }
        }

        return array(
            'price_product'       => $priceProd,
            'listPrice_product'   => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'     => $priceSkus
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
            $price = empty($variation->special_price ?? null) ? $variation->price : $variation->special_price;
            $typeVariations = array();

            foreach ($variation->attributes as $attribute) {
                $typeVariations[$attribute->label] = $attribute->value->label;
            }

            $arrVariation[] = array(
                'id'            => $variation->id,
                'sku'           => $variation->sku,
                'stock'         => $variation->quantity,
                'price'         => $price,
                'list_price'    => $variation->price,
                'ean'           => $variation->ean,
                'images'        => $this->getImagesFormatted($variation->photos),
                'variations'    => $typeVariations
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

        array_multisort(array_map(function ($image) {
            return $image->order ?? 0;
        }, $payload), SORT_ASC, $payload);

        foreach ($payload as $image) {
            $url = $image->url;
            if ($this->sellerCenter == 'fastshop' && $this->store == 29 && likeTextNew('%imgur.com%', $url)) {
                continue;
            }
            $arrImages[] = $url;
        }

        return $arrImages;
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                   Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): array
    {
        $stockProd  = 0;
        $skuProduct = array();
        $stockSkus  = array();

        if (!is_array($id)) {
            $skuProduct = [$id];
        }
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->sku;
            }
        }

        foreach ($skuProduct as $sku) {
            $stockSkus[$sku] = 0;

            $dataStock = $this->getDataProductIntegration($sku);

            if (!$dataStock) {
                continue;
            }

            $stock = $dataStock->quantity;
            $stockSkus[$sku] = $stock;
            $stockProd += $stock;
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
     * @return  bool                Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }

    /**
     * Formata as datas de filtro para a integradora
     */
    public function formatDateFilter()
    {
        if ($this->dateLastJob) {
            $this->dateStartJob = dateFormat($this->dateStartJob, DATE_INTERNATIONAL);
            $this->dateLastJob  = dateFormat($this->dateLastJob, DATE_INTERNATIONAL);
        }
    }
}