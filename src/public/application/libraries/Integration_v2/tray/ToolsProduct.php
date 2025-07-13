<?php

namespace Integration\Integration_v2\tray;

require APPPATH . "libraries/Integration_v2/Product_v2.php";
require_once APPPATH . 'libraries/Validations/ProductIntegrationValidation.php';
require_once APPPATH . "libraries/Integration_v2/tray/Validation/ProductIntegrationValidationTray.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\tray
 * @property \ProductIntegrationValidation $productIntegrationValidation
 */
class ToolsProduct extends Product_v2
{

    private $integrationSkuField = 'id'; // reference

    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $productImages;

    private $variationImgLink;

    private $productsIntegrationList;

    private $variationsIntegrationList;

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidationTray(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
    }

    public function getIntegrationSkuField(): string
    {
        return $this->integrationSkuField;
    }

    /**
     * Define os atributos para o produto.
     *
     * @param string $productId Código do produto (products.id).
     * @param string $productIntegration Código do produto na integradora.
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"].
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        return array(
            'Cor' => 'Preto',
            'Tamanho' => 'M'
        );
    }

    public function getProductsPagination(array $filter, int $page = 1, int $size = 50)
    {
        $query = $filter;
        $query['page'] = $page;
        $query['limit'] = $size;
        if (isset($filter['modified'])) {
            $query['modified'] = date('Y-m-d H:i:s', strtotime($filter['modified']));
        }
        if (isset($filter['created'])) {
            $query['created'] = date('Y-m-d H:i:s', strtotime($filter['created']));
        }

        try {
            $request = $this->request('GET', 'products', ['query' => $query]);
            try {
                $contentResponse = $request->getBody()->getContents();
                $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
                $response = Utils::jsonDecode($contentResponse);
            } catch (\Throwable $e) {
                throw new \Exception(sprintf("JSON DECODE ERROR: %s: %s", $request->getBody()->getContents(), print_r($request, true)));
            }
            return $response;
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            throw new \Exception($message);
        }
    }

    public function getDataProductIntegration(string $id)
    {
        if (!isset($this->productsIntegrationList[$id])) {
            $this->productsIntegrationList[$id] = $this->getDataIntegrationByEndpoint("products/{$id}")->Product ?? (object)[];
        }
        return $this->productsIntegrationList[$id];
    }

    public function getDataVariationIntegration($variationId)
    {
        if (!isset($this->variationsIntegrationList[$variationId])) {
            $this->variationsIntegrationList[$variationId] = $this->getDataIntegrationByEndpoint("products/variants/{$variationId}")->Variant ?? (object)[];
        }
        return $this->variationsIntegrationList[$variationId];
    }

    public function getDataIntegrationByEndpoint(string $endpoint)
    {
        try {
            $request = $this->request('GET', $endpoint);
        } catch (InvalidArgumentException | GuzzleException $exception) {
            $message = $exception->getMessage();
            if ($exception instanceof GuzzleException) {
                $message = $exception->getResponse()->getBody()->getContents();
            }
            throw new \Exception($message);
        }
        return Utils::jsonDecode($request->getBody()->getContents());
    }

    public function fetchProductPriceStockProductVariation($productId, $variation = false)
    {
        try {
            try {
                $product = $variation ? $this->getDataVariationIntegration($productId) : $this->getDataProductIntegration($productId);
            } catch (\Exception $exception) {
                if ($this->store_uses_catalog) {
                    $variation = $variation ? false : $productId;
                    return $this->fetchProductPriceStockProductVariation($productId, $variation);
                }
            }
            if (!isset($product->id)) {
                throw new \Exception(sprintf("Falha ao obter %s #%s", ($variation ? 'a variação' : 'o produto'), $productId));
            }
            $parentSku = $product->{$this->integrationSkuField};
            if ($variation) {
                $parent = $this->getDataProductIntegration($product->product_id);
                $parentSku = $parent->{$this->integrationSkuField} ?? $parentSku;
            }
            return [[
                'sku' => $parentSku,
                'varSku' => $variation ? $product->{$this->integrationSkuField} : null,
                'stock' => $product->stock ?? null,
                'crossdocking' => $product->availability_days ?? 0
            ], [
                'sku' => $parentSku,
                'varSku' => $variation ? $product->{$this->integrationSkuField} : null,
                'listPrice' => (float)($product->price > 0 ? $product->price : $product->promotional_price),
                'price' => (float)($product->promotional_price > 0 ? $product->promotional_price : $product->price),
                'costPrice' => (float)$product->cost_price
            ]];
        } catch (\Throwable $e) {
            return [[], [
                'sku' => null,
                'varSku' => null,
                'listPrice' => 0,
                'price' => 0,
                'costPrice' => 0
            ]];
        }
    }

    /**
     * Formata os dados do produto para criar ou atualizar.
     *
     * @param array|object $payload Dados do produto para formatação.
     * @param mixed $option Dados opcionais para auxílio na formatação.
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->productPayload = $payload;
        $decodedDescription = html_entity_decode($this->productPayload->description, ENT_NOQUOTES, 'UTF-8');
        $decodedDescription = preg_replace('/[ \n\n\t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($decodedDescription)));
        $this->variationImgLink = null;
        $this->productImages = $this->productPayload->ProductImage ?? [];
        $this->productPayload->{$this->integrationSkuField} = (string)$this->productPayload->{$this->integrationSkuField};
        $productIdErp = $this->productPayload->id;
        $weight = $this->productPayload->weight ?? 0;
        $price = $this->productPayload->current_price > 0 ? $this->productPayload->current_price : $this->productPayload->price;
        $listPrice = $this->productPayload->price > 0 ? $this->productPayload->price : $price;
        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->name, 'field_database' => 'name'],
            'sku' => ['value' => $this->productPayload->{$this->integrationSkuField}, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => $price, 'field_database' => 'price'],
            'list_price' => ['value' => $listPrice, 'field_database' => 'list_price'],
            'stock' => ['value' => $this->productPayload->stock, 'field_database' => 'qty'],
            'status' => ['value' => ((int)$this->productPayload->available) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT, 'field_database' => 'status'],
            'ean' => ['value' => $this->productPayload->ean, 'field_database' => 'EAN'],
            'origin' => ['value' => 0, 'field_database' => 'origin'],
            'ncm' => ['value' => $this->productPayload->ncm, 'field_database' => 'NCM'],
            'net_weight' => ['value' => $weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $weight, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => $this->productPayload->height ?? null, 'field_database' => 'altura'],
            'depth' => ['value' => $this->productPayload->length ?? null, 'field_database' => 'profundidade'],
            'width' => ['value' => $this->productPayload->width ?? null, 'field_database' => 'largura'],
            'product_height' => ['value' => $this->productPayload->height ?? null, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $this->productPayload->length ?? null, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $this->productPayload->width ?? null, 'field_database' => 'actual_width'],
            'images' => ['value' => $this->getImagesFormatted($this->productPayload->ProductImage ?? []), 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => $this->productPayload->availability_days, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $this->productPayload->category_name ?? '', 'field_database' => 'category_id'],
            'brand' => ['value' => $this->productPayload->brand ?? '', 'field_database' => 'brand_id'],
            'guarantee' => ['value' => $this->productPayload->warranty ?? 0, 'field_database' => 'garantia'],
            '_product_id_erp' => ['value' => $productIdErp, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => '']
        ];

        $this->productValidationHandler($this->productPayload->id);

        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->isPublishedProduct) {
                $this->parsedProduct['_published']['value'] = true;
            }
            $isSimpleProduct = ((int)$this->productPayload->has_variation) !== 1;
            if ($isSimpleProduct) {
                $this->parsedProduct['stock']['value'] = $this->parsedProduct['stock']['value'] ?? $existingProduct['qty'];
            }
            $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $existingProduct['price'];
            $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $existingProduct['list_price'];
        }

        $this->parsedProduct['variations']['value'] = $this->getVariationFormatted($this->productPayload->Variant ?? []);

        if(!empty($this->parsedProduct['variations']['value']) && empty($existingProduct['id'])){
            $this->parsedProduct['sku']['value'] = "P_".$this->parsedProduct['sku']['value'];
        }

        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }
        $this->handleWithParsedProductWeight('grams');
        return $this->parsedProduct;
    }

    protected function handleWithParsedProductWeight($unit = 'grams')
    {
        foreach (['net_weight', 'gross_weight'] as $dType) {
            if (!isset($this->parsedProduct[$dType]['value'])) continue;
            $conValue = $this->parsedProduct[$dType]['value'];
            if (!is_numeric($conValue)) continue;
            if (strcasecmp($unit, 'grams') === 0) $conValue = (double)($conValue / 1000);
            $this->parsedProduct[$dType]['value'] = $conValue;
        }
    }

    public function productValidationHandler($productId)
    {
        $productData = $this->getDataProductIntegration($productId);
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $productData->{$this->integrationSkuField},
                'store_id' => $this->store,
                'product_id_erp' => $productData->id
            ]
        );
        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $productData->id,
            $this->store
        );
        if (!empty($productIdErp)) {
            $this->productIntegrationValidation->setProduct($productIdErp);
        }
        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->isPublishedProduct = false;
            if ($this->productIntegrationValidation->isPublishedProduct($existingProduct['id'])) {
                $this->isPublishedProduct = true;
            }
        }
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param array|string|int $id Código do sku do produto.
     * @param float|null $price Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array                       Retorna array com preço (int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        return [];
    }

    /**
     * Formata os dados da variação.
     *
     * @param array $payload Dados da variação para formatação.
     * @param mixed $option Dados opcionais para auxílio na formatação.
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        $this->parsedVariations = [];

        $hasActiveVar = false;
        foreach ($payload as $variation) {
            try {
                $variationData = $this->getDataVariationIntegration($variation->id);
                if (!isset($variationData->id)) continue;
                $this->parsedProduct['net_weight']['value'] = $this->parsedProduct['net_weight']['value'] ?? $variationData->weight;
                $this->parsedProduct['gross_weight']['value'] = $this->parsedProduct['gross_weight']['value'] ?? $variationData->weight;
                $this->parsedProduct['height']['value'] = $this->parsedProduct['height']['value'] ?? $variationData->height;
                $this->parsedProduct['depth']['value'] = $this->parsedProduct['depth']['value'] ?? $variationData->length;
                $this->parsedProduct['width']['value'] = $this->parsedProduct['width']['value'] ?? $variationData->width;
                $this->parsedProduct['product_height']['value'] = $this->parsedProduct['product_height']['value'] ?? $variationData->height;
                $this->parsedProduct['product_depth']['value'] = $this->parsedProduct['product_depth']['value'] ?? $variationData->length;
                $this->parsedProduct['product_width']['value'] = $this->parsedProduct['product_width']['value'] ?? $variationData->width;

                $parsedVariation = $this->getVariationParsed($variationData);
                if (empty($parsedVariation['variations'] ?? [])) continue;

                $this->parsedProduct['price']['value'] = empty($this->parsedProduct['price']['value']) || $this->parsedProduct['price']['value'] > ($parsedVariation['price'] ?? 0) ? $this->parsedProduct['price']['value'] : $parsedVariation['price'] ?? 0;
                $this->parsedProduct['list_price']['value'] = empty($this->parsedProduct['list_price']['value']) || $this->parsedProduct['list_price']['value'] > ($parsedVariation['list_price'] ?? 0) ? $this->parsedProduct['list_price']['value'] : $parsedVariation['list_price'] ?? 0;

                $hasActiveVar = $parsedVariation['status'] == \Model_products::ACTIVE_PRODUCT ? true : $hasActiveVar;
                array_push($this->parsedVariations, $parsedVariation);

            } catch (\Throwable $e) {

            }
        }

        $this->parsedProduct['status']['value'] = $hasActiveVar ? \Model_products::ACTIVE_PRODUCT : $this->parsedProduct['status']['value'];
        return $this->parsedVariations;
    }

    public function getVariationParsed(object $variationData): array
    {

        $parentSku = $this->getDataProductIntegration($variationData->product_id)->{$this->integrationSkuField} ?? '';

        if (empty(trim($variationData->{$this->integrationSkuField})) || strcasecmp($variationData->{$this->integrationSkuField}, $parentSku) === 0) {
            return [];
        }

        $variationsTypes = [];
        foreach ($variationData->Sku ?? [] as $variation) {
            $variationsTypes[$variation->type ?? null] = $variation->value;
        }

        $price = $variationData->promotional_price > 0 ? $variationData->promotional_price : $variationData->price;
        $listPrice = $variationData->price > 0 ? $variationData->price : $price;

        $parsedVariation = [
            'id' => 0,
            'sku' => $variationData->{$this->integrationSkuField},
            'status' => ((int)$variationData->available == 1) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
            'ean' => $variationData->ean,
            'images' => $this->getImagesFormatted($variationData->VariantImage ?? []),
            'variations' => $variationsTypes,
            'price' => $price,
            'list_price' => $listPrice,
            'stock' => $variationData->stock,
            '_parent_sku' => $parentSku,
            '_variant_id_erp' => $variationData->id,
            '_published' => $this->isPublishedProduct
        ];

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
            $parsedVariation['_parent_sku'] = $prd['sku'] ?? $parsedVariation['_parent_sku'];
            if (empty($parsedVariation['id'] ?? 0)) $this->isPublishedProduct = false;
            if ($this->isPublishedProduct) {
                $updatableFields = [
                    'id', 'sku', 'status', 'stock', 'price', 'list_price', '_parent_sku', '_variant_id_erp', '_published'
                ];
                $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
            }
        }
        return $parsedVariation;
    }

    /**
     * @param array $payload Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        foreach ($payload as $image) {
            $fullUrl = $image->http ?? $image->https ?? '';
            if (empty($fullUrl)) continue;
            $images[] = $fullUrl;
        }
        return $images;
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param array|string|int $id Código(s) do(s) sku(s) do(s) produto(s)
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
     * @param array|string|int $id Código(s) do(s) sku(s) do(s) produto(s)
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
}
