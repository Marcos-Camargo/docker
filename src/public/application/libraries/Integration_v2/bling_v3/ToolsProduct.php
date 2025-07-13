<?php

namespace Integration\Integration_v2\bling_v3;

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
        $urlGetSku = "produtos/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($product->data)) {
            return null;
        }

        return $product->data;
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
            'name'                      => array('value' => $payload->nome, 'field_database' => 'name'),
            'status'                    => array('value' => $payload->situacao == 'A' ? 1 : 0, 'field_database' => 'status'),
            'sku'                       => array('value' => $payload->codigo, 'field_database' => 'sku'),
            'unity'                     => array('value' => $payload->unidade, 'field_database' => 'attribute_value_id'),
            'price'                     => array('value' => 0, 'field_database' => 'price'),
            'list_price'                => array('value' => 0, 'field_database' => 'list_price'),
            'stock'                     => array('value' => 0, 'field_database' => 'qty'),
            'ncm'                       => array('value' => $payload->tributacao->ncm, 'field_database' => 'NCM'), // não tenho certeza
            'origin'                    => array('value' => $payload->tributacao->origem, 'field_database' => 'origin'),
            'ean'                       => array('value' => $payload->gtin, 'field_database' => 'EAN'),
            'net_weight'                => array('value' => $payload->pesoLiquido, 'field_database' => 'peso_liquido'),
            'gross_weight'              => array('value' => $payload->pesoBruto, 'field_database' => 'peso_bruto'),
            'sku_manufacturer'          => array('value' => $payload->tributacao->codigoItem, 'field_database' => 'codigo_do_fabricante'),
            'description'               => array('value' => $payload->descricaoCurta, 'field_database' => 'description'),
            'guarantee'                 => array('value' => 0, 'field_database' => 'garantia'),
            'brand'                     => array('value' => $payload->marca, 'field_database' => 'brand_id'),
            'height'                    => array('value' => $payload->dimensoes->altura, 'field_database' => 'altura'),
            'depth'                     => array('value' => $payload->dimensoes->profundidade, 'field_database' => 'profundidade'),
            'width'                     => array('value' => $payload->dimensoes->largura, 'field_database' => 'largura'),
            'items_per_package'         => array('value' => $payload->itensPorCaixa, 'field_database' => 'products_package'),
            'category'                  => array('value' => $this->getCategoryName($payload->categoria->id ?? null), 'field_database' => 'category_id'),
            'images'                    => array('value' => $this->getImagesFormatted($payload->midia->imagens->externas ?: $payload->midia->imagens->internas), 'field_database' => NULL),
            'variations'                => array('value' => count($payload->variacoes) ? $this->getVariationFormatted($payload->variacoes, $payload) : array(), 'field_database' => 'has_variants'),
            'extra_operating_time'      => array('value' => $payload->estoque->crossdocking, 'field_database' => 'prazo_operacional_extra')
        );

        if (count($payload->variacoes) && empty($productFormatted['variations']['value'])) {
            $this->log_integration(
                "Alerta para integrar o produto com id ({$productFormatted['sku']['value']})",
                "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>Produto contém variação, mas não existe nenhuma variação na multiloja.</p><br><strong>Descrição</strong>: {$productFormatted['name']['value']}",
                "E");
            throw new InvalidArgumentException("Produto contém variação, mas não existe nenhuma variação na multi loja.");
        }

        if ($payload->dimensoes->unidadeMedida != 1) { // Centímetros
            if ($payload->dimensoes->unidadeMedida == 0) { // Metros. Multiplicar por 100 para obter o valor em centímetros
                if ($productFormatted['height']['value'] != 0) $productFormatted['height']['value'] *= 100;
                if ($productFormatted['depth']['value'] != 0) $productFormatted['depth']['value'] *= 100;
                if ($productFormatted['width']['value'] != 0) $productFormatted['width']['value'] *= 100;
            }
            if ($payload->dimensoes->unidadeMedida == 2) { // Milímetro. Dividir por 10 para obter o valor em centímetros
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
            $product_loja = null;
            if (!empty($this->credentials->loja_bling)) {
                $product_loja = $this->getProductIntegrationByLojaAndProduct($payload->id);
            }

            $price      = $payload->preco;
            $list_price = $payload->preco;

            if (!empty($product_loja)) {
                $price      = $product_loja->precoPromocional == 0 ? $product_loja->preco : $product_loja->precoPromocional;
                $list_price = $product_loja->preco;
            }

            $stock = $this->getStockErp($payload->id);
            $stock = $stock['stock_product'] ?? 0;
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
                $skuProduct[] = $prd->id;
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;

            $dataPrice = $this->getDataProductIntegration($sku);

            $price      = $dataPrice->preco;
            $list_price = $dataPrice->preco;

            if (!empty($product_loja)) {
                $price      = $product_loja->precoPromocional == 0 ? $product_loja->preco : $product_loja->precoPromocional;
                $list_price = $product_loja->preco;
            }

            $priceSkus[$dataPrice->codigo] = roundDecimal($price);
            $listPriceSkus[$dataPrice->codigo] = roundDecimal($list_price);

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = roundDecimal($price);
            }
            if ($list_price > $listPriceProd) {
                $listPriceProd = roundDecimal($list_price);
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

            foreach (explode(';', $variation->variacao->nome) as $typeVariation) {
                $expVariation = explode(':', $typeVariation);
                $arrTempVariation[trim($expVariation[0] ?? '')] = trim($expVariation[1] ?? '');
            }

            $clonePrdPai = $variation->variacao->produtoPai->cloneInfo ?? false;

            $product_loja = null;
            if (!empty($this->credentials->loja_bling)) {
                $product_loja = $this->getProductIntegrationByLojaAndProduct($variation->id);
                if (!$product_loja) {
                    continue;
                }
            }

            $price      = $variation->preco;
            $list_price = $variation->preco;

            if (!empty($product_loja)) {
                $price      = $product_loja->precoPromocional == 0 ? $product_loja->preco : $product_loja->precoPromocional;
                $list_price = $product_loja->preco;
            }

            $stock = $this->getStockErp($variation->id);
            $stock = $stock['stock_product'] ?? 0;

            $arrVariation[] = array(
                'id'            => $variation->id,
                'sku'           => $variation->codigo,
                'stock'         => $stock,
                'price'         => $price,
                'list_price'    => $list_price,
                'ean'           => $clonePrdPai ? $variation->gtin : $option->gtin,
                'images'        => $this->getImagesFormatted($variation->midia->imagens->externas ?: $variation->midia->imagens->internas),
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
            $arrImages[] = $image->link;
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
                $skuProduct[] = $prd->id;
            }
        }

        $stock_id_bling = $this->credentials->stock_id_bling;

        foreach ($skuProduct as $sku) {
            $stockSkus[$sku] = 0;

            $urlGetAtock = "estoques/saldos";
            $options = array(
                'query' => array(
                    'idsProdutos[]' => $sku
                )
            );

            try {
                $request = $this->request('GET', $urlGetAtock, $options);
            } catch (InvalidArgumentException $exception) {
                continue;
            }

            $product = Utils::jsonDecode($request->getBody()->getContents());

            if (empty($product->data)) {
                continue;
            }

            $depositos = $product->data[0]->depositos;

            $depositos_filter = array_filter($depositos, function($deposito) use ($stock_id_bling) {
                return $deposito->id == $stock_id_bling;
            });
            sort($depositos_filter);

            $stock = $product->data[0]->saldoFisicoTotal;
            if (!empty($depositos_filter)) {
                $stock = $depositos_filter[0]->saldoFisico;
            }

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
                $skuProduct[] = $prd->id;
            }
        }

        foreach ($skuProduct as $sku) {
            $priceSkus[$sku] = 0;
            $listPriceSkus[$sku] = 0;
            $stockSkus[$sku] = 0;

            $dataPriceStock = $this->getDataProductIntegration($sku);

            $product_loja = null;
            if (!empty($this->credentials->loja_bling)) {
                $product_loja = $this->getProductIntegrationByLojaAndProduct($dataPriceStock->id);
            }

            $price      = $dataPriceStock->preco;
            $list_price = $dataPriceStock->preco;

            if (!empty($product_loja)) {
                $price      = $product_loja->precoPromocional == 0 ? $product_loja->preco : $product_loja->precoPromocional;
                $list_price = $product_loja->preco;
            }

            $stock = $this->getStockErp($dataPriceStock->id);
            $stock = $stock['stock_product'] ?? 0;

            // recupera o maior preço de todos os itens
            if ($price > $priceProd) {
                $priceProd = roundDecimal($price);
            }
            if ($list_price > $listPriceProd) {
                $listPriceProd = roundDecimal($list_price);
            }

            $stockSkus[$dataPriceStock->codigo] = $stock;
            $stockProd += $stock;
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

    public function getProductIntegrationByLojaAndProduct(int $product_id)
    {
        $urlGetSku = "produtos/lojas";
        $queryGetSku = array(
            'query' => array(
                'idProduto' => $product_id,
                'idLoja' => $this->credentials->loja_bling
            )
        );

        try {
            $request = $this->request('GET', $urlGetSku, $queryGetSku);
        } catch (InvalidArgumentException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($product->data)) {
            return null;
        }

        return $product->data[0] ?? null;
    }

    /**
     * @param   int|null    $category_id    Código da categoria
     * @return  string
     */
    public function getCategoryName(int $category_id = null): string
    {
        if (empty($category_id)) {
            return '';
        }

        $urlGetSku = "categorias/produtos/$category_id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            return ''; // retorna null, pois, ocorreu um problema na consulta
        }
        $category = Utils::jsonDecode($request->getBody()->getContents());

        return $category->data->descricao ?? '';
    }

    /**
     * Recupera dados do produto na integradora pelo código SKU.
     *
     * @param   string $sku Código do produto
     */
    public function getDataProductIntegrationBySku(string $sku)
    {
        $urlGetSku = "produtos";
        $queryGetSku = array(
            'query' => array(
                'codigo' => $sku
            )
        );

        try {
            $request = $this->request('GET', $urlGetSku, $queryGetSku);
        } catch (InvalidArgumentException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($product->data)) {
            return null;
        }

        return $product->data[0] ?? null;
    }
}