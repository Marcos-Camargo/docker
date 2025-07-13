<?php

namespace Integration\Integration_v2\internal_api;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Product_v2;
use stdClass;

class ToolsProduct extends Product_v2
{
    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (empty($this->credentials)) {
            $this->credentials = new stdClass();
        }
        if (empty($this->credentials->api_internal)) {
            $this->credentials->api_internal = [];
        }

        $this->credentials->api_internal['internal_image_download'] = true;
        $this->credentials->api_internal['import_internal_by_csv'] = true;
    }

    /**
     * Define os atributos para o produto
     *
     * @notice Programa interno, não faz nada.
     *
     * @param   string      $productId          Código do produto (products.id)
     * @param   string      $productIntegration Código do produto na integradora
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        return array();
    }

    /**
     * Recupera dados do produto na integradora
     *
     * @param   string  $id Código do sku do produto
     */
    public function getDataProductIntegration(string $id)
    {
        return null;
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
        return array(
            'name'                  => array('value' => $payload['name'] ?? null, 'field_database' => 'name'),
            'status'                => array('value' => $payload['status'] ?? null, 'field_database' => 'status'),
            'sku'                   => array('value' => $payload['sku'] ?? null, 'field_database' => 'sku'),
            'unity'                 => array('value' => $payload['unity'] ?? null, 'field_database' => 'attribute_value_id'),
            'price'                 => array('value' => $payload['price'] ?? null, 'field_database' => 'price'),
            'list_price'            => array('value' => $payload['list_price'] ?? null, 'field_database' => 'list_price'),
            'stock'                 => array('value' => $payload['qty'] ?? null, 'field_database' => 'qty'),
            'ncm'                   => array('value' => $payload['ncm'] ?? null, 'field_database' => 'NCM'), // não tenho certeza
            'origin'                => array('value' => $payload['origin'] ?? null, 'field_database' => 'origin'),
            'ean'                   => array('value' => $payload['ean'] ?? null, 'field_database' => 'EAN'),
            'net_weight'            => array('value' => $payload['net_weight'] ?? null, 'field_database' => 'peso_liquido'),
            'gross_weight'          => array('value' => $payload['gross_weight'] ?? null, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'      => array('value' => $payload['sku_manufacturer'] ?? null, 'field_database' => 'codigo_do_fabricante'),
            'description'           => array('value' => $payload['description'] ?? null, 'field_database' => 'description'),
            'guarantee'             => array('value' => $payload['guarantee'] ?? null, 'field_database' => 'garantia'),
            'brand'                 => array('value' => $payload['manufacturer'] ?? null, 'field_database' => 'brand_id'),
            'height'                => array('value' => $payload['height'] ?? null, 'field_database' => 'altura'),
            'depth'                 => array('value' => $payload['depth'] ?? null, 'field_database' => 'profundidade'),
            'width'                 => array('value' => $payload['width'] ?? null, 'field_database' => 'largura'),
            'items_per_package'     => array('value' => $payload['products_package'] ?? null, 'field_database' => 'products_package'),
            'category'              => array('value' => $payload['category'] ?? null, 'field_database' => 'category_id'),
            'images'                => array('value' => $payload['images'] ?? array(), 'field_database' => NULL),
            'variations'            => array('value' => $this->getVariationFormatted($payload['variations'] ?? array()), 'field_database' => 'has_variants'),
            'extra_operating_time'  => array('value' => $payload['extra_operating_time'] ?? null, 'field_database' => 'prazo_operacional_extra')
        );
    }

    /**
     * Recupera se o preço do produto
     *
     * @param   array|string|int    $id Código do sku do produto
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_product]  e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id): array
    {
        return array(
            'price_product'         => 0,
            'listPrice_product'     => 0,
            'listPrice_variation'   => array(),
            'price_variation'       => array()
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
            $arrVariation[] = array(
                'id'            => $variation['sku'] ?? null,
                'sku'           => $variation['sku'] ?? null,
                'stock'         => $variation['qty'] ?? null,
                'price'         => $variation['price'] ?? null,
                'list_price'    => $variation['list_price'] ?? null,
                'ean'           => $variation['ean'] ?? null,
                'status'        => $variation['status'] ?? null,
                'images'        => $variation['images'] ?? null,
                'variations'    => $variation['variations'] ?? null
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
        return array();
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                   Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): array
    {
        return array(
            'stock_product'     => 0,
            'stock_variation'   => array()
        );
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array               Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): ?array
    {
        return array(
            'stock_product'         => 0,
            'stock_variation'       => array(),
            'price_product'         => 0,
            'listPrice_product'     => 0,
            'listPrice_variation'   => array(),
            'price_variation'       => array()
        );
    }
}