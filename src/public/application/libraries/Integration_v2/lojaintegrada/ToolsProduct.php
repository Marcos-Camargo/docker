<?php

namespace Integration\Integration_v2\lojaintegrada;

require_once APPPATH . "libraries/Integration_v2/Product_v2.php";
require_once APPPATH . 'libraries/Validations/ProductIntegrationValidation.php';
require_once APPPATH . "libraries/Integration_v2/lojaintegrada/Validation/ProductIntegrationValidationLojaIntegrada.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\lojaintegrada
 * @property \ProductIntegrationValidationAnyMarket $productIntegrationValidation
 */
class ToolsProduct extends Product_v2
{

    const TYPE_NORMAL_PRODUCT = 'normal';
    const TYPE_VARIABLE_PRODUCT = 'atributo';
    const TYPE_VARIATION_PRODUCT = 'atributo_opcao';

    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $productImages;

    private $variationImgLink;

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidationLojaIntegrada(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
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
        return [];
    }

    public function getProductsPagination(array $filter, int $page = 1, int $size = 20)
    {
        $query['limit'] = $size;
        $query['offset'] = (--$page) * $size;
        if (isset($filter['updatedAt'])) {
            $query['data_modificacao__gte'] = date('Y-m-d H:i:s', strtotime($filter['updatedAt']));
        }
        if (isset($filter['createdAt'])) {
            $query['data_criacao__gte'] = date('Y-m-d H:i:s', strtotime($filter['createdAt']));
        }

        try {
            $request = $this->request('GET', 'produto', ['query' => $query]);
            try {
                $contentResponse = $request->getBody()->getContents();
                $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
                $response = Utils::jsonDecode($contentResponse);
            } catch (\Throwable $e) {
                throw new \Exception(sprintf("JSON DECODE ERROR: %s: %s", $request->getBody()->getContents(), print_r($request, true)));
            }
            return (object)[
                'meta' => (object)[
                    'totalCount' => $response->meta->total_count ?? $response->meta->totalCount ?? 0
                ],
                'products' => $response->objects ?? $response->response ?? [],
            ];
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            throw new \Exception($message);
        }
    }

    public function request(string $method, string $uri = '', array $options = [], $retry = 0): ResponseInterface
    {
        $request = parent::request($method, $uri, $options);
        if (in_array($method, ['GET']) && in_array($request->getStatusCode(), [200])) {
            $content = trim($request->getBody()->getContents());
            if ((empty($content) || empty(json_decode($content))) && $retry <= 5) {
                return $this->request($method, $uri, $options, ++$retry);
            }
            $request->getBody()->rewind();
        }
        return $request;
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param   string  $id Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        try {
            $request = $this->request('GET', "produto/{$id}", ['query' => [
                'descricao_completa' => 1
            ]]);
            return Utils::jsonDecode($request->getBody()->getContents());
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            if (method_exists($exception, 'getResponse')) {
                $message = $exception->getResponse()->getBody()->getContents();
            }
            if ((strpos($message, 'json_decode error') !== false) && ($request !== null)) {
                $message = "[{$id}] - {$message} ({$request->getStatusCode()}): {$request->getBody()->getContents()}";
            }
            throw new \Exception($message);
        }
        return (object)[];
    }

