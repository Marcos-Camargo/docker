<?php

namespace Integration\Integration_v2\NEW_INTEGRATION;

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
        return array(
            'Cor' => 'Preto',
            'Tamanho' => 'M'
        );
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param   string  $id Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "produto/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        return Utils::jsonDecode($request->getBody()->getContents());
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
        $image = array('https://site.conectala.com.br/wp-content/uploads/2020/04/logo.png');
        $variation = array(
            array(
                'id'            => 1,
                'sku'           => 'P_001-1',
                'stock'         => 8,
                'price'         => 100,
                'list_price'    => 100,
                'ean'           => '7896196600208',
                'images'        => $this->getImagesFormatted($image),
                'variations'    => array('Cor' => 'Preto', 'Voltagem' => 220)
            ),
            array(
                'id'            => 1,
                'sku'           => 'P_001-2',
                'stock'         => 12,
                'price'         => 100,
                'list_price'    => 100,
                'ean'           => '7891691065248',
                'images'        => $this->getImagesFormatted($image),
                'variations'    => array('Cor' => 'Branco', 'Voltagem' => 110)
            )
        );

        $productFormatted = array(
            'name'                  => array('value' => 'Produto de Teste', 'field_database' => 'name'),
            'status'                => array('value' => 1, 'field_database' => 'status'),
            'sku'                   => array('value' => 'P_001', 'field_database' => 'sku'),
            'unity'                 => array('value' => 'UN', 'field_database' => 'attribute_value_id'),
            'price'                 => array('value' => 100, 'field_database' => 'price'),
            'list_price'            => array('value' => 100, 'field_database' => 'list_price'),
            'stock'                 => array('value' => 20, 'field_database' => 'qty'),
            'ncm'                   => array('value' => '1111.22.33', 'field_database' => 'NCM'), // não tenho certeza
            'origin'                => array('value' => 0, 'field_database' => 'origin'),
            'ean'                   => array('value' => '7896484101127', 'field_database' => 'EAN'),
            'net_weight'            => array('value' => 10, 'field_database' => 'peso_liquido'),
            'gross_weight'          => array('value' => 11, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'      => array('value' => 'T_001', 'field_database' => 'codigo_do_fabricante'),
            'description'           => array('value' => '<h4>Título Descrição</h4><p>Descrição Produto de Teste</p>', 'field_database' => 'description'),
            'guarantee'             => array('value' => 12, 'field_database' => 'garantia'),
            'brand'                 => array('value' => 'Marca de Teste', 'field_database' => 'brand_id'),
            'height'                => array('value' => 10, 'field_database' => 'altura'),
            'depth'                 => array('value' => 15, 'field_database' => 'profundidade'),
            'width'                 => array('value' => 12, 'field_database' => 'largura'),
            'items_per_package'     => array('value' => 1, 'field_database' => 'products_package'),
            'category'              => array('value' => 'Categoria de Teste', 'field_database' => 'category_id'),
            'images'                => array('value' => $this->getImagesFormatted($image), 'field_database' => NULL),
            'variations'            => array('value' => $this->getVariationFormatted($variation, $payload), 'field_database' => 'has_variants'),
            'extra_operating_time'  => array('value' => 5, 'field_database' => 'prazo_operacional_extra')
        );

        return $productFormatted;
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param   array|string|int    $id     Código do sku do produto.
     * @param   float|null          $price  Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        $priceProd  = 0;
        $listPriceProd = 0;
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

            $price = empty($dataPrice->preco_promocional ?? null) ? $dataPrice->price : $dataPrice->preco_promocional;
            $listPrice = $dataPrice->price ;
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
            'price_product'     => $priceProd,
            'listPrice_product' => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'   => $priceSkus
        );
    }

    /**
     * Formata os dados da variação.
     *
     * @param   array   $payload    Dados da variação para formatação.
     * @param   mixed   $option     Dados opcionais para auxílio na formatação.
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        $arrVariation = array();

        foreach ($payload as $variation) {
            $arrVariation[] = array(
                'id'            => $variation['id'],
                'sku'           => $variation['sku'],
                'stock'         => $variation['stock'],
                'price'         => $variation['price'],
                'list_price'    => $variation['list_price'],
                'ean'           => $variation['ean'],
                'images'        => $this->getImagesFormatted($variation['images']),
                'variations'    => $variation['variations']
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
            $arrImages[] = $image;
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
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  bool                Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }
}