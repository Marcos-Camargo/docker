<?php

namespace Integration\Integration_v2\ideris;

require APPPATH . "libraries/Integration_v2/Product_v2.php";
require_once APPPATH . 'libraries/Validations/ProductIntegrationValidation.php';
require_once APPPATH . "libraries/Integration_v2/ideris/Validation/ProductIntegrationValidationIderis.php";
require_once APPPATH . 'libraries/Helpers/StringHandler.php';

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;
use libraries\Helpers\StringHandler;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\ideris
 * @property \ProductIntegrationValidation $productIntegrationValidation
 */
class ToolsProduct extends Product_v2
{
    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $brandList = [];
    private $categoryList = [];
    private $categories = [];

    private $ncmList = [];
    private $originList = [];

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidationIderis(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
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
        $query['limit'] = $size;
        $query['offset'] = (--$page) * $size;

        try {
            $request = $this->request('GET', '/listingModel/search', ['query' => $query]);
            try {
                $contentResponse = $request->getBody()->getContents();
                $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
                $response = Utils::jsonDecode($contentResponse);
            } catch (\Throwable $e) {
                throw new Exception(sprintf("JSON DECODE ERROR: %s: %s", $request->getBody()->getContents(), print_r($request, true)));
            }
            return $response;
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            $code = $e->getCode();
            if (in_array((int)$code, [401, 403])) {
                $message = "Erro de autenticação/permissão ({$code}).";
            }
            throw new Exception($message);
        }
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param string $id Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "listingModel/search";
        $options = [
            'query' => array(
                'sku' => $id
            )
        ];

        try {
            $request = $this->request('GET', $urlGetSku, $options);
            $product = Utils::jsonDecode($request->getBody()->getContents());
        } catch (InvalidArgumentException|GuzzleException $exception) {
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        if (empty($product->obj[0])) {
            return null;
        }

        return $product->obj[0];
    }

    protected function sendRequest(string $endpoint, array $options = [])
    {
        try {
            $request = $this->request('GET', $endpoint, $options);
        } catch (\Throwable $e) {
            return (object)[];
        }
        $result = Utils::jsonDecode($request->getBody()->getContents());
        return $result->obj ?? (object)[];
    }

    protected function retrieveSku(int $skuId): object
    {
        return $this->sendRequest("/sku/{$skuId}");
    }

    protected function retrieveListingModelPrices(int $listingModelId): array
    {
        return $this->sendRequest("/listingModel/{$listingModelId}/price");
    }

    protected function retrieveBrand(int $brandId): string
    {
        if (empty($this->brandList[$brandId] ?? '')) {
            $brandData = $this->sendRequest("/brand/{$brandId}");
            $this->brandList[$brandId] = $brandData->description ?? '';
        }
        return $this->brandList[$brandId];
    }

    protected function retrieveCategory(int $categoryId): string
    {
        if (empty($this->categoryList[$categoryId] ?? '')) {
            $categoryData = $this->sendRequest("/category/{$categoryId}");
            $this->categoryList[$categoryId] = $categoryData->name ?? '';
            $this->categories[$categoryId] = $categoryData ?? (object)[];
        }
        return $this->categoryList[$categoryId];
    }

    protected function retrieveProductNCM(int $ncmId): string
    {
        if (empty($this->ncmList[$ncmId] ?? '')) {
            $ncmData = $this->sendRequest("/ncm/{$ncmId}");
            $this->ncmList[$ncmId] = $ncmData->code ?? '';
        }
        return $this->ncmList[$ncmId];
    }

    protected function retrieveProductOrigin(int $originId): int
    {
        if (empty($this->originList[$originId] ?? '')) {
            $originData = $this->sendRequest("/listingModel/origin/{$originId}");
            $this->originList[$originId] = $originData->code ?? '';
        }
        return (int)$this->originList[$originId];
    }

    protected function retrieveProductImages(array $filter = []): array
    {
        return (array)$this->sendRequest("/listingModel/image/searchFilter", ['query' => $filter]);
    }

    protected function retrieveProductWaranty(string $warantyDesc): int
    {
        if (strpos(strtolower($warantyDesc), 'ano') !== false) {
            $years = (int)preg_replace('/[^0-9.]+/', '', $warantyDesc);
            return $years * 12;
        }
        if (strpos(StringHandler::slugify($warantyDesc), 'mes') !== false) {
            return (int)preg_replace('/[^0-9.]+/', '', $warantyDesc);
        }
        if (strpos(StringHandler::slugify($warantyDesc), 'dia') !== false) {
            $days = (int)preg_replace('/[^0-9.]+/', '', $warantyDesc);
            return (int)($days >= 30 ? $days / 30 : 1);
        }
        return 0;
    }

    protected function retrieveAttributeByCategory(int $categoryId, int $attributeId): object
    {
        $filteredAttribute = array_filter($this->categories[$categoryId]->attributes ?? [], function ($attribute) use ($attributeId) {
            return ($attribute->id ?? 0) == $attributeId;
        });
        return current($filteredAttribute) ?: (object)[];
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
        $this->parsedProduct = [];
        $this->productPayload = $payload;
        $variants = $this->productPayload->variant ?? [(object)[]];
        $variantData = !empty($variants[0] ?? []) ? $variants[0] : (current($variants) ?: []);
        $decodedDescription = html_entity_decode($this->productPayload->longDescription, ENT_NOQUOTES, 'UTF-8');
        $decodedDescription = preg_replace('/[ \n\n\t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($decodedDescription)));

        $skuInfo = empty($this->productPayload->skuId ?? null) ? $this->retrieveSku($variantData->skuId) : $this->retrieveSku($this->productPayload->skuId);

        $ncm = '';
        if (($skuInfo->ncmId ?? 0) > 0) {
            $ncm = $this->retrieveProductNCM($skuInfo->ncmId);
        }
        $origin = 0;
        if (($skuInfo->originId ?? 0) > 0) {
            $origin = $this->retrieveProductOrigin($skuInfo->originId);
        }
        $warranty = $this->retrieveProductWaranty($this->productPayload->warranty ?? '');

        $productIdErp = $this->productPayload->id;
        $price = max($this->productPayload->value ?? 0, 0);
        $listPrice = $price;
        $eanProduct = empty($variantData) ? $skuInfo->ean : '';

        $brandName = $this->retrieveBrand($this->productPayload->brandId ?? 0);
        $categoryName = $this->retrieveCategory($this->productPayload->categoryId ?? 0);
        $prodImages = !empty($this->productPayload->listingModelImage ?? []) ? $this->productPayload->listingModelImage : $this->retrieveProductImages(['productIntId' => $this->productPayload->id]);

        $heightPackage = ($this->productPayload->heightPackage ?? 0) / 100;
        $lengthPackage = ($this->productPayload->lengthPackage ?? 0) / 100;
        $widthPackage = ($this->productPayload->widthPackage ?? 0) / 100;
        $weightPackage = ($this->productPayload->weightPackage ?? 0) / 100000;

        $height = ($this->productPayload->height ?? 0) / 100;
        $length = ($this->productPayload->length ?? 0) / 100;
        $width = ($this->productPayload->width ?? 0) / 100;
        $weight = ($this->productPayload->weight ?? 0) / 100000;

        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->title, 'field_database' => 'name'],
            'sku' => ['value' => $this->productPayload->sku, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => $price, 'field_database' => 'price'],
            'list_price' => ['value' => $listPrice, 'field_database' => 'list_price'],
            'stock' => ['value' => $this->productPayload->quantity ?? 0, 'field_database' => 'qty'],
            'status' => ['value' => ($this->productPayload->statusId ?? 0) == 1 ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT, 'field_database' => 'status'],
            'ean' => ['value' => $eanProduct ?? '', 'field_database' => 'EAN'],
            'origin' => ['value' => $origin, 'field_database' => 'origin'],
            'ncm' => ['value' => $ncm, 'field_database' => 'NCM'],
            'net_weight' => ['value' => $weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $weightPackage, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => $heightPackage, 'field_database' => 'altura'],
            'depth' => ['value' => $lengthPackage, 'field_database' => 'profundidade'],
            'width' => ['value' => $widthPackage, 'field_database' => 'largura'],
            'product_height' => ['value' => $height, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $length, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $width, 'field_database' => 'actual_width'],
            'images' => ['value' => $this->getImagesFormatted($prodImages), 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => $this->productPayload->manufacturingTime ?? 0, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $categoryName ?? $this->productPayload->categoryId ?? '', 'field_database' => 'category_id'],
            'brand' => ['value' => $brandName ?? '', 'field_database' => 'brand_id'],
            'guarantee' => ['value' => $warranty, 'field_database' => 'garantia'],
            '_product_id_erp' => ['value' => $productIdErp, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => '']
        ];

        $this->productValidationHandler($this->productPayload);

        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->isPublishedProduct) {
                $this->parsedProduct['_published']['value'] = true;
            }
            $isSimpleProduct = empty($variants);
            if ($isSimpleProduct) {
                $this->parsedProduct['stock']['value'] = $this->parsedProduct['stock']['value'] ?? $existingProduct['qty'];
            }
            $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $existingProduct['price'];
            $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $existingProduct['list_price'];
        }

        $this->parsedProduct['variations']['value'] = $this->getVariationFormatted($variants ?? []);

        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }
        //$this->handleWithParsedProductDimensions('centimeter');
        //$this->handleWithParsedProductWeight('grams');
        return $this->parsedProduct;
    }

    public function productValidationHandler($productData)
    {
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $productData->sku,
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

    protected function handleWithParsedProductWeight($unit = 'grams')
    {
        foreach (['net_weight', 'gross_weight'] as $dType) {
            if (!isset($this->parsedProduct[$dType]['value'])) continue;
            $conValue = $this->parsedProduct[$dType]['value'];
            $conValue = (float)str_replace(['.', ','], ['', '.'], $conValue);
            if (!is_numeric($conValue)) continue;
            if (strcasecmp($unit, 'grams') === 0) $conValue = (double)($conValue / 1000);
            $this->parsedProduct[$dType]['value'] = $conValue;
        }
    }

    protected function handleWithParsedProductDimensions($unit = 'centimeter')
    {
        foreach (['height', 'depth', 'width', 'product_height', 'product_depth', 'product_width'] as $dType) {
            if (!isset($this->parsedProduct[$dType]['value'])) continue;
            $conValue = $this->parsedProduct[$dType]['value'];
            $conValue = (float)str_replace(['.', ','], ['', '.'], $conValue);
            if (!is_numeric($conValue)) continue;
            if (strcasecmp($unit, 'meter') === 0) $conValue = ($conValue * 100);
            $this->parsedProduct[$dType]['value'] = (int)$conValue;
        }
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param array|string|int $id Código do sku do produto.
     * @param float|null $price Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
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
     * @throws Exception
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {

        $listingModelPrices = $this->retrieveListingModelPrices($this->productPayload->id ?? 0);

        $this->parsedVariations = [];
        foreach ($payload ?? [] as $variation) {
            if (strcasecmp($variation->sku, $this->parsedProduct['sku']['value']) === 0) {
                $this->parsedProduct['sku']['value'] = "P_{$this->parsedProduct['sku']['value']}";
            }
            if (empty($variation->variantAttribute ?? [])) {
                continue;
            }

            $skuData = $this->retrieveSku($variation->skuId);

            $variationsTypes = [];
            foreach ($variation->variantAttribute ?? [] as $variationAttr) {
                $attribute = $this->retrieveAttributeByCategory($this->productPayload->categoryId, $variationAttr->attributeId);
                if (empty($attribute->name ?? null)) {
                    throw new Exception("Variação incompleta $variation->sku, não informado o tipo de variação.");
                }
                $attributeValue = $variationAttr->attributeValue;
                if (empty($attributeValue)) {
                    foreach ($attribute->values as $attribute_value) {
                        if ($attribute_value->id == $variationAttr->attributeValueId) {
                            $attributeValue = $attribute_value->value;
                            break;
                        }
                    }
                }

                if (empty($attributeValue)) {
                    throw new Exception("Variação incompleta $variation->sku, não informado o valor da variação.");
                }

                $variationsTypes[$attribute->name] = $attributeValue;
            }

            $variation->variantPrice = current(array_filter($listingModelPrices, function ($price) use ($variation) {
                return ($price->variantId ?? 0) == $variation->id;
            })) ?: (object)[];

            $price = max($variation->variantPrice->value, 0);
            $listPrice = $price;

            $parsedVariation = [
                'id' => 0,
                'sku' => $variation->sku,
                'status' => (($variation->variantPrice->statusId ?? 1) == 1) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
                'ean' => $skuData->ean ?? '',
                'images' => $this->handleWithVariationImages($variation->variantImage ?? [], $this->productPayload->listingModelImage ?? []),
                'variations' => $variationsTypes,
                'price' => $price,
                'list_price' => $listPrice,
                'stock' => $variation->quantity ?? null,
                '_parent_sku' => $this->parsedProduct['sku']['value'],
                '_variant_id_erp' => $variation->id
            ];

            if ($parsedVariation['stock'] == null) {
                $parsedVariation['stock'] = (int)(current(array_column($skuData->stocks, 'currentStock') ?: null));
            }

            $this->parsedProduct['price']['value'] = !empty($this->parsedProduct['price']['value']) || $this->parsedProduct['price']['value'] > ($parsedVariation['price'] ?? 0) ? $this->parsedProduct['price']['value'] : $parsedVariation['price'] ?? 0;
            $this->parsedProduct['list_price']['value'] = !empty($this->parsedProduct['list_price']['value']) || $this->parsedProduct['list_price']['value'] > ($parsedVariation['list_price'] ?? 0) ? $this->parsedProduct['list_price']['value'] : $parsedVariation['list_price'] ?? 0;

            if (($skuData->ncmId ?? 0) > 0) {
                $this->parsedProduct['ncm']['value'] = $this->retrieveProductNCM($skuData->ncmId);
            }
            if (($skuData->originId ?? 0) > 0) {
                $this->parsedProduct['origin']['value'] = $this->retrieveProductOrigin($skuData->originId);
            }

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
                $parsedVariation['_published'] = $this->isPublishedProduct;
            }
            $this->parsedVariations[] = $parsedVariation;
        }

        // Corrigindo o sku do pai nas variações.
        foreach ($this->parsedVariations as $key_var => $variation) {
            if ($variation['_parent_sku'] != $this->parsedProduct['sku']['value'] && !$this->productIntegrationValidation->productExists()) {
                $this->parsedVariations[$key_var]['_parent_sku'] = $this->parsedProduct['sku']['value'];
            }
        }

        return $this->parsedVariations;
    }

    /**
     * @param array $payload Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        array_multisort(array_map(function ($image) {
            return $image->order ?? 0;
        }, $payload), SORT_ASC, $payload);
        foreach ($payload as $image) {
            $fullUrl = $image->url ?? '';
            if (empty($fullUrl)) continue;
            $images[] = $fullUrl;
        }
        return $images;
    }

    protected function handleWithVariationImages(array $variationImages = [], array $productImages = []): array
    {
        $images = [];
        array_multisort(array_map(function ($image) {
            return $image->order ?? 0;
        }, $variationImages), SORT_ASC, $variationImages);
        foreach ($variationImages as $variationImage) {
            $checkImg = array_filter($productImages, function ($prdImg) use ($variationImage) {
                return ($prdImg->id ?? 0) === ($variationImage->listingModelImageId ?? null);
            });
            if (!empty($checkImg)) {
                $images[] = current($checkImg)->url ?? '';
            }
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
        return array(
            'stock_product' => [],
            'stock_variation' => []
        );
    }

    /**
     * Recupera o estoque de produto(s)
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param array|string|int $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  bool                Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }

    public function getParsedVariations(): array
    {
        return $this->parsedVariations;
    }

    /**
     * @param $intProdId
     * @param $intVarId
     * @return array
     */
    public function fetchProductPriceStockProductVariation($intProdId, $intVarId = null)
    {
        $query = ['id' => $intProdId];
        try{
            $response = $this->getProductsPagination($query);
            $product = current($response->obj ?? []);
            $variant = current($product->variant ?? []);
            if (!empty($intVarId)) {
                $variant = current(array_filter($product->variant ?? [], function ($var) use ($intVarId) {
                    return ((int)$var->id) === ((int)$intVarId);
                }));
            }

            $listingModelPrices = $this->retrieveListingModelPrices($product->id);
            if(!empty($variant->id ?? null)) {
                $variant->variantPrice = current(array_filter($listingModelPrices, function ($price) use ($variant) {
                    return ($price->variantId ?? 0) == ($variant->id ?? null);
                })) ?: (object)[];
            }

            $price = max($variant->variantPrice->value ?? $product->value ?? null, 0);
            $stock = $variant->quantity ?? $product->quantity ?? null;
            return [[
                'stock' => $stock
            ], [
                'price' => $price,
                'listPrice' => $price
            ]];
        }catch (\Throwable $e) {

        }
        return [[], []];
    }
}