    protected function getIdByURI($uri = '')
    {
        $parts = explode('/', $uri ?? '');
        $lastPartIdx = count($parts) - 1;
        return (int)($parts[$lastPartIdx] ?? 0);
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
        $this->isPublishedProduct = false;
        $this->productPayload = $payload;
        $decodedDescription = html_entity_decode($this->productPayload->descricao_completa, ENT_NOQUOTES, 'UTF-8');
        $decodedDescription = trim(preg_replace('/[ \n\n\t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($decodedDescription))));
        $this->variationImgLink = null;
        $this->productImages = $this->productPayload->imagens ?? [];
        $this->productPayload->sku = (string)$this->productPayload->sku;
        $productIdErp =  $this->productPayload->id;
        $weight = $this->productPayload->peso ?? null;
        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->nome, 'field_database' => 'name'],
            'sku' => ['value' => $this->productPayload->sku, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => 0, 'field_database' => 'price'],
            'list_price' => ['value' => 0, 'field_database' => 'list_price'],
            'stock' => ['value' => 0, 'field_database' => 'qty'],
            'status' => ['value' => $this->productPayload->ativo ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT, 'field_database' => 'status'],
            'ean' => ['value' => $this->productPayload->gtin, 'field_database' => 'EAN'],
            'origin' => ['value' => 0, 'field_database' => 'origin'],
            'ncm' => ['value' => $this->productPayload->ncm, 'field_database' => 'NCM'],
            'net_weight' => ['value' => $weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $weight, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => $this->productPayload->altura ?? null, 'field_database' => 'altura'],
            'depth' => ['value' => $this->productPayload->profundidade ?? null, 'field_database' => 'profundidade'],
            'width' => ['value' => $this->productPayload->largura ?? null, 'field_database' => 'largura'],
            'product_height' => ['value' => $this->productPayload->altura ?? null, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $this->productPayload->profundidade ?? null, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $this->productPayload->largura ?? null, 'field_database' => 'actual_width'],
            'images' => ['value' => $this->getImagesFormatted($this->productPayload->imagens ?? []), 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => 0, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => '', 'field_database' => 'category_id'],
            'brand' => ['value' => '', 'field_database' => 'brand_id'],
            '_product_id_erp' => ['value' => $productIdErp, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => '']
        ];

        $categories = $this->productPayload->categorias ?? [['']];
        $category = $this->fetchProductCategory($this->getIdByURI(current($categories)));
        $this->parsedProduct['category']['value'] = $category['name'] ?? '';
        $brand = $this->fetchProductBrand($this->getIdByURI($this->productPayload->marca));
        $this->parsedProduct['brand']['value'] = $brand['name'] ?? '';

        $price = $this->fetchProductPrice($productIdErp);
        $this->parsedProduct['price'] = ['value' => $price['price'], 'field_database' => 'price'];
        $this->parsedProduct['list_price'] = ['value' => $price['listPrice'], 'field_database' => 'list_price'];

        $simpleProduct = strcasecmp($this->productPayload->tipo, self::TYPE_NORMAL_PRODUCT) === 0;
        if($simpleProduct) {
            $stock = $this->fetchProductStock($productIdErp);
            $this->parsedProduct['stock']['value'] = $stock['stock'] ?? $this->parsedProduct['stock']['value'];
            $this->parsedProduct['extra_operating_time']['value'] = $stock['crossdocking'] ?? $this->parsedProduct['extra_operating_time']['value'];
            $this->parsedProduct['status']['value'] = $stock['status'] ?? $this->parsedProduct['status']['value'];
        }
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $this->productPayload->sku,
                'store_id' => $this->store,
                'product_id_erp' => $this->productPayload->id
            ]
        );
        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $this->productPayload->id,
            $this->store
        );
        if (!empty($productIdErp)) {
            $this->productIntegrationValidation->setProduct($productIdErp);
        }
        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (isset($existingProduct['id'])) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->productIntegrationValidation->isPublishedProduct($existingProduct['id'])) {
                $this->isPublishedProduct = true;
                $this->parsedProduct['_published']['value'] = true;
            }
            if ($simpleProduct) {
                $this->parsedProduct['stock']['value'] = $this->parsedProduct['stock']['value'] ?? $existingProduct['qty'];
            }
            $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $existingProduct['price'];
            $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $existingProduct['list_price'];
        }

        $this->parsedProduct['variations']['value'] = $this->getVariationFormatted($this->productPayload->filhos ?? [ ]);

        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }
        return $this->parsedProduct;
    }

    public function fetchProductStock($productId)
    {
        try {
            $request = $this->request('GET', "produto_estoque/{$productId}");
            $stock = Utils::jsonDecode($request->getBody()->getContents());
            if (!$stock->gerenciado) return [];
            $qty = $stock->quantidade ?? null;
            $crossdocking = $qty > 0 ? $stock->situacao_em_estoque ?? 0 : $stock->situacao_sem_estoque ?? 0;
            $status = $crossdocking > -1 ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT;
            if ($qty < 0) $qty = 0;
            return [
                'stock' => $qty ?? null,
                'crossdocking' => $crossdocking > -1 ? $crossdocking : 0,
                'status' => $status
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function fetchProductPrice($productId)
    {
        try {
            $request = $this->request('GET', "produto_preco/{$productId}");
            $price = Utils::jsonDecode($request->getBody()->getContents());
            return [
                'listPrice' => (float)($price->cheio > 0 ? $price->cheio : $price->promocional),
                'price' => (float)($price->promocional > 0 ? $price->promocional : $price->cheio),
                'costPrice' => (float)$price->custo
            ];
        } catch (\Throwable $e) {
            return [
                'listPrice' => 0,
                'price' => 0,
                'costPrice' => 0
            ];
        }
    }

    public function fetchProductCategory($categoryId)
    {
        if (empty($categoryId)) return [];
        try {
            $request = $this->request('GET', "categoria/{$categoryId}");
            $category = Utils::jsonDecode($request->getBody()->getContents());
            return [
                'id' => $category->id ?? 0,
                'name' => $category->nome ?? '',
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function fetchProductBrand($brandId)
    {
        if (empty($brandId)) return [];
        try {
            $request = $this->request('GET', "marca/{$brandId}");
            $brand = Utils::jsonDecode($request->getBody()->getContents());
            return [
                'id' => $brand->id ?? 0,
                'name' => $brand->nome ?? '',
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function fetchProductImages($productId): array
    {
        $parsedImages = [];
        try {
            $request = $this->request('GET', "produto_imagem/?produto={$productId}");
            $images = Utils::jsonDecode($request->getBody()->getContents());
            $images = $images->objects ?? [];
            foreach ($images as $image) {
                array_push($parsedImages, [
                    'url' => "https://cdn.awsli.com.br/{$image->caminho}",
                    'main' => $image->principal,
                    'order' => $image->posicao
                ]);
            }
        } catch (\Throwable $e) {

        }
        return $parsedImages;
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param   array|string|int    $id     Código do sku do produto.
     * @param   float|null          $price  Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array                       Retorna array com preço (int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        return [];
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
        $this->parsedVariations = [];

        $variationGrid = [];
        $hasActiveVar = false;
        foreach ($payload as $variation) {
            try {
                $variationData = $this->getDataProductIntegration($this->getIdByURI($variation));
                if (!isset($variationData->id)) continue;
                $this->parsedProduct['net_weight']['value'] = $this->parsedProduct['net_weight']['value'] ?? $variationData->peso;
                $this->parsedProduct['gross_weight']['value'] = $this->parsedProduct['gross_weight']['value'] ?? $variationData->peso;
                $this->parsedProduct['height']['value'] = $this->parsedProduct['height']['value'] ?? $variationData->altura;
                $this->parsedProduct['depth']['value'] = $this->parsedProduct['depth']['value'] ?? $variationData->profundidade;
                $this->parsedProduct['width']['value'] = $this->parsedProduct['width']['value'] ?? $variationData->largura;
                $this->parsedProduct['product_height']['value'] = $this->parsedProduct['product_height']['value'] ?? $variationData->altura;
                $this->parsedProduct['product_depth']['value'] = $this->parsedProduct['product_depth']['value'] ?? $variationData->profundidade;
                $this->parsedProduct['product_width']['value'] = $this->parsedProduct['product_width']['value'] ?? $variationData->largura;

                $images = $variationData->imagens ?? $this->productImages ?? [];
                $images = !empty($images) ? $images : $this->productImages;

                $parsedImages = [];
                $variationsTypes = [];
                foreach ($variationData->variacoes ?? [] as $variation) {
                    $this->variationImgLink = $this->getIdByURI($variation) ?? null;
                    $variationValue = $this->requestByURI($variation);
                    if(empty((array)$variationValue)) continue;
                    $gridId = $this->getIdByURI($variationValue->grade);
                    if (!isset($variationGrid[$gridId])) {
                        $requestGrid = $this->requestByURI($variationValue->grade);
                        $gridName = !empty($requestGrid->nome_visivel) ? $requestGrid->nome_visivel : $requestGrid->nome ?? '';
                        if(empty($gridName)) continue;
                        $variationGrid[$gridId] = $gridName;
                    }
                    $type = $variationGrid[$gridId];
                    $value = !empty($variationValue->nome_visivel) ? $variationValue->nome_visivel : $variationValue->nome;
                    $variationsTypes[$type] = $value;
                    $parsedImages = array_merge($parsedImages, $this->getImagesFormatted($images));
                }

                $parsedVariation = [
                    'id' => 0,
                    'sku' => $variationData->sku,
                    'status' => ((int)$variationData->ativo == 1) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
                    'ean' => $variationData->gtin,
                    'images' => $parsedImages,
                    'variations' => $variationsTypes,
                    '_variant_id_erp' => $variationData->id,
                    '_published' => $this->isPublishedProduct
                ];

                $price = $this->fetchProductPrice($variationData->id);
                $parsedVariation['price'] =  $price['price'];
                $parsedVariation['list_price'] = $price['listPrice'];

                $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > $parsedVariation['price'] ? $this->parsedProduct['price']['value'] : $parsedVariation['price'];
                $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > $parsedVariation['list_price'] ? $this->parsedProduct['list_price']['value'] : $parsedVariation['list_price'];

                $stock = $this->fetchProductStock($variationData->id);
                $parsedVariation['stock'] = $stock['stock'] ?? null;

                if ($this->productIntegrationValidation->productExists()) {
                    $prd = $this->productIntegrationValidation->getProduct();
                    $variation = $this->model_products->getVariantByPrdIdAndSku($prd['id'], $parsedVariation['sku']);
                    if (empty($variation)) {
                        $variation = $this->model_products->getVariantByPrdIdAndIDErp($prd['id'], $parsedVariation['_variant_id_erp']);
                    }
                    $parsedVariation['id'] = $variation['id'] ?? 0;
                    $parsedVariation['stock'] = (int)$parsedVariation['stock'] ?? $variation['qty'];
                    $parsedVariation['price'] = $parsedVariation['price'] > 0 ? $parsedVariation['price'] : $variation['price'];
                    $parsedVariation['list_price'] = $parsedVariation['list_price'] > 0 ? $parsedVariation['list_price'] : $variation['list_price'] ?? $parsedVariation['price'];
                    if ($this->isPublishedProduct) {
                        $updatableFields = [
                            'id', 'sku', 'status', 'stock', 'price', 'list_price', '_variant_id_erp', '_published'
                        ];
                        $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
                    }
                }

                $hasActiveVar = $parsedVariation['status'] == \Model_products::ACTIVE_PRODUCT ? true : $hasActiveVar;
                if (empty($variationsTypes)) continue;
                array_push($this->parsedVariations, $parsedVariation);

            } catch (\Throwable $e) {

            }
        }

        $this->parsedProduct['status']['value'] = $hasActiveVar ? \Model_products::ACTIVE_PRODUCT : $this->parsedProduct['status']['value'];
        return $this->parsedVariations;
    }

    public function requestByURI(string $uri = '', string $method = 'GET', array $options = [])
    {
        $uri = str_replace('/api/v1/', '', $uri);
        try {
            $request = $this->request($method, $uri, $options);
            return Utils::jsonDecode($request->getBody()->getContents());
        } catch (\Throwable $e) {

        }
        return (object)[];
    }

    /**
     * @param   array   $payload    Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        foreach ($payload as $image) {
            $image->{'imagem_variacao'} = $image->imagem_variacao ?? null;
            if ($image->imagem_variacao !== $this->variationImgLink) continue;
            if (empty($image->caminho ?? '')) continue;
            $fullUrl = "https://cdn.awsli.com.br/{$image->caminho}";
            $images[] = $fullUrl;
        }
        return $images;
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                   Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): array
    {
        return [];
    }

    /**
     * Recupera o estoque de produto(s)
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  bool                    Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }

    public function getParsedVariations()
    {
        return $this->parsedVariations;
    }

    /**
     * Recupera dados do produto na integradora pelo cóidgo sku.
     *
     * @param   string  $sku Código SKU do produto.
     */
    public function getDataProductIntegrationBySku(string $sku)
    {
        try {
            $request = $this->request('GET', "produto", ['query' => [
                'sku' => $sku,
                'descricao_completa' => 1
            ]]);
            $content = Utils::jsonDecode($request->getBody()->getContents());
            if (!empty($content->objects) && count($content->objects) === 1) {
                return current($content->objects);
            }
        } catch (\Throwable $exception) {}

        return null;
    }

}
