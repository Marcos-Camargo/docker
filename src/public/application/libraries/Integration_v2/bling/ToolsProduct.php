<?php

namespace Integration\Integration_v2\bling;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use Integration\Integration_v2\Product_v2;

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
     * @todo Não encontrado doc onde mostrar atributos de produto
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
        $urlGetSku = "produto/$id";
        $queryGetSku = array(
            'query' => array(
                'imagem' => 'S',
                'estoque' => 'S'
            )
        );

        try {
            $request = $this->request('GET', $urlGetSku, $queryGetSku);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            //$message = $exception->getResponse()->getBody()->getContents() ?? $exception->getMessage();
            echo "[LINE: " . __LINE__ . "] ". $message."\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (!isset($product->retorno->produtos[0]->produto)) {
            return null;
        }

        return $product->retorno->produtos[0]->produto;
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
        $productFormatted = array(
            'name'                      => array('value' => $payload->descricao, 'field_database' => 'name'),
            'status'                    => array('value' => 1, 'field_database' => 'status'),
            'sku'                       => array('value' => $payload->codigo, 'field_database' => 'sku'),
            'unity'                     => array('value' => $payload->unidade, 'field_database' => 'attribute_value_id'),
            'price'                     => array('value' => 0, 'field_database' => 'price'),
            'list_price'                => array('value' => 0, 'field_database' => 'list_price'),
            'stock'                     => array('value' => 0, 'field_database' => 'qty'),
            'ncm'                       => array('value' => $payload->class_fiscal, 'field_database' => 'NCM'), // não tenho certeza
            'origin'                    => array('value' => $payload->origem, 'field_database' => 'origin'),
            'ean'                       => array('value' => $payload->gtin, 'field_database' => 'EAN'),
            'net_weight'                => array('value' => $payload->pesoLiq, 'field_database' => 'peso_liquido'),
            'gross_weight'              => array('value' => $payload->pesoBruto, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'          => array('value' => $payload->codigoFabricante, 'field_database' => 'codigo_do_fabricante'),
            'description'               => array('value' => $payload->descricaoCurta, 'field_database' => 'description'),
            'guarantee'                 => array('value' => $payload->garantia ?? 0, 'field_database' => 'garantia'),
            'brand'                     => array('value' => $payload->marca, 'field_database' => 'brand_id'),
            'height'                    => array('value' => $payload->alturaProduto, 'field_database' => 'altura'),
            'depth'                     => array('value' => $payload->profundidadeProduto, 'field_database' => 'profundidade'),
            'width'                     => array('value' => $payload->larguraProduto, 'field_database' => 'largura'),
            'items_per_package'         => array('value' => $payload->itensPorCaixa, 'field_database' => 'products_package'),
            'category'                  => array('value' => $payload->categoria->descricao ?? '', 'field_database' => 'category_id'),
            'images'                    => array('value' => $this->getImagesFormatted($payload->imagem), 'field_database' => NULL),
            'variations'                => array('value' => property_exists($payload, 'variacoes') ? $this->getVariationFormatted($payload->variacoes, $payload) : array(), 'field_database' => 'has_variants'),
            'extra_operating_time'      => array('value' => $payload->crossdocking, 'field_database' => 'prazo_operacional_extra')
        );

        if (property_exists($payload, 'variacoes') && empty($productFormatted['variations']['value'])) {
            $this->log_integration(
                "Alerta para integrar o produto com id ({$productFormatted['sku']['value']})",
                "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto contém variação, mas não existe nenhuma variação na multiloja.</p><br><strong>Descrição</strong>: {$productFormatted['name']['value']}",
                "E");
            throw new InvalidArgumentException("Produto contém variação, mas não existe nenhuma variação na multi loja.");
        }

        if ($payload->unidadeMedida != "Centímetros") {
            if ($payload->unidadeMedida == "Metros") { // multiplicar por 100 para obter o valor em centímetros
                if ($productFormatted['height']['value'] != 0) $productFormatted['height']['value'] *= 100;
                if ($productFormatted['depth']['value'] != 0) $productFormatted['depth']['value'] *= 100;
                if ($productFormatted['width']['value'] != 0) $productFormatted['width']['value'] *= 100;
            }
            if ($payload->unidadeMedida == "Milímetro") { // dividir por 10 para obter o valor em centímetros
                if ($productFormatted['height']['value'] != 0) $productFormatted['height']['value'] /= 10;
                if ($productFormatted['depth']['value'] != 0) $productFormatted['depth']['value'] /= 10;
                if ($productFormatted['width']['value'] != 0) $productFormatted['width']['value'] /= 10;
            }
        }

        $price = 0;
        $list_price = 0;
        $stock = 0;
        if (count($productFormatted['variations']['value'])) {
            foreach ($productFormatted['variations']['value'] as $variation) {
                if ($variation['price'] > $price) {
                    $price = $variation['price'];
                }
                if ($variation['list_price'] > $list_price) {
                    $list_price = $variation['list_price'];
                }
                $stock += $variation['stock'];
            }

        } else {
            $price      = $payload->preco;
            $list_price = $payload->preco;

            if (!empty($this->credentials->loja_bling)) {
                $parentPrice = $payload->produtoLoja->preco;
                $price = $parentPrice->precoPromocional == 0 ? $parentPrice->preco : $parentPrice->precoPromocional;
                $list_price = $parentPrice->preco;
            }
            $stock = $payload->estoqueAtual;
            if (isset($this->credentials->stock_bling) && !empty($this->credentials->stock_bling)) {
                foreach ($payload->depositos as $deposito) {
                    $deposito = (array) $deposito->deposito;
                    if ($this->credentials->stock_bling == $deposito['nome']) {
                        $stock = $deposito['saldo'];
                        break;
                    }
                }
            }
        }

        $productFormatted['price']['value']      = $price;
        $productFormatted['list_price']['value'] = $list_price;
        $productFormatted['stock']['value']      = $stock;

        return $productFormatted;
    }

    /**
     * Recupera se o preço do produto
     *
     * @param   array|string|int    $id Código do sku do produto
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_product]  e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id): array
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
                array_push($skuProduct, $prd->variacao->codigo);
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;

            $dataPrice = $this->getDataProductIntegration($sku);

            if ($dataPrice === null) {
                continue;
            }

            $price      = $dataPrice->preco;
            $listPrice  = $dataPrice->preco;

            if (!empty($this->credentials->loja_bling)) {
                $parentPrice = $dataPrice->produtoLoja->preco;
                $price = $parentPrice->precoPromocional == 0 ? $parentPrice->preco : $parentPrice->precoPromocional;
                $listPrice = $parentPrice->preco;
            }

            $priceSkus[$dataPrice->codigo] = roundDecimal($price);
            $listPriceSkus[$dataPrice->codigo] = roundDecimal($listPrice);

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
     * @param   array   $payload    Dados da variação para formatação
     * @param   mixed   $option     Dados opcionais para auxílio na formatação
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        $arrVariation = array();

        foreach ($payload as $variation) {
            $variation = $variation->variacao;
            $arrTempVariation = array();

            foreach (explode(';', $variation->nome) as $typeVariation) {
                $expVariation = explode(':', $typeVariation);
                $arrTempVariation[trim($expVariation[0] ?? '')] = trim($expVariation[1] ?? '');
            }

            $dataVariation = $this->getDataProductIntegration($variation->codigo);

            if ($dataVariation === null) {
                continue;
            }

            $clonePrdPai = isset($dataVariation->clonarDadosPai) ? $dataVariation->clonarDadosPai === 'S' : false;

            if (!empty($this->credentials->loja_bling) && !property_exists($dataVariation, 'produtoLoja')) {
                continue;
            }

            $price = $dataVariation->preco;
            $list_price = $dataVariation->preco;

            if (!empty($this->credentials->loja_bling)) {
                $parentPrice = $dataVariation->produtoLoja->preco;
                $price = $parentPrice->precoPromocional == 0 ? $parentPrice->preco : $parentPrice->precoPromocional;
                $list_price = $parentPrice->preco;
            }

            $stock = isset($dataVariation->estoqueAtual) ? $dataVariation->estoqueAtual : 0;
            if (isset($this->credentials->stock_bling) && !empty($this->credentials->stock_bling)) {
                foreach ($dataVariation->depositos as $deposito) {
                    $deposito = (array) $deposito->deposito;
                    if ($this->credentials->stock_bling == $deposito['nome']) {
                        $stock = $deposito['saldo'];
                        break;
                    }
                }
            }

            $arrVariation[] = array(
                'id'            => $variation->codigo,
                'sku'           => $variation->codigo,
                'stock'         => $stock,
                'price'         => $price,
                'list_price'    => $list_price,
                'ean'           => $clonePrdPai ? $dataVariation->gtin : $option->gtin,
                'images'        => $this->getImagesFormatted($dataVariation->imagem ?? $option->imagem),
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
            array_push($arrImages, $image->link);
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
                $skuProduct[] = $prd->variacao->codigo;
            }
        }

        foreach ($skuProduct as $sku) {
            $stockSkus[$sku] = 0;

            $dataStock = $this->getDataProductIntegration($sku);

            if ($dataStock === null) {
                continue;
            }

            if (!empty($this->credentials->loja_bling) && !property_exists($dataStock, 'produtoLoja')) {
                continue;
            }

            $stockTemp = $dataStock->estoqueAtual;
            if (isset($this->credentials->stock_bling) && !empty($this->credentials->stock_bling)) {
                $stockTemp = 0;
                foreach ($dataStock->depositos as $deposito) {
                    $deposito = (array) $deposito->deposito;
                    if ($this->credentials->stock_bling == $deposito['nome']) {
                        $stockTemp = $deposito['saldo'];
                        break;
                    }
                }
            }
            $stockSkus[$dataStock->codigo] = $stockTemp;
            $stockProd += $stockTemp;
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
     * @return  array               Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): ?array
    {
        $stockProd  = 0;
        $priceProd  = 0;
        $listPriceProd = 0;
        $skuProduct = array();
        $stockSkus  = array();
        $priceSkus  = array();
        $listPriceSkus  = array();

        if (!is_array($id)) {
            $skuProduct = [$id];
        }
        else {
            foreach ($id as $prd) {
                $skuProduct[] = $prd->variacao->codigo;
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;
            $stockSkus[$sku] = 0;

            $dataPriceStock = $this->getDataProductIntegration($sku);

            if ($dataPriceStock === null) {
                continue;
            }

            if (!empty($this->credentials->loja_bling) && !property_exists($dataPriceStock, 'produtoLoja')) {
                continue;
            }

            $price = $dataPriceStock->preco;
            $listPrice = $dataPriceStock->preco;

            if (!empty($this->credentials->loja_bling)) {
                $parentPrice = $dataPriceStock->produtoLoja->preco;
                $price = $parentPrice->precoPromocional == 0 ? $parentPrice->preco : $parentPrice->precoPromocional;
                $listPrice = $parentPrice->preco;
            }

            $priceSkus[$dataPriceStock->codigo] = roundDecimal($price);
            $listPriceSkus[$dataPriceStock->codigo] = roundDecimal($listPrice);

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = roundDecimal($price);
            }
            if ($listPrice > $listPriceProd) {
                $listPriceProd = roundDecimal($listPrice);
            }

            $stockTemp = $dataPriceStock->estoqueAtual;
            if (isset($this->credentials->stock_bling) && !empty($this->credentials->stock_bling)) {
                $stockTemp = 0;
                foreach ($dataPriceStock->depositos as $deposito) {
                    $deposito = (array) $deposito->deposito;
                    if ($this->credentials->stock_bling == $deposito['nome']) {
                        $stockTemp = $deposito['saldo'];
                        break;
                    }
                }
            }
            $stockSkus[$dataPriceStock->codigo] = $stockTemp;
            $stockProd += $stockTemp;
        }

        if (empty($stockSkus) || empty($priceSkus)) {
            return null;
        }

        return array(
            'stock_product'     => $stockProd,
            'stock_variation'   => $stockSkus,
            'price_product'     => $priceProd,
            'listPrice_product' => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'   => $priceSkus
        );
    }
}