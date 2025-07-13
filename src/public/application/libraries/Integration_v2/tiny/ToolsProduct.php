<?php

namespace Integration\Integration_v2\tiny;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

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
        return array();
    }

    /**
     * Recupera dados do produto na integradora
     *
     * @param   string $id Código do produto
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "produto.obter.php";
        $queryGetSku = array(
            'query' => array(
                'id' => $id
            )
        );

        try {
            $request = $this->request('GET', $urlGetSku, $queryGetSku);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            echo "[LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
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
        $webhook = $option === 'webhook';
        
        $precoPromocional = property_exists($payload, 'precoPromocional') ? (float)$payload->precoPromocional : (float)$payload->preco_promocional;
        $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
        $price            = empty($precoPromocional ?? null) ? (float)$payload->preco : $precoPromocional;
        $list_price       = (float)($payload->preco > 0 ? $payload->preco : $precoPromocional);

        if ($webhook) {
            $stock = $payload->estoqueAtual;
        } else {
            $dataPrice = $this->getPriceErp($payload->id, $price, $list_price);
            $price = $dataPrice['price_product'] ?? $price;
            $list_price = $dataPrice['listPrice_product'] ?? $list_price;
            $stock = 0;
        }

        $unity = empty($payload->unidade) ? 'UN' : $payload->unidade;
        $guaranteeValid = $payload->garantia ?? 0;
        $guarantee      = onlyNumbers($guaranteeValid);

        if (likeText("%ano%", strtolower($guaranteeValid))) {
            $guarantee *= 12;
        }
        if (likeText("%dia%", strtolower($guaranteeValid))) {
            $guarantee /= 30;
            $guarantee = (int)$guarantee;
        }

        $productFormatted = array(
            'name'                  => array('value' => $payload->nome, 'field_database' => 'name'),
            'status'                => array('value' => 1, 'field_database' => 'status'),
            'sku'                   => array('value' => $payload->codigo, 'field_database' => 'sku'),
            'unity'                 => array('value' => $unity, 'field_database' => 'attribute_value_id'),
            'price'                 => array('value' => $price, 'field_database' => 'price'),
            'list_price'            => array('value' => $list_price, 'field_database' => 'list_price'), 
            'stock'                 => array('value' => $stock, 'field_database' => 'qty'),
            'ncm'                   => array('value' => $payload->ncm, 'field_database' => 'NCM'), // não tenho certeza
            'origin'                => array('value' => $payload->origem, 'field_database' => 'origin'),
            'ean'                   => array('value' => $payload->gtin, 'field_database' => 'EAN'),
            'net_weight'            => array('value' => $webhook ? $payload->pesoLiquido : $payload->peso_liquido, 'field_database' => 'peso_liquido'),
            'gross_weight'          => array('value' => $webhook ? $payload->pesoBruto : $payload->peso_bruto, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'      => array('value' => $webhook ? $payload->codigoPeloFornecedor : $payload->codigo_pelo_fornecedor, 'field_database' => 'codigo_do_fabricante'),
            'description'           => array('value' => $webhook ? $payload->descricaoComplementar : $payload->descricao_complementar, 'field_database' => 'description'),
            'guarantee'             => array('value' => $guarantee, 'field_database' => 'garantia'),
            'brand'                 => array('value' => $payload->marca, 'field_database' => 'brand_id'),
            'height'                => array('value' => $payload->alturaEmbalagem, 'field_database' => 'altura'),
            'depth'                 => array('value' => $payload->comprimentoEmbalagem, 'field_database' => 'profundidade'),
            'width'                 => array('value' => $payload->larguraEmbalagem, 'field_database' => 'largura'),
            'items_per_package'     => array('value' => $webhook ? $payload->unidadePorCaixa : $payload->unidade_por_caixa, 'field_database' => 'products_package'),
            'category'              => array('value' => $webhook ? $payload->descricaoArvoreCategoria : $payload->categoria, 'field_database' => 'category_id'),
            'images'                => array('value' => $this->getImagesFormatted(array('anexos' => $payload->anexos ?? array(), 'imagens_externas' => $payload->imagens_externas ?? array())), 'field_database' => NULL),
            'variations'            => array('value' => property_exists($payload, 'variacoes') && is_array($payload->variacoes) ? $this->getVariationFormatted($payload->variacoes, $payload) : array(), 'field_database' => 'has_variants'),
            'extra_operating_time'  => array('value' => $payload->dias_preparacao ?? 1, 'field_database' => 'prazo_operacional_extra')
        );

        if (!$webhook) {
            if (!count($productFormatted['variations']['value'])) {
                $stock = $this->getStockErp($payload->id);
                $stock = $stock['stock_product'];
            } else {
                $stock = array_sum(
                        array_map(function ($variation) {
                        return $variation['stock'];
                    }, $productFormatted['variations']['value'])
                );
            }

            // Define o estoque do produto pai/simples
            $productFormatted['stock']['value'] = $stock;
        }

        return $productFormatted;
    }

    /**
     * Recupera se o preço do produto
     *
     * @param   array|string|int    $id     Código do sku do produto
     * @param   float|null          $price  Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null, float $listPrice = null): array
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
                array_push($skuProduct, $prd->variacao->codigo);
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;

            // preciso ver apenas a lista de preço, caso a loja tenha
            if ($this->credentials->id_lista_tiny) {
                $urlGetProduct = 'listas.precos.excecoes.php';
                $queryGetProduct = array(
                    'query' => array(
                        'idProduto'     => $sku,
                        'idListaPreco'  => $this->credentials->id_lista_tiny
                    )
                );

                // consulta a lista de produtos
                try {
                    $request          = $this->request('GET', $urlGetProduct, $queryGetProduct);
                    $dataPrice        = Utils::jsonDecode($request->getBody()->getContents());
                    $dataPrice        = $dataPrice->retorno->registros[0]->registro;
                    $precoPromocional = property_exists($dataPrice, 'precoPromocional') ? (float)$dataPrice->precoPromocional : 
                        (property_exists($dataPrice, 'preco_promocional') ? (float)$dataPrice->preco_promocional : 0);
                    $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
                    $price            = empty($precoPromocional ?? null) ? ((float)$dataPrice->preco ?? 0) : $precoPromocional;
                    $listPrice        = (float)($dataPrice->preco > 0 ? $dataPrice->preco : $precoPromocional);
                } catch (InvalidArgumentException | GuzzleException $exception) {
                    // Não está na lista de preço, recuperar o preço atual
                    if ($price === null) {
                        $product          = $this->getDataProductIntegration($sku);
                        $product          = $product->retorno->produto;
                        $precoPromocional = property_exists($product, 'precoPromocional') ? (float)$product->precoPromocional : (float)$product->preco_promocional;
                        $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
                        $price            = empty($precoPromocional ?? null) ? ((float)$product->preco ?? 0) : $precoPromocional;
                        $listPrice        = (float)($product->preco > 0 ? $product->preco : $precoPromocional);
                    }
                }
            } elseif ($price === null) {
                $product          = $this->getDataProductIntegration($sku);
                $product          = $product->retorno->produto;
                $precoPromocional = property_exists($product, 'precoPromocional') ? (float)$product->precoPromocional : (float)$product->preco_promocional;
                $precoPromocional = $precoPromocional > 0 ? (float)$precoPromocional : null;
                $price            = empty($precoPromocional ?? null) ? ((float)$product->preco ?? 0) : $precoPromocional;
                $listPrice        = (float)($product->preco > 0 ? $product->preco : $precoPromocional);
            }

            $priceSkus[$sku] = roundDecimal($price);
            $listPriceSkus[$sku] = roundDecimal($listPrice);

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
            $webhook    = !property_exists($variation, 'variacao');
            $variation  = $variation->variacao ?? $variation;

            if ($webhook) {
                $stock          = $variation->estoqueAtual;
                $price          = empty($variation->precoPromocional ?? null) ? $variation->preco : $variation->precoPromocional;
                $listPrice      = $variation->preco;
                $idVariation    = $variation->id;
                $skuVariation   = $variation->codigo;
                $eanVariation   = $variation->gtin;
                $imagesAnexos   = $variation->anexos;
                $imagesExternal = array();
                $typeVariations = array();

                foreach ($variation->grade as $grade) {
                    $typeVariations[$grade->chave] = $grade->valor;
                }
            } else {
                $dataVariation  = $this->getDataProductIntegration($variation->id);

                if (!$dataVariation) {
                    throw new InvalidArgumentException("Variação com o código $variation->id não encontrada.");
                }

                $dataVariation  = $dataVariation->retorno->produto;

                $dataStock      = $this->getStockErp($dataVariation->id);
                $stock          = $dataStock['stock_variation'][$dataVariation->id];
                $price          = empty($dataVariation->preco_promocional ?? null) ? $dataVariation->preco : $dataVariation->preco_promocional;
                $listPrice      = $dataVariation->preco;
                $dataPrice      = $this->getPriceErp($variation->id, $price, $listPrice);
                $price          = $dataPrice['price_variation'][$dataVariation->id] ?? $price;
                $listPrice      = $dataPrice['listPrice_variation'][$dataVariation->id] ?? $listPrice;
                $idVariation    = $dataVariation->id;
                $skuVariation   = $dataVariation->codigo;
                $eanVariation   = $dataVariation->gtin;
                $imagesAnexos   = $dataVariation->anexos;
                $imagesExternal = $dataVariation->imagens_externas;
                $typeVariations = $dataVariation->grade;
            }

            array_push(
                $arrVariation,
                array(
                    'id'            => $idVariation,
                    'sku'           => $skuVariation,
                    'stock'         => $stock,
                    'price'         => $price,
                    'list_price'    => $listPrice,
                    'ean'           => $eanVariation,
                    'images'        => $this->getImagesFormatted(array('anexos' => $imagesAnexos, 'imagens_externas' => $imagesExternal)),
                    'variations'    => $typeVariations
                )
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

        foreach ($payload as $type) {
            foreach ($type as $image) {
                array_push($arrImages, $image->anexo ?? $image->imagem_externa->url ?? $image->url);
            }
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
                array_push($skuProduct, $prd->variacao->id);
            }
        }

        foreach ($skuProduct as $sku) {
            $stockSkus[$sku] = 0;

            $urlGetProduct = 'produto.obter.estoque.php';
            $queryGetProduct = array(
                'query' => array(
                    'id' => $sku
                )
            );

            // consulta a lista de produtos
            try {
                $request = $this->request('GET', $urlGetProduct, $queryGetProduct);
            } catch (GuzzleException | InvalidArgumentException $exception) {
                continue;
            }

            $dataStock = Utils::jsonDecode($request->getBody()->getContents());
            $dataStock = $dataStock->retorno->produto;

            if (!property_exists($dataStock, 'saldo')) {
                continue;
            }

            $stock = $dataStock->saldo ?? 0;
            $stockReserved = $dataStock->saldoReservado ?? 0;
            
            $stock -= $stockReserved;
            
            if (isset($this->credentials->stock_tiny) && !empty($this->credentials->stock_tiny)) {
                $stock = 0;
                foreach ($dataStock->depositos as $deposit) {
                    if (strtolower($deposit->deposito->nome) === strtolower($this->credentials->stock_tiny)) {
                        $stock = $deposit->deposito->saldo - $stockReserved;
                        break;
                    }
                }
            }

            $stockSkus[$dataStock->id] = $stock;
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
     * @return  bool                Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_product_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }

    /**
     * Recupera dados do produto na integradora pelo código SKU.
     *
     * @param   string $sku Código do produto
     */
    public function getDataProductIntegrationBySku(string $sku)
    {
        $urlGetSku = "produtos.pesquisa.php";
        $queryGetSku = array(
            'query' => array(
                'pesquisa' => $sku
            )
        );

        try {
            $request = $this->request('GET', $urlGetSku, $queryGetSku);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            echo "[LINE: " . __LINE__ . "] {$exception->getMessage()}\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $content = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($content->retorno->produtos)) {
            return null;
        }

        foreach ($content->retorno->produtos as $product) {
            if ($product->produto->codigo == $sku) {
                return $product->produto;
            }
        }

        return null;
    }
